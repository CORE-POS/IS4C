<?php

include(dirname(__FILE__) . '/../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_REQUEST['loadID'])){
    $q = $dbc->prepare("SELECT reportName,reportQuery FROM 
        customReports WHERE reportID=?");
    $r = $dbc->execute($q,array($_REQUEST['loadID']));
    $w = $dbc->fetch_row($r);
    echo $w['reportName'];
    echo '`';
    echo base64_decode($w['reportQuery']);
    return;
}

$errors = "";
$query = "";
if (isset($_REQUEST['query'])){
    $query = $_REQUEST['query'];
    if (stristr($query,"drop"))
        $errors .= "Illegal term <b>drop</b><br />";
    if (stristr($query,"truncate"))
        $errors .= "Illegal term <b>truncate</b><br />";
    if (stristr($query,"delete"))
        $errors .= "Illegal term <b>delete</b><br />";
    if (stristr($query,"update"))
        $errors .= "Illegal term <b>update</b><br />";
    if (stristr($query,"alter"))
        $errors .= "Illegal term <b>alter</b><br />";
}

if ($errors == "" && $query != ""){
    $dlog = "";
    $dtrans = "";
    if (!empty($_REQUEST['date1']) && !empty($_REQUEST['date2'])){
        $dlog = DTransactionsModel::selectDlog($_REQUEST['date1'],$_REQUEST['date2']);
        $dtrans = DTransactionsModel::selectDtrans($_REQUEST['date1'],$_REQUEST['date2']);
    }
    elseif (!empty($_REQUEST['date1'])){
        $dlog = DTransactionsModel::selectDlog($_REQUEST['date1']);
        $dtrans = DTransactionsModel::selectDtrans($_REQUEST['date1']);
    }

    if (!empty($dlog))
        $query = str_ireplace(" dlog "," ".$dlog." ",$query);
    if (!empty($dtrans))
        $query = str_ireplace(" dtransactions "," ".$dtrans." ",$query);

    $prep = $dbc->prepare($query);
    $result = $dbc->execute($query);
    if (!$result){
        echo "<i>Error occured</i>: ".$dbc->error();
        echo "<hr />";
        echo "<i>Your query</i>: ".$query;  
    }
    else if ($dbc->num_rows($result) == 0){
        echo "<i>Query returned zero results</i><hr />";
        echo "<i>Your query</i>: ".$query;  
    }
    else {
        if (isset($_REQUEST['excel'])){
            $ext = \COREPOS\Fannie\API\data\DataConvert::excelFileExtension();
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="resultset.' . $ext . '"');
            ob_start();
        }
        echo '<table cellspacing="0" cellpadding="4" border="1">';
        echo '<tr>';
        $num = $dbc->numFields($result);
        for($i=0;$i<$num;$i++){
            echo '<th>'.$dbc->fieldName($result,$i)."</th>";
        }
        echo '</tr>';
        while($row = $dbc->fetch_row($result)){
            echo '<tr>';
            for($i=0;$i<$num;$i++)
                echo '<td>'.$row[$i].'</td>';
            echo '</tr>';
        }
        echo '</table>';

        if (isset($_REQUEST['excel'])){
            $output = ob_get_contents();
            ob_end_clean();
            $array = \COREPOS\Fannie\API\data\DataConvert::htmlToArray($output);
            $xls = \COREPOS\Fannie\API\data\DataConvert::arrayToExcel($array);
            echo $xls;
        }

        if (!empty($_REQUEST['repName'])){
            $name = $_REQUEST['repName'];
            $saveableQ = base64_encode($_REQUEST['query']);

            $chkQ = $dbc->prepare("SELECT reportID FROM customReports WHERE reportName=?");
            $chkR = $dbc->execute($chkQ,array($name));
            if ($dbc->num_rows($chkR) == 0){
                $idQ = $dbc->prepare("SELECT max(reportID) FROM customReports");
                $idR = $dbc->execute($idQ);
                $id = array_pop($dbc->fetch_row($idR));
                $id = ($id=="")?1:$id+1;
                $insQ = $dbc->prepare("INSERT INTO customReports (reportID,reportName,reportQuery)
                    VALUES (?,?,?)");
                $insR = $dbc->execute($insQ,array($id,$name,$saveableQ));
            }
            else {
                $id = array_pop($dbc->fetch_row($chkR));
                $upQ = $dbc->prepare("UPDATE customReports SET reportQuery=? WHERE reportID=?");
                $upR = $dbc->execute($upQ,array($saveableQ,$id));
            }
        }
    }
}
else {
    $header = "Reporting for DBAs";
    $page_title = "Fannie :: Skip learning PHP/HTML";
    include($FANNIE_ROOT.'src/header.html');

    if (!empty($errors))
        echo "<blockquote>".$errors."</blockquote>";

    $q = $dbc->prepare("SELECT reportID,reportName FROM customReports ORDER BY reportName");
    $r = $dbc->execute($q);
    $opts = "";
    while($w = $dbc->fetch_row($r))
        $opts .= sprintf('<option value="%d">%s</option>',$w['reportID'],$w['reportName']);

    ?>
    <script type="text/javascript">
    function loadSaved(id){
        if (id==-1){
            $('#repName').val('');
            $('#query').val('');
        }
        else {
            $.ajax({
            url:'index.php',
            type:'get',
            cache: false,
            data:'loadID='+id
            }).done(function(data){
                var tmp = data.split('`');
                $('#repName').val(tmp[0]);
                $('#query').val(tmp[1]);
            });
        }
    }
    </script>
    <?php

    echo ' <script type="text/javascript">
            $(document).ready(function() {
                $(\'#date1\').datepicker();
                $(\'#date2\').datepicker();
            });
            </script>
        <form action="index.php" method="post">
        Saved reports: <select onchange="loadSaved(this.value);">
        <option value="-1">Choose...</option>'.$opts.'</select>
        <p />Save As <input type="text" name="repName" id="repName" />
        <p />
        <textarea name="query" id="query" rows="10" cols="40"></textarea>
        <p />
        Date range
        <input type="text" name="date1" size="10" id="date1" />
        <input type="text" name="date2" size="10" id="date2" />
        <br /><input type="checkbox" name="excel" id="excel" />
        <label for="excel">Download results</label>
        <p />
        <input type="submit" value="Run Report" />
        </form>';

    include($FANNIE_ROOT.'src/footer.html');
}

