<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Community Co-op

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

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

if (isset($_REQUEST['ajax'])){
    // required fields
    $req = array('store_id','section','subsection','shelfset','shelf','location');
    foreach($req as $key){
        if (!isset($_REQUEST[$key]) || !is_numeric($_REQUEST[$key])){
            echo json_encode(array('errors'=>'invalid request '));
            exit;
        }
    }
    $store = $_REQUEST['store_id'];
    $section = $_REQUEST['section'];
    $subsection = $_REQUEST['subsection'];
    $shelfset = $_REQUEST['shelfset'];
    $shelf = $_REQUEST['shelf'];
    $location = $_REQUEST['location'];
    switch($_REQUEST['ajax']){
    case 'get':
        $output = lookupItem($store,$section,$subsection,$shelfset,$shelf,$location);
        echo json_encode($output);
        break;
    case 'set':
        if (!isset($_REQUEST['upc']) || !is_numeric($_REQUEST['upc'])){
            echo json_encode(array('errors'=>'invalid request'));
            exit;
        }
        saveItem($store,$section,$subsection,$shelfset,$shelf,$location,$_REQUEST['upc']);
        $output = lookupItem($store,$section,$subsection,$shelfset,$shelf,$location+1);
        echo json_encode($output);
        break;
    case 'default':
        echo json_encode(array('errors'=>'invalid request'));
        break;
    }
    exit;
}

