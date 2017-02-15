<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('RecipesModel')) {
    include(__DIR__ . '/RecipesModel.php');
}
if (!class_exists('RecipeIngredientsModel')) {
    include(__DIR__ . '/RecipeIngredientsModel.php');
}

class RecipeEditor extends FannieRESTfulPage
{
    protected $header = 'Recipes';
    protected $title = 'Recipes';

    private function extractAllergens($lines)
    {
        if (!is_array($lines)) {
            $lines = explode("\n", $lines);
        }

        $terms = array(
            'MILK'          => 'MILK',
            'BUTTERMILK'    => 'MILK',
            'CREAM'         => 'MILK',
            'EGGS'          => 'EGGS',
            'EGG'           => 'EGGS',
            'FISH'          => 'FISH',
            'SALMON'        => 'FISH',
            'TROUT'         => 'FISH',
            'TUNA'          => 'FISH',
            'TUNAFISH'      => 'FISH',
            'SHRIMP'        => 'SHELLFISH',
            'CRAB'          => 'SHELLFISH',
            'LOBSTER'       => 'SHELLFISH',
            'ALMOND'        => 'ALMONDS',
            'ALMONDS'       => 'ALMONDS',
            'PECAN'         => 'PECANS',
            'PECANS'        => 'PECANS',
            'WALNUT'        => 'WALNUTS',
            'WALNUTS'       => 'WALNUTS',
            'HAZELNUT'      => 'HAZELNUTS',
            'HAZELNUTS'     => 'HAZELNUTS',
            'FILBERT'       => 'HAZELNUTS',
            'FILBERTS'      => 'HAZELNUTS',
            'PINE NUT'      => 'PINE NUTS',
            'PINE NUTS'     => 'PINE NUTS',
            'CASHEW'        => 'CASHEWS',
            'CASHEWS'       => 'CASHEWS',
            'MACADAMIA'     => 'MACADAMIAS',
            'MACADAMIAS'    => 'MACADAMIAS',
            'PISTACHIO'     => 'PISTACHIOS',
            'PISTACHIOS'    => 'PISTACHIOS',
            'BRAZIL NUT'    => 'BRAZIL NUTS',
            'BRAZIL NUTS'   => 'BRAZIL NUTS',
            'SHEA NUT'      => 'SHEA NUTS',
            'SHEA NUTS'     => 'SHEA NUTS',
            'PEANUT'        => 'PEANUTS',
            'PEANUTS'       => 'PEANUTS',
            'WHOLEWHEAT'    => 'WHEAT',
            'WHEAT'         => 'WHEAT',
            'PANKO'         => 'WHEAT',
            'AP FLOUR'      => 'WHEAT',
            'ALL PURPOSE FLOUR' => 'WHEAT',
            'GOLD-N-WHITE FLOUR' => 'WHEAT',
            'SEITAN'        => 'WHEAT',
            'SOY'           => 'SOY',
            'SOYBEAN'       => 'SOY',
            'SOYBEANS'      => 'SOY',
            'SOY BEANS'     => 'SOY',
            'EDAMAME'       => 'SOY',
            'TOFU'          => 'SOY',
            'TEMPEH'        => 'SOY',
            'TAMARI'        => 'SOY',
        );


        $ret = array();
        foreach ($lines as $line) {
            foreach ($terms as $term => $label) {
                if (preg_match('/\s*' . $term . '[\s\*]*/', strtoupper($line))) {
                    if (!in_array($label, $ret)) {
                        $ret[] = $label;
                    }
                }
            }
        }

        return $ret;
    }

    private function extractIngredients($lines)
    {
        if (!is_array($lines)) {
            $lines = explode("\n", $lines);
        }
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

        $ret = array();
        foreach ($lines as $line) {
            if (substr(trim($line), -1) == ':') continue;
            $found = false;
            $line = strtoupper($line);
            foreach ($splits as $split) {
                if (strpos($line, " {$split} ")) {
                    list(,$rest) = explode(" {$split} ", $line, 2);
                    $ing = '';
                    foreach (explode(' ', $rest) as $part) {
                        if (in_array($part, $dashes)) {
                            break;
                        }
                        $ing .= $part . ' ';
                    }
                    $ret[] = trim($ing);
                    $found = true;
                    break;
                }
            }
            if (!empty(trim($line)) && !$found) {
                $ret[] = preg_replace('/^\d+\S*\s+/', '', trim($line));
            }
        }

        return $ret;
    }

