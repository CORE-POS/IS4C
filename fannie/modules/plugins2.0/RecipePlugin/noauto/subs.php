<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

if (count($argv) !== 2) {
    echo "Usage: sync.php [file]" . PHP_EOL;
    exit(1);
}

$dbc = FannieDB::get($FANNIE_OP_DB);

// find category
$chkP = $dbc->prepare('SELECT recipeCategoryID FROM RecipeCategories WHERE name=\'Ingredients\'');
$catID = $dbc->getValue($chkP);
if (!$catID) {
    $res = $dbc->query("INSERT INTO RecipesCategories (name) VALUES ('Ingredients')");
    $catID = $dbc->insertID();
}

// clear category
$clearP = $dbc->prepare("SELECT recipeID FROM Recipes WHERE recipeCategoryID=?");
$delP = $dbc->prepare("DELETE FROM RecipeIngredients WHERE recipeID=?");
$del2P = $dbc->prepare("DELETE FROM Recipes WHERE recipeID=?");
$clearR = $dbc->execute($clearP, array($catID));
while ($row = $dbc->fetchRow($clearR)) {
    $dbc->execute($delP, array($row['recipeID']));
    $dbc->execute($del2P, array($row['recipeID']));
}

// populate from spreadsheet
$arr = COREPOS\Fannie\API\data\FileData::fileToArray($argv[1]);
$recipeP = $dbc->prepare("INSERT INTO Recipes (name, recipeCategoryID, allergens) VALUES (?, ?, ?)");
$ingP = $dbc->prepare("INSERT INTO RecipeIngredients (recipeID, name, position) VALUES (?, ?, ?)");
foreach ($arr as $line) {
    $list = trim($line[0]);
    if (preg_match('/(.+)\s\((.+)\)/', $list, $matches)) {
        $name = trim($matches[1]);
        $aller = trim($line[1]);
        $res = $dbc->execute($recipeP, array($name, $catID, $aller));
        $rID = $dbc->insertID();
        $pos = 0;
        foreach (explode(',', $matches[2]) as $ing) {
            $ing = trim($ing);
            if ($ing !== '') {
                $dbc->execute($ingP, array($rID, $ing, $pos));
                $pos++;
            }
        }
    }
}