function lookupItem($store,$sec,$subsec,$sh_set,$shelf,$loc){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $q = $dbc->prepare_statement("SELECT l.upc,p.description FROM prodPhysicalLocation AS l
        LEFT JOIN products AS p ON l.upc=p.upc
        WHERE l.store_id=? AND section=? AND subsection=?
        AND shelf_set=? AND shelf=? AND location=?");
    $args = array($store,$sec,$subsec,$sh_set,$shelf,$loc);
    $r = $dbc->exec_statement($q,$args);
    $ret = array('upc'=>'','description'=>'no item at this location');
    if ($dbc->num_rows($r) > 0){
        $w = $dbc->fetch_row($r);
        $ret['upc'] = $w['upc'];
        $ret['description'] = $w['description'];
    }
    return $ret;
}

function saveItem($store,$sec,$subsec,$sh_set,$shelf,$loc,$upc){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $upc = BarcodeLib::padUPC($upc);
    $q = sprintf("DELETE FROM prodPhysicalLocation WHERE
        store_id=? AND section=? AND subsection=?
        AND shelf_set=? AND shelf=? AND location=?");
    $args = array($store,$sec,$subsec,$sh_set,$shelf,$loc);
    $r = $dbc->exec_statement($q,$args);
    $q = $dbc->prepare_statement("INSERT INTO prodPhysicalLocation (upc,
        store_id,section,subsection,shelf_set,shelf,
        location) VALUES (?,?,?,?,?,?,?)");
    $args = array($upc,$store,$sec,$subsec,$sh_set,
        $shelf,$loc);
    $r = $dbc->exec_statement($q,$args);
}


$dbc = FannieDB::get($FANNIE_OP_DB);
$sectionsQ = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
    WHERE superID > 0 ORDER BY superID");
$sectionsR = $dbc->exec_statement($sectionsQ);
$supers = array();
while($sectionsW = $dbc->fetch_row($sectionsR))
    $supers[$sectionsW[0]] = $sectionsW[1];
?>
<html><head><title>Shelf Mapping</title>
<script type="text/javascript" 
    src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js">
</script>
<script type="text/javascript">
function doLookup(){
    $.ajax({
    'url': 'index.php',
    'data': $('#mappingform').serialize()+'&ajax=get',
    'cache': false,
    'dataType': 'json',
    'type': 'get',
    'success': function(data){
        if (data.errors)
            $('#productInfo').html(data.errors);
        else {
            $('#productInfo').html(data.upc+" "+data.description);
        }
    }
    });
}
function doSave(){
    $.ajax({
    'url': 'index.php',
    'data': $('#mappingform').serialize()+'&ajax=set',
    'cache': false,
    'dataType': 'json',
    'type': 'get',
    'success': function(data){
        if (data.errors)
            $('#productInfo').html(data.errors);
        else {
            $('#productInfo').html("Saved. Next: "+data.upc+" "+data.description);
            $('#location').val(Number($('#location').val())+(Number(1)));
        }
    }
    });
    $('#upc').val('');
    $('#upc').focus();
}
function changeSection(){
    $('#subsection').val(1);
    $('#shelfset').val(1);
    $('#shelf').val(1);
    $('#location').val(1);
    doLookup();
    $('#upc').focus();
}
function changeSubSection(amt){
    $('#subsection').val(Number($('#subsection').val())+(Number(amt)));
    $('#shelfset').val(1);
    $('#shelf').val(1);
    $('#location').val(1);
    doLookup();
    $('#upc').focus();
}
function changeShelfSet(amt){
    $('#shelfset').val(Number($('#shelfset').val())+(Number(amt)));
    $('#shelf').val(1);
    $('#location').val(1);
    doLookup();
    $('#upc').focus();
}
function changeShelf(amt){
    $('#shelf').val(Number($('#shelf').val())+(Number(amt)));
    $('#location').val(1);
    doLookup();
    $('#upc').focus();
}
function changeLocation(amt){
    $('#location').val(Number($('#location').val())+(Number(amt)));
    doLookup();
    $('#upc').focus();
}
$(document).ready(function(){
    $('#upc').focus();
    doLookup();
});
</script>
<style>
body {
    margin: 10px;
}
em {
    color: #330066;
    font-style: normal;
}
a {
    color: blue;
}
input[type=submit] {
    width: 100%;
    font-size: 110%;
}
</style>
</head>

<body>
<div id="productInfo" style="border: solid 1px gray; padding:5px; width: 400px;">

</div>
<form id="mappingform" onsubmit="doSave();return false;">
<table cellspacing="4" cellpadding="4">
<input type="hidden" name="store_id" value="1" />

<tr>
<td align="center" colspan="4">
<input type="submit" value="Update Location"  />
</td>
</tr>

<tr><td colspan="2">
<select id="section" onchange="changeSection();" name="section">
<?php foreach($supers as $id=>$name)
    printf('<option value="%d">%s</option>',$id,$name);
?>
</select>
</td></tr>

<tr>
<th>Subsection:</th>
<td><input type="text" size="3" value="1" id="subsection" name="subsection" /></td>
<td><input type="submit" value="+" onclick="changeSubSection(1);return false;" /></td>
<td><input type="submit" value="-" onclick="changeSubSection(-1);return false;" /></td>
</tr>

<tr>
<th>Shelf Set:</th>
<td><input type="text" size="3" value="1" id="shelfset" name="shelfset" /></td>
<td><input type="submit" value="+" onclick="changeShelfSet(1);return false;" /></td>
<td><input type="submit" value="-" onclick="changeShelfSet(-1);return false;" /></td>
</tr>

<tr>
<th>Shelf:</th>
<td><input type="text" size="3" value="1" id="shelf" name="shelf" /></td>
<td><input type="submit" value="+" onclick="changeShelf(1);return false;" /></td>
<td><input type="submit" value="-" onclick="changeShelf(-1);return false;" /></td>
</tr>

<tr>
<th>Location:</th>
<td><input type="text" size="3" value="1" id="location" name="location" /></td>
<td><input type="submit" value="+" onclick="changeLocation(1);return false;" /></td>
<td><input type="submit" value="-" onclick="changeLocation(-1);return false;" /></td>
</tr>

<tr>
<th>UPC</th>
<td colspan="3"><input type="text" value="" id="upc" name="upc" /></td>
</tr>

</table>

</form>
</body>
</html>
