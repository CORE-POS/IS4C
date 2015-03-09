<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
if (!isset($_REQUEST['excel'])){
?>
<style type=text/css>
.dept {
    display: table-row;
}
.class {
    display: none;
}
.green td {
    background: #009900;
    color: white;
}
.blue td {
    background: #000099;
    color: white;
}
th a {
    color:blue; 
}
</style>
<script type="text/javascript">
function re_sort(col_name){
    document.getElementById('sort').value=col_name;
    document.forms[0].submit();
    return false;
}
</script>
<?php
}
else {
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="localReport.xls"');
}

if (isset($_REQUEST['sort'])){
    $super = isset($_REQUEST['deptSub']) ? (int)$_REQUEST['deptSub'] : 0;
    $start = isset($_REQUEST['deptStart']) ? (int)$_REQUEST['deptStart'] : 0;
    $end = isset($_REQUEST['deptEnd']) ? (int)$_REQUEST['deptEnd'] : 0;
    $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'dept_name';
    if (isset($_REQUEST['local']) && is_array($_REQUEST['local'])){
        $p = $dbc->prepare_statement("UPDATE products SET local=?
            WHERE upc=?");
        for($i=0;$i<count($_REQUEST['local']);$i++){
            $r = $dbc->exec_statement($p,array(
                $_REQUEST['local'][$i],
                $_REQUEST['upc'][$i]
            ));
        }
    }

    $q = "SELECT p.upc,x.manufacturer,p.description,p.local,
        p.department,d.dept_name FROM
        products AS p LEFT JOIN departments AS d
        ON p.department=d.dept_no LEFT JOIN
        MasterSuperDepts AS m ON p.department=m.dept_ID
        LEFT JOIN prodExtra AS x ON p.upc=x.upc
        WHERE ";
    $args = array();
    if ($super != 0){
        $q .= "m.superID=?";
        $q = str_replace("MasterSuperDepts","superdepts",$q);
        $args = array($super);
    }
    else{
        $q .= "p.department BETWEEN ? AND ?";
        $args = array($start,$end);
    }
    switch($sort){
    case 'upc':
    default:
        $q .= ' ORDER BY p.upc';
        break;
    case 'manu':
        $q .= ' ORDER BY manufacturer,p.upc';
        break;
    case 'desc':
        $q .= ' ORDER BY description,p.upc';
        break;
    case 'dept':
        $q .= ' ORDER BY dept_name,p.upc';
        break;
    }

    if (!isset($_REQUEST['excel']))
        echo '<form action="localItems.php" id="formlocal" name="formlocal" method="post">';
    echo '<table cellpadding="4" cellspacing="0" border="1">';
    if (!isset($_REQUEST['excel'])){
        echo '<tr>';
        echo '<th><a href="" onclick="return re_sort(\'upc\');">UPC</a></th>';
        echo '<th><a href="" onclick="return re_sort(\'manu\');">Brand</a></th>';
        echo '<th><a href="" onclick="return re_sort(\'desc\');">Desc</a></th>';
        echo '<th colspan="2"><a href="" onclick="return re_sort(\'dept\');">Dept</a></th>';
        echo '<th>Local</th>';
        echo '</tr>';
    }
    else
        echo '<tr><th>UPC</th><th>Brand</th><th>Desc</th><th colspan="2">Dept</th><th>Local</th></tr>';
    $p = $dbc->prepare_statement($q);
    $r = $dbc->exec_statement($p, $args);
    while($w = $dbc->fetch_row($r)){
        $class = "";
        if ($w['local'] > 0){
            if ($w['local'] == 1) $class = ' class="green" ';
            elseif ($w['local'] == 2) $class = ' class="blue" ';
        }
        printf('<tr %s><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%s</td>
            <input type="hidden" name="upc[]" value="%s" />',
            $class,$w['upc'],$w['manufacturer'],
            $w['description'],$w['department'],
            $w['dept_name'],$w['upc']);
        if (!isset($_REQUEST['excel'])){
            echo '<td><select name="local[]">';
            echo '<option value="0" '.($w['local']==0?'selected':'').'>No</option>';
            echo '<option value="1" '.($w['local']==1?'selected':'').'>SC</option>';
            echo '<option value="2" '.($w['local']==2?'selected':'').'>300mi</option>';
            echo '</select></td></tr>';
        }
        else {
            echo '<td>';
            switch($w['local']){
                case 0: echo 'No'; break;
                case 1: echo 'SC'; break;
                case 2: echo '300mi'; break;
            }
            echo '</td></tr>';
        }
    }
    echo '</table>';
    if (!isset($_REQUEST['excel'])){
        echo '<input type="submit" name="submitbtn" value="Update" />';
        printf('<input type="hidden" name="deptSub" value="%d" />
            <input type="hidden" name="deptStart" value="%d" />
            <input type="hidden" name="deptEnd" value="%d" />
            <input type="hidden" id="sort" name="sort" value="%s" />',
            $super,$start,$end,$sort);
        echo '</form>';
    }

    exit;
}

$page_title = 'Fannie - Local Products';
$header = 'Local Products';
include($FANNIE_ROOT.'src/header.html');

$deptQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
$deptR = $dbc->exec_statement($deptQ);
$dept_nos = array();
$dept_names = array();
$count = 0;
while ($deptW = $dbc->fetch_array($deptR)){
    $dept_nos[$count] = $deptW[0];
    $dept_names[$count] = $deptW[1];
    $count++;
}

$deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames WHERE 
    superID > 0 ORDER BY superID");
$deptSubR = $dbc->exec_statement($deptSubQ);
$deptSubList = "";

while($deptSubW = $dbc->fetch_array($deptSubR)){
  $deptSubList .="<option value=$deptSubW[0]>$deptSubW[0] $deptSubW[1]</option>";
}

?>

<script type="text/javascript">
function selectChange(selectid,targetid){
    document.getElementById(targetid).value = document.getElementById(selectid).value;
}
</script>
</head>

<div id=textwlogo> 
    <form method = "get" action="localItems.php">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr class=dept id=dept1>
            <td valign=top><p><b>Buyer</b></p></td>
            <td><p><select name=deptSub>
            <option value=0></option>
            <?php
            echo $deptSubList;  
            ?>
            </select></p>
            <i>Selecting a Buyer/Dept overrides Department Start/Department End.
            To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'</i></td>

        </tr>
        <tr class=dept id=dept2> 
            <td> <p><b>Department Start</b></p>
            <p><b>End</b></p></td>
            <td> <p>
            <select id=deptStartSelect onchange="selectChange('deptStartSelect','deptStart');">
            <?php
            for ($i = 0; $i < $count; $i++)
                echo "<option value=$dept_nos[$i]>$dept_nos[$i] $dept_names[$i]</option>";
            ?>
            </select>
            <input type=text size= 5 id=deptStart name=deptStart value=1>
            </p>
            <p>
            <select id=deptEndSelect onchange="selectChange('deptEndSelect','deptEnd');">
            <?php
            for ($i = 0; $i < $count; $i++)
                echo "<option value=$dept_nos[$i]>$dept_nos[$i] $dept_names[$i]</option>";
            ?>
            </select>
            <input type=text size= 5 id=deptEnd name=deptEnd value=1>
            </p></td>
        </tr>
        <tr> 
            <td><b>Sort report by?</b></td>
            <td> <select name="sort" size="1">
                    <option value="dept_name">Department</option>
                    <option value="p.upc">UPC</option>
                    <option value="description">Description</option>
            </select> 
            <input type=checkbox name=excel /> <b>Excel</b></td>
            <td>&nbsp;</td>
                <td>&nbsp; </td>
            </tr>
            <td>&nbsp;</td>
            <td>&nbsp; </td>
        </tr>
        <tr> 
            <td> <input type=submit name=submitbtn value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</form>
</div>

<?php
include($FANNIE_ROOT.'src/footer.html');
?>



