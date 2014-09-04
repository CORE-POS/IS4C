<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

include($FANNIE_ROOT.'src/header.html');

if (isset($_POST["MAX_FILE_SIZE"])){
    $filename = tempnam(sys_get_temp_dir(),'PRF');
    move_uploaded_file($_FILES['upload']['tmp_name'],$filename);
    
    $pp = $_POST["pp"];

    $fp = fopen($filename,"r");
    $errors = False;
    $argsSets = array();
    while (!feof($fp)){
        $fields = fgetcsv($fp);
        if (count($fields) == 0) continue;
        if (!is_numeric($fields[0])) continue;
        if (count($fields) < 8){
            echo "Bad Record: $line";
            $errors = True;
            break;
        }
    
        $args = array($fields[0],sanitize_xls_money($fields[1]),-1*sanitize_xls_money($fields[2]),
            -1*sanitize_xls_money($fields[3]),sanitize_xls_money($fields[4]),
            sanitize_xls_money($fields[5]),sanitize_xls_money($fields[6]),
            sanitize_xls_money($fields[7]),$_REQUEST['fy']);
        $argSets[] = $args;
    }
    if (!$errors){
        $insP = $dbc->prepare_statement("INSERT INTO patronage (cardno,purchase,discounts,rewards,net_purch,tot_pat,
            cash_pat,equit_pat,FY) VALUES (?,?,?,?,?,?,?,?,?)");
        foreach($argSets as $args)
            $dbc->exec_statement($insP,$args);
        echo "Patronage imported!";
    }

    fclose($fp);
    unlink($filename);
}
else {
?>

<form enctype="multipart/form-data" action="upload.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Fiscal Year: <input type=text name=fy /><p />
</select><p />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>

</body>
</html>

<?php
}
include($FANNIE_ROOT.'src/footer.html');
?>
