<?php
/*******************************************************************************

    Copyright 2011,2013 Whole Foods Co-op

    This file is part of Fannie.

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
include(dirname(__FILE__). '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchTypeEditor extends FanniePage {

    private $price_methods = array(
        0 => "None (Change regular price)",
        1 => "Sale for Everyone",
        2 => "Sale for Members"
    );

    protected $title = 'Fannie - Batch Module';
    protected $header = 'Sales Batches';

    public $description = '[Batch Type Editor] manages different kinds of batches.';

    function preprocess(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (FormLib::get_form_value('saveDesc') !== ''){
            $q = $dbc->prepare_statement("UPDATE batchType
                SET typeDesc=? WHERE batchTypeID=?");
            $r = $dbc->exec_statement($q,array(
                FormLib::get_form_value('saveDesc'),
                FormLib::get_form_value('bid')
            ));
            echo "Desc saved";
            return False; // ajax call
        }
        if (FormLib::get_form_value('saveType') !== ''){
            $q = $dbc->prepare_statement("UPDATE batchType
                SET discType=? WHERE batchTypeID=?");
            $r = $dbc->exec_statement($q,array(
                FormLib::get_form_value('saveType'),
                FormLib::get_form_value('bid')
            ));
            echo "Desc saved";
            return False; // ajax call
        }
        if (FormLib::get_form_value('addtype') !== ''){
            $p = $dbc->prepare_statement("SELECT MAX(batchTypeID) FROM batchType");
            $r = $dbc->exec_statement($p);
            $id = array_pop($dbc->fetch_row($r));
            $id = (empty($id)) ? 1 : $id + 1;

            $ins = $dbc->prepare_statement("INSERT INTO batchType (batchTypeID,typeDesc,discType)
                VALUES (?,'New Type',1)");
            $dbc->exec_statement($ins,array($id));
        }
        else if (FormLib::get_form_value('deltype') !== ''){
            $q = $dbc->prepare_statement("DELETE FROM batchType WHERE batchTypeID=?");
            $dbc->exec_statement($q,array(FormLib::get_form_value('bid')));
        }

        return True;
    }

    function javascript_content(){
        ob_start();
        ?>
function saveDesc(val,bid){
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveDesc='+val+'&bid='+bid,
        success: function(data){
        }
    });
}
function saveType(val,bid){
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveType='+val+'&bid='+bid,
        success: function(data){
        }
    });
}
        <?php
        return ob_get_clean();
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare_statement("SELECT batchTypeID,typeDesc,discType FROM batchType ORDER BY batchTypeID");
        $r = $dbc->exec_statement($q);

        $ret = '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>ID#</th><th>Description</th><th>Discount Type</th><th>&nbsp;</td></tr>';
        while($w = $dbc->fetch_row($r)){
            $ret .= sprintf('<tr><td>%d</td>
                <td><input type="text" onchange="saveDesc(this.value,%d)" value="%s" /></td>
                <td><select onchange="saveType($(this).val(),%d);">',
                $w['batchTypeID'],$w['batchTypeID'],$w['typeDesc'],$w['batchTypeID']);
        $found = False;
        foreach($this->price_methods as $id=>$desc){
            if ($id == $w['discType']){
                $found = True;
                $ret .= sprintf('<option value="%d" selected>%d %s</option>',$id,$id,$desc);
            }
            else
                $ret .= sprintf('<option value="%d">%d %s</option>',$id,$id,$desc);
        }
        if (!$found)
            $ret .= sprintf('<option value="%d" selected>%d (Custom)</option>',$w['discType'],$w['discType']);
        $ret .= '</select></td>';
        $ret .= sprintf('<td><a href="BatchTypeEditor.php?deltype=yes&bid=%d"
                onclick="return confirm(\'Are you sure?\');">Delete</a>
            </td></tr>',$w['batchTypeID']);
        }
        $ret .= '</table>';

        $ret .= '<br /><a href="BatchTypeEditor.php?addtype=yes">Create New Type</a>';

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
