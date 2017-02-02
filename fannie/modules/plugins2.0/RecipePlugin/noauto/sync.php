<?php

use PhpOffice\PhpWord\IOFactory;

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
include(__DIR__ . '/vendor/autoload.php');

if (count($argv) !== 2) {
    echo "Usage: sync.php [directory]" . PHP_EOL;
    exit(1);
}

$dbc = FannieDB::get($FANNIE_OP_DB);
$dbc->query('TRUNCATE TABLE RecipeCategories');
$dbc->query('TRUNCATE TABLE Recipes');

$catP = $dbc->prepare('INSERT INTO RecipeCategories (name) VALUES (?)');
$addP = $dbc->prepare('INSERT INTO Recipes (name, recipeCategoryID, ingredientList, instructions) VALUES (?, ?, ?, ?)');

function extractText($obj, $text, $depth=0)
{
    $inRun = false;
    foreach ($obj->getElements() as $element) {
        $name = get_class($element);
        if ($name == 'PhpOffice\PhpWord\Element\Text') {
            if ($inRun) $text .= "\n";
            $inRun = false;
            $text .= $element->getText() . "\n";
        } elseif ($name == 'PhpOffice\PhpWord\Element\TextBreak') {
            if ($inRun) $text .= "\n";
            $inRun = false;
            $text .= "\n";
        } elseif ($name == 'PhpOffice\PhpWord\Element\TextRun') {
            foreach ($element->getElements() as $e2) {
                if (get_class($e2) == 'PhpOffice\PhpWord\Element\Text') {
                    $inRun = true;
                    $run = $e2->getText();
                    $text .= $run;
                    if ($run != rtrim($run)) $text .= "\n";
                }
            }
        }
    }

    return $text;
}

$dir = opendir($argv[1]);
while (($file=readdir($dir)) !== false) {
    if ($file[0] == '.') continue;
    if (!is_dir($argv[1] . '/' . $file)) continue;

    if ($file == 'G&G Recipes') {
        $ggdir = opendir($argv[1] . '/' . $file);
        $files = array();
        while (($ggfile = readdir($ggdir)) !== false) {
            if ($ggfile[0] == '.') continue;
            $path = $argv[1] . '/G&G Recipes/' . $ggfile;
            if (is_dir($path)) $files["G&G {$ggfile} Recipes"] = 'G&G Recipes/' . $ggfile;
        }
    } else {
        $files = array($file => $file);
    }

    foreach ($files as $file) {
        $dbc->execute($catP, array($file));
        $catID = $dbc->insertID();
        $subdir = opendir($argv[1] . '/' . $file);
        while (($subfile=readdir($subdir)) !== false) {
            if ($subfile[0] == '.' || $subfile[0] == '~') continue;
            if (!is_file($argv[1] . '/' . $file . '/' . $subfile)) continue;
            if (substr($subfile, -5) != '.docx' && substr($subfile, -4) != '.doc') continue;
            $doc = $argv[1] . '/' . $file . '/' . $subfile;

            $word = IOFactory::load($doc);
            $text = '';
            foreach ($word->getSections() as $section) {
                $text .= extractText($section, $text);
            }
            $name = substr($subfile, 0, strlen($subfile)-5);
            $ingredients = '';
            $instructions = '';
            $section = 'none';
            foreach (explode("\n", $text) as $line) {
                if (preg_match('/INGREDIENTS/i', $line)) {
                    $section = 'ingredients';
                    continue;
                }
                if (strtoupper(trim($line)) == 'SPECIAL INSTRUCTIONS') {
                    $section = 'instructions';
                    continue;
                }
                if (strtoupper(trim($line)) == 'SPECIAL INSTRUCTIONS:') {
                    $section = 'instructions';
                    continue;
                }
                switch ($section) {
                    case 'ingredients':
                        if (trim($line) != '' || $ingredients != '') {
                            $ingredients .= $line . "\n";
                        } 
                        break;
                    case 'instructions':
                        if (trim($line) != '' || $instructions != '') {
                            $instructions .= $line . "\n";
                        } 
                        break;
                }
            }
            if ($instructions == '' && $ingredients == '') {
                $instructions = $text;
            } elseif ($ingredients == '') {
                $ingredients = str_replace($instructions, '', $text);
            }

            $dbc->execute($addP, array($name, $catID, $ingredients, $instructions));
        }
    }
}
