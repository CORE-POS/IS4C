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

        $ret = '<div>';
        $ret .= '<div style="float:left;">';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr>
                 </tr>';
        foreach($model->find() as $cat) {
            $ret .= '<tr><th colspan="2">' . $cat->name() . '</th>
                    <td><img alt="delete" src="' . $FANNIE_URL . 'src/img/buttons/trash.png" />
                    </td></tr>';
            $map->obfCategoryID($cat->obfCategoryID());
            foreach($map->find() as $obj) {
                $ret .= sprintf('<tr>
                                <input type="hidden" name="id[]" value="%d" />
                                <input type="hidden" name="superID[]" value="%d" />
                                <td>%s</td>
                                <td><input type="text" name="growth[]" size="6" value="%.3f" />%%</td>
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
        $ret .= '<input type="submit" value="Save Mapping" />';
        $ret .= '</form>';
        $ret .= '</div>';
        $ret .= '<div style="float:left; margin-left: 50px;">';
        $ret .= '<fieldset>';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= 'Add <select name="add">';
        foreach ($sdepts as $id => $name) {
            $ret .= sprintf('<option value="%d">%s</option>', $id, $name);
        }
        $ret .= '</select>';
        $ret .= ' to <select name="cat">';
        foreach ($model->find() as $cat) {
            $ret .= sprintf('<option value="%d">%s</option>', $cat->obfCategoryID(), $cat->name());
        }
        $ret .= '</select>';
        $ret .= '<br /><br />';
        $ret .= '<input type="submit" value="Add New Mapping" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button onclick="location=\'ObfIndexPage.php\';return false;">Home</button>';
        $ret .= '</form>';
        $ret .= '</fieldset>';
        $ret .= '<i>Note: percentages are sales growth targets for categories</i>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

