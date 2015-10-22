<?php
if (!headers_sent()) {
    header('Content-type: text/css');
}
if (!isset($FANNIE_CSS_BG_COLOR)) {
    include(dirname(__FILE__) . '/../../config.php');
}

$conf_background = isset($FANNIE_CSS_BG_COLOR) ? $FANNIE_CSS_BG_COLOR : '#FFFFFF';
$conf_background = str_replace(';', '', $conf_background);
if (empty($conf_background)) {
    $conf_background = '#FFFFFF';
} else if (is_numeric($conf_background)) {
    $conf_background = '#' . $conf_background;
}

$conf_foreground = isset($FANNIE_CSS_FG_COLOR) ? $FANNIE_CSS_FG_COLOR : '#222222';
$conf_foreground = str_replace(';', '', $conf_foreground);
if (empty($conf_foreground)) {
    $conf_foreground = '#222222';
} else if (is_numeric($conf_foreground)) {
    $conf_foreground = '#' . $conf_foreground;
}

$conf_primary_color = isset($FANNIE_CSS_PRIMARY_COLOR) ? $FANNIE_CSS_PRIMARY_COLOR : '#330066';
$conf_primary_color = str_replace(';', '', $conf_primary_color);
if (empty($conf_primary_color)) {
    $conf_primary_color = '#330066';
} else if (is_numeric($conf_primary_color)) {
    $conf_primary_color = '#' . $conf_primary_color;
}

$conf_secondary_color = isset($FANNIE_CSS_SECONDARY_COLOR) ? $FANNIE_CSS_SECONDARY_COLOR : '#444444';
$conf_secondary_color = str_replace(';', '', $conf_secondary_color);
if (empty($conf_secondary_color)) {
    $conf_secondary_color = '#444444';
} else if (is_numeric($conf_secondary_color)) {
    $conf_secondary_color = '#' . $conf_secondary_color;
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
    color: ' . $conf_primary_color . ';
}
.primaryBorder {
    border-color: ' . $conf_primary_color . ';
}
.primaryBackground, .MemFormTable th {
    background-color: ' . $conf_primary_color . ' !important;
    /*background-color: #FF5800;*/
}
.btn-core {
    border-color: ' . $conf_primary_color . ';
    border-width: 3px;
}

.secondaryColor, #css_menu_root {
    color: ' . $conf_secondary_color . ';
    /*color: #673F17;*/
}
.secondaryBackground {
    background-color: ' . $conf_secondary_color . ';
}
.secondaryBorder, #css_menu_root div.sub {
    border-color: ' . $conf_secondary_color . ';
    /*border-color: #7AB800;*/
}

.bgColor, body {
    background-color: ' . $conf_background . ';
}

.textColor, body {
    color: ' . $conf_foreground . ';
}

.fannieFont, body {
    font-family: ' . $font . ';
}
';
