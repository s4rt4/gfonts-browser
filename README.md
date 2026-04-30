# Gfonts Browser

Local Google Fonts–style browser for your offline TTF collection.
Browse, preview, filter, compare, install — all served from your own machine.

![Gfonts Browser — light + dark themes](screenshot.jpg)

> Stack: Laravel 11 + Alpine.js + Tailwind + SQLite. Python-only loader (no fonttools dependency). PowerShell-based per-user font installer for Windows.

## Features

- **Index grid + list view** with search, category filter, sort, paginated 48/page
- **Live preview** — type your own sample text, scale 14–96 px, all cards render in their actual font
- **Lazy `@font-face` injection** so 1000+ fonts don't all download at once
- **Detail page** per family — sample editor, **variable axis sliders**, type specimen, per-weight install/download, slnt-as-italic fallback
- **Install to Windows** — POST endpoint runs `tools/install_font.ps1` to copy + register the TTF under `HKCU` and broadcast `WM_FONTCHANGE` so apps pick it up without restart (no admin needed)
- **Compare mode** — pick up to 4 families, side-by-side at shared size/weight/italic
- **Favorites** (heart) and **Collections** (named multi-set with bookmark) — persisted to `localStorage`
- **Theme toggle** — light "daylight cream" + dark, persisted, no flash
- **Branded modals** in place of native `prompt`/`confirm`

## Setup

```bash
# 1. Clone & install
git clone https://github.com/s4rt4/gfonts-browser.git
cd gfonts-browser
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# Edit .env:
#   APP_NAME="Gfonts Browser"
#   DB_CONNECTION=sqlite
#   DB_DATABASE="C:/absolute/path/to/database/fonts.sqlite"
#   FONTS_ROOT="C:/absolute/path/to/your/ttf/folder"

# 3. Database
touch database/fonts.sqlite
php artisan migrate

# 4. Index your TTF collection (one-shot, idempotent)
python tools/load_fonts.py

# 5. Build assets + run
npm run build
php artisan serve
```

Open <http://localhost:8000>.

## Re-indexing

Whenever you add/remove TTF files in `FONTS_ROOT`, just re-run:

```bash
python tools/load_fonts.py
```

The loader truncates and re-inserts — schema lives in Laravel migrations, Python is a pure data loader.

## Refreshing TTFs from upstream Google Fonts

To pull the latest TTFs from [github.com/google/fonts](https://github.com/google/fonts), run:

```bash
python tools/sync_from_google_fonts_repo.py
```

This idempotently clones (or fast-forwards) `google/fonts` into `.google-fonts-source/`,
copies every `.ttf` into `FONTS_ROOT` (flat), refreshes `database/seed/fonts.json`,
and re-runs the indexer. Re-runs only copy changed files (size-based diff).

For a one-time refresh that cleans up the clone afterwards:

```bash
python tools/sync_from_google_fonts_repo.py --clean-source
```

Other flags: `--skip-clone`, `--skip-metadata`, `--skip-index`, `--dry-run`.

## Where to put fonts

Anywhere you like — point `FONTS_ROOT` at any folder of `.ttf` files. The repo intentionally does **not** ship a font collection (`/google-ttf` is gitignored).

The loader expects Google-Fonts-style filenames (`Family-Style.ttf` or `Family[axes].ttf`) and matches them against `database/seed/fonts.json` for metadata. Custom/third-party fonts that don't match get `category="Unknown"` but are still browsable.

## Requirements

| Tool | Tested |
|---|---|
| PHP | 8.4 (with `pdo_sqlite` + `sqlite3` extensions) |
| Composer | 2.4+ |
| Python | 3.10+ (stdlib only — no extra deps) |
| Node | 22 / npm 10 |

Install-to-Windows feature requires Windows + PowerShell. Other features are cross-platform (UI side); install button is hidden on non-Windows.

## License

MIT.
