<?php

use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class TaxComponentEditor extends FannieRESTfulPage 
{
    protected $title = "Fannie : Tax Rate Components";
    protected $header = "Tax Rate Components";

    public $description = '[Tax Rate Components] defines rate(s) that combine to form an effective tax rate.';

    protected function post_handler()
    {
        try {
            $names = $this->form->newName;
            $rates = $this->form->newRate;
            $ids = $this->form->newRateID;
            for ($i=0; $i<count($ids); $i++) {
                $rate = trim($rates[$i]);
                $name = trim($names[$i]);
                $rateID = $ids[$i];
                if ($rate != '' && $name != '' && is_numeric($rateID)) {
                    $rate /= 100;
                    $model = new TaxRateComponentsModel($this->connection);
                    $model->taxRateID($rateID);
                    $model->rate($rate);
                    $model->description($name);
                    $model->save();
                }
            }
        } catch (Exception $ex) {
        }

        try {
            $ids = $this->form->cID;
            $names = $this->form->name;
            $rates = $this->form->rate;
            for ($i=0; $i<count($ids); $i++) {
                $rates[$i] /= 100;
                $model = new TaxRateComponentsModel($this->connection);
                $model->taxRateComponentID($ids[$i]);
                $model->rate($rates[$i]);
                $model->description($names[$i]);
                $model->save();
            }
        } catch (Exception $ex) {
        }

        return 'TaxComponentEditor.php';
    }

    protected function delete_id_handler()
    {
        $model = new TaxRateComponentsModel($this->connection);
        $model->taxRateComponentID($this->id);
        $model->delete();

        return 'TaxComponentEditor.php';
    }

    protected function get_view()
    {
        $tax = new TaxRatesModel($this->connection);
        $taxes = $tax->find();
        $ret = '<form method="post"><table class="table">';
        foreach ($taxes as $t) {
            $effectiveRate = $t->rate();
            $ret .= sprintf('<tr><th>%d</th><th>%s</th><th>%.4f%%</th></tr>',
                $t->id(), $t->description(), $effectiveRate*100);
            $comp = new TaxRateComponentsModel($this->connection);
            $comp->taxRateID($t->id());
            $combined = 0;
            foreach ($comp->find() as $c) {
                $ret .= sprintf('<tr><td><input type="hidden" name="cID[]" value="%d" /></td>
                    <td><input type="text" class="form-control" name="name[]" value="%s" /></td>
                    <td>
                        <div class="input-group">
                            <input type="text" class="form-control" name="rate[]" value="%.4f" />
                            <span class="input-group-addon">%%</span>
                        </div>
                    </td>
                    <td><a class="btn btn-danger btn-small" href="TaxComponentEditor.php?_method=delete&id=%d">%s</a></td>
                    </tr>',
                    $c->taxRateComponentID(),
                    $c->description(), $c->rate()*100,
                    $c->taxRateComponentID(),
                    FannieUI::deleteIcon()
                );
                $combined += $c->rate();
            }
            if (abs($combined - $effectiveRate) > 0.00005) {
                $ret .= '<tr><td colspan="3" class="danger">Components don\'t add up correctly</td></tr>';
                if ($effectiveRate > $combined) {
                    $ret .= '<tr><td>NEW</td><td><input type="text" name="newName[]" class="form-control" /></td>
                        <td><div class="input-group">
                            <input type="text" name="newRate[]" class="form-control" />
                            <span class="input-group-addon">%%</span>
                        </div></td></tr>';
                    $ret .= sprintf('<input type="hidden" name="newRateID[]" value="%d" />', $t->id());
                }
            }
        }
        $ret .= '</table>
            <p><button type="submit" class="btn btn-default btn-core">Save</button>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="TaxRateEditor.php" class="btn btn-default">Back to Effective Rates</a>
            </p></form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>An effective rate may be composed of multiple different taxes. For example,
            if state sales tax is 5% and city sales tax is 1% the effective rate on items were both
            taxes apply will be 6%. Tax Rate Components are a place to store these individual rates
            that make up each effective tax rate.</p>';
    }
}

FannieDispatch::conditionalExec();

