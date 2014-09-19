<?php
/*******************************************************************************

    Copyright 2011,2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TaxRateEditor extends FanniePage {
    protected $title = "Fannie : Tax Rates";
    protected $header = "Tax Rates";

    public $description = '[Tax Rates] defines applicable sales tax rates.';

    function preprocess(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (FormLib::get_form_value('sub',False) !== False){
            $desc = FormLib::get_form_value('desc',array());
            $rate = FormLib::get_form_value('rate',array());
            $id = 1;
            $trun = $dbc->prepare_statement("TRUNCATE TABLE taxrates");
            $dbc->exec_statement($trun);
            $p = $dbc->prepare_statement("INSERT INTO taxrates (id,rate,description)
                VALUES (?,?,?)");
            for ($j=0;$j<count($desc);$j++){
                if (empty($desc[$j]) || empty($rate[$j])) continue;
                if (FormLib::get_form_value('del'.$j) !== '') continue;

                $dbc->exec_statement($p, array($id,$rate[$j],$desc[$j]));
                $id++;
            }
        }

        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $taxQ = $dbc->prepare_statement("SELECT id,rate,description 
                FROM taxrates ORDER BY id");
        $taxR = $dbc->exec_statement($taxQ);

        $ret = '<form action="TaxRateEditor.php" method="post">';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Description</th><th>Rate</th><th>Delete</th></tr>';
        $ret .= '<tr><td>NoTax</th><td>0.00</td><td>&nbsp;</td></tr>';
        $i=0;
        while($taxW = $dbc->fetch_row($taxR)){
            $ret .= sprintf('<tr><td><input type="text" name="desc[]" value="%s" /></td>
                <td><input type="text" size="8" name="rate[]" value="%f" /></td>
                <td><input type="checkbox" name="del%d" /></td></tr>',
                $taxW['description'],$taxW['rate'],$i);
            $i++;
        }
        $ret .= '<tr><td><input type="text" name="desc[]" /></td>
            <td><input type="text" size="8" name="rate[]" /></td>
            <td>NEW</td></tr>';
        $ret .= "</table>";
        $ret .= '<br /><input type="submit" value="Save Tax Rates" name="sub" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

