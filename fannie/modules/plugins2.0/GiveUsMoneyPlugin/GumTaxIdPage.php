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
class GumTaxIdPage extends FannieRESTfulPage 
{
    public function preprocess()
    {
        $acct = FormLib::get('id');
        $this->header = 'Tax Identification' . ' : ' . $acct;
        $this->title = 'Tax Identification' . ' : ' . $acct;

        return parent::preprocess();
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $this->ssn_model = new GumTaxIdentifiersModel($dbc);
        $this->ssn_model->card_no($this->id);

        $this->custdata = new CustdataModel($dbc);
        $this->custdata->whichDB($FANNIE_OP_DB);
        $this->custdata->CardNo($this->id);
        $this->custdata->personNum(1);
        $this->custdata->load();

        $this->settings = new GumSettingsModel($dbc);

        return true;
    }

    public function css_content()
    {
        return '
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr>';
        $ret .= '<th>Mem#</th><td>' . $this->id . '</td>';
        $ret .= '</tr><tr>';
        $ret .= '<th>Name/th><td>' . $this->custdata->FirstName() . ' ' . $this->custdata->LastName() . '</td>';
        $ret .= '</tr><tr>';
        $ret .= '<th>Current</th>';
        $ssn = 'No Value';
        if ($this->ssn_model->load()) {
            $ssn = '*****' . $this->ssn_model->maskedTaxIdentifier();
        }
        $ret .= '<td>' . $ssn . '</td>';
        $ret .= '</tr>';
        $ret .= '</table>';

        $ret .= '<hr />';

        $ret .= '<form autocomplete="off">';
        $ret .= '<input type="hidden" id="hidden_id" value="' . $this->id . '" />';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr>';
        $ret .= '<th colspan="2" id="replaceInfoLine">Replace Current Value</th>';
        $ret .= '</tr><tr>';
        $ret .= '<th>New Value</th>';
        $ret .= '<td><input type="text" class="autodash" id="newVal1" autocomplete="off" /></td>';
        $ret .= '</tr><tr>';
        $ret .= '<th>Re-type New Value</th>';
        $ret .= '<td><input type="text" class="autodash" id="newVal2" autocomplete="off" /></td>';
        $ret .= '</tr>';
        $ret .= '<tr><td colspan="2"><input type="button" onclick="doReplace(); return false;" value="Replace" /></td></tr>';
        $ret .= '</table>';
        $ret .= '</form>';

        $this->add_script('js/tax.js');

        return $ret;
    }
}

FannieDispatch::conditionalExec();

