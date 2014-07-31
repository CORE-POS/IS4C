<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include('../ini.php');
CoreState::loadParams();
include('InstallUtilities.php');
?>
<html>
<head>
<title>Security configuration options</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Security</h2>
<form action=security.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
    <td><b>Scale Beep on Login</b>: </td>
    <td>
    <?php echo InstallUtilities::installSelectField('LoudLogins', array(1=>'Yes',0=>'No'), 0); ?>
    (Scale makes noise when admin login attempted)
    </td>
</tr>
<tr>
    <td><b>Cancel Transaction</b>: </td>
    <td>
    <?php
    $privLevels = array(30=>'Admin only', 20=>'All');
    echo InstallUtilities::installSelectField('SecurityCancel', $privLevels, 20);
    ?>
    </td>
</tr>
<tr>
    <td><b>Suspend/Resume</b>: </td>
    <td><?php echo InstallUtilities::installSelectField('SecuritySR', $privLevels, 20); ?></td>
</tr>
<tr>
    <td><b>Print Tender Report</b>: </td>
    <td><?php echo InstallUtilities::installSelectField('SecurityTR', $privLevels, 20); ?></td>
</tr>
<tr>
    <td><b>Refund Item</b>: </td>
    <td><?php echo InstallUtilities::installSelectField('SecurityRefund', $privLevels, 20); ?></td>
</tr>
<tr>
    <td><b>Line Item Discount</b>: </td>
    <td><?php echo InstallUtilities::installSelectField('SecurityLineItemDiscount', $privLevels, 20); ?></td>
</tr>
<tr>
    <td><b>Void Limit</b>:</td>
    <td>
    <?php
    echo InstallUtilities::installTextField('VoidLimit', 0);
    ?> 
    (in dollars, per transaction. Zero for unlimited).
    </td>
</tr>
<tr>
    <td colspan=2>
    <hr />
    <input type=submit name=secsubmit value="Save Changes" />
    </td>
</tr>
</table>
</form>
</div> <!--	wrapper -->
</body>
</html>
