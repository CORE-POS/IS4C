<?php

use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include($FANNIE_ROOT . 'install/util.php');
}

class OverShortParsPage extends FannieRESTfulPage
{
    protected $header = 'Safe Pars';
    protected $title = 'Safe Pars';
    public $discoverable = false;

    protected function post_handler()
    {
        $stores = new StoresModel($this->connection);
        $stores->hasOwnItems(1);
        $stores = $stores->find();
        $pars = array();
        foreach (array("0.01", "0.05", "0.10", "0.25", "1.00", "5.00", "10.00") as $denom) {
            $ret .= '<tr><td>' . $denom . '</td>';
            foreach ($stores as $s) {
                if (!isset($pars[$s->storeID()])) {
                    $pars[$s->storeID()] = array();
                }
                $key = 'osp_' . $s->storeID() . '_' . str_replace('.', '_', $denom);
                $pars[$s->storeID()][$denom] = FormLib::get($key, 0);
            }
        }
        $this->wrote = file_put_contents(__DIR__ . '/pars.json', FannieUI::prettyJSON(json_encode($pars)));
        return true;
    }

    protected function post_view()
    {
        $msg = $this->wrote ? '<div class="alert alert-success">Pars Updated</div>' : '<div class="alert alert-danger">Cannot save pars</div>';
        return $msg . $this->get_view();
    }

    protected function get_view()
    {
        $pars = array();
        if (file_exists(__DIR__ . '/pars.json')) {
            $pars = json_decode(file_get_contents(__DIR__ . '/pars.json'), true);
        }
        $stores = new StoresModel($this->connection);
        $stores->hasOwnItems(1);
        $stores = $stores->find();

        $ret = '<form method="post">
            <table class="table table-bordered table-striped">
            <thead><tr><th>&nbsp;</th>';
        foreach ($stores as $s) {
            $ret .= '<th>' . $s->description() . '</th>';
        }
        $ret .= '</tr></thead><tbody>';

        foreach (array("0.01", "0.05", "0.10", "0.25", "1.00", "5.00", "10.00") as $denom) {
            $ret .= '<tr><td>' . $denom . '</td>';
            foreach ($stores as $s) {

                $ret .= sprintf('<td>
                        <input type="text" class="form-control" name="osp_%d_%s" value="%s" />
                        </td>',
                        $s->storeID(), $denom,
                        (isset($pars[$s->storeID()][$denom]) ? $pars[$s->storeID()][$denom] : 0)
                );
            }
            $ret .= '</tr>';
        }
        $ret .= '</tbody></table>
            <p><button type="submit" class="btn btn-default btn-core">Save</button></p>
            </form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

