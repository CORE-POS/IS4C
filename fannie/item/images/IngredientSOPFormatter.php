<?php
include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FpdfLib')) {
    include_once(__DIR__.'/../../admin/labels/pdf_layouts/FpdfLib.php');
}
/*
**  @class IngredientSOPFormatter 
*/
class IngredientSOPFormatter extends FanniePage 
{

    protected $header = 'Ingredients SOP Formatter';
    protected $title = 'Ingredients SOP Formatter';

    public function body_content()
    {

        $ingrHead = FormLib::get("ingrHead", '');
        $ingrCheck = ($ingrHead == 1) ? ' checked ' : '';

        $long_text = strtolower(FormLib::get('ingredients'));
        $long_text = str_replace('ingredients', '', $long_text);
        $long_text = str_replace(':', '', $long_text);
        $long_text = str_replace('.', '', $long_text);
        $allergens = strtolower(FormLib::get('allergens'));
        $allergens = str_replace('contains', '', $allergens);
        $allergens = str_replace(':', '', $allergens);
        $allergens = str_replace('.', '', $allergens);

        $contains = "
Contains: " . ucwords($allergens);
        $contains = rtrim($contains, ',');
        $contains = rtrim($contains, ':');
        
        $ing = ucwords($long_text);
        if ($ingrHead == 1) {
            $ing = "Ingredients: " . ucwords($long_text);
        }
        if ($allergens != null) {
            $ing .= "
";
            $ing .= $contains;
        }

        $ing = FpdfLib::strtolower_inpara($ing);
        $ing = str_replace("(", " (", $ing);
        $ing = str_replace("  ", " ", $ing);

        $ing = str_replace("organic", "Organic", $ing);
        $ing = str_replace("Certified", "", $ing);

        $ing = str_replace(";", ", ", $ing);

        $ing = rtrim($ing, ";");
        $ing = rtrim($ing, ",");

        $ing = str_replace("usa", "USA", $ing);




        $ret = <<<HTML
<div class="container" style="padding-top: 15px">
    <div class="row">
        <div class="col-lg-3">
            <ul>
                <li><a href= "NutriFactEntry.php">Enter Nutrition Facts</a></li>
                <li><a href= "ScannieBulkWrapper.php">Print Bulk Bin Labels</a></li>
            </ul>
        </div>
        <div class="col-lg-8">
            <form>
                <div class="form-group">
                    <label><strong>Ingredients</strong></label>
                    <textarea name="ingredients" class="form-control" rows=6>$long_text</textarea>
                </div>
                <div class="form-group">
                    <label><strong>Allergents</strong></label>
                    <textarea name="allergens" class="form-control" rows=4>$allergens</textarea>
                </div>
                <div class="form-group">
                    <label for="ingrHead">Include the word <i>Ingredients:</i> in Formatted Text</label>
                    <input type="checkbox" id="ingrHead" name="ingrHead" value=1 $ingrCheck/>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-default">
                </div>
                <div class="form-group">
                    <a href="IngredientSOPFormatter.php">Reset</a>
                </div>
            </form>
            <table class="table table-bordered">
            </table>

            <div class="form-group">
                <label><strong>Formatted Text</strong></label>
                <textarea class="form-control" rows=6>$ing</textarea>
            </div>
        </div>
        <div class="col-lg-1"></div>
    </div>
</div>
HTML;

        return  $ret;
    }

}

FannieDispatch::conditionalExec();
