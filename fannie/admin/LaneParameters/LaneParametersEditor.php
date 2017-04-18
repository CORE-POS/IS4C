<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class LaneParametersEditor extends FannieRESTfulPage 
{
    protected $title = 'Lane Configuration: Parameters';
    protected $header = 'Lane Configuration: Parameters';
    protected $auth_classes = array('admin');
    protected $must_authenticate = true;

    public $description = "[Lane Parameters Editor] provides raw access to the lane configuration settings";

    private function paramType($param)
    {
        if ($param->lane_id() != 0) {
            return 'Lane Override';
        } elseif ($param->store_id() != 0) {
            return 'Store Override';
        }

        return 'Global';
    }

    protected function post_id_handler()
    {
        $parameters = new ParametersModel($this->connection);
        $parameters->lane_id($this->id);
        $parameters->store_id(FormLib::get('store'));
        $parameters->param_key(FormLib::get('key'));
        $parameters->param_value('');
        $parameters->is_array(0);
        $parameters->save();

        return 'LaneParametersEditor.php';
    }

    protected function post_handler()
    {
        $parameters = new ParametersModel($this->connection);
        $lanes = FormLib::get('lane');
        $stores = FormLib::get('stores');
        $keys = FormLib::get('key');
        $vals = FormLib::get('val');
        $arrays = FormLib::get('array');
        for ($i=0; $i<count($lanes); $i++) {
            $parameters->lane_id($lanes[$i]);
            $parameters->store_id($stores[$i]);
            $parameters->param_key($keys[$i]);
            $parameters->param_value($vals[$i]);
            $parameters->is_array(in_array($keys[$i], $arrays) ? 1 : 0);
            $parameters->save();
        }

        return 'LaneParametersEditor.php';
    }

    protected function delete_id_handler()
    {
        $parameters = new ParametersModel($this->connection);
        $parameters->lane_id($this->id);
        $parameters->store_id(FormLib::get('store'));
        $parameters->param_key(FormLib::get('key'));
        $parameters->delete();

        return 'LaneParametersEditor.php';
    }

    protected function get_view()
    {
        $parameters = new ParametersModel($this->connection);
        $all = $parameters->find(array('param_key', 'store_id', 'lane_id'));

        $ret = '<form method="post">
            <table class="table table-bordered">
            <thead><tr>
                <th>Type</th>
                <th>Lane</th>
                <th>Store</th>
                <th>Key</th>
                <th>Value</th>
                <th>Is Array</th>
                <th>&nbsp;</th>
            </tr></thead><tbody>';
        foreach ($all as $p) {
            $ret .= sprintf('<tr class="%s">
                <td>%s</td>
                <td><input type="hidden" value="%d" name="lane[]" />%d</td>
                <td><input type="hidden" value="%d" name="store[]" />%d</td>
                <td><input type="text" class="form-control input-sm" value="%s" name="key[]" /></td>
                <td><input type="text" class="form-control input-sm" value="%s" name="val[]" /></td>
                <td><input type="checkbox" %s name="array[]" value="%s" /></td>
                <td><a class="btn btn-danger btn-xs" href="?_method=delete&id=%d&store=%d&key=%s">%s</a>
                </tr>',
                ($this->paramType($p) != 'Global' ? 'alert-warning' : ''),
                $this->paramType($p),
                $p->lane_id(), $p->lane_id(),
                $p->store_id(), $p->store_id(),
                $p->param_key(),
                $p->param_value(),
                ($p->is_array() ? 'checked' : ''),
                $p->param_key(),
                $p->lane_id(),
                $p->store_id(),
                $p->param_key(),
                FannieUI::deleteIcon()
            );
        }
        $ret .= '</tbody></table>
            <p>
                <button type="submit" class="btn btn-default btn-core">Save Parameters</button>
                <button type="reset" class="btn btn-default btn-reset">Reset</button>
            </p>
            </form>
            <div class="panel panel-default">
                <div class="panel-heading">Add a setting</div>
                <div class="panel-body">
                    <form method="post" class="form-inline">
                    <div class="form-group">
                        Lane # <input type="number" class="form-control" name="id" required />
                        Store # <input type="number" class="form-control" name="store" required />
                        Key <input type="text" class="form-control" name="key" required />
                        <button type="submit" class="btn btn-default">Add</button>
                    </div>
                    </form>
                </div>
            </div>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>This is a very low-level tool to directly edit lane parameters (or "settings") 
stored in the database. Some understanding of what these names mean is required. Store overrides
are settings that apply instead of the global parameter for a given store. Lane overrides are
settings that apply instead of the global or store parameter for a given lane.</p>';
    }
}

FannieDispatch::conditionalExec();

