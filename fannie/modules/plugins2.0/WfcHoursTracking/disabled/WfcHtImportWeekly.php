<?php
class WfcHtImportWeekly {}
/**
  No longer in use; not cleaned up to be plugin-safe.
  Kept for reference.
return;

include(dirname(__FILE__).'/../../../config.php');
require($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('upload_hours_data')){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$_SERVER['PHP_SELF']}");
    return;
}

require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/tmp_dir.php');
require(dirname(__FILE__).'/db.php');
$db = hours_dbconnect();

$ADP_COL = 3;
$HOURS_COL = 7;
$STATUS_COL = 4;
$HEADERS = true;

$colors = array("one","two");

echo "<html>
<head><title>Upload Data</title>
<script src=\"/CalendarControl/CalendarControl.js\"
        language=\"javascript\"></script>
<link href=\"/CalendarControl/CalendarControl.css\"
    type=\"text/css\" rel=\"stylesheet\">
<style type=text/css>
.one {
    background: #ffffff;
}
.one td {
    text-align: right;
}
.two {
    background: #ffffcc;
}
.two td {
    text-align: right;
}
</style>
</head>
<body bgcolor=#cccccc>";

function strnorm($str){
    return strtolower(trim($str));
}


if (isset($_POST["MAX_FILE_SIZE"])){
    $filename = md5(time());
    $tmp = sys_get_temp_dir();
    move_uploaded_file($_FILES['upload']['tmp_name'],"$tmp/$filename");
    
    $fp = fopen("$tmp/$filename","r");
    $emps = array();
    $checkQ = $db->prepare_statement("select empID from employees where adpID=?");
    while (!feof($fp)){
        $line = fgets($fp);
        $fields = csv_parser($line);
        if (!is_numeric(trim($fields[$HOURS_COL]))){
            for($i=0;$i<count($fields);$i++){
                if (strnorm($fields[$i]) == "employee")
                    $ADP_COL = $i;
                if (strnorm($fields[$i]) == "payroll id")
                    $ADP_COL = $i;
                elseif(strnorm($fields[$i]) == "pay group")
                    $STATUS_COL = $i;
                elseif(strnorm($fields[$i]) == "pay status")
                    $STATUS_COL = $i;
                elseif(strnorm($fields[$i]) == "hours")
                    $HOURS_COL = $i;    
            }
            continue;
        }
        if (count($fields) == 0) continue;

        $adpID = $fields[$ADP_COL];
        $adpID = ltrim($adpID,"U8Uu8u");

        $checkR = $db->exec_statement($checkQ, array($adpID));
        if ($db->num_rows($checkR) < 1){
            echo "Notice: ADP ID #$adpID doesn't match any current employee.";
            echo "Data for this ID is being omitted.<br />";
            continue;
        }
        $empID = array_pop($db->fetch_row($checkR));

        $hours = 0;
        if (is_numeric(trim($fields[$HOURS_COL]))){
            $hours = trim($fields[$HOURS_COL]);
        }   
        $status = $fields[$STATUS_COL];

        if(!isset($emps["$adpID"])){
            $emps["$adpID"] = array();
            $emps["$adpID"][0] = $hours;
            $emps["$adpID"][1] = $status;
            $emps["$adpID"][2] = $empID;
        }
        else
            $emps["$adpID"][0] += $hours;
        
    }
    fclose($fp);
    unlink("$tmp/$filename");

    $start = $_POST["startDay"];
    $temp = explode("-",$start);
    $end = date("Y-m-d",mktime(0,0,0,ltrim($temp[1],"0"),ltrim($temp[2],"0")+6,$temp[0]));

    echo "<form action=importWeekly.php method=post>";
    echo "<b>Start date</b>: ".$start."<br />";
    echo "<b>End date</b>: ".$end."<br />";
    echo "<table cellpadding=4 cellspacing=0 border=1>";
    echo "<tr class=one><th>ADP ID</th><th>Hours</th><th>Status</th></tr>";
    $c = 1;
    foreach($emps as $k=>$v){
        echo "<tr class=$colors[$c]>";
        echo "<td>$k</td>";
        echo "<td>$v[0]</td>";
        echo "<td>$v[1]</td>";
        echo "</tr>";

        echo "<input type=hidden name=data[] value=\"$v[2],$v[0],$v[1]\" />";
    }
    echo "</table>";
    echo "<input type=hidden name=startDay value=\"$start\" />";
    echo "<input type=hidden name=endDay value=\"$end\" />";
    echo "<input type=submit value=\"Import Data\" />";
    echo "</form>";

    return; 
}
elseif (isset($_POST["data"])){
    $start = $_POST["startDay"];
    $end = $_POST["endDay"];
    $prep = $db->prepare_statement("SELECT * FROM fullTimeStatus WHERE empID=?");
    $ins = $db->prepare_statement("INSERT INTO fullTimeStatus VALUES (?, ?)");
    $up = $db->prepare_statement("UPDATE fullTimeStatus SET status=? WHERE empID=?");
    $add = $db->prepare_statement("INSERT INTO weeklyHours VALUES (?,?,?,?)");
    foreach($_POST["data"] as $d){
        $fields = explode(",",$d);
        $empID = $fields[0];
        $hours = $fields[1];
        $status = $fields[2];

        $res = $db->exec_statement($prep, array($empID));
        if ($db->num_rows($res) == 0){
            $db->exec_statement($ins, array($empID, $status));
        }
        else {
            $db->exec_statement($up, array($status, $empID));
        }

        $db->exec_statement($add, array($start, $end, $empID, $hours));
    }

    echo "ADP data import complete!<br />";
    echo "<a href=index.php>Main Menu</a><br />";
    
    return;
}

?>

<form enctype="multipart/form-data" action="importWeekly.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Week Beginning On: <input type=text name=startDay onfocus="showCalendarControl(this);" /><p />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>

</body>
</html>
*/
