<?php
use COREPOS\pos\install\conf\FormFactory;
use COREPOS\pos\install\InstallUtilities;
use COREPOS\pos\lib\CoreState;
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
$form = new FormFactory(InstallUtilities::dbOrFail(CoreLocal::get('pDatabase')));
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
<h2><?php echo _('IT CORE Lane Installation: Security'); ?></h2>
<form action=security.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
    <td><b><?php echo _('Scale Beep on Login'); ?></b>: </td>
    <td>
    <?php echo $form->selectField('LoudLogins', array(1=>_('Yes'),0=>_('No')), 0); ?>
    <?php echo _('(Scale makes noise when admin login attempted)'); ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Cancel Transaction'); ?></b>: </td>
    <td>
    <?php
    $privLevels = array(30=>_('Admin only'), 25=>_('Current & Admin'), 20=>_('All'));
    echo $form->selectField('SecurityCancel', $privLevels, 20);
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Suspend/Resume'); ?></b>: </td>
    <td>
    <?php
    $privLevels = array(30=>_('Admin only'), 20=>_('All'));
    echo $form->selectField('SecuritySR', $privLevels, 20);
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Print Tender Report'); ?></b>: </td>
    <td><?php echo $form->selectField('SecurityTR', $privLevels, 20); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Refund Item'); ?></b>: </td>
    <td><?php echo $form->selectField('SecurityRefund', $privLevels, 20); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Line Item Discount'); ?></b>: </td>
    <td><?php echo $form->selectField('SecurityLineItemDiscount', $privLevels, 20); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Void Limit'); ?></b>: </td>
    <td>
    <?php
    echo $form->textField('VoidLimit', 0);
    ?> 
    <?php echo _('(in dollars, per transaction. Zero for unlimited).'); ?>
    </td>
</tr>
<tr>
    <td colspan=2>
    <hr />
    <input type=submit name=secsubmit value="<?php echo _('Save Changes'); ?>" />
    </td>
</tr>
</table>
</form>
</div> <!--    wrapper -->
</body>
</html>
