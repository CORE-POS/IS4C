<?php

if (!isset($_GET['excel'])){
    echo "<a href=index.php?excel=yes>Save to Excel</a><br />";
}
else {
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="departments.xls"');
}

include('../../../config.php');
if (!class_exists("SQLManager")) require($FANNIE_ROOT."src/SQLManager.php");

include('../../db.php');

$sub_depts = array("MISC","BULK","COOL","DELI","GROCERY","HBC","PRODUCE","MARKETING","MEAT","GEN MERCH");

echo "<table cellspacing=0 cellpadding=3 border=1>";
echo "<tr><th>Dept#</th><th>Desc</th><th>Tax</th><th>FS</th><th>pCode</th></tr>";
$cur_dept = "-1";

$deptQ = "select dept_no,superID,dept_name,dept_tax,dept_fs,salesCode from departments 
    as d LEFT JOIN MasterSuperDepts AS m on d.dept_no=m.dept_ID
    order by superID,dept_no";
$deptR = $sql->query($deptQ);

while ($row = $sql->fetch_row($deptR)){
    if ($cur_dept != $row[1]){
        echo "<tr><th colspan=5 align=center>".$sub_depts[$row[1]]."</th></tr>";
        $cur_dept = $row[1];
    }
    echo "<tr>";
    echo "<td>".$row[0]."</td>";
    echo "<td>".$row[2]."</td>";
    echo "<td>".$row[3]."</td>";
    echo "<td>".$row[4]."</td>";
    echo "<td>".$row[5]."</td>";
    echo "</tr>";
}
echo "</table>";

