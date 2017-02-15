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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PISearchPage extends PIKillerPage {

    protected $title = 'Search';
    protected $results = array();

    function preprocess(){
        $this->__routes[] = 'get<id><first><last>';
        return parent::preprocess();
    }

    function get_id_handler(){
        $this->first = '';
        $this->last = '';
        return $this->get_id_first_last_handler();
    }

    public function get_id_first_last_handler()
    {
        if (empty($this->id) && empty($this->last) && empty($this->first)) {
            return true; // invalid search  
        }
            
        if (!empty($this->id)) {
            $account = \COREPOS\Fannie\API\member\MemberREST::get($this->id);
            if ($account != false) {
                header('Location: PIMemberPage.php?id='.$this->id);
                return false;
            }

            $json = array(
                'idCardUPC' => BarcodeLib::padUPC($this->id),
            );
            $accounts = \COREPOS\Fannie\API\member\MemberREST::search($json, 0, true);
            foreach ($accounts as $a) {
                header('Location: PIMemberPage.php?id='.$a['cardNo']);
                return false;
            }

            $json = array(
                'customers' => array(
                    array('phone' => $this->phoneMarkup($this->id)),
                ),
            );
            $accounts = \COREPOS\Fannie\API\member\MemberREST::search($json, 0, true);
            if (count($accounts) == 1) {
                header('Location: PIMemberPage.php?id='.$accounts[0]['cardNo']);
                return false;
            } else {
                $this->results = $accounts;
            }
        } else {
            $json = array(
                'customers' => array(
                    array(
                        'firstName' => $this->first,
                        'lastName' => $this->last,
                    ),
                ),
            );
            $accounts = \COREPOS\Fannie\API\member\MemberREST::search($json, 350, true);
            if (count($accounts) == 1) {
                header('Location: PIMemberPage.php?id='.$accounts[0]['cardNo']);
                return false;
            } else {
                $this->results = $accounts;
            }
        }

        return true;
    }

    private function phoneMarkup($number)
    {
        if (strlen($number) === 7) {
            return substr($number, 0, 3) . '-' . substr($number, 3, 4);
        } elseif (strlen($number) === 10) {
            return substr($number, 0, 3) . '-' . substr($number, 3, 3) . '-' . substr($number, 6, 4);
        } else {
            return $number;
        }
    }

    public function get_view()
    {
        global $FANNIE_URL;
        ob_start();
        ?>
        <tr>
        <form name="memNum" id="memNum" method="get" action="PISearchPage.php">
        <td width="1" align="right">&nbsp;</td>
        <td width="47" align="right" valign="middle" style="padding: 3px;">
        Owner # or UPC:
        </td>
        <td>
            <input name="id" type="text" id="memNum_t" size="5" maxlength="12" />
        </td>
        <td width="82" valign="middle" align="right">Last Name: </td>
        <td colspan="5">
            <input name="last" type="text" id="last" size="25" maxlength="50" />
        </td>
        <td width="75" valign="middle" align="right">First Name: </td>
        <td>
        <input name="first" type="text" id="first" size="20" maxlength="50" /></td>
        <td><input type="submit" name="submit" value="submit">
        </form></td>
        </tr>
        <?php
        $this->add_script($FANNIE_URL . 'item/autocomplete.js');
        $this->add_onload_command("bindAutoComplete('#last', '" . $FANNIE_URL . "ws/', 'mLastName');\n");
        $this->add_onload_command("bindAutoComplete('#first', '" . $FANNIE_URL . "ws/', 'mFirstName');\n");
        $this->add_onload_command('$(\'#memNum_t\').focus();');
        return ob_get_clean();
    }

    function get_id_first_last_view()
    {
        if (count($this->results) == 0) {
            return '<tr><td colspan="9"><p>No results from search</p></td></tr>'
                .$this->get_view();
        }
        $ret = '<tr><td colspan="9"><p>There is more than one result</p>';
        $ret .= '<form action="PISearchPage.php" method="get">';
        $ret .= '<select name="id" id="memNum_s">';
        $names = array();
        foreach ($this->results as $account) {
            foreach ($account['customers'] as $row) {
                if ($this->first && !stristr($row['firstName'], $this->first)) {
                    continue;
                }
                if ($this->last && !stristr($row['lastName'], $this->last)) {
                    continue;
                }
                $names[] = $row['firstName'] . ' ' . $row['lastName'] . '::' . $account['cardNo'];
                /*
                $ret .= sprintf('<option value="%d">%d %s %s</option>',
                    $account['cardNo'],$account['cardNo'],
                    $row['firstName'],$row['lastName']);
                */
            }
        }
        sort($names);
        foreach ($names as $name) {
            list($n, $id) = explode('::', $name, 2);
            $ret .= sprintf('<option value="%d">%s (%d)</option>',
                    $id, $n, $id); 
        }
        $ret .= '</select> ';
        $ret .= '<input type="submit" value="submit" />';
        $ret .= '</form></td></tr>';
        $this->add_onload_command('$(\'#memNum_s\').focus();');
        return $ret;
    }
}

FannieDispatch::conditionalExec();

