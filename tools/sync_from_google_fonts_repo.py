"""
Sync TTFs from github.com/google/fonts into the local FONTS_ROOT,
refresh fonts.json metadata, and re-run the indexer.

Idempotent — safe to re-run. Skips files whose size matches the destination.

Usage:
    python tools/sync_from_google_fonts_repo.py
    python tools/sync_from_google_fonts_repo.py --clean-source
    python tools/sync_from_google_fonts_repo.py --source D:/cache/google-fonts
    python tools/sync_from_google_fonts_repo.py --skip-clone --skip-metadata

Flags:
    --source PATH      Local path to clone google/fonts into.
                       Default: <project_root>/.google-fonts-source
    --clean-source     Delete the source clone after copying (one-time refresh mode).
    --skip-clone       Use the existing local clone as-is, don't run git.
    --skip-metadata    Don't fetch latest fonts.json.
    --skip-index       Don't run load_fonts.py at the end.
    --dry-run          Show what would happen, don't change anything.
"""

from __future__ import annotations

import argparse
import shutil
import subprocess
import sys
import urllib.request
from pathlib import Path
from typing import Optional

GOOGLE_FONTS_REPO = 'https://github.com/google/fonts.git'
METADATA_URL = 'https://fonts.google.com/metadata/fonts'


def env_value(env_path: Path, key: str) -> Optional[str]:
    """Tiny .env reader (no python-dotenv dependency)."""
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


def run(cmd, cwd=None, check=True, dry=False):
    label = ' '.join(str(c) for c in cmd)
    print(f'  $ {label}')
    if dry:
        return None
    return subprocess.run(cmd, cwd=cwd, check=check)


def have_git() -> bool:
    try:
        subprocess.run(['git', '--version'], check=True, capture_output=True)
        return True
    except (FileNotFoundError, subprocess.CalledProcessError):
        return False


def clone_or_pull(source: Path, dry: bool = False) -> bool:
    """Clone google/fonts (--depth=1) or fast-forward existing clone."""
    if source.exists() and (source / '.git').is_dir():
        print(f'Existing clone at {source} — fetching latest...')
        # --depth=1 clones don't pull cleanly with `git pull` across
        # branch resets; fetch + reset is the reliable way.
        try:
            run(['git', '-C', str(source), 'fetch', '--depth=1', 'origin', 'HEAD'], dry=dry)
            run(['git', '-C', str(source), 'reset', '--hard', 'FETCH_HEAD'], dry=dry)
            run(['git', '-C', str(source), 'gc', '--prune=all', '--quiet'], dry=dry, check=False)
        except subprocess.CalledProcessError as e:
            print(f'  ERROR pulling: {e}', file=sys.stderr)
            return False
        return True

    if source.exists():
        print(f'  ERROR: {source} exists but is not a git repo. Move/remove it first.', file=sys.stderr)
        return False

    print(f'Cloning {GOOGLE_FONTS_REPO} → {source} (shallow)...')
    source.parent.mkdir(parents=True, exist_ok=True)
    try:
        run(['git', 'clone', '--depth=1', GOOGLE_FONTS_REPO, str(source)], dry=dry)
    except subprocess.CalledProcessError as e:
        print(f'  ERROR cloning: {e}', file=sys.stderr)
        return False
    return True


def sync_ttfs(source: Path, fonts_root: Path, dry: bool = False) -> dict:
    """Walk source, copy every *.ttf to FONTS_ROOT (flat). Skip same-size existing."""
    if dry:
        print(f'(dry-run) Would scan {source} for *.ttf files')

    fonts_root.mkdir(parents=True, exist_ok=True)
    print(f'Scanning {source} for *.ttf files...')

    ttf_files = sorted(source.rglob('*.ttf'))
    print(f'  Found {len(ttf_files):,} .ttf files in source')

    added = 0
    updated = 0
    skipped = 0
    duplicates: list[str] = []
    seen: dict[str, Path] = {}

    for src in ttf_files:
        name = src.name
        if name in seen:
            duplicates.append(name)
            continue
        seen[name] = src

        dst = fonts_root / name
        src_size = src.stat().st_size

        if dst.exists():
            if dst.stat().st_size == src_size:
                skipped += 1
                continue
            if not dry:
                shutil.copy2(src, dst)
            updated += 1
        else:
            if not dry:
                shutil.copy2(src, dst)
            added += 1

    return {
        'total_in_source': len(ttf_files),
        'unique':          len(seen),
        'added':           added,
        'updated':         updated,
        'unchanged':       skipped,
        'duplicates':      len(duplicates),
    }


