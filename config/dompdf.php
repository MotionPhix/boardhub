<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Settings
  |--------------------------------------------------------------------------
  |
  | Set some default values. It is possible to add all defines that can be set
  | in dompdf_config.inc.php. You can also override the entire config file.
  |
  */
  'show_warnings' => false,   // Throw an Exception on warnings from dompdf

  'public_path' => null,  // Override the public path if needed

  /*
   * Dejavu Sans font is missing glyphs for converted entities, turn it off if you need to show € and £.
   */
  'convert_entities' => true,

  // Add this new fonts configuration
  'fonts' => [
    // Directory where the fonts should be stored
    'dir' => storage_path('fonts/'),

    // Font cache directory
    'cache' => storage_path('fonts/'),

    // Default font configuration
    'default_font' => 'Roboto Condensed',

    // Font families
    'families' => [
      'Geist Mono' => [
        'normal' => storage_path('fonts/GeistMono-Regular.ttf'),
        'medium' => storage_path('fonts/GeistMono-Medium.ttf'),
        'bold' => storage_path('fonts/GeistMono-Bold.ttf'),
      ],
      'Roboto Condensed' => [
        'normal' => storage_path('fonts/RobotoCondensed-Regular.ttf'),
        'bold' => storage_path('fonts/RobotoCondensed-Bold.ttf'),
        'italic' => storage_path('fonts/RobotoCondensed-Italic.ttf'),
        'bold_italic' => storage_path('fonts/RobotoCondensed-BoldItalic.ttf')
      ],
      'Lato' => [
        'normal' => storage_path('fonts/Lato-Regular.ttf'),
        'bold' => storage_path('fonts/Lato-Bold.ttf'),
        'italic' => storage_path('fonts/Lato-Italic.ttf'),
        'bold_italic' => storage_path('fonts/Lato-BoldItalic.ttf')
      ],
      'Lekton' => [
        'normal' => storage_path('fonts/Lekton-Regular.ttf'),
        'bold' => storage_path('fonts/Lekton-Bold.ttf'),
        'italic' => storage_path('fonts/Lekton-Italic.ttf'),
      ],
      'Roboto Flex' => [
        'normal' => storage_path('fonts/RobotoFlex-VariableFont.ttf'),
      ]
    ]
  ],

  'options' => [
    'font_dir' => storage_path('fonts'),
    'font_cache' => storage_path('fonts'),
    'temp_dir' => sys_get_temp_dir(),
    'chroot' => realpath(base_path()),
    'allowed_protocols' => [
      'data://' => ['rules' => []],
      'file://' => ['rules' => []],
      'http://' => ['rules' => []],
      'https://' => ['rules' => []],
    ],
    'enable_font_subsetting' => true,  // Changed to true for smaller PDF size
    'pdf_backend' => 'CPDF',
    'default_media_type' => 'screen',
    'default_paper_size' => 'a4',
    'default_font' => 'Geist Mono',
    'dpi' => 96,
    'enable_php' => false,
    'enable_javascript' => true,
    'enable_remote' => true,  // Changed to true to allow loading remote resources
    'font_height_ratio' => 1.1,
    'enable_html5_parser' => true,
  ],

];
