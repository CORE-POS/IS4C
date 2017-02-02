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

    function post_id_handler()
    {
        $model = new RecipesModel($this->connection);
        $model->recipeID($this->id);
        if (FormLib::get('instructions', false) !== false) {
            $model->instructions(FormLib::get('instructions'));
        }
        if (FormLib::get('ingredientText', false) !== false) {
            $model->instructions(FormLib::get('ingredientText'));
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

        echo "<h3>{$get['name']}</h3>
            <p><label>Ingredients</label>
            <textarea name=\"ingredientList\" rows=\"20\" class=\"form-control edit-field\">" . $get['ingredientList'] . "</textarea>
            </p>
            <p><label>Instructions</label>
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

