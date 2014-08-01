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
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PISearchPage extends PIKillerPage {

    protected $title = 'Search';

    function preprocess(){
        $this->__routes[] = 'get<id><first><last>';
        return parent::preprocess();
    }

    function get_id_handler(){
        $this->first = '';
        $this->last = '';
        return $this->get_id_first_last_handler();
    }

    function get_id_first_last_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (empty($this->id) && empty($this->last)) 
            return True; // invalid search  
            
        if (!empty($this->id)){
            $custdata = new CustdataModel($dbc);
            $custdata->CardNo($this->id);
            if (count($custdata->find()) > 0){
                header('Location: PIMemberPage.php?id='.$this->id);
                return False;
            }
            $cards = new MemberCardsModel($dbc);
            $cards->upc(str_pad($this->id,13,'0',STR_PAD_LEFT));
            foreach($cards->find() as $obj){
                header('Location: PIMemberPage.php?id='.$obj->card_no());
                return False;
            }
        }
        else {
            $q = $dbc->prepare_statement('SELECT CardNo, LastName, FirstName FROM
                custdata WHERE LastName LIKE ? AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo');
            $r = $dbc->exec_statement($q, array($this->last.'%',$this->first.'%'));
            $this->__models['custdata'] = array();
            while($w = $dbc->fetch_row($r)){
                $this->__models['custdata'][] = $w;
            }
            if (count($this->__models['custdata'])==1){
                header('Location: PIMemberPage.php?id='.$this->__models['custdata'][0]['CardNo']);
                return False;
            }
        }
        return True;
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

    function get_id_first_last_view(){
        if (!isset($this->__models['custdata']) || count($this->__models['custdata']) == 0){
            return '<tr><td colspan="9"><p>No results from search</p></td></tr>'
                .$this->get_view();
        }
        $ret = '<tr><td colspan="9"><p>There is more than one result</p>';
        $ret .= '<form action="PISearchPage.php" method="get">';
        $ret .= '<select name="id" id="memNum_s">';
        foreach($this->__models['custdata'] as $row){
            $ret .= sprintf('<option value="%d">%d %s %s</option>',
                $row['CardNo'],$row['CardNo'],
                $row['FirstName'],$row['LastName']);
        }
        $ret .= '</select> ';
        $ret .= '<input type="submit" value="submit" />';
        $ret .= '</form></td></tr>';
        $this->add_onload_command('$(\'#memNum_s\').focus();');
        return $ret;
    }
}

FannieDispatch::conditionalExec();
