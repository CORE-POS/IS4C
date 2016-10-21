<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class ObfCategoriesPageV2 extends FannieRESTfulPage 
{
    protected $title = 'OBF: Categories';
    protected $header = 'OBF: Categories';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Categories] sets up labor category divisions.';
    public $themed = true;
    protected $lib_class = 'ObfLibV2';

    public function preprocess()
    {
        return FannieRESTfulPage::preprocess();
    }

    public function post_id_handler()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();
        $model = $lib_class::getCategory($dbc);

        $ids = FormLib::get('id', array());
        $names = FormLib::get('cat', array());
        $sales = FormLib::get('hasSales', array());
        $growth = FormLib::get('growth', array());
        $hours = FormLib::get('hours', array());
        $splh = FormLib::get('splh', array());
        $stores = FormLib::get('store', array());
        for ($i=0; $i<count($ids); $i++) {
            $model->reset();
            $model->obfCategoryID($ids[$i]);
            $model->name($names[$i]);
            $model->storeID($stores[$i]);
            $model->hasSales( in_array($ids[$i], $sales) ? 1 : 0 );
            $model->growthTarget($growth[$i] / 100.00);
            $model->salesPerLaborHourTarget($splh[$i]);
            $model->save();
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function put_handler()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();
        $model = $lib_class::getCategory($dbc);
        $model->name('New Category');
        $model->save();
        
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function get_view()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();

        $model = $lib_class::getCategory($dbc);

        $ret = '<p><button class="btn btn-default"
                onclick="location=\'index.php\';return false;">Home</button>
                </p>';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table class="table">';
        $ret .= '<tr>
                    <th>Name</th>
                    <th>Store</th>
                    <th>Has Sales</th>
                    <th>Sales Growth Goal</th>
                    <th>SPLH Goal</th>
                 </tr>';
        $stores = FormLib::storePicker(); 
        foreach($model->find() as $cat) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="id[]" value="%d" />
                            <td><input type="text" name="cat[]" class="form-control" required value="%s" /></td>
                            <td><select name="store[]" class="form-control">%s</select></td>
                            <td><input type="checkbox" name="hasSales[]" value="%d" %s /></td>
                            <td><div class="input-group">
                                <input type="text" class="form-control" required name="growth[]" value="%.2f" />
                                <span class="input-group-addon">%%</span>
                            </div></td>
                            <td><input type="number" class="form-control" required name="splh[]" value="%.2f" /></td>
                            </tr>',
                            $cat->obfCategoryID(),
                            $cat->name(),
                            $this->storeOpts($stores['names'], $cat->storeID()),
                            $cat->obfCategoryID(), ($cat->hasSales() == 1 ? 'checked' : ''),
                            $cat->growthTarget() * 100,
                            $cat->salesPerLaborHourTarget()
            );
        }
        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default">Save</button>
                <a href="' . $_SERVER['PHP_SELF'] . '?_method=put" class="btn btn-default">Add Category</a></p>';
        $ret .= '</form>';

        return $ret;
    }

    private function storeOpts($stores, $sID)
    {
        $ret = '';
        foreach ($stores as $id => $name) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($id == $sID ? 'selected' : ''), $id, $name);
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();

