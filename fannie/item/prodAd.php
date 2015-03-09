<?php
/*******************************************************************************

    Copyright 2005,2009 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_REQUEST['upc']) && isset($_REQUEST['ajax'])){
    //save
    $upc = $_REQUEST['upc'];
    $desc = $_REQUEST['desc'];
    $size = $_REQUEST['size'];
    $brand = $_REQUEST['brand'];

    $q = $dbc->prepare_statement("SELECT upc FROM productUser WHERE upc=?");
    $r = $dbc->exec_statement($q, array($upc));
    if ($dbc->num_rows($r) == 0){
        $ins = $dbc->prepare_statement("INSERT INTO productUser 
                (upc, description, brand, sizing, photo, long_text, enableOnline)
                VALUES (?,?,?,?,NULL,NULL,0)");
        $dbc->exec_statement($ins, array($upc,$desc,$brand,$size));
    }
    else {
        $up = $dbc->prepare_statement("UPDATE productUser SET description=?,
            brand=?,sizing=?
            WHERE upc=?");
        $dbc->exec_statement($up, array($desc,$brand,$size,$upc));
    }

    echo "Saved";
    exit;
}

$page_title = 'Fannie - Advertising Info';
$header = 'Advertising Info';
include('../src/header.html');

if (isset($_REQUEST['upc']) && isset($_REQUEST['savebutton'])){
    //save
    $upc = $_REQUEST['upc'];
    $desc = $_REQUEST['desc'];
    $size = $_REQUEST['size'];
    $brand = $_REQUEST['brand'];

    $q = $dbc->prepare_statement("SELECT upc FROM productUser WHERE upc=?");
    $r = $dbc->exec_statement($q, array($upc));
    if ($dbc->num_rows($r) == 0){
        $ins = $dbc->prepare_statement("INSERT INTO productUser 
                (upc, description, brand, sizing, photo, long_text, enableOnline)
                VALUES (?,?,?,?,NULL,NULL,0)");
        $dbc->exec_statement($ins, array($upc,$desc,$brand,$size));
    }
    else {
        $up = $dbc->prepare_statement("UPDATE productUser SET description=?,
            brand=?,sizing=?
            WHERE upc=?");
        $dbc->exec_statement($up, array($desc,$brand,$size,$upc));
    }

    unset($_REQUEST['upc']);
}

if (isset($_REQUEST['upc'])){
    $q = $dbc->prepare_statement("SELECT description,brand,sizing,photo FROM productUser
        WHERE upc=?");
    $r = $dbc->exec_statement($q,array($_REQUEST['upc']));
    $desc = '';
    $brand = '';
    $size = '';
    $photo = '';
    if ($dbc->num_rows($r) > 0){
        $row = $dbc->fetch_row($r);
        $desc = $row[0];
        $brand = $row[1];
        $size = $row[2];
        $photo = $row[3];
    }
    echo "<form action=prodAd.php method=post>";
    echo "<b>Description</b>: <input size=50 id=fcme type=text name=desc value=\"$desc\" />";
    echo "<p />";
    echo "<b>Brand</b>: <input size=50 type=text name=brand value=\"$brand\" />";
    echo "<p />";
    echo "<b>Size</b>: <input size=50 type=text name=size value=\"$size\" />";
    echo "<p />";
    echo "<input type=submit name=savebutton value=Save />";    
    echo "<input type=hidden name=upc value=\"{$_REQUEST['upc']}\" />";
    if (isset($_REQUEST['sale']))
        echo "<input type=hidden name=sale value=yes />";
    if (isset($_REQUEST['trim']))
        echo "<input type=hidden name=trim value=yes />";
    echo "</form>";
    echo "<script type=\"text/javascript\">";
    echo "document.getElementById('fcme').focus();";
    echo "</script>";
    if (!empty($photo)){
        $img = 'images/done/'.$photo;
        $pts = explode('.',$photo);
        $thumb = 'images/done/';
        for($i=0; $i<count($pts); $i++){
            if ($i == count($pts)-1){
                $thumb .= 'thumb.'.$pts[$i];
            }
            else
                $thumb .= $pts[$i].'.';
        }
        printf('<hr /><a href="%s"><img src="%s" border="0" /></a>',$img,$thumb);
    }
}
else {

?>
<script src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js">
</script>
<script type="text/javascript">
function edit(upc){
    var desc = $('#desc'+upc).html().replace(/^\s/,"");
    var manu = $('#manu'+upc).html();
    var size = $('#size'+upc).html();
    
    $('#desc'+upc).html('<input id=d'+upc+' type=text size=10 value="'+desc+'" />');
    $('#manu'+upc).html('<input id=m'+upc+' type=text size=8 value="'+manu+'" />');
    $('#size'+upc).html('<input id=s'+upc+' type=text size=8 value="'+size+'" />');

    $('#link'+upc).html("<input type=submit id=sub"+upc+" value=Save onclick=\"save('"+upc+"');return false; />");

    $("#d"+upc).keyup(function(event){
        if(event.keyCode == 13)
            $("#sub"+upc).click();
    });
    $("#m"+upc).keyup(function(event){
        if(event.keyCode == 13)
            $("#sub"+upc).click();
    });
    $("#s"+upc).keyup(function(event){
        if(event.keyCode == 13)
            $("#sub"+upc).click();
    });


    $('#d'+upc).focus();
}

function save(upc){
    var desc = $('#d'+upc).val();
    var manu = $('#m'+upc).val();
    var size = $('#s'+upc).val();

    $('#desc'+upc).html(desc);
    $('#manu'+upc).html(manu);
    $('#size'+upc).html(size);

    var content = "<a href=\"\" onclick=\"edit('"+upc+"');return false;\">";
    content += upc.replace(/^0+/,"")+"</a> ";
    content += '<a href="itemMaint.php?upc='+upc+'>p</a>';

    $('#link'+upc).html(content);

    var vstr = "ajax=yes&upc="+upc+"&desc="+desc+"&size="+size+"&brand="+manu;

    $.ajax({
        type: "POST",
        url: "prodAd.php",
        data: vstr, 
        success: function(msg){
            // no response
        }
    });
}
</script>
<?php

    $q = "SELECT p.upc,u.description,p.description,
        u.brand,u.sizing,u.photo
        FROM productUser AS u LEFT JOIN
        products AS p ON u.upc=p.upc
        WHERE (u.upc is not null
        OR p.discounttype <> 0)
        ORDER BY p.upc";

    // sales, current or starting within the week
    if (isset($_REQUEST['sale'])){
        $q = "SELECT p.upc,u.description,p.description,
                u.brand,u.sizing,u.photo,p.start_date,p.end_date
                FROM products AS p LEFT JOIN
                productUser AS u ON u.upc=p.upc
                WHERE p.discounttype <> 0
                ORDER BY p.upc";
    }

    $p = $dbc->prepare_statement($q);
    $r = $dbc->exec_statement($p);

    if (isset($_REQUEST['trim'])){
        echo "<a href=prodAd.php>Show all items</a>";
    }
    else {
        echo "<a href=prodAd.php?trim=yes>Show unfinished items</a>";
    }
    if (!isset($_REQUEST['sale'])){
        echo "&nbsp;&nbsp;&nbsp;&nbsp;";
        echo "<a href=prodAd.php?sale=yes>Show current & upcoming sale items</a>";
    }
    echo "<table cellspacing=0 cellpadding=5 border=1>";
    echo "<tr><th>UPC</th><th>Ad</th><th>POS</th><th>Brand</th><th>Sizing</th><th>Img</th></tr>";
    while($w = $dbc->fetch_row($r)){
        $hilite = "";
        if (empty($w[1]) || empty($w[3]) || empty($w[4])
            || $w[1] == strtoupper($w[1])
            || $w[3] == strtoupper($w[3]))
            $hilite = "style=\"background:#ffffcc;\"";
        if (isset($_REQUEST['trim']) && empty($hilite)) $hilite = "style=\"display:none;\"";
        printf("<tr %s><td id=link%s><a href=\"\" onclick=\"edit('%s');return false;\">%s</a>
            <a href=\"itemMaint.php?upc=%s\">p</a></td>
            <td id=desc%s>%s</td>
            <td>%s</td>
            <td id=manu%s>%s</td>
            <td id=size%s>%s</td>
            <td>%s</td></tr>",
            $hilite,$w[0],$w[0],
            ltrim($w[0],'0'),$w[0],
            $w[0],(empty($w[1])?'&nbsp;':$w[1]),
            $w[2],
            $w[0],$w[3],
            $w[0],$w[4],
            (empty($w[5])?'&nbsp;':'X'));
    }
    echo "</table>";
}

include('../src/footer.html');
?>
