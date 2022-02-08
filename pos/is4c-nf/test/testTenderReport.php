<?php
use COREPOS\pos\lib\ReceiptLib;
include('test_env.php');
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
$mods = AutoLoader::ListModules('COREPOS\\pos\\lib\\ReceiptBuilding\\TenderReports\\TenderReport');
?>
<form action="testTenderReport.php">
<select name='t_mod'>
<?php foreach ($mods as $m){ ?>
<option><?php echo $m; ?></option>
<?php } ?>
</select> <br />

Send to printer <select name="print">
<option value="0">No</option>
<option value="1">Yes</option>
</select> <br />


Emp# <input type="text" size="3" name="emp_no" />

<input type="submit" value="Get Output" />
</form>
<hr />
<?php
if (isset($_REQUEST['t_mod']) && isset($_REQUEST['emp_no'])){
    CoreLocal::set('CashierNo',$_REQUEST['emp_no']);
    $tmod = $_REQUEST['t_mod'];
    $session = new WrappedStorage();
    echo "Output for $tmod:<br />";
    echo '<pre>';
    $report = $tmod::get($session);
    echo $report;
    echo '</pre>';

    if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1){
        ReceiptLib::writeLine($report);
    }
}

