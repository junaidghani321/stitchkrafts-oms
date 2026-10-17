<?php

/*
|--------------------------------------------------------------------------
| Order option lists
|--------------------------------------------------------------------------
|
| Every select on the order forms whose options carry no behaviour lives here,
| as value => label. One source feeds both the Blade @foreach and the
| validation rule:
|
|     Rule::in(array_keys(config('orders.placements')))
|
| Lists that DO carry behaviour (type prefixes, status badges, SLA hours) are
| enums in app/Enums instead.
|
| Values are transcribed verbatim from the existing create forms — changing
| one here without changing the form will silently fail validation.
|
*/

return [

    /*
    | Shared
    */

    'units' => [
        'inches' => 'Inches',
        'cm' => 'Centimetres',
        'mm' => 'Millimetres',
    ],

    /*
    | Required output formats — digitizing and vector have DISJOINT lists,
    | patch has none. Keyed by order type so OrderType::formatOptions() works.
    */

    'formats' => [

        'digitizing' => [
            'dst' => 'DST',
            'pes' => 'PES',
            'exp' => 'EXP',
            'jef' => 'JEF',
            'vp3' => 'VP3',
            'emb' => 'EMB',
            'tap' => 'TAP',
            'cnd' => 'CND',
            'all' => 'All Formats',
        ],

        'vector' => [
            'ai' => 'AI',
            'eps' => 'EPS',
            'pdf' => 'PDF',
            'cdr' => 'CDR',
            'svg' => 'SVG',
            'psd' => 'PSD',
            'all' => 'All Formats',
        ],

        'patch' => [],
    ],

    /*
    | Digitizing only
    */

    'placements' => [
        'left_chest' => 'Left Chest',
        'full_back' => 'Full Back',
        'cap' => 'Cap / Hat',
        'sleeve' => 'Sleeve',
        'jacket_back' => 'Jacket Back',
        'beanie' => 'Beanie',
        'bag' => 'Bag',
        'towel' => 'Towel',
        'other' => 'Other',
    ],

    /*
    | The four yes/no selects on the digitizing form. Stored as nullable
    | booleans — null means "not answered", false means "explicitly No".
    */

    'yes_no' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    /*
    | Vector only
    */

    'color_types' => [
        'spot' => 'Spot Color',
        'cmyk' => 'CMYK',
        'pantone' => 'Pantone',
        'grayscale' => 'Grayscale',
    ],

    'printing_processes' => [
        'screen' => 'Screen Printing',
        'dtg' => 'Direct to Garment',
        'sublimation' => 'Sublimation',
        'heat_transfer' => 'Heat Transfer',
        'vinyl' => 'Vinyl',
        'offset' => 'Offset',
    ],

    /*
    | Patch only
    */

    'patch_types' => [
        'embroidered' => 'Embroidered',
        'woven' => 'Woven',
        'pvc' => 'PVC',
        'leather' => 'Leather',
        'chenille' => 'Chenille',
        'sublimated' => 'Sublimated',
        'printed' => 'Printed',
    ],

    'patch_backgrounds' => [
        'twill' => 'Twill',
        'felt' => 'Felt',
        'mesh' => 'Mesh',
        'full_embroidery' => '100% Embroidery',
    ],

    'backing_types' => [
        'iron_on' => 'Iron On',
        'velcro' => 'Velcro (Hook & Loop)',
        'adhesive' => 'Adhesive / Sticker',
        'plastic' => 'Plastic Backing',
        'sew_on' => 'Sew On / Plain',
    ],

    'patch_borders' => [
        'merrowed' => 'Merrowed',
        'hot_cut' => 'Hot Cut',
        'laser_cut' => 'Laser Cut',
        'die_cut' => 'Die Cut',
        'stitched' => 'Stitched',
    ],

    /*
    |--------------------------------------------------------------------------
    | Task durations
    |--------------------------------------------------------------------------
    |
    | The #assignTaskModal duration select. Note admin/employees/show.blade.php
    | offers the same list without 24 — that view passes its own subset.
    |
    */

    'task_durations' => [1, 2, 3, 4, 6, 8, 12, 24],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | `extensions` is used with the Rule::extensions() validator, NOT `mimes:`.
    | Embroidery and CAD formats (dst, emb, pes, cdr...) have no MIME mapping
    | and are detected as application/octet-stream, so `mimes:` rejects
    | perfectly valid files with an error the customer cannot act on.
    |
    | These land on the private `orders` disk (see config/filesystems.php),
    | which is never web-served, and are renamed to a ULID on write.
    |
    */

    'uploads' => [

        'disk' => 'orders',

        'max_files' => 20,

        // Kilobytes — 20 MB per file.
        'max_size' => 20480,

        'extensions' => [
            // Raster + vector artwork
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp',
            'pdf', 'ai', 'eps', 'psd', 'cdr', 'svg',
            // Embroidery machine formats
            'dst', 'emb', 'pes', 'exp', 'jef', 'vp3', 'tap', 'cnd', 'hus', 'pcs', 'xxx',
            // Archives — customers routinely send a zip of references
            'zip', 'rar', '7z',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Index scope
    |--------------------------------------------------------------------------
    |
    | oms-table.js does search, filtering, paging and pagination entirely
    | client-side, so controllers MUST return full collections — never a
    | paginator. That makes the rendered row count the real scaling limit,
    | so the index queries apply this cap as a safety valve.
    |
    | Set `recent_days` to null to disable the date window.
    |
    */

    'index' => [
        'recent_days' => null,
        'max_rows' => 1000,
    ],

];
