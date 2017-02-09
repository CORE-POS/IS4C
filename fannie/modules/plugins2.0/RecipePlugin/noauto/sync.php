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
$dbc->query('TRUNCATE TABLE RecipeIngredients');

$catP = $dbc->prepare('INSERT INTO RecipeCategories (name) VALUES (?)');
$addP = $dbc->prepare('INSERT INTO Recipes (name, recipeCategoryID, ingredientList, instructions) VALUES (?, ?, ?, ?)');
$ingP = $dbc->prepare('INSERT INTO RecipeIngredients (recipeID, amount, unit, name, notes, position) VALUES (?, ?, ?, ?, ?, ?)');

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

            $splits = array(
                'CUP',
                'CUPS',
                'C',
                'T',
                't',
                'QT',
                'QTS',
                'QUARTS',
                'CAN',
                'JAR',
                'BUNCH',
                'OZ',
                'LB',
                'LBS',
                'POUNDS',
                'BU',
                'PACKAGE',
                'PACKAGES',
                'EA',
                'EACH',
                'LARGE',
            );
            $limit = count($splits);
            for ($i=0; $i<$limit; $i++) {
                $splits[] = $splits[$i] . '.';
            }
            $utfEmdash = pack('CCC', 0xe2, 0x80, 0x94);
            $utfEndash = pack('CCC', 0xe2, 0x80, 0x93);
            $dashes = array('-', chr(150), chr(151), $utfEmdash, $utfEndash);

            $ingredients = array();
            $instructions = '';
            foreach (explode("\n", $text) as $line) {
                $found = false;
                foreach ($splits as $split) {
                    if (strpos($line, " {$split} ")) {
                        list($amt,$rest) = explode(" {$split} ", $line, 2);
                        $ing = $rest;
                        $notes = '';
                        foreach ($dashes as $dash) {
                            if (strstr($ing, $dash)) {
                                list($ing, $notes) = explode($dash, $ing, 2);
                                break;
                            }
                        }
                        $ingredients[] = array(trim($amt), trim($split), trim($ing), trim($notes));
                        $found = true;
                        break;
                    }
                }
                if (!$found && preg_match('/^(\d+)\S*\s+(.+)/', $line, $matches)) {
                    $ingredients[] = array($matches[1], '', $matches[2], '');
                } elseif (!$found && substr(trim($line), -1) == ':') {
                    $ingredients[] = array('SECTION', '', trim($line), '');
                } elseif (!$found) {
                    $instructions .= $line . "\n";
                }
            }

            $dbc->execute($addP, array($name, $catID, '', $instructions));
            $recipeID = $dbc->insertID();
            $pos = 0;
            foreach ($ingredients as $ing) {
                array_unshift($ing, $recipeID);
                array_push($ing, $pos);
                $dbc->execute($ingP, $ing);
                $pos++;
            }
        }
    }
}

