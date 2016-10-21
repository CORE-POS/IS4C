<?php
use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\FormFactory;
use COREPOS\pos\install\InstallUtilities;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MiscLib;
?>
<!DOCTYPE html>
<html>
<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
$form = new FormFactory(InstallUtilities::dbOrFail(CoreLocal::get('pDatabase')));
?>
<head>
<title>IT CORE Lane Installation: Receipt Configuration</title>
<link rel="stylesheet" href="../css/toggle-switch.css" type="text/css" />
<script type="text/javascript" src="../js/<?php echo MiscLib::jqueryFile(); ?>"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">    
<h2><?php echo _('IT CORE Lane Installation: Receipt Configuration'); ?></h2>

<div class="alert"><?php Conf::checkWritable('../ini.json', False, 'JSON'); ?></div>
<div class="alert"><?php Conf::checkWritable('../ini.php', False, 'PHP'); ?></div>

<form action=receipt.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
    <td colspan=2 class="tblHeader">
    <h3><?php echo _('Receipt Settings'); ?></h3>
    </td>
</tr>
<tr>
    <td style="width: 30%;"></td>
    <td><?php echo $form->checkboxField('print', _('Enable receipts'), 0); ?></td>
</tr>
<tr>
    <td style="width: 30%;"></td>
    <td><?php echo $form->checkboxField('CancelReceipt', _('Print receipt on canceled transaction'), 1); ?></td>
</tr>
<tr>
    <td style="width: 30%;"></td>
    <td><?php echo $form->checkboxField('SuspendReceipt', _('Print receipt on suspended transaction'), 1); ?></td>
</tr>
<tr>
    <td style="width: 30%;"></td>
    <td><?php echo $form->checkboxField('ShrinkReceipt', _('Print receipt on shrink/DDD transaction'), 1); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Receipt Type'); ?></b>: </td>
    <td>
    <?php
    $receipts = array(
        2 => _('Modular'),
        1 => _('Grouped (static, legacy)'),
        0 => _('In Order (static, legacy)'),
    );
    /**
      Nested views no longer creaed by default. Only present
      the option if views already exist
    */
    $dbc = Database::tDataConnect();
    if (!$dbc->tableExists('rp_receipt_reorder_unions_g')) {
        unset($receipts[1]);
    }
    echo $form->selectField('newReceipt', $receipts, 2);
    ?>
    <span class='noteTxt'><?php echo _('
    The Modular receipt uses the modules below to assemble the receipt\'s contents.
    The Grouped option groups items together in categories. The In Order option
    simply prints items in the order they were entered. The default set of modulars
    will group items in categories. The InOrder modules will print items in order.
    Legacy options may not be supported in the future.'); ?>
    </span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('List Savings'); ?></b>: </td>
    <td>
    <?php
    $savings = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Savings\\DefaultReceiptSavings', true);
    $savings = array_map(function($i){ return str_replace('\\', '-', $i); }, $savings);
    echo $form->selectField('ReceiptSavingsMode', $savings, 'COREPOS-pos-lib-ReceiptBuilding-Savings-DefaultReceiptSavings');
    CoreLocal::set('ReceiptSavingsMode', str_replace('-', '\\', CoreLocal::get('ReceiptSavingsMode')), true);
    InstallUtilities::paramSave('ReceiptSavingsMode', CoreLocal::get('ReceiptSavingsMode'));
    ?>
    <span class='noteTxt'><?php echo _('
    Different options for displaying lines about total savings from
    sales and discounts'); ?>
    </span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('List Local Items'); ?></b>: </td>
    <td>
    <?php
    $local = array(
        'total' => _('As $ Amount'),
        'percent' => _('As % of Purchase'),
        'omit' => _('Do not print'),
    );
    echo $form->selectField('ReceiptLocalMode', $local, 'total');
    ?>
    <span class='noteTxt'><?php echo _('
    Display information about items in the transaction marked "local". This
    can be displayed as the total dollar value or as a percent of all
    items on the receipt.'); ?>
    </span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Thank You Line'); ?></b>: </td>
    <td>
    <?php
    $thanks = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\ThankYou\\DefaultReceiptThanks', true);
    $thanks = array_map(function($i){ return str_replace('\\', '-', $i); }, $thanks);
    echo $form->selectField('ReceiptThankYou', $thanks, 'COREPOS-pos-lib-ReceiptBuilding-ThankYou-DefaultReceiptThanks');
    CoreLocal::set('ReceiptThankYou', str_replace('-', '\\', CoreLocal::get('ReceiptThankYou')), true);
    InstallUtilities::paramSave('ReceiptThankYou', CoreLocal::get('ReceiptThankYou'));
    ?>
    <span class='noteTxt'><?php echo _('
    Different options for the receipt line(s) thanking the customer and/or member'); ?>
    </span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Receipt Driver'); ?></b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\PrintHandlers\\PrintHandler',True);
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    echo $form->selectField('ReceiptDriver', $mods, 'COREPOS-pos-lib-PrintHandlers-ESCPOSPrintHandler');
    $fixed = str_replace('-', '\\', CoreLocal::get('ReceiptDriver'));
    InstallUtilities::paramSave('ReceiptDriver', $fixed);
    ?>
    <span class="noteTxt"></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Line width'); ?></b>:</td>
    <td>
    <?php
    echo $form->textField('ReceiptLineWidth', 56); 
    ?>
    <span class="noteTxt"></span>
    </td>
</tr>
<tr>
    <td colspan="2"><h3><?php echo _('PHP Receipt Modules'); ?></h3></td>
</tr>
<tr>
    <td><b><?php echo _('Data Fetch Mod'); ?></b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\DataFetch\\DefaultReceiptDataFetch', true);
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    sort($mods);
    echo $form->selectField('RBFetchData', $mods, 'COREPOS-pos-lib-ReceiptBuilding-DataFetch-DefaultReceiptDataFetch');
    CoreLocal::set('RBFetchData', str_replace('-', '\\', CoreLocal::get('RBFetchData')), true);
    InstallUtilities::paramSave('RBFetchData', CoreLocal::get('RBFetchData'));
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Filtering Mod'); ?></b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Filter\\DefaultReceiptFilter',True);
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    sort($mods);
    echo $form->selectField('RBFilter', $mods, 'DefaultReceiptFilter');
    CoreLocal::set('RBFilter', str_replace('-', '\\', CoreLocal::get('RBFilter')), true);
    InstallUtilities::paramSave('RBFilter', CoreLocal::get('RBFilter'));
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Sorting Mod'); ?></b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Sort\\DefaultReceiptSort',True);
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    sort($mods);
    echo $form->selectField('RBSort', $mods, 'DefaultReceiptSort');
    CoreLocal::set('RBSort', str_replace('-', '\\', CoreLocal::get('RBSort')), true);
    InstallUtilities::paramSave('RBSort', CoreLocal::get('RBSort'));
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Tagging Mod'); ?></b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Tag\\DefaultReceiptTag',True);
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    sort($mods);
    echo $form->selectField('RBTag', $mods, 'DefaultReceiptTag');
    CoreLocal::set('RBTag', str_replace('-', '\\', CoreLocal::get('RBTag')), true);
    InstallUtilities::paramSave('RBTag', CoreLocal::get('RBTag'));
    ?>
    </td>
