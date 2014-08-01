<?php
header('Content-type: text/css');
if (!isset($FANNIE_CSS_BG_COLOR)) {
    include(dirname(__FILE__) . '/../../config.php');
}

$bg = isset($FANNIE_CSS_BG_COLOR) ? $FANNIE_CSS_BG_COLOR : '#FFFFFF';
$bg = str_replace(';', '', $bg);
if (empty($bg)) {
    $bg = '#FFFFFF';
} else if (is_numeric($bg)) {
    $bg = '#' . $bg;
}

$fg = isset($FANNIE_CSS_FG_COLOR) ? $FANNIE_CSS_FG_COLOR : '#222222';
$fg = str_replace(';', '', $fg);
if (empty($fg)) {
    $fg = '#222222';
} else if (is_numeric($fg)) {
    $fg = '#' . $fg;
}

$pc = isset($FANNIE_CSS_PRIMARY_COLOR) ? $FANNIE_CSS_PRIMARY_COLOR : '#330066';
$pc = str_replace(';', '', $pc);
if (empty($pc)) {
    $pc = '#330066';
} else if (is_numeric($pc)) {
    $pc = '#' . $pc;
}

$sc = isset($FANNIE_CSS_SECONDARY_COLOR) ? $FANNIE_CSS_SECONDARY_COLOR : '#444444';
$sc = str_replace(';', '', $sc);
if (empty($sc)) {
    $sc = '#444444';
} else if (is_numeric($sc)) {
    $sc = '#' . $sc;
}

$font = isset($FANNIE_CSS_FONT) ? $FANNIE_CSS_FONT : '';
$font = str_replace(';', '', $font);
$font = rtrim($font, ',');
if (!empty($font)) {
    $font .= ', ';
}
$font .= 'arial, sans-serif';

echo '
.primaryColor, a, a:hover {
    color: ' . $pc . ';
}
.primaryBorder {
    border-color: ' . $pc . ';
}
.primaryBackground, .MemFormTable th {
    background-color: ' . $pc . ';
    /*background-color: #FF5800;*/
}

.secondaryColor, #css_menu_root {
    color: ' . $sc . ';
    /*color: #673F17;*/
}
.secondaryBackground {
    background-color: ' . $sc . ';
}
.secondaryBorder, #css_menu_root div.sub {
    border-color: ' . $sc . ';
    /*border-color: #7AB800;*/
}

.bgColor, body {
    background-color: ' . $bg . ';
}

.textColor, body {
    color: ' . $fg . ';
}

.fannieFont, body {
    font-family: ' . $font . ';
}
';
