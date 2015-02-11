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
class ObfMappingPage extends FannieRESTfulPage 
{
    protected $title = 'OBF: Department Mapping';
    protected $header = 'OBF: Department Mapping';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Department Mapping] associates POS sales departments with
    OBF labor categories.';
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'post<id><superID><growth>';
        $this->__routes[] = 'post<add><cat>';

        return parent::preprocess();
    }

    public function post_add_cat_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $map = new ObfCategorySuperDeptMapModel($dbc);
        $map->obfCategoryID($this->cat);
        $map->superID($this->add);
        $map->save();

        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;

    }

    public function post_id_superID_growth_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $map = new ObfCategorySuperDeptMapModel($dbc);
        for ($i=0; $i<count($this->id); $i++) {
            if (!isset($this->superID[$i])) {
                continue;
            }
            $map->obfCategoryID($this->id[$i]);
            $map->superID($this->superID[$i]);
            $map->growthTarget(isset($this->growth[$i]) ? $this->growth[$i]/100.00 : 0);
            $map->save();
        }

        $delete = FormLib::get('delete', array());
        if (is_array($delete)) {
            foreach ($delete as $ids) {
                list($cat, $super) = explode(':', $ids, 2);
                $map->obfCategoryID($cat);
                $map->superID($super);
                $map->delete();
            }
        }

        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $model = new ObfCategoriesModel($dbc);
        $model->hasSales(1);

        $map = new ObfCategorySuperDeptMapModel($dbc);

        $supers = 'SELECT s.superID, s.super_name
                   FROM ' . $FANNIE_OP_DB . $dbc->sep() . 'superDeptNames AS s
                   ORDER BY s.super_name';
        $res = $dbc->query($supers);
        $sdepts = array();
        while($row = $dbc->fetch_row($res)) {
            $sdepts[$row['superID']] = $row['super_name'];
        }

        $ret = '<div class="col-sm-5">';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table class="table">';
        $ret .= '<tr>
                 </tr>';
        foreach($model->find() as $cat) {
            $ret .= '<tr><th colspan="2">' . $cat->name() . '</th>
                    <td>' . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon('Check box for row to delete') . '
                    </td></tr>';
            $map->obfCategoryID($cat->obfCategoryID());
            foreach($map->find() as $obj) {
                $ret .= sprintf('<tr>
                                <input type="hidden" name="id[]" value="%d" />
                                <input type="hidden" name="superID[]" value="%d" />
                                <td>%s</td>
                                <td><div class="input-group">
                                    <input type="text" name="growth[]" class="form-control" required value="%.3f" />
                                    <span class="input-group-addon">%%</span>
                                </div></td>
                                <td><input type="checkbox" name="delete[]" value="%d:%d" /></td>
                                </tr>',
                                $obj->obfCategoryID(),
                                $obj->superID(),
                                $sdepts[$obj->superID()],
                                $obj->growthTarget()*100,
                                $obj->obfCategoryID(), $obj->superID()
                );
                unset($sdepts[$obj->superID()]);
            }
        }
        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default">Save Mapping</button></p>';
        $ret .= '</form>';
        $ret .= '</div>';
        $ret .= '<div class="col-sm-5">';
        $ret .= '<div class="panel panel-default"><div class="panel-body">';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">
                    <div class="form-group form-inline">';
        $ret .= '<label>Add</label> <select name="add" class="form-control">';
        foreach ($sdepts as $id => $name) {
            $ret .= sprintf('<option value="%d">%s</option>', $id, $name);
        }
        $ret .= '</select>';
        $ret .= ' <label>to</label> <select name="cat" class="form-control">';
        foreach ($model->find() as $cat) {
            $ret .= sprintf('<option value="%d">%s</option>', $cat->obfCategoryID(), $cat->name());
        }
        $ret .= '</select></div>';
        $ret .= '<p><button type="submit" class="btn btn-default">Add New Mapping</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="button" class="btn btn-default"
                onclick="location=\'ObfIndexPage.php\';return false;">Home</button></p>';
        $ret .= '</form>';
        $ret .= '</div>';
        $ret .= '<div class="panel-footer">Note: percentages are sales growth targets for categories</div>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

