<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('RecipesModel')) {
    include(__DIR__ . '/RecipesModel.php');
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
                if (preg_match('/\s+' . $term . '[\s\*]*/', strtoupper($line))) {
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
        if (FormLib::get('ingredientList', false) !== false) {
            $model->ingredientList(FormLib::get('ingredientList'));
        }
        if (FormLib::get('ingredientText', false) !== false) {
            $model->ingredientText(FormLib::get('ingredientText'));
        }
        if (FormLib::get('allergens', false) !== false) {
            $model->allergens(FormLib::get('allergens'));
        }
        if (FormLib::get('plu', false) !== false) {
            $model->scalePLU(FormLib::get('plu'));
        }
        $model->save();

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

        $autoIng = $this->extractIngredients($get['ingredientList']);
        $autoAller = $this->extractAllergens($get['ingredientList'] . "\n" . $get['instructions']);

        echo "<h3>{$get['name']}</h3>
            <p><label>Scale PLU</label>
            <input name=\"plu\" class=\"form-control edit-field\" value=\"" . $get['scalePLU'] . "\" />
            </p>
            <p><label>Ingredients for Prep</label>
            <textarea name=\"ingredientList\" rows=\"20\" class=\"form-control edit-field\">" . $get['ingredientList'] . "</textarea>
            </p>
            <p><label>Ingredients for Sign/Label</label>
            <br />Suggested: " . implode(', ', $autoIng) . "
            <textarea name=\"ingredientText\" rows=\"10\" class=\"form-control edit-field\">" . $get['ingredientText'] . "</textarea>
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

