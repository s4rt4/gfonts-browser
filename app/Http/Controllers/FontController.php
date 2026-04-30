<?php

namespace App\Http\Controllers;

use App\Models\FontFamily;
use App\Models\FontFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class FontController extends Controller
{
    public function index(Request $request)
    {
        $families = FontFamily::query()
            ->where('file_count', '>', 0)
            ->with(['fontFiles' => function ($q) {
                $q->orderBy('weight')->orderBy('style');
            }])
            ->get();

        $bundle = $families->map(function (FontFamily $f) {
            return [
                'id'           => $f->id,
                'family'       => $f->family,
                'category'     => $f->category,
                'popularity'   => $f->popularity,
                'trending'     => $f->trending,
                'file_count'   => $f->file_count,
                'is_variable'  => $f->is_variable,
                'is_noto'      => $f->is_noto,
                'date_added'   => optional($f->date_added)->format('Y-m-d'),
                'designers'    => $f->designers,
                'subsets'      => $f->subsets,
                'files'        => $f->fontFiles->map(fn ($x) => [
                    'id'       => $x->id,
                    'weight'   => $x->is_variable ? null : ($x->weight ?? 400),
                    'style'    => $x->style,
                    'variable' => $x->is_variable,
                ])->values(),
            ];
        })->values();

        $categories = $families->pluck('category')->unique()->sort()->values();

        return view('fonts.index', [
            'bundle'     => $bundle,
            'categories' => $categories,
            'totalCount' => $families->count(),
        ]);
    }

    public function compare(Request $request)
    {
        $names = collect(explode(',', (string) $request->query('fonts', '')))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->take(4)
            ->values();

        if ($names->isEmpty()) {
            return redirect()->route('fonts.index');
        }

        $families = FontFamily::query()
            ->whereIn('family', $names)
            ->with(['fontFiles' => fn ($q) => $q->orderBy('weight')->orderBy('style')])
            ->get()
            ->sortBy(fn ($f) => $names->search($f->family))
            ->values();

        $axisRegistry = Cache::rememberForever('fonts.axis_registry', function () {
            $path = database_path('seed/fonts.json');
            if (! is_file($path)) return [];
            $data = json_decode(file_get_contents($path), true);
            return collect($data['axisRegistry'] ?? [])->keyBy('tag')->all();
        });

        return view('fonts.compare', [
            'families'     => $families,
            'axisRegistry' => $axisRegistry,
        ]);
    }

    public function show(string $slug)
    {
        $family = FontFamily::query()
            ->whereRaw('LOWER(REPLACE(family, " ", "-")) = ?', [strtolower($slug)])
            ->with(['fontFiles' => fn ($q) => $q->orderBy('weight')->orderBy('style')])
            ->firstOrFail();

        $axisRegistry = Cache::rememberForever('fonts.axis_registry', function () {
            $path = database_path('seed/fonts.json');
            if (! is_file($path)) {
                return [];
            }
            $data = json_decode(file_get_contents($path), true);
            return collect($data['axisRegistry'] ?? [])->keyBy('tag')->all();
        });

        $installedFiles = [];
        if (PHP_OS_FAMILY === 'Windows' && ($appData = getenv('LOCALAPPDATA'))) {
            $userFontsDir = $appData . DIRECTORY_SEPARATOR . 'Microsoft' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts';
            foreach ($family->fontFiles as $file) {
                $installedFiles[$file->id] = is_file($userFontsDir . DIRECTORY_SEPARATOR . $file->filename);
            }
        }

        return view('fonts.show', [
            'family'         => $family,
            'axisRegistry'   => $axisRegistry,
            'installedFiles' => $installedFiles,
        ]);
    }

    public function serveFile(FontFile $fontFile)
    {
        $real = $this->resolveFontPath($fontFile);
        if ($real === null) {
            abort(404);
        }

        return Response::file($real, [
            'Content-Type' => 'font/ttf',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function installFont(FontFile $fontFile)
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return response()->json(['error' => 'Only supported on Windows'], 400);
        }

        $real = $this->resolveFontPath($fontFile);
        if ($real === null) {
            return response()->json(['error' => 'Font file not found'], 404);
        }

        $script = base_path('tools/install_font.ps1');
        if (! is_file($script)) {
            return response()->json(['error' => 'Install script missing'], 500);
        }

        $cmd = sprintf(
            'powershell -NoProfile -ExecutionPolicy Bypass -File %s -Path %s',
            escapeshellarg($script),
            escapeshellarg($real)
        );

        exec($cmd . ' 2>&1', $output, $exitCode);
        $combined = trim(implode("\n", $output));

        if ($exitCode !== 0) {
            return response()->json([
                'error'  => 'PowerShell install failed',
                'output' => $combined,
            ], 500);
        }

        $status = str_contains($combined, 'ALREADY') ? 'already_installed' : 'installed';

        return response()->json([
            'status'   => $status,
            'filename' => $fontFile->filename,
        ]);
    }

    private function resolveFontPath(FontFile $fontFile): ?string
    {
        $root = config('fonts.root');
        $filename = $fontFile->filename;

        if (! preg_match('/^[A-Za-z0-9_\[\],\-]+\.ttf$/', $filename)) {
            return null;
        }

        $path = $root . DIRECTORY_SEPARATOR . $filename;
        $real = realpath($path);
        $rootReal = realpath($root);

        if ($real === false || $rootReal === false || ! str_starts_with($real, $rootReal)) {
            return null;
        }

        return $real;
    }
}
