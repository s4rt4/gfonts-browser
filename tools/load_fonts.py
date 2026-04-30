"""
Loads Google Fonts metadata + scanned TTF files into the SQLite database
created by Laravel migrations. Schema authority is Laravel — this script
only INSERTs.

Usage:
    python tools/load_fonts.py
    python tools/load_fonts.py --db database/fonts.sqlite --fonts-root google-ttf --metadata database/seed/fonts.json
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sqlite3
import sys
from datetime import datetime
from pathlib import Path

WEIGHT_MAP = {
    "thin": 100, "hairline": 100,
    "extralight": 200, "ultralight": 200,
    "light": 300,
    "regular": 400, "normal": 400, "": 400,
    "medium": 500,
    "semibold": 600, "demibold": 600,
    "bold": 700,
    "extrabold": 800, "ultrabold": 800,
    "black": 900, "heavy": 900,
}

AXES_RE = re.compile(r"\[([^\]]+)\](?=\.ttf$)")


def parse_filename(name: str, nospace_map: dict) -> dict | None:
    """Parse a TTF filename. Tries progressively shorter family prefixes
    against the metadata map so multi-hyphen names like
    'PT_Sans-Web-Bold.ttf' resolve to family 'PT Sans'."""
    if not name.lower().endswith(".ttf"):
        return None
    axes_match = AXES_RE.search(name)
    axes = axes_match.group(1) if axes_match else None
    stem = AXES_RE.sub("", name)[:-4]

    parts = stem.split("-")
    family_meta = None
    family_name = None
    suffix_parts: list[str] = []
    for i in range(len(parts), 0, -1):
        candidate = "".join(parts[:i]).replace("_", "")
        if candidate in nospace_map:
            family_meta = nospace_map[candidate]
            family_name = family_meta["family"]
            suffix_parts = parts[i:]
            break

    if family_name is None:
        family_name = parts[0].replace("_", "")
        suffix_parts = parts[1:]

    italic = False
    weight = None
    for token in suffix_parts:
        t = token.lower()
        if t.endswith("italic"):
            italic = True
            t = t[: -len("italic")]
        if t in WEIGHT_MAP:
            weight = WEIGHT_MAP[t]

    if not suffix_parts:
        weight = 400

    return {
        "family_meta": family_meta,
        "family": family_name,
        "subfamily": "-".join(suffix_parts) or "Regular",
        "weight": None if axes else weight,
        "style": "italic" if italic else "normal",
        "is_variable": axes is not None,
        "axes_in_filename": axes,
    }


def parse_date(value: str | None) -> str | None:
    if not value:
        return None
    try:
        return datetime.strptime(value, "%Y-%m-%d").date().isoformat()
    except ValueError:
        return None


def env_value(env_path: Path, key: str) -> str | None:
    if not env_path.exists():
        return None
    for raw in env_path.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, _, v = line.partition("=")
        if k.strip() == key:
            return v.strip().strip('"').strip("'")
    return None


def main() -> int:
    project_root = Path(__file__).resolve().parent.parent
    env_db = env_value(project_root / ".env", "DB_DATABASE")
    env_root = env_value(project_root / ".env", "FONTS_ROOT")

    parser = argparse.ArgumentParser()
    parser.add_argument("--db", default=env_db or str(project_root / "database" / "fonts.sqlite"))
    parser.add_argument("--fonts-root", default=env_root or str(project_root / "google-ttf"))
    parser.add_argument("--metadata", default=str(project_root / "database" / "seed" / "fonts.json"))
    args = parser.parse_args()

    db_path = Path(args.db)
    fonts_root = Path(args.fonts_root)
    metadata_path = Path(args.metadata)

    for label, p in [("DB", db_path), ("Fonts root", fonts_root), ("Metadata JSON", metadata_path)]:
        if not p.exists():
            print(f"ERROR: {label} not found: {p}", file=sys.stderr)
            return 1

    print(f"DB:           {db_path}")
    print(f"Fonts root:   {fonts_root}")
    print(f"Metadata:     {metadata_path}")
    print()

    print("Loading metadata...")
    metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
    families_meta = metadata.get("familyMetadataList", [])
    nospace_to_family = {f["family"].replace(" ", ""): f for f in families_meta}
    print(f"  Google families: {len(families_meta)}")

    print("Scanning TTF files...")
    ttf_files = sorted(p for p in fonts_root.iterdir() if p.suffix.lower() == ".ttf" and p.is_file())
    print(f"  TTF files: {len(ttf_files)}")

    parsed_files = []
    matched_families: dict[str, dict] = {}
    unmatched_families: dict[str, dict] = {}

    for ttf in ttf_files:
        info = parse_filename(ttf.name, nospace_to_family)
        if not info:
            print(f"  WARN: cannot parse {ttf.name}", file=sys.stderr)
            continue
        if info["family_meta"]:
            matched_families.setdefault(info["family"], info["family_meta"])
        else:
            unmatched_families.setdefault(info["family"], {"family": info["family"], "category": "Unknown"})
        info["file_size"] = ttf.stat().st_size
        info["filename"] = ttf.name
        parsed_files.append(info)

    print(f"  Matched to Google: {len(matched_families)} families ({sum(1 for f in parsed_files if f['family'] in matched_families)} files)")
    print(f"  Unmatched (Unknown category): {len(unmatched_families)} families ({sum(1 for f in parsed_files if f['family'] in unmatched_families)} files)")

    print("Inserting into database...")
    conn = sqlite3.connect(str(db_path))
    conn.execute("PRAGMA foreign_keys = ON")
    cur = conn.cursor()

    cur.execute("DELETE FROM font_files")
    cur.execute("DELETE FROM font_families")
    cur.execute("DELETE FROM sqlite_sequence WHERE name IN ('font_files','font_families')")

    file_counts: dict[str, int] = {}
    for f in parsed_files:
        file_counts[f["family"]] = file_counts.get(f["family"], 0) + 1

    now = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
    family_id: dict[str, int] = {}

    family_rows = []
    for fam_name, meta in {**matched_families, **unmatched_families}.items():
        axes = meta.get("axes") or []
        is_variable = bool(axes) or any(
            f["family"] == fam_name and f["is_variable"] for f in parsed_files
        )
        family_rows.append((
            fam_name,
            meta.get("displayName"),
            meta.get("category") or "Unknown",
            meta.get("stroke"),
            json.dumps(meta.get("subsets") or []),
            json.dumps(axes),
            json.dumps(meta.get("designers") or []),
            json.dumps(meta.get("languages") or []),
            json.dumps(meta.get("classifications") or []),
            json.dumps(meta.get("colorCapabilities") or []),
            meta.get("popularity"),
            meta.get("trending"),
            meta.get("defaultSort"),
            1 if meta.get("isNoto") else 0,
            1 if meta.get("isBrandFont") else 0,
            1 if meta.get("isOpenSource", True) else 0,
            1 if is_variable else 0,
            parse_date(meta.get("dateAdded")),
            parse_date(meta.get("lastModified")),
            file_counts.get(fam_name, 0),
            now,
            now,
        ))

    cur.executemany(
        """
        INSERT INTO font_families
        (family, display_name, category, stroke,
         subsets, axes, designers, languages, classifications, color_capabilities,
         popularity, trending, default_sort,
         is_noto, is_brand_font, is_open_source, is_variable,
         date_added, last_modified, file_count,
         created_at, updated_at)
        VALUES (?,?,?,?, ?,?,?,?,?,?, ?,?,?, ?,?,?,?, ?,?,?, ?,?)
        """,
        family_rows,
    )

    cur.execute("SELECT id, family FROM font_families")
    for fid, fam in cur.fetchall():
        family_id[fam] = fid

    file_rows = []
    for f in parsed_files:
        file_rows.append((
            family_id[f["family"]],
            f["filename"],
            f["subfamily"],
            f["weight"],
            f["style"],
            1 if f["is_variable"] else 0,
            f["axes_in_filename"],
            f["file_size"],
            now,
            now,
        ))

    cur.executemany(
        """
        INSERT INTO font_files
        (font_family_id, filename, subfamily, weight, style,
         is_variable, axes_in_filename, file_size,
         created_at, updated_at)
        VALUES (?,?,?,?,?, ?,?,?, ?,?)
        """,
        file_rows,
    )

    conn.commit()

    print()
    print("Done.")
    print(f"  Inserted families: {len(family_rows)}")
    print(f"  Inserted files:    {len(file_rows)}")

    cur.execute("SELECT category, COUNT(*) FROM font_families GROUP BY category ORDER BY 2 DESC")
    print()
    print("By category:")
    for cat, n in cur.fetchall():
        print(f"  {cat:20s} {n}")

    cur.execute("SELECT COUNT(*) FROM font_families WHERE is_variable = 1")
    print(f"\nVariable families: {cur.fetchone()[0]}")

    conn.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
