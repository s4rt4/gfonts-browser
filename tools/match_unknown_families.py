"""
Migrate "Unknown" families to current Google Fonts names.

For each family in the Unknown bucket, this tool tries to identify a current
Google Fonts family that the old TTF was a legacy version of (e.g.
'BalooBhai' → 'Baloo Bhai 2', 'ArchivoVFBeta' → 'Archivo'). When confident:
- Copies the new TTFs from the source repo (if not already in FONTS_ROOT)
- Deletes the old TTFs (that match the legacy filename prefix)

Then re-runs load_fonts.py and clears the Laravel cache so the UI reflects
the change.

Usage:
    # Preview without changing anything
    python tools/match_unknown_families.py \
        --source "C:/Users/Sarta/Downloads/fonts-main/fonts-main" --dry-run

    # Execute (with confirmation prompt)
    python tools/match_unknown_families.py \
        --source "C:/Users/Sarta/Downloads/fonts-main/fonts-main"

    # Execute non-interactively
    python tools/match_unknown_families.py \
        --source "C:/Users/Sarta/Downloads/fonts-main/fonts-main" --yes
"""

from __future__ import annotations

import argparse
import json
import re
import shutil
import sqlite3
import subprocess
import sys
from pathlib import Path
from typing import Optional


def env_value(env_path: Path, key: str) -> Optional[str]:
    if not env_path.exists():
        return None
    for raw in env_path.read_text(encoding='utf-8').splitlines():
        line = raw.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, _, v = line.partition('=')
        if k.strip() == key:
            return v.strip().strip('"').strip("'")
    return None


def insert_spaces(name: str) -> str:
    """Insert spaces at camelCase boundaries: 'BalooBhai' → 'Baloo Bhai'."""
    s = re.sub(r'(?<=[a-z])(?=[A-Z])', ' ', name)
    s = re.sub(r'(?<=[A-Z])(?=[A-Z][a-z])', ' ', s)
    return s.strip()


def candidates(unknown: str) -> list[str]:
    """All plausible 'modern' names for an old family."""
    out: list[str] = []
    seen: set[str] = set()

    def add(c: str):
        c = c.strip()
        if c and c not in seen:
            seen.add(c)
            out.append(c)

    spaced = insert_spaces(unknown)
    add(unknown)
    add(spaced)

    # Common suffix additions (Google often appends version numbers)
    for suffix in (' 2', ' Pro', ' Display', ' Text', ' Sans', ' Mono', ' One',
                   ' Caption', ' Variable', ' Hand', ' Brush', ' Code'):
        add(spaced + suffix)

    # Common suffix strips
    for legacy_suffix in ('VFBeta', 'Alpha', 'Beta', 'VF'):
        if unknown.endswith(legacy_suffix):
            stripped = insert_spaces(unknown[: -len(legacy_suffix)])
            add(stripped)
            add(stripped + ' 2')
            add(stripped + ' Pro')

    # 'BigShouldersInlineDisplaySC' → without trailing SC
    if unknown.endswith('SC') and len(unknown) > 2:
        add(insert_spaces(unknown[:-2]))

    # Strip last camelCase word: 'ArimaMadurai' → 'Arima'
    parts = re.findall(r'[A-Z][a-z]+|[A-Z]+(?=[A-Z][a-z]|$)', unknown)
    if len(parts) > 1:
        add(' '.join(parts[:-1]))
        add(' '.join(parts[:-1]) + ' Pro')

    return out


def find_family_folder(source_root: Path, family_name: str) -> Optional[Path]:
    """Find google/fonts repo folder for a family — folder name is lowercase no-space."""
    folder_name = family_name.lower().replace(' ', '')
    for license_dir in ('ofl', 'apache', 'ufl'):
        candidate = source_root / license_dir / folder_name
        if candidate.is_dir():
            return candidate
    return None


def filename_pattern(family_name: str) -> re.Pattern:
    """Regex matching all TTFs of a given family (handles -Style suffix and [axes])."""
    nospace = re.escape(family_name.replace(' ', ''))
    return re.compile(rf'^{nospace}([\-\[].*)?\.ttf$', re.IGNORECASE)


def find_ttfs(folder: Path, family_name: str) -> list[Path]:
    pat = filename_pattern(family_name)
    return [f for f in folder.iterdir() if f.is_file() and pat.match(f.name)]