</tr>
<tr><td colspan="2"><h3><?php echo _('Message Modules'); ?></h3></td></tr>
<tr><td colspan="3">
<p><?php echo _('Message Modules provide special blocks of text on the end
of the receipt & special non-item receipt types.'); ?></p>
</td></tr>
<tr><td>&nbsp;</td><td>
<?php
if (isset($_REQUEST['RM_MODS'])){
    $mods = array();
    foreach($_REQUEST['RM_MODS'] as $m){
        if ($m != '') $mods[] = str_replace('-', '\\', $m);
    }
    CoreLocal::set('ReceiptMessageMods', $mods);
}
if (!is_array(CoreLocal::get('ReceiptMessageMods'))){
    CoreLocal::set('ReceiptMessageMods', array());
}
$available = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Messages\\ReceiptMessage');
$available = array_map(function($i){ return str_replace('\\', '-', $i); }, $available);
$current = CoreLocal::get('ReceiptMessageMods');
for($i=0;$i<=count($current);$i++){
    $c = isset($current[$i]) ? $current[$i] : '';
    echo '<select name="RM_MODS[]">';
    echo '<option value="">' . _('[None]') . '</option>';
    foreach($available as $a) {
        $match = false;
        if ($a == $c) $match = true;
        elseif (substr($a, -1*(strlen($c)+1)) == '-' . $c) $match=true;
        printf('<option %s>%s</option>',($match?'selected':''),$a);
    }
    echo '</select><br />';
}
InstallUtilities::paramSave('ReceiptMessageMods',CoreLocal::get('ReceiptMessageMods'));
?>
</td></tr>
<tr>
    <td colspan="2"><h3><?php echo _('Email Receipts'); ?></h3></td>
</tr>
<tr>
    <td><b><?php echo _('Email Receipt Sender Address'); ?></b>:</td>
    <td><?php echo $form->textField('emailReceiptFrom', ''); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Email Receipt Sender Name'); ?></b>:</td>
    <td><?php echo $form->textField('emailReceiptName', 'CORE-POS'); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Use SMTP'); ?></b>:</td>
    <td><?php echo $form->selectField('emailReceiptSmtp', array(1=>_('Yes'),0=>_('No')), 0); ?></td>
</tr>
<tr>
    <td><b><?php echo _('SMTP Server'); ?></b>:</td>
    <td><?php echo $form->textField('emailReceiptHost', '127.0.0.1'); ?></td>
</tr>
<tr>
    <td><b><?php echo _('SMTP Port'); ?></b>:</td>
    <td><?php echo $form->textField('emailReceiptPort', '25'); ?></td>
</tr>
<tr>
    <td><b><?php echo _('SMTP Username'); ?></b>:</td>
    <td><?php echo $form->textField('emailReceiptUser', ''); ?></td>
</tr>
<tr>
    <td><b><?php echo _('SMTP Password'); ?></b>:</td>
    <td><?php echo $form->textField('emailReceiptPw', '', Conf::PARAM_SETTING, true, array('type'=>'password')); ?></td>
</tr>
<tr>
    <td><b><?php echo _('SMTP Security'); ?></b>:</td>
    <td><?php echo $form->selectField('emailReceiptSSL', array('none', 'SSL', 'TLS'), 'none'); ?></td>
</tr>
<tr>
    <td><b><?php echo _('HTML Receipt Builder'); ?></b>:</td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\HtmlEmail\\DefaultHtmlEmail');
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    sort($mods);
    $e_mods = array('' => '[None]');
    foreach ($mods as $m) {
        $e_mods[$m] = $m;
    }
    ?>
    <td><?php echo $form->selectField('emailReceiptHtml', $e_mods, ''); ?></td>
    <?php
    CoreLocal::set('emailReceiptHtml', str_replace('-', '\\', CoreLocal::get('emailReceiptHtml')), true);
    InstallUtilities::paramSave('emailReceiptHtml', CoreLocal::get('emailReceiptHtml'));
    ?>
</tr>
<tr><td colspan=2 class="submitBtn">
<input type=submit name=esubmit value="<?php echo _('Save Changes'); ?>" />
</td></tr>
</table>
</form>
</div> <!--    wrapper -->
</body>
</html>
