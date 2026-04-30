<?php

return [
    /*
     * Absolute path to the directory containing the .ttf files served by
     * the FontController. Defined in .env as FONTS_ROOT.
     */
    'root' => env('FONTS_ROOT', base_path('google-ttf')),
];
