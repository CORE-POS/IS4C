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
class ObfCategoriesPage extends FannieRESTfulPage 
{
    public function preprocess()
    {
        if (!headers_sent()) {
            header('Location: ../OpenBookFinancingV2/ObfCategoriesPageV2.php');
        }
        return false;
    }

    protected $title = 'OBF: Categories';
    protected $header = 'OBF: Categories';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Categories] sets up labor category divisions.';
    public $themed = true;
    protected $lib_class = 'ObfLibV2';

    public function post_id_handler()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();
        $model = $lib_class::getCategory($dbc);

        $ids = FormLib::get('id', array());
        $names = FormLib::get('cat', array());
        $sales = FormLib::get('hasSales', array());
        $labor = FormLib::get('labor', array());
        $hours = FormLib::get('hours', array());
        $splh = FormLib::get('splh', array());
        for ($i=0; $i<count($ids); $i++) {
            $model->reset();
            $model->obfCategoryID($ids[$i]);
            $model->name($names[$i]);
            $model->hasSales( in_array($ids[$i], $sales) ? 1 : 0 );
            $model->laborTarget($labor[$i] / 100.00);
            $model->hoursTarget($hours[$i]);
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
                    <th>Has Sales</th>
                    <th>Labor Goal</th>
                    <th>Allocated Hours</th>
                    <th>SPLH Goal</th>
                 </tr>';
        foreach($model->find() as $cat) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="id[]" value="%d" />
                            <td><input type="text" name="cat[]" class="form-control" required value="%s" /></td>
                            <td><input type="checkbox" name="hasSales[]" value="%d" %s /></td>
                            <td><div class="input-group">
                                <input type="text" class="form-control" required name="labor[]" value="%.2f" />
                                <span class="input-group-addon">%%</span>
                            </div></td>
                            <td><input type="number" class="form-control" required name="hours[]" value="%d" /></td>
                            <td><input type="number" class="form-control" required name="splh[]" value="%.2f" /></td>
                            </tr>',
                            $cat->obfCategoryID(),
                            $cat->name(),
                            $cat->obfCategoryID(), ($cat->hasSales() == 1 ? 'checked' : ''),
                            $cat->laborTarget()*100,
                            $cat->hoursTarget(),
                            $cat->salesPerLaborHourTarget()
            );
        }
        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default">Save</button>
                <a href="' . $_SERVER['PHP_SELF'] . '?_method=put" class="btn btn-default">Add Category</a></p>';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

