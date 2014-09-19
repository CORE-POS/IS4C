<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemberTypeEditor extends FanniePage {

    protected $title = "Fannie :: Member Types";
    protected $header = "Member Types";
    public $description = '[Member Types] creates, updates, and deletes account types.';
    protected $must_authenticate = True;
    protected $auth_classes = array('editmembers');

    function preprocess(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $mtModel = new MemtypeModel($dbc);
        /* ajax callbacks to save changes */
        if (FormLib::get('saveMem', false) !== false) {
            $type = FormLib::get('saveMem', 'REG');
            $id = FormLib::get('t_id', 0);
            $mtModel->memtype($id);
            $mtModel->custdataType($type);
            $mtModel->save();
            if ($dbc->tableExists('memdefaults')) {
                $q = $dbc->prepare_statement("UPDATE memdefaults SET cd_type=?
                    WHERE memtype=?");
                $r = $dbc->exec_statement($q,array($type, $id));
            }

            return false;
        } else if (FormLib::get('saveStaff', false) !== false){
            $staff = FormLib::get('saveStaff', 0);
            $id = FormLib::get('t_id', 0);
            $mtModel->memtype($id);
            $mtModel->staff($staff);
            $mtModel->save();
            if ($dbc->tableExists('memdefaults')) {
                $q = $dbc->prepare_statement("UPDATE memdefaults SET staff=?
                    WHERE memtype=?");
                $r = $dbc->exec_statement($q,array($staff, $id));
            }

            return false;
        } else if (FormLib::get('saveSSI', false) !== false) {
            $ssi = FormLib::get('saveSSI', 0);
            $id = FormLib::get('t_id', 0);
            $mtModel->memtype($id);
            $mtModel->ssi($ssi);
            $mtModel->save();
            if ($dbc->tableExists('memdefaults')) {
                $q = $dbc->prepare_statement("UPDATE memdefaults SET SSI=?
                    WHERE memtype=?");
                $r = $dbc->exec_statement($q,array($ssi, $id));
            }

            return false;
        } else if (FormLib::get('saveDisc', false) !== false) {
            $disc = FormLib::get('saveDisc', 0);
            $id = FormLib::get('t_id', 0);
            $mtModel->memtype($id);
            $mtModel->discount($disc);
            $mtModel->save();
            if ($dbc->tableExists('memdefaults')) {
                $q = $dbc->prepare_statement("UPDATE memdefaults SET discount=?
                    WHERE memtype=?");
                $r = $dbc->exec_statement($q,array($disc, $id));
            }

            return false;
        } else if (FormLib::get('saveType', false) !== false) {
            $name = FormLib::get('saveType', 0);
            $id = FormLib::get('t_id', 0);
            $mtModel->memtype($id);
            $mtModel->memDesc($name);
            $mtModel->save();

            return false;
        } else if (FormLib::get('newMemForm', false) !== false) {
            $q = $dbc->prepare_statement("SELECT MAX(memtype) FROM memtype");
            $r = $dbc->exec_statement($q);
            $sug = 0;
            if($dbc->num_rows($r)>0){
                $w = $dbc->fetch_row($r);
                if(!empty($w)) $sug = $w[0]+1;
            }
            echo "Give the new memtype an ID number. The one
                provided is only a suggestion. ID numbers
                must be unique.";
            printf('<br /><br /><b>New ID</b>: <input size="4" value="%d"
                id="newTypeID" />',$sug);
            echo ' <input type="submit" value="Create New Type"
                onclick="finishMemType();return false;" />';
            echo ' <input type="submit" value="Cancel"
                onclick="cancelMemType();return false;" />';
            return False;
        } else if (FormLib::get('new_t_id', false) !== false) {
            /* do some extra sanity checks
               on a new member type
            */
            $id = FormLib::get('new_t_id');
            if (!is_numeric($id)){
                echo 'ID '.$id.' is not a number';
                echo '<br /><br />';
                echo '<a href="" onclick="newMemType();return false;">Try Again</a>';
            } else {
                $mtModel->reset();
                $mtModel->memtype($id);
                if ($mtModel->load()) {
                    echo 'ID is already in use';
                    echo '<br /><br />';
                    echo '<a href="" onclick="newMemType();return false;">Try Again</a>';
                } else {
                    $mtModel->memDesc('');
                    $mtModel->custdataType('REG');
                    $mtModel->discount(0);
                    $mtModel->staff(0);
                    $mtModel->ssi(0);
                    $mtModel->save();
                    if ($dbc->tableExists('memdefaults')) {
                        $mdP = $dbc->prepare_statement("INSERT INTO memdefaults (memtype,cd_type,
                                discount,staff,SSI) VALUES (?, 'REG', 0, 0, 0)");
                        $dbc->exec_statement($mdP, array($id));
                    }

                    echo $this->getTypeTable();
                }
            }
            exit;

        } else if (FormLib::get('goHome', false) !== false) {
            echo $this->getTypeTable();
            exit;
        }
        /* end ajax callbacks */

        return true;
    }

    private function getTypeTable(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = '<table cellspacing="0" cellpadding="4" border="1">
            <tr><th>ID#</th><th>Description</th>
            <th>Member</th><th>Discount</th>
            <th>Staff</th><th>SSI</th>
            </tr>';

        $q = $dbc->prepare_statement("SELECT m.memtype,m.memDesc,d.cd_type,d.discount,d.staff,d.SSI
            FROM memtype AS m LEFT JOIN memdefaults AS d
            ON m.memtype=d.memtype
            ORDER BY m.memtype");
        $r = $dbc->exec_statement($q);
        while($w = $dbc->fetch_row($r)){
            $ret .= sprintf('<tr><td>%d</td>
                    <td><input value="%s" onchange="saveType(this.value,%d);" /></td>
                    <td><input type="checkbox" %s onclick="saveMem(this.checked,%d);" /></td>
                    <td><input value="%d" size="4" onchange="saveDisc(this.value,%d);" /></td>
                    <td><input type="checkbox" %s onclick="saveStaff(this.checked,%d);" /></td>
                    <td><input type="checkbox" %s onclick="saveSSI(this.checked,%d);" /></td>
                    </tr>',$w['memtype'],
                    $w['memDesc'],$w['memtype'],
                    ($w['cd_type']=='PC'?'checked':''),$w['memtype'],
                    $w['discount'],$w['memtype'],
                    ($w['staff']=='1'?'checked':''),$w['memtype'],
                    ($w['SSI']=='1'?'checked':''),$w['memtype']
                );
        }
        $ret .= "</table>";
        $ret .= '<br /><a href="" onclick="newMemType();return false;">New Member Type</a>';
        return $ret;
    }

    function javascript_content(){
        ob_start();
        ?>
        function newMemType(){
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 'newMemForm=yes',
                success: function(data){
                    $('#mainDisplay').html(data);
                }
            });
        }

        function finishMemType(){
            var t_id = $('#newTypeID').val();
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 'new_t_id='+t_id,
                success: function(data){
                    $('#mainDisplay').html(data);
                }
            });
        }

        function cancelMemType(){
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 'goHome=yes',
                success: function(data){
                    $('#mainDisplay').html(data);
                }
            });
        }

        function saveMem(st,t_id){
            var cd_type = 'REG';
            if (st == true) cd_type='PC';
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 't_id='+t_id+'&saveMem='+cd_type,
                success: function(data){

                }
            });
        }

        function saveStaff(st,t_id){
            var staff = 0;
            if (st == true) staff=1;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 't_id='+t_id+'&saveStaff='+staff,
                success: function(data){

                }
            });
        }

        function saveSSI(st,t_id){
            var ssi = 0;
            if (st == true) ssi=1;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 't_id='+t_id+'&saveSSI='+ssi,
                success: function(data){

                }
            });
        }

        function saveDisc(disc,t_id){
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 't_id='+t_id+'&saveDisc='+disc,
                success: function(data){

                }
            });
        }

        function saveType(typedesc,t_id){
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 't_id='+t_id+'&saveType='+typedesc,
                success: function(data){

                }
            });
        }
        <?php
        return ob_get_clean();
    }

    function body_content(){
        return '<div id="mainDisplay">'
            .$this->getTypeTable()
            .'</div>';
    }
}

FannieDispatch::conditionalExec(false);

?>
