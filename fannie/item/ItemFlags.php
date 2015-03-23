<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ItemFlags extends FanniePage {
    
    private $msgs;

    public $description = '[Item Flags] are extra fields that can be associated with an item.';
    public $themed = true;

    function preprocess(){
        global $FANNIE_OP_DB;
        $this->title = 'Fannie - Product Flag Maintenance';
        $this->header = 'Product Flag Maintenance';
        $this->msgs = array();
        $db = FannieDB::get($FANNIE_OP_DB);

        if (FormLib::get_form_value('addBtn') !== ''){
            $desc = FormLib::get_form_value('new');         
            if (empty($desc)) $this->msgs[] = 'Error: no new description given';
            else {
                $bit=1;
                $bit_number=1;
                $chkP = $db->prepare_statement("SELECT bit_number FROM prodFlags WHERE bit_number=?");
                for($i=0; $i<30; $i++){
                    $chkR = $db->exec_statement($chkP,array($bit_number));
                    if ($db->num_rows($chkR) == 0) break;
                    $bit *= 2;
                    $bit_number++;
                }
                if ($bit > (1<<30)) $this->msgs[] = 'Error: can\'t add more flags';
                else {
                    $insP = $db->prepare_statement("INSERT INTO prodFlags 
                                (bit_number, description) VALUES (?,?)");
                    $db->exec_statement($insP,array($bit_number,$desc));    
                }
            }
        }
        elseif (FormLib::get_form_value('updateBtn') !== ''){
            $ids = FormLib::get_form_value('mask',array());
            $descs = FormLib::get_form_value('desc',array());
            $upP = $db->prepare_statement("UPDATE prodFlags SET description=? WHERE bit_number=?");
            for($i=0;$i<count($ids);$i++){
                if (isset($descs[$i]) && !empty($descs[$i])){
                    $db->exec_statement($upP,array($descs[$i],$ids[$i]));   
                }
            }
        }
        elseif (FormLib::get_form_value('delBtn') !== ''){
            $ids = FormLib::get_form_value('del',array());
            $delP = $db->prepare_statement("DELETE FROM prodFlags WHERE bit_number=?");
            foreach($ids as $id)
                $db->exec_statement($delP,array($id));
        }

        for($i=1; $i<=count($this->msgs); $i++) {
            $db->logger($this->msgs[($i-1)]);
        }

        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB;
        global $FANNIE_COOP_ID;
        // If there were errors in preprocess().
        if (count($this->msgs) > 0){
            echo '<ul>';
            foreach($this->msgs as $m) echo '<li>'.$m.'</li>';
            echo '</ul>';
        }
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
        $db = FannieDB::get($FANNIE_OP_DB);
        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID = 'WEFC_Toronto' ) {
            $q = $db->prepare_statement("SELECT bit_number,description FROM prodFlags ORDER BY bit_number");
            $excelCols = array('','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        } else {
            $q = $db->prepare_statement("SELECT bit_number,description FROM prodFlags ORDER BY description");
        }
        $r = $db->exec_statement($q);
        echo '<div class="row">
            <div class="col-sm-6">';
        echo '<b>Current Flags</b>:<br />';
        echo '<table class="table form-horizontal">';
        while($w = $db->fetch_row($r)){
            if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' ) {
                printf('<tr><td>%d. %s</td><td><input type="text" name="desc[]" 
                    class="form-control" value="%s" />
                    <input type="hidden" name="mask[]" value="%d" /></td>
                    <td><input type="checkbox" name="del[]" value="%d" /></td>
                    </tr>',
                    $w['bit_number'],($w['bit_number'] <= count($excelCols))?$excelCols[$w['bit_number']]:'',$w['description'],$w['bit_number'],$w['bit_number']
                );
            } else {
                printf('<tr><td><input type="text" name="desc[]" value="%s" 
                    class="form-control" />
                    <input type="hidden" name="mask[]" value="%d" /></td>
                    <td><input type="checkbox" name="del[]" value="%d" /></td>
                    </tr>',
                    $w['description'],$w['bit_number'],$w['bit_number']
                );
            }
        }
        echo '</table>';
        echo '<p>';
        echo '<button type="submit" name="updateBtn" value="1" 
                class="btn btn-default">Update Selected</button> | ';
        echo '<button type="submit" name="delBtn" value="1" 
                class="btn btn-default">Delete Selected</button> ';
        echo '</p>';
        echo '</form>';
        echo '</div>
            <div class="col-sm-4 panel panel-default">
            <div class="panel-body">';
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post"
                class="form-inline">';
        echo '<label>New</label>: <input type="text" name="new" class="form-control" /> ';
        echo '<button type="submit" name="addBtn" value="1"
                class="btn btn-default">Add New Flag</button>';
        echo '</div>'; // end panel-body
        echo '</div>'; // end col-sm-4
        echo '</div>'; // end row
        echo '</form>';
    }

    public function helpContent()
    {
        return '<p>Product Flags are custom attributes that can
            be attached to products. Each flag is a yes/no setting.
            Most systems should support at least 30 flags. Flags
            can add new settings that are not built-in such as
            gluten-free or organic.</p>';
    }

}

FannieDispatch::conditionalExec();

?>
