<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
class GumSettingsPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Settings] manages customizable features of the plugin.';

    public function preprocess()
    {
        $this->header = 'Plugin Settings';
        $this->title = 'Plugin Settings';
        $this->__routes[] = 'post<keys><values>';

        return parent::preprocess();
    }

    public function post_keys_values_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumSettingsModel($dbc);
        for($i=0; $i<count($this->keys); $i++) {
            if (!isset($this->values[$i])) continue;

            $model->key($this->keys[$i]);
            $model->value($this->values[$i]);
            $model->save();
        }

        header('Location: GumSettingsPage.php');

        return false;
    }

    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumSettingsModel($dbc);
        $ret = '<form action="GumSettingsPage.php" method="post">';
        $ret .= '<input type="submit" value="Save Settings" />';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Setting</th><th>Current Value</th></tr>';
        $sum = 0.0;
        foreach($model->find('key') as $obj) {
            $ret .= sprintf('<tr>
                            <td>%s<input type="hidden" name="keys[]" value="%s" /></td>
                            <td><input type="text" name="values[]" value="%s" /></td>
                            </tr>',
                            $obj->key(),
                            $obj->key(),
                            $obj->value()
            );
        }
        $ret .= '</table>';
        $ret .= '<input type="submit" value="Save Settings" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

