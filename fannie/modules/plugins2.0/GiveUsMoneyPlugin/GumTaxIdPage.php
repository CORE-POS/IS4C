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
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');
    
    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Tax Identifier] saves sensitive tax IDs with encryption.';

    public function preprocess()
    {
        $acct = FormLib::get('id');
        $this->header = 'Tax Identification' . ' : ' . $acct;
        $this->title = 'Tax Identification' . ' : ' . $acct;
        $this->__routes[] = 'post<id><new1><new2>';
        $this->__routes[] = 'post<id><key>';

        return parent::preprocess();
    }

    public function post_id_new1_new2_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $ret = array('errors' => '');
        $safe = $this->safetyCheck();
        if ($safe !== true) {
            $ret['errors'] = $safe;
        } else {
            $keyfile = realpath(dirname(__FILE__).'/keys/public.key');
            $pubkey = openssl_pkey_get_public(file_get_contents($keyfile));
            $try = openssl_public_encrypt($this->new1, $encrypted, $pubkey);
            if (!$try) {
                $ret['errors'] = 'Error occurred during encryption';
            } else if ($this->new1 !== $this->new2) {
                $ret['errors'] = 'New values do not match';
            } else {
                $model = new GumTaxIdentifiersModel($dbc);
                $model->card_no($this->id); 
                $model->encryptedTaxIdentifier($encrypted);
                $model->maskedTaxIdentifier(substr($this->new1, -4));
                $model->save();
            }
        }

        echo json_encode($ret);

        return false;
    }

    public function post_id_key_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $privkey = openssl_pkey_get_private($this->key);
        if (!$privkey) {
            echo 'Invalid Key!';
        } else {
            $model = new GumTaxIdentifiersModel($dbc);
            $model->card_no($this->id); 
            $model->load(); 
            $try = openssl_private_decrypt($model->encryptedTaxIdentifier(), $decrypted, $privkey);
            if (!$try) {
                echo 'Error during decryption';
            } else {
                echo $decrypted;
            }
        }

        return false;
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $this->ssn_model = new GumTaxIdentifiersModel($dbc);
        $this->ssn_model->card_no($this->id);

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->id);

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

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
        $ret .= '<th>Name</th><td>' . $this->custdata->FirstName() . ' ' . $this->custdata->LastName() . '</td>';
        $ret .= '</tr><tr>';
        $ret .= '<th>Current</th>';
        $ssn = 'No Value';
        if ($this->ssn_model->load()) {
            $ssn = 'Ends In ' . $this->ssn_model->maskedTaxIdentifier();
        }
        $ret .= '<td id="tax_id_field">' . $ssn . '</td>';
        $ret .= '</tr>';
        $ret .= '</table>';

        $ret .= '<hr />';

        $ret .= '<div><div style="float: left;">';

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

        $ret .= '</div><div style="float:left;margin-left:50px;">';

        $ret .= '<table>';
        $ret .= '<tr><th>Enter Key to View Current Value</th></tr>';
        $ret .= '<tr><td><textarea id="keyarea" rows="10" cols="30"></textarea></td></tr>';
        $ret .= '<tr><td><input type="button" onclick="viewInfo(); return false;" value="View" /></td></tr>';
        $ret .= '</table>';

        $ret .= '</div></div>';
        $ret .= '<div style="clear:left;"></div>';

        $this->add_script('js/tax.js');

        return $ret;
    }

    private function safetyCheck()
    {
        $keys_dir = dirname(__FILE__).'/keys';
        if (!file_exists($keys_dir.'/public.key')) {
            return 'Key is missing (' . $keys_dir . '/public.key)';
        }

        $dh = opendir($keys_dir);
        while( ($file = readdir($dh)) !== false) {
            if ($file === '.') continue;
            if ($file === '..') continue;
            if ($file === '.gitignore') continue;
            if ($file === 'public.key') continue;
            return 'Unknown file in keys directory: ' . $file;
        }

        if (!function_exists('openssl_pkey_get_public')) {
            return 'OpenSSL extension not found';
        }

        return true;
    }
}

FannieDispatch::conditionalExec();

