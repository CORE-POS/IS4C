<?php
use COREPOS\pos\lib\ReceiptLib;
include('test_env.php');
?>
<html>
<head>Test Receipt</head>
<body>
<em>You need an inprogress transaction to use this</em>
<form action="receiptTest.php" method="get">
<b>Receipt Type</b>:
<select name="rtype">
    <option value="full">Normal Transaction</option>
    <option value="cab">Cab Coupon</option>
    <option value="partial">Partial Transaction</option>
    <option value="cancelled">Cancelled Transaction</option>
    <option value="resume">Resumed Transaction</option>
    <option value="suspended">Suspended Transaction</option>
    <option value="ccSlip">Credit Card Slip</option>
    <option value="gcSlip">Gift Card Slip</option>
    <option value="gcBalSlip">Gift Card Balance</option>
</select>
<input type="submit" value="Get Receipt">
</form>
<hr />
<?php
if (isset($_REQUEST['rtype'])){
    echo '<b>Results</b><br /><pre>';
    $receipt = ReceiptLib::printReceipt($_REQUEST['rtype'],False,False);
    if (is_array($receipt))
        echo $receipt['print'];
    else
        echo $receipt;
    echo '</pre>';
}
?>
</body>
</html>
