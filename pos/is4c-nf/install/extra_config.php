<?php
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\FormFactory;
use COREPOS\pos\install\InstallUtilities;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
$form = new FormFactory(InstallUtilities::dbOrFail(CoreLocal::get('pDatabase')));
?>
<!DOCTYPE html>
<html>
<head>
<title>IT CORE Lane Installation: Additional Configuration</title>
<link rel="stylesheet" href="../css/toggle-switch.css" type="text/css" />
<script type="text/javascript" src="../js/<?php echo MiscLib::jqueryFile(); ?>"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">    
<h2><?php echo _('IT CORE Lane Installation: Additional Configuration (Extras)'); ?></h2>

<div class="alert"><?php Conf::checkWritable('../ini.json', False, 'JSON'); ?></div>
<div class="alert"><?php Conf::checkWritable('../ini.php', False, 'PHP'); ?></div>

<form action=extra_config.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
    <td colspan=2 class="tblHeader"><h3><?php echo _('General Settings'); ?></h3></td>
</tr>
<tr>
    <td style="width: 30%;"><b><?php echo _('Organization'); ?></b>:</td>
    <td>
    <?php echo $form->textField('store', ''); ?>
    <span class='noteTxt'><?php echo _('In theory, any hard-coded, organization specific sequences should be blocked
    off based on the organization setting. Adherence to this principle is less than ideal.'); ?></span>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo $form->checkboxField('discountEnforced', _('Discounts Enabled'), 0); ?>
    <span class='noteTxt'><?php echo _('If yes, members get a percentage discount as specified in custdata.'); ?></span>
    </td>
</tr>
<tr>
    <td></td>
    <td> 
    <?php echo $form->checkboxField('NonStackingDiscounts', _('Only One Discount Applies'), 0); ?>
    <span class='noteTxt'><?php echo _('If a customer is eligible for a 5% discount and a 10% discount and
    only one applies, then the customer will get a 10% discount. Otherwise they stack and
    the total discount is 15%.'); ?></span>
    </td>
</tr>
<tr>
    <td></td>
    <td> 
    <?php echo $form->checkboxField('refundDiscountable', _('Discounts on refunds'), 0); ?>
    <span class='noteTxt'><?php echo _('If yes, percent discount is applied to refunds'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Line Item Discount (member)'); ?></b>: </td>
    <td>
    <?php echo $form->textField('LineItemDiscountMem', 0); ?>
    <span class='noteTxt'><?php echo _('(percentage; 0.05 => 5%)'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Line Item Discount (non-member)'); ?></b>: </td>
    <td>
    <?php echo $form->textField('LineItemDiscountNonMem', 0); ?>
    <span class='noteTxt'><?php echo _('(percentage; 0.05 => 5%)'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Default Non-member #'); ?></b>: </td>
    <td>
    <?php echo $form->textField('defaultNonMem', 99999); ?>
    <span class='noteTxt'><?php echo _('Normally a single account number is used for most if not all non-member
    transactions. Specify that account number here.'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Default Non-member behavior'); ?></b>: </td><td>
    <?php
    $behavior = array('1' => _('Cannot override other accounts'), '0' => _('No different than other accounts'));
    echo $form->selectField('RestrictDefaultNonMem', $behavior, 0);
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Visiting Member #'); ?></b>: </td>
    <td><?php echo $form->textField('visitingMem', ''); ?>
    <span class='noteTxt'><?php echo _('This account provides members of other co-ops with member pricing
    but no other benefits. Leave blank to disable.'); ?></span>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo $form->checkboxField('memlistNonMember', _('Show non-member'), 0); ?>
    <span class='noteTxt'><?php echo _('Display non-member acct. in member searches?'); ?></span>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo $form->checkboxField('useMemTypeTable', _('Use memtype table'), 0); ?>
    <span class='noteTxt'><?php echo _('Use memtype table when applicable. This forces all memberships of a given
    type to have the same discount, among other things.'); ?></span>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo $form->checkboxField('InvertAR', 'Invert Display A/R', 0); ?>
    <span class='noteTxt'><?php echo _('Normally a positive A/R balance indicates the amount the customer
    owes to the store (i.e., a debt). Inverting this means a positive A/R balance indicates
    the amount the store owes to the customer (i.e., a credit)'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Bottle Return Department number'); ?></b>: </td>
    <td><?php echo $form->textField('BottleReturnDept', ''); ?>
    <span class='noteTxt'><?php echo _('Add a BOTTLE RETURN item to your products table with a normal_price of 0, 
    CORE will prompt for Bottle Return amt. and then make it a negative value.'); ?></span>
    </td>
</tr>
<tr>
    <td colspan=2 class="tblHeader">
    <h3><?php echo _('Hardware Settings'); ?></h3>
    </td>
</tr>
<?php
// 28Jan14 EL There's an inch or so of whitespace at the bottom of this row.
// I don't know what causes it.
?>
<tr><td><b><?php echo _('Printer port'); ?></b>:
</td><td><?php
// If values entered on the form are being saved, set session variable
//  and flag type of port choice: "other" or not.
if (FormLib::get('PPORT',false) !== false) {
    $PPORT = FormLib::get('PPORT');
    $otherPortText = FormLib::get('otherpport', false);
    if ($PPORT === 'other' && $otherPortText !== false) {
        CoreLocal::set('printerPort',trim($otherPortText), true);
        $otherPortChecked = True;
        $otherPortText = trim($otherPortText);
    } else {
        CoreLocal::set('printerPort',$PPORT, true);
        $otherPortChecked = False;
        $otherPortText = "";
    }
} else {
    $pport = CoreLocal::get('printerPort');
    if (isset($pport) && $pport !== False && $pport != "") {
        $pports = array("/dev/lp0","/dev/usb/lp0","LPT1:","fakereceipt.txt");
        if (in_array($pport,$pports)) {
            $otherPortChecked = False;
            $otherPortText = "";
        } else {
            $otherPortChecked = True;
            $otherPortText = "$pport";
        }
    } else {
        $otherPortChecked = False;
        $otherPortText = "";
    }
}
?>
<input type="radio" name=PPORT value="/dev/lp0" id="div-lp0"
    <?php if (!$otherPortChecked && CoreLocal::get('printerPort')=="/dev/lp0")
            echo "checked";
    ?> /><label for="div-lp0">/dev/lp0 (*nix)</label><br />
<input type="radio" name=PPORT value="/dev/usb/lp0" id="div-usb-lp0"
    <?php if (!$otherPortChecked && CoreLocal::get('printerPort')=="/dev/usb/lp0")
            echo "checked"; ?> /><label for="div-usb-lp0">/dev/usb/lp0 (*nix)</label><br />
<input type="radio" name=PPORT value="LPT1:" id="lpt1-"
    <?php if (!$otherPortChecked && CoreLocal::get('printerPort')=="LPT1:")
                echo "checked"; ?> /><label for="lpt1-">LPT1: (windows)</label><br />
<input type="radio" name=PPORT value="fakereceipt.txt" id="fakercpt"
    <?php if (!$otherPortChecked && CoreLocal::get('printerPort')=="fakereceipt.txt")
                echo "checked";
    ?> /><label for="fakercpt">fakereceipt.txt</label><br />
<input type="radio" name=PPORT value="other"
    <?php if ($otherPortChecked)
                echo "checked";
?> /> <input type=text name="otherpport"
    value="<?php echo "$otherPortText"; ?>"><br />
<span class='noteTxt' style="top:-110px;"> <?php printf("<p>" . _('Current value') . ": <span class='pre'>%s</span></p>",CoreLocal::get('printerPort')); ?>
<?php echo _('Path to the printer. Select from common values, or enter a custom path.
Some ubuntu distros might put your USB printer at /dev/usblp0'); ?></span>
</td></tr>
<?php
// Write to database.
InstallUtilities::paramSave('printerPort',CoreLocal::get('printerPort'));
?>

<tr>
    <td></td>
    <td>
    <?php echo $form->checkboxField('enableFranking', _('Enable Check Franking'), 0); ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Drawer Behavior Module'); ?></b>:</td>
    <td>
    <?php
    $kmods = AutoLoader::listModules('COREPOS\pos\lib\Kickers\Kicker',True);
    $kmods = array_map(function($i){ return str_replace('\\', '-', $i); }, $kmods);
    echo $form->selectField('kickerModule', $kmods, 'Kicker');
    $rewrite = str_replace('-', '\\', CoreLocal::get('kickerModule')); 
    InstallUtilities::paramSave('kickerModule', $rewrite);
    ?>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo $form->checkboxField('dualDrawerMode', _('Dual Drawer Mode'), 0); ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Scanner/scale driver'); ?></b>:</td>
    <td><?php echo $form->selectField('scaleDriver', array('NewMagellan', 'ssd'), 'NewMagellan'); ?></td>
</tr>
<tr>
    <td colspan=2>
    <p><?php echo _('The name of your scale driver. Known good values include "ssd" and "NewMagellan".'); ?></p>
    </td>
</tr>
<tr>
    <td colspan=2 class="tblHeader">
    <h3><?php echo _('Display Settings'); ?></h3>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Screen Height'); ?></b>:</td>
    <td><?php echo $form->selectField('screenLines', range(9, 19), 11); ?>
    <span class='noteTxt'><?php echo _('Number of items to display at once'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Alert Bar'); ?></b>:</td>
    <td><?php echo $form->textField('alertBar', ''); ?></td>
</tr>
<tr>
    <td></td>
    <td><?php echo $form->checkboxField('lockScreen', _('Lock screen on idle'), 0); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Lock Screen Timeout'); ?></b>:</td>
    <td><?php echo $form->textField('timeout', 180000); ?>
    <span class='noteTxt'><?php echo _('Enter timeout in milliseconds. Default: 180000 (3 minutes)'); ?></span>
    </td>
</tr>
<tr><td>
<b><?php echo _('Footer Modules'); ?></b> <?php echo _('(left to right)'); ?>:</td><td>
<?php
$footer_mods = array();
// get current settings
$current_mods = CoreLocal::get("FooterModules");
// replace w/ form post if needed
// fill in defaults if missing
if (is_array(FormLib::get('FOOTER_MODS'))) $current_mods = FormLib::get('FOOTER_MODS');
elseif(!is_array($current_mods) || count($current_mods) != 5){
    $current_mods = array(
    'COREPOS-pos-lib-FooterBoxes-SavedOrCouldHave',
    'COREPOS-pos-lib-FooterBoxes-TransPercentDiscount',
    'COREPOS-pos-lib-FooterBoxes-MemSales',
    'COREPOS-pos-lib-FooterBoxes-EveryoneSales',
    'COREPOS-pos-lib-FooterBoxes-MultiTotal'
    );
}
$footer_mods = AutoLoader::listModules('COREPOS\\pos\\lib\\FooterBoxes\\FooterBox');
$footer_mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $footer_mods);
$current_mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $current_mods);
for($i=0;$i<5;$i++){
    echo '<select name="FOOTER_MODS[]">';
    foreach($footer_mods as $fm){
        $match = false;
        if ($current_mods[$i] == $fm) {
            $match = true;
        } elseif (substr($fm, -1*(strlen($current_mods[$i])+1)) == '-' . $current_mods[$i]) {
            $match = true;
        }
        printf('<option %s>%s</option>',
            ($match?'selected':''),$fm);
    }
    echo '</select><br />';
}
$current_mods = array_map(function($i){ return str_replace('-', '\\', $i); }, $current_mods);
InstallUtilities::paramSave('FooterModules',$current_mods);
?>
</td></tr>
<tr>
    <td><b><?php echo _('Notifier Modules'); ?></b>:</td>
    <td>
    <?php
    // get current settings
    $notifiers = AutoLoader::listModules('COREPOS\\pos\\lib\\Notifier');
    echo $form->selectField('Notifiers', 
        $notifiers, 
        array(), 
        Conf::EITHER_SETTING, 
        true, 
        array('size'=>5,'multiple'=>'multiple')
    );
    ?>
    <span class='noteTxt'><?php echo _('Notifiers are displayed on the right below the scale weight'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Enable onscreen keys'); ?></b>:</td>
    <td><?php echo $form->selectField('touchscreen', array(true=>_('Yes'), false=>_('No')), false); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Separate customer display'); ?></b>:</td>
    <td><?php echo $form->selectField('CustomerDisplay', array(1=>_('Yes'), 0=>_('No')), 0); ?></td>
</tr>
<tr>
    <td colspan=2>
    <p><?php echo _('Touchscreen keys and menus really don\'t need to appear on
    the customer-facing display. Experimental feature where one
    window always shows the item listing. Very alpha.'); ?></p>
    </td>
</tr>
<tr>
    <td colspan=2 class="tblHeader"><h3><?php echo _('Subtotal Settings'); ?></h3></td>
</tr>
<!-- Normal/default Yes/True -->
<tr>
    <td><b><?php echo _('Member ID trigger subtotal'); ?></b>:</td>
    <td><?php echo $form->selectField('member_subtotal', array(true=>_('Yes'), false=>_('No')), true); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Subtotal Actions'); ?></b></td>
    <td rowspan="2">
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\TotalActions\\TotalAction');
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    echo $form->selectField('TotalActions',
        $mods,
        array(),
        Conf::EITHER_SETTING,
        true,
        array('multiple'=>'multiple', 'size'=>5)
    );
    CoreLocal::set('TotalActions', array_map(function($i){ return str_replace('-', '\\', $i); }, CoreLocal::get('TotalActions')));
    InstallUtilities::paramSave('TotalActions', CoreLocal::get('TotalActions'));
    ?>
    </td>
</tr>
<tr>
    <td><?php echo _('These are additional bits of functionality that
    will occur whenever a transaction is subtotalled.'); ?></td>
</tr>
<tr>
    <td colspan=2 class="tblHeader"><h3><?php echo _('Tender Settings'); ?></h3></td>
</tr>
<tr>
    <td><b><?php echo _('Tender min/max limits'); ?></b>: </td>
    <td><?php echo $form->selectField('TenderHardMinMax', array(1=>_('Absolute Limit'),0=>_('Warning Only')), 0); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Allow members to write checks over purchase amount'); ?></b>: </td>
    <td><?php echo $form->selectField('cashOverLimit', array(1=>_('Yes'),0=>_('No')), 0); ?></td>
</tr>
<tr>
    <td><b><?php echo _('Check over limit'); ?></b>:</td>
    <td>$<?php echo $form->textField('dollarOver', 0); ?></td>
</tr>
<tr>
    <td><b><?php echo _('EBT Total Default'); ?></b>: </td>
    <td>
    <?php 
    $ebtOpts = array(1 => _('Cash Side'), 0 => _('Food Side'));
    echo $form->selectField('fntlDefault', $ebtOpts, 1);
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Tender Report'); ?></b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\TenderReports\\TenderReport');
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    sort($mods);
    echo $form->selectField('TenderReportMod', $mods, 'COREPOS-pos-lib-ReceiptBuilding-DefaultTenderReport');
    CoreLocal::set('TenderReportMod', str_replace('-', '\\', CoreLocal::get('TenderReportMod')));
    InstallUtilities::paramSave('TenderReportMod', CoreLocal::get('TenderReportMod'));
    ?>
    </td>
</tr>
<tr><td>
<b><?php echo _('Tender Mapping'); ?></b>:<br />
<p><?php echo _('Map custom tenders to CORE\'s expected tenders Tender Rpt. column: Include the checked tenders 
    in the Tender Report (available via Mgrs. Menu [MG])'); ?></p></td><td>
<?php
$settings = CoreLocal::get("TenderMap");
$db = Database::pDataConnect();
$tender_table = $db->tableDefinition('tenders');
/**
  Load tender map from database if
  the schema supports it
*/
if (isset($tender_table['TenderModule'])) {
    $model = new \COREPOS\pos\lib\models\op\TendersModel($db);
    $settings = $model->getMap();
}
if (!is_array($settings)) $settings = array();
if (is_array(FormLib::get('TenderMapping'))) {
    $settings = array();
    foreach (FormLib::get('TenderMapping') as $tm) {
        if ($tm=="") {
            continue;
        }
        list($code, $mod) = explode(":", $tm);
        $settings[$code] = str_replace('-', '\\', $mod);
    }
    if (!isset($tender_table['TenderModule'])) {
        InstallUtilities::paramSave('TenderMap',$settings);
    } else {
        /**
          Save posted mapping to database
          First update the records where a non-default
          module is specified, then set everything
          else to default
        */
        $not_default_sql = '';
        $not_default_args = array();
        $saveP = $db->prepare('
            UPDATE tenders
            SET TenderModule=?
            WHERE TenderCode=?');
        foreach ($settings as $code => $module) {
            $db->execute($saveP, array($module, $code));
            $not_default_sql .= '?,';
            $not_default_args[] = $code;
        }
        if (count($not_default_args) > 0) {
            $not_default_sql = substr($not_default_sql, 0, strlen($not_default_sql)-1);
            $defaultP = $db->prepare('
                UPDATE tenders
                SET TenderModule=\'TenderModule\'
                WHERE TenderCode NOT IN (' . $not_default_sql . ')');
            $db->execute($defaultP, $not_default_args);
        } else {
            $db->query("UPDATE tenders SET TenderModule='TenderModule'");
        }
        CoreLocal::set('TenderMap', $settings);
    }
}
$mods = AutoLoader::listModules('COREPOS\\pos\\lib\\Tenders\\TenderModule');
$mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
//  Tender Report: Desired tenders column
$settings2 = CoreLocal::get("TRDesiredTenders");
if (!is_array($settings2)) $settings2 = array();
if (is_array(FormLib::get('TR_LIST'))) {
    $saveStr2 = "array(";
    $settings2 = array();
    foreach(FormLib::get('TR_LIST') as $dt){
        if($dt=="") continue;
        list($code2,$name2) = explode(":",$dt);
        $settings2[$code2] = $name2;
        $saveStr2 .= "'".$code2."'=>'".addslashes($name2)."',";
    }
    $saveStr2 = rtrim($saveStr2,",").")";
    InstallUtilities::paramSave('TRDesiredTenders',$settings2);
} //end TR desired tenders
$db = Database::pDataConnect();
$res = $db->query("SELECT TenderCode, TenderName FROM tenders ORDER BY TenderName");
?>
<table cellspacing="0" cellpadding="4" border="1">
<?php
echo "<thead><tr><th>" . _('Tender Name') . "</th><th>" . _('Map To') . "</th><th>" . _('Tender Rpt') . "</th></tr></thead><tbody>\n";
while($row = $db->fetch_row($res)){
    printf('<tr><td>%s (%s)</td>',$row['TenderName'],$row['TenderCode']);
    echo '<td><select name="TenderMapping[]">';
    echo '<option value="">' . _('default') . '</option>';
    foreach($mods as $m){
        /**
          Map unnamespaced values to namespaced values
          so the configuration doesn't break
        */
        $selected = false;
        if (isset($settings[$row['TenderCode']])) {
            $current = str_replace('\\', '-', $settings[$row['TenderCode']]);
            if ($current == $m) {
                $selected = true; // direct match
            } elseif (substr($m, -1*(strlen($current)+1)) == '-' . $current) {
                $selected = true; // namespace match
            }
        }
        printf('<option value="%s:%s" %s>%s</option>',
            $row['TenderCode'],$m,
            ($selected ? 'selected' : ''),
            $m);    
    }
    echo '</select></td>';
    echo "<td><input type=checkbox name=\"TR_LIST[]\" ";
    echo 'value="'.$row['TenderCode'].':'.$row['TenderName'].'"';
    if (array_key_exists($row['TenderCode'], $settings2)) echo " CHECKED";
    echo "></td></tr></tbody>";
}
?>
</table>

</td></tr>
<!--
<tr><td colspan=2 class="tblHeader">
<h3>Various</h3>
<p>This group was started in order to handle variations as options rather than per-coop code variations.</p>
<h4 style="margin: 0.25em 0.0em 0.25em 0.0em;">Related to transactions:</h4></td></tr>
-->

<tr><td colspan=2 class="submitBtn">
<input type=submit name=esubmit value="<?php echo _('Save Changes'); ?>" />
</td></tr>
</table>
</form>
</div> <!--    wrapper -->
</body>
</html>
