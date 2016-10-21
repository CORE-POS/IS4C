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

    protected $title = 'Fannie - Product Flag Maintenance';
    protected $header = 'Product Flag Maintenance';
    public $description = '[Item Flags] are extra fields that can be associated with an item.';

    private function addCallback($dbc, $desc)
    {
        $bit=1;
        $bit_number=1;
        $chkP = $dbc->prepare("SELECT bit_number FROM prodFlags WHERE bit_number=?");
        for ($i=0; $i<30; $i++) {
            $chkR = $dbc->execute($chkP,array($bit_number));
            if ($dbc->num_rows($chkR) == 0) break;
            $bit *= 2;
            $bit_number++;
        }
        if ($bit > (1<<30)) {
            $this->msgs[] = 'Error: can\'t add more flags';
        } else {
            $insP = $dbc->prepare("INSERT INTO prodFlags 
                        (bit_number, description) VALUES (?,?)");
            $dbc->execute($insP,array($bit_number,$desc));    
        }
    }

    private function updateCallback($dbc, $ids, $descs, $active)
    {
        $upP = $dbc->prepare("
            UPDATE prodFlags 
            SET description=?,
                active=?
            WHERE bit_number=?");
        for ($i=0;$i<count($ids);$i++) {
            if (isset($descs[$i]) && !empty($descs[$i])) {
                $a = in_array($ids[$i], $active) ? 1 : 0;
                $dbc->execute($upP,array($descs[$i],$a,$ids[$i]));   
            }
        }
    }

    private function delCallback($dbc, $ids)
    {
        $delP = $dbc->prepare("DELETE FROM prodFlags WHERE bit_number=?");
        foreach ($ids as $id) {
            $dbc->execute($delP,array($id));
        }
    }

    function preprocess()
    {
        $this->msgs = array();
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        if (FormLib::get('addBtn') !== ''){
            $desc = FormLib::get('new');         
            if (empty($desc)) {
                $this->msgs[] = 'Error: no new description given';
            } else {
                $this->addCallback($dbc, $desc);
            }
        } elseif (FormLib::get('updateBtn') !== '') {
            $ids = FormLib::get('mask',array());
            $descs = FormLib::get('desc',array());
            $active = FormLib::get('active', array());
            $this->updateCallback($dbc, $ids, $descs, $active);
        } elseif (FormLib::get('delBtn') !== ''){
            $ids = FormLib::get('del',array());
            $this->delCallback($dbc, $ids);
        }

        return true;
    }

    function body_content()
    {
        global $FANNIE_COOP_ID;
        ob_start();
        // If there were errors in preprocess().
        if (count($this->msgs) > 0) {
            echo '<ul>';
            foreach($this->msgs as $m) echo '<li>'.$m.'</li>';
            echo '</ul>';
        }
        $self = filter_input(INPUT_SERVER, 'PHP_SELF');
        echo '<form action="'.$self.'" method="post">';
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' ) {
            $prep = $dbc->prepare("SELECT bit_number,description,active FROM prodFlags ORDER BY bit_number");
            $excelCols = array('','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        } else {
            $prep = $dbc->prepare("SELECT bit_number,description,active FROM prodFlags ORDER BY description");
        }
        $res = $dbc->execute($prep);
        echo '<div class="row">
            <div class="col-sm-6">';
        echo '<table class="table form-horizontal">';
        echo '<tr>
            <th>Current Flags</th>
            <th>Enabled</th>
            <th><span class="glyphicon glyphicon-trash"></span></th>
            </tr>';
        while ($w = $dbc->fetchRow($res)) {
            if (isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' ) {
                printf('<tr><td>%d. %s <input type="text" name="desc[]" 
                    class="form-control" value="%s" />
                    <input type="hidden" name="mask[]" value="%d" /></td>
                    <td><input type="checkbox" name="active[]" value="%d"
                        %s /></td>
                    <td><input type="checkbox" name="del[]" value="%d" /></td>
                    </tr>',
                    $w['bit_number'],
                    ($w['bit_number'] <= count($excelCols))?$excelCols[$w['bit_number']]:'',
                    $w['description'],
                    $w['bit_number'],
                    $w['bit_number'],
                    ($w['active'] ? 'checked' : ''),
                    $w['bit_number']
                );
            } else {
                printf('<tr><td><input type="text" name="desc[]" value="%s" 
                    class="form-control" />
                    <input type="hidden" name="mask[]" value="%d" /></td>
                    <td><input type="checkbox" name="active[]" value="%d"
                        %s /></td>
                    <td><input type="checkbox" name="del[]" value="%d" /></td>
                    </tr>',
                    $w['description'],$w['bit_number'],
                    $w['bit_number'],
                    ($w['active'] ? 'checked' : ''),
                    $w['bit_number']
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
        echo '<form action="'.$self.'" method="post"
                class="form-inline">';
        echo '<label>New</label>: <input type="text" name="new" class="form-control" /> ';
        echo '<button type="submit" name="addBtn" value="1"
                class="btn btn-default">Add New Flag</button>';
        echo '</div>'; // end panel-body
        echo '</div>'; // end col-sm-4
        echo '</div>'; // end row
        echo '</form>';
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Product Flags are custom attributes that can
            be attached to products. Each flag is a yes/no setting.
            Most systems should support at least 30 flags. Flags
            can add new settings that are not built-in such as
            gluten-free or organic.</p>';
    }

    public function unitTest($phpunit)
    {
        $this->msgs = array('foo');
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $dbc = $this->connection;
        $dbc->SelectDB($this->config->get('OP_DB'));
        $this->addCallback($dbc, 'test');
        $this->updateCallback($dbc, array(1), array('update'), array(1));
        $this->delCallback($dbc, array(1));
    }

}

FannieDispatch::conditionalExec();