def refresh_metadata(metadata_path: Path, dry: bool = False) -> Optional[int]:
    print(f'Fetching latest metadata: {METADATA_URL}')
    if dry:
        print(f'  (dry-run) Would write to {metadata_path}')
        return None

    metadata_path.parent.mkdir(parents=True, exist_ok=True)
    req = urllib.request.Request(
        METADATA_URL,
        headers={'User-Agent': 'Mozilla/5.0 (gfonts-browser sync)'},
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = resp.read()
    except urllib.error.URLError as e:
        print(f'  ERROR fetching metadata: {e}', file=sys.stderr)
        return None

    metadata_path.write_bytes(data)
    print(f'  Saved {len(data):,} bytes to {metadata_path}')
    return len(data)


def run_loader(project_root: Path, dry: bool = False):
    loader = project_root / 'tools' / 'load_fonts.py'
    print(f'Running indexer: {loader}')
    if dry:
        print(f'  (dry-run) skipped')
        return
    run([sys.executable, str(loader)], cwd=project_root)


def main() -> int:
    project_root = Path(__file__).resolve().parent.parent

    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--source',
                        default=str(project_root / '.google-fonts-source'))
    parser.add_argument('--clean-source', action='store_true')
    parser.add_argument('--skip-clone',    action='store_true')
    parser.add_argument('--skip-metadata', action='store_true')
    parser.add_argument('--skip-index',    action='store_true')
    parser.add_argument('--dry-run',       action='store_true')
    args = parser.parse_args()

    fonts_root_raw = env_value(project_root / '.env', 'FONTS_ROOT') or str(project_root / 'google-ttf')
    fonts_root = Path(fonts_root_raw)
    metadata_path = project_root / 'database' / 'seed' / 'fonts.json'
    source = Path(args.source)

    print('=' * 64)
    print('  Gfonts Browser — sync from github.com/google/fonts')
    print('=' * 64)
    print(f'  Project root:   {project_root}')
    print(f'  Source clone:   {source}')
    print(f'  FONTS_ROOT:     {fonts_root}')
    print(f'  Metadata path:  {metadata_path}')
    print(f'  Clean source:   {args.clean_source}')
    print(f'  Dry run:        {args.dry_run}')
    print('=' * 64)
    print()

    # Step 1 — clone or pull
    if not args.skip_clone:
        if not have_git():
            print('ERROR: `git` is not on PATH. Install Git or use --skip-clone.', file=sys.stderr)
            return 2
        if not clone_or_pull(source, dry=args.dry_run):
            return 2
        print()
    else:
        print('Skipping git clone/pull (--skip-clone).\n')

    # Step 2 — copy TTFs
    if not source.exists():
        print(f'ERROR: source {source} does not exist.', file=sys.stderr)
        return 2

    stats = sync_ttfs(source, fonts_root, dry=args.dry_run)
    print()
    print('TTF sync summary:')
    print(f'  In source:    {stats["total_in_source"]:,}')
    print(f'  Unique names: {stats["unique"]:,}')
    print(f'  Added:        {stats["added"]:,}')
    print(f'  Updated:      {stats["updated"]:,}')
    print(f'  Unchanged:    {stats["unchanged"]:,}')
    if stats['duplicates']:
        print(f'  Duplicates skipped (same filename in 2+ source dirs): {stats["duplicates"]:,}')
    print()

    # Step 3 — refresh metadata
    if not args.skip_metadata:
        refresh_metadata(metadata_path, dry=args.dry_run)
        print()
    else:
        print('Skipping metadata refresh (--skip-metadata).\n')

    # Step 4 — re-run loader
    if not args.skip_index:
        run_loader(project_root, dry=args.dry_run)
        print()
    else:
        print('Skipping indexer (--skip-index).\n')

    # Step 5 — cleanup
    if args.clean_source and not args.skip_clone:
        print(f'Removing source clone at {source}...')
        if not args.dry_run:
            try:
                shutil.rmtree(source)
                print('  Done. Disk space reclaimed.')
            except Exception as e:
                print(f'  WARN: failed to remove: {e}', file=sys.stderr)
        else:
            print('  (dry-run) skipped')
        print()

    if args.dry_run:
        print('Dry run complete — no changes were made.')
    else:
        print('Sync complete. Refresh http://localhost:9000 to see updated fonts.')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