    function post_id_handler()
    {
        $model = new RecipesModel($this->connection);
        $model->recipeID($this->id);
        if (FormLib::get('instructions', false) !== false) {
            $model->instructions(FormLib::get('instructions'));
        }
        if (FormLib::get('allergens', false) !== false) {
            $model->allergens(FormLib::get('allergens'));
        }
        if (FormLib::get('plu', false) !== false) {
            $model->scalePLU(FormLib::get('plu'));
        }
        if (FormLib::get('name', false) !== false) {
            $ids = FormLib::get('ingID');
            $name = FormLib::get('name');
            $amount = FormLib::get('amount');
            $unit = FormLib::get('unit');
            $notes = FormLib::get('notes');
            $json = array();
            $upP = $this->connection->prepare('
                UPDATE RecipeIngredients
                SET name=?,
                    amount=?,
                    unit=?,
                    notes=?,
                    position=?
                WHERE recipeIngredientID=?');
            $insP = $this->connection->prepare('
                INSERT INTO RecipeIngredients
                    (name, amount, unit, notes, position, recipeID)
                VALUES (?, ?, ?, ?, ?, ?)');
            $validIDs = array();
            /**
              Update existing ingredients and add new ingredients

              $json is an array mapping the fake, javascript-generated IDs
              to the real, SQL-generated IDs. This needs to be returned to
              the caller to update the form values.

              $validIDs are ingredient IDs that are still present in the POST.
              Any ID not in this list should be deleted
            */
            for ($i=0; $i<count($ids); $i++) {
                if (!isset($name[$i]) || trim($name[$i]) == '') continue;
                if (substr($ids[$i], 0, 3) == 'id_') { 
                    // new addition placeholder
                    $fakeID = $ids[$i];
                    $args = array(
                        trim($name[$i]),
                        isset($amount[$i]) ? trim($amount[$i]) : '',
                        isset($unit[$i]) ? trim($unit[$i]) : '',
                        isset($notes[$i]) ? trim($notes[$i]) : '',
                        $i,
                        $this->id,
                    );
                    $this->connection->execute($insP, $args);
                    $realID = $this->connection->insertID();
                    $validIDs[] = $realID;
                    $json[] = array('fakeID'=>$fakeID, 'realID'=>$realID);
                } else {
                    $args = array(
                        trim($name[$i]),
                        isset($amount[$i]) ? trim($amount[$i]) : '',
                        isset($unit[$i]) ? trim($unit[$i]) : '',
                        isset($notes[$i]) ? trim($notes[$i]) : '',
                        $i,
                        $ids[$i],
                    ); 
                    $this->connection->execute($upP, $args);
                    $validIDs[] = $ids[$i];
                }
            }
            list($inStr, $inArgs) = $this->connection->safeInClause($validIDs);
            $cleanP = $this->connection->prepare("
                DELETE FROM RecipeIngredients
                WHERE recipeIngredientID NOT IN ({$inStr})
                    AND recipeID=?");
            $inArgs[] = $this->id;
            $this->connection->execute($cleanP, $inArgs);
        }
        $model->save();
        echo json_encode($json);

        return false;
    }

    function get_id_handler()
    {
        $getP = $this->connection->prepare('SELECT * FROM Recipes WHERE recipeID=?');
        $getR = $this->connection->execute($getP, array($this->id));
        if ($this->connection->numRows($getR) == 0) {
            echo '<div class="alert alert-danger">No recipe found</div>';
            return false;
        }

        $get = $this->connection->fetchRow($getR);

        $ing = '<table class="table table-bordered">
            <tr><th>Amount</th><th>Unit</th><th>Ingredient</th><th>Notes</th>
            <th><a class="btn btn-success btn-xs" href="" onclick="recipe.addRow(this); return false;"><span class="glyphicon glyphicon-plus"></span></a></th></tr>';
        $ingList = '';
        $ingP = $this->connection->prepare('SELECT * FROM RecipeIngredients WHERE recipeID=? ORDER BY position');
        $ingR = $this->connection->execute($ingP, array($this->id));
        while ($ingW = $this->connection->fetchRow($ingR)) {
            $ing .= sprintf('<tr>
                    <td><input type="hidden" class="edit-field" name="ingID[]" value="%d" />
                    <input type="text" class="form-control input-sm edit-field" name="amount[]" value="%s" /></td>
                    <td><input type="text" class="form-control input-sm edit-field" name="unit[]" value="%s" /></td>
                    <td><input type="text" class="form-control input-sm edit-field" name="name[]" value="%s" /></td>
                    <td><input type="text" class="form-control input-sm edit-field" name="notes[]" value="%s" /></td>
                    <td><a class="btn btn-success btn-xs" href="" onclick="recipe.addRow(this); return false;"><span
                        class="glyphicon glyphicon-plus"></span></a>
                        <a class="btn btn-danger btn-xs" href="" onclick="recipe.delRow(this); return false;"><span
                        class="glyphicon glyphicon-minus"></span></a>
                    </td>
                    </tr>',
                    $ingW['recipeIngredientID'],
                    $ingW['amount'],
                    $ingW['unit'],
                    $ingW['name'],
                    $ingW['notes']
            );
            $ingList .= $ingW['name'] . "\n";
        }
        $ing .= '</table>';

        $autoAller = $this->extractAllergens($get['ingredientList'] . "\n" . $get['instructions']);

        echo "<h3>{$get['name']}</h3>
            <p><label>Scale PLU</label>
            <input name=\"plu\" class=\"form-control edit-field\" value=\"" . $get['scalePLU'] . "\" />
            </p>
            <p><label>Ingredients for Prep</label>
            {$ing}
            </p>
            <p><label>Allergens</label>
            <br />Suggested: " . implode(', ', $autoAller) . "
            <input name=\"allergens\" class=\"form-control edit-field\" value=\"" . $get['allergens'] . "\" />
            </p>
            <p><label>Instructions for Prep</label>
            <textarea name=\"instructions\" rows=\"20\" class=\"form-control edit-field\">" . $get['instructions'] . "</textarea>
            </p>
            <p>
            <a href=\"\" onclick=\"recipe.save({$get['recipeID']}); return false;\" class=\"btn btn-default\">Save</a>
            </p>";

        return false;
    }

    function get_view()
    {
        $ret = '<div class="row">';
        $ret .= '<div class="col-sm-3">';
        $ret .= '<div class="panel-group" id="accordion">';

        $res = $this->connection->query('SELECT recipeCategoryID AS id, name FROM RecipeCategories ORDER BY name');
        $recP = $this->connection->prepare('SELECT recipeID, name FROM Recipes WHERE recipeCategoryID=? ORDER BY name');         
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= '<div class="panel panel-default">
                <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse' . $row['id'] . '">
                        <span class="glyphicon glyphicon-folder-close"></span> ' . $row['name'] . '</a>
                </h4>
                </div>
                <div id="collapse' . $row['id'] . '" class="panel-collapse collapse">
                    <div class="panel-body">
                        <table class="table">';
            $recR = $this->connection->execute($recP, array($row['id']));
            while ($recW = $this->connection->fetchRow($recR)) {
                $ret .= '<tr><td><a href="" onclick="recipe.edit(' . $recW['recipeID'] . '); return false;">' 
                    . $recW['name'] . '</a></td></tr>';
            }
            $ret .= '</table>
                </div>
                </div>
                </div>';
        }

        $ret .= '</div>
            <p>
                <a class="btn btn-default" href="RecipeViewer.php">Back to Viewer</a>
            </p>
        </div>';
        $ret .= '<div class="col-sm-8">
            <div class="panel panel-default">
                <div class="panel-body" id="recipeContent"></div>
            </div>
            </div>';
        $ret .= '</div>';
        $this->addScript('recipe.js');

        return $ret;
    }
}

FannieDispatch::conditionalExec();

