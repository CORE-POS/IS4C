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

class GumSearchPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Search Page] looks up member accounts.';

    function preprocess(){
        $this->__routes[] = 'get<id><first><last>';
        $this->header = 'Loans & Equity Search';
        $this->title = 'Loans & Equity Search';
        return parent::preprocess();
    }

    function get_id_handler(){
        $this->first = '';
        $this->last = '';
        return $this->get_id_first_last_handler();
    }

    function get_id_first_last_handler() {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (empty($this->id) && empty($this->last)) 
            return True; // invalid search  
            
        if (!empty($this->id)){
            $custdata = new CustdataModel($dbc);
            $custdata->CardNo($this->id);
            $custdata->Type('PC');
            if (count($custdata->find()) > 0){
                header('Location: GumMainPage.php?id='.$this->id);
                return False;
            }
            $cards = new MemberCardsModel($dbc);
            $cards->upc(str_pad($this->id,13,'0',STR_PAD_LEFT));
            foreach($cards->find() as $obj){
                header('Location: GumMainPage.php?id='.$obj->card_no());
                return False;
            }
        }
        else {
            $q = $dbc->prepare_statement('SELECT CardNo, LastName, FirstName FROM
                custdata WHERE LastName LIKE ? AND FirstName LIKE ?
                AND Type = \'PC\'
                ORDER BY LastName,FirstName,CardNo');
            $r = $dbc->exec_statement($q, array($this->last.'%',$this->first.'%'));
            $this->__models['custdata'] = array();
            while($w = $dbc->fetch_row($r)){
                $this->__models['custdata'][] = $w;
            }
            if (count($this->__models['custdata'])==1){
                header('Location: GumMainPage.php?id='.$this->__models['custdata'][0]['CardNo']);
                return False;
            }
        }

        return true;
    }

    function get_view(){
        ob_start();
        ?>
        <form name="memNum" id="memNum" method="get" action="GumSearchPage.php">
        <table>
        <tr>
        <td width="1" align="right">&nbsp;</td>
        <td width="47" align="right" valign="middle">Owner # or UPC:</td>
        <td>
            <input name="id" type="text" id="memNum_t" size="5" maxlength="12" />
        </td>
        <td width="82" align="right">Last Name</td>
        <td colspan="5">
            <input name="last" type="text" id="last" size="25" maxlength="50" />
        </td>
        <td width="75" align="right">First Name</td>
        <td>
            <input name="first" type="text" id="first" size="20" maxlength="50" />
        </td>
        <td>
            <input type="submit" name="submit" value="submit">
        </td>
        </tr>
        </table>
        </form>
        <hr />
        <a href="reports/GumReportIndex.php">Reporting</a>
        <?php
        $this->add_onload_command('$(\'#memNum_t\').focus();');
        return ob_get_clean();
    }

    function get_id_view()
    {
        return $this->get_id_first_last_view();
    }

    function get_id_first_last_view(){
        if (!isset($this->__models['custdata']) || count($this->__models['custdata']) == 0){
            return '<p>No results from search</p>' . $this->get_view();
        }
        $ret = '<table><tr><td colspan="9"><p>There is more than one result</p>';
        $ret .= '<form action="GumSearchPage.php" method="get">';
        $ret .= '<select name="id" id="memNum_s">';
        foreach($this->__models['custdata'] as $row){
            $ret .= sprintf('<option value="%d">%d %s %s</option>',
                $row['CardNo'],$row['CardNo'],
                $row['FirstName'],$row['LastName']);
        }
        $ret .= '</select> ';
        $ret .= '<input type="submit" value="submit" />';
        $ret .= '</form></td></tr></table>';
        $this->add_onload_command('$(\'#memNum_s\').focus();');
        return $ret;
    }
}

FannieDispatch::conditionalExec();