def main() -> int:
    project_root = Path(__file__).resolve().parent.parent
    env_db   = env_value(project_root / '.env', 'DB_DATABASE')
    env_root = env_value(project_root / '.env', 'FONTS_ROOT')

    parser = argparse.ArgumentParser(description=__doc__,
                                     formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--source', required=True,
                        help='Path to extracted google/fonts repo (folder containing ofl/, apache/, ufl/)')
    parser.add_argument('--db',         default=env_db   or str(project_root / 'database/fonts.sqlite'))
    parser.add_argument('--metadata',   default=str(project_root / 'database/seed/fonts.json'))
    parser.add_argument('--fonts-root', default=env_root or str(project_root / 'google-ttf'))
    parser.add_argument('--dry-run',    action='store_true')
    parser.add_argument('--yes', '-y',  action='store_true', help='skip confirmation prompt')
    args = parser.parse_args()

    source     = Path(args.source)
    db         = Path(args.db)
    metadata   = Path(args.metadata)
    fonts_root = Path(args.fonts_root)

    for label, p in [('Source', source), ('DB', db), ('Metadata', metadata), ('Fonts root', fonts_root)]:
        if not p.exists():
            print(f'ERROR: {label} not found: {p}', file=sys.stderr)
            return 1

    print('=' * 64)
    print('  Match Unknown families to current Google Fonts')
    print('=' * 64)
    print(f'  Source repo:   {source}')
    print(f'  Fonts root:    {fonts_root}')
    print(f'  Database:      {db}')
    print(f'  Mode:          {"dry-run" if args.dry_run else "execute"}')
    print('=' * 64)
    print()

    # Load Google metadata
    meta_data = json.loads(metadata.read_text(encoding='utf-8'))
    google_set = set(f['family'] for f in meta_data['familyMetadataList'])

    # Load Unknown families from DB
    conn = sqlite3.connect(str(db))
    cur = conn.cursor()
    cur.execute("SELECT family FROM font_families WHERE category = 'Unknown' ORDER BY family")
    unknowns = [r[0] for r in cur.fetchall()]
    conn.close()
    print(f'Unknown families in DB:  {len(unknowns)}')

    # Resolve each Unknown
    mappings: list[tuple[str, str, list[Path], list[Path]]] = []  # (old, new, new_ttfs_to_copy, old_ttfs_to_delete)
    no_match: list[str] = []

    for u in unknowns:
        # Try candidates against metadata
        new_name = next((c for c in candidates(u) if c in google_set), None)
        if not new_name:
            no_match.append(u)
            continue

        # Find old TTFs in fonts_root that match the legacy name
        old_pat = filename_pattern(u)
        old_ttfs = [f for f in fonts_root.iterdir() if f.is_file() and old_pat.match(f.name)]
        if not old_ttfs:
            # No old TTFs to delete — already clean
            continue

        # Find new TTFs (already in fonts_root, OR need to copy from source)
        new_ttfs_in_root = find_ttfs(fonts_root, new_name)
        to_copy: list[Path] = []
        if not new_ttfs_in_root:
            src_folder = find_family_folder(source, new_name)
            if src_folder:
                to_copy = [f for f in src_folder.rglob('*.ttf')]
            if not to_copy:
                # No source TTFs to copy and none in root — skip (would lose font entirely)
                no_match.append(u)
                continue

        mappings.append((u, new_name, to_copy, old_ttfs))

    print(f'Mappings resolved:      {len(mappings)}')
    print(f'No mapping (truly unknown / non-Google): {len(no_match)}')
    print()

    if mappings:
        print('=' * 64)
        print('  PROPOSED CHANGES')
        print('=' * 64)
        for old, new, to_copy, old_ttfs in mappings:
            tag = 'NEW + DEL' if to_copy else 'DEL only'
            print(f'  [{tag:9s}] {old:32s} -> {new:32s}  '
                  f'(-{len(old_ttfs)} old, +{len(to_copy)} new)')
        print()

    if args.dry_run:
        print('Dry-run only. No files changed.')
        return 0

    if not mappings:
        print('Nothing to do.')
        return 0

    if not args.yes:
        ans = input(f'Proceed with replacing {len(mappings)} families? [y/N] ').strip().lower()
        if ans != 'y':
            print('Aborted.')
            return 0

    # Execute
    copied = 0
    deleted = 0
    skipped_dup = 0

    for old, new, to_copy, old_ttfs in mappings:
        for src in to_copy:
            dst = fonts_root / src.name
            if dst.exists() and dst.stat().st_size == src.stat().st_size:
                skipped_dup += 1
                continue
            shutil.copy2(src, dst)
            copied += 1
        for f in old_ttfs:
            f.unlink()
            deleted += 1

    print()
    print(f'  Copied:  {copied} new TTFs')
    print(f'  Deleted: {deleted} old TTFs')
    if skipped_dup:
        print(f'  Skipped (already present): {skipped_dup}')
    print()

    # Re-run loader
    print('Re-indexing...')
    subprocess.run([sys.executable, str(project_root / 'tools/load_fonts.py')], check=True)

    # Clear Laravel cache
    print('\nClearing Laravel cache...')
    try:
        subprocess.run(['php', 'artisan', 'cache:clear'],
                       cwd=str(project_root), check=True)
    except Exception as e:
        print(f'  WARN: failed to clear cache: {e}', file=sys.stderr)
        print(f'  Run manually: php artisan cache:clear')

    print('\nDone. Refresh http://localhost:9000')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
