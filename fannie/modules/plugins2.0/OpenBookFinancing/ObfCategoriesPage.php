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
    protected $title = 'OBF: Categories';
    protected $header = 'OBF: Categories';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Categories] sets up labor category divisions.';

    public function post_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
        $model = new ObfCategoriesModel($dbc);

        $ids = FormLib::get('id', array());
        $sales = FormLib::get('hasSales', array());
        $labor = FormLib::get('labor', array());
        $hours = FormLib::get('hours', array());
        for ($i=0; $i<count($ids); $i++) {
            $model->reset();
            $model->obfCategoryID($ids[$i]);
            $model->hasSales( in_array($ids[$i], $sales) ? 1 : 0 );
            $model->laborTarget($labor[$i] / 100.00);
            $model->hoursTarget($hours[$i]);
            $model->save();
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $model = new ObfCategoriesModel($dbc);

        $ret = '<button onclick="location=\'ObfIndexPage.php\';return false;">Home</button>
                <br /><br />';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr>
                    <th>Name</th>
                    <th>Has Sales</th>
                    <th>Labor Goal</th>
                    <th>Allocated Hours</th>
                 </tr>';
        foreach($model->find() as $cat) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="id[]" value="%d" />
                            <td>%s</td>
                            <td><input type="checkbox" name="hasSales[]" value="%d" %s /></td>
                            <td><input type="text" size="5" name="labor[]" value="%.2f" />%%</td>
                            <td><input type="text" size="5" name="hours[]" value="%d" /></td>
                            </tr>',
                            $cat->obfCategoryID(),
                            $cat->name(),
                            $cat->obfCategoryID(), ($cat->hasSales() == 1 ? 'checked' : ''),
                            $cat->laborTarget()*100,
                            $cat->hoursTarget()
            );
        }
        $ret .= '</table>';
        $ret .= '<input type="submit" value="Save" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

