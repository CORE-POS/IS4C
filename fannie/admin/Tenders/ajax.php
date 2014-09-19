<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

$dbc = FannieDB::get($FANNIE_OP_DB);
$model = new TendersModel($dbc);
$id = FormLib::get_form_value('id',0);
$model->TenderID($id);

if (FormLib::get_form_value('saveCode',False) !== False){
    $code = FormLib::get_form_value('saveCode');

    $chkP = $dbc->prepare_statement("SELECT TenderID FROM tenders WHERE
        TenderCode=? AND TenderID<>?");
    $chk = $dbc->exec_statement($chkP,array($code,$id));
    if ($dbc->num_rows($chk) > 0)
        echo "Error: Code $code is already in use";
    else{
        $model->TenderCode($code);
        $model->save();
    }
}
elseif(FormLib::get_form_value('saveName',False) !== False){
    $name = FormLib::get_form_value('saveName');
    $model->TenderName($name);
    $model->save();
}
elseif(FormLib::get_form_value('saveType',False) !== False){
    $type = FormLib::get_form_value('saveType');
    $model->TenderType($type);
    $model->save();
}
elseif(FormLib::get_form_value('saveCMsg',False) !== False){
    $msg = FormLib::get_form_value('saveCMsg');
    $model->ChangeMessage($msg);
    $model->save();
}
elseif(FormLib::get_form_value('saveMin',False) !== False){
    $min = FormLib::get_form_value('saveMin');
    if (!is_numeric($min))
        echo "Error: Minimum must be a number";
    else {
        $model->MinAmount($min);
        $model->save();
    }
}
elseif(FormLib::get_form_value('saveMax',False) !== False){
    $max = FormLib::get_form_value('saveMax');
    if (!is_numeric($max))
        echo "Error: Maximum must be a number";
    else {
        $model->MaxAmount($max);
        $model->save();
    }
}
elseif(FormLib::get_form_value('saveRLimit',False) !== False){
    $limit = FormLib::get_form_value('saveRLimit');
    if (!is_numeric($limit))
        echo "Error: Refund limit must be a number";
    else {
        $model->MaxRefund($limit);
        $model->save();
    }
}
elseif(FormLib::get_form_value('newTender',False) !== False){
    $newID=1;
    $idQ = $dbc->prepare_statement("SELECT MAX(TenderID) FROM tenders");
    $idR = $dbc->exec_statement($idQ);
    if ($dbc->num_rows($idR) > 0){
        $idW = $dbc->fetch_row($idR);
        if (!empty($idW[0])) $newID = $idW[0] + 1;
    }

    $model->reset();
    $model->TenderID($newID);
    $model->TenderName('NEW TENDER');
    $model->TenderType('CA');
    $model->MinAmount(0);
    $model->MaxAmount(500);
    $model->MaxRefund(0);
    $model->save();
    
    echo getTenderTable();
}

function getTenderTable(){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $model = new TendersModel($dbc);
    
    $ret = '<table cellpadding="4" cellspacing="0" border="1">
        <tr><th>Code</th><th>Name</th><th>Change Type</th>
        <th>Change Msg</th><th>Min</th><th>Max</th>
        <th>Refund Limit</th></tr>';

    foreach($model->find('TenderID') as $row){
        $ret .= sprintf('<tr>
            <td><input size="2" maxlength="2" value="%s"
                onchange="saveCode(this.value,%d);" /></td>
            <td><input size="10" maxlength="255" value="%s"
                onchange="saveName(this.value,%d);" /></td>
            <td><input size="2" maxlength="2" value="%s"
                onchange="saveType(this.value,%d);" /></td>
            <td><input size="10" maxlength="255" value="%s"
                onchange="saveCMsg(this.value,%d);" /></td>
            <td><input size="6" maxlength="10" value="%.2f"
                onchange="saveMin(this.value,%d);" /></td>
            <td><input size="6" maxlength="10" value="%.2f"
                onchange="saveMax(this.value,%d);" /></td>
            <td><input size="6" maxlength="10" value="%.2f"
                onchange="saveRLimit(this.value,%d);" /></td>
            </tr>',
            $row->TenderCode(),$row->TenderID(),
            $row->TenderName(),$row->TenderID(),
            $row->TenderType(),$row->TenderID(),
            $row->ChangeMessage(),$row->TenderID(),
            $row->MinAmount(),$row->TenderID(),
            $row->MaxAmount(),$row->TenderID(),
            $row->MaxRefund(),$row->TenderID()
        );
    }
    $ret .= "</table>";
    $ret .= "<br /><br />";
    $ret .= '<a href="" onclick="addTender();return false;">Add a new tender</a>';
    $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    $ret .= '<a href="DeleteTenderPage.php">Delete a tender</a>';
    return $ret;
}

?>
