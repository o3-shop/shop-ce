<?php

// Counts how often the theme info is read (ThemeTemplateEngineProviderTest).
$GLOBALS['invalidThemeInfoLoads'] = ($GLOBALS['invalidThemeInfoLoads'] ?? 0) + 1;

$aTheme = [
    'id' => 'invalidtheme', 'templateEngine' => 'blade',
];
