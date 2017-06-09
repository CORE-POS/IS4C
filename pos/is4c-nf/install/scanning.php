<?php
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\FormFactory;
use COREPOS\pos\install\InstallUtilities;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Scanning\DiscountType;
use COREPOS\pos\lib\Scanning\PriceMethod;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
$form = new FormFactory(InstallUtilities::dbOrFail(CoreLocal::get('pDatabase')));
?>
<html>
<head>
<title>IT CORE Lane Installation: Scanning options</title>
<style type="text/css">
body {
    line-height: 1.5em;
}
</style>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2><?php echo _('IT CORE Lane Installation: Scanning Options'); ?></h2>

<div class="alert"><?php Conf::checkWritable('../ini.json', False, 'JSON'); ?></div>
<div class="alert"><?php Conf::checkWritable('../ini.php', False, 'PHP'); ?></div>

<form action=scanning.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
<td colspan="2">
    <hr />
    <b><?php echo _('Check Digits'); ?></b>
    <p><?php echo _('
    Most users omit check digits. If you have no particular preference, this
    option is more thoroughly tested. Mixing and matching is not recommended.
    It should work but uniformly omitting or including check digits for
    all barcodes will make some back end tasks easier.'); ?>
    </p>
</td>
</tr>
<tr>
    <td style="width: 30%;">
        <b><?php echo _('UPCs'); ?></b>
    </td>
    <td>
    <?php
    $checkOpts = array(1=>_('Include Check Digits'), 0=>_('Omit Check Digits'));
    echo $form->selectField('UpcIncludeCheckDigits', $checkOpts, 0);
    ?>
    </td>
</tr>
<tr>
<td colspan="2">
    <hr />
    <b><?php echo _('Forced Keyed Weight'); ?></b>
    <p><?php echo _('
    By-weight / scaleable items will prompt the cashier to key a weight when QtyFrc is also enabled.
    This is commonly used when items must be weighed on a non-integrated scale. In Three Digit Thousandths
    mode the cashier must enter exactly three digits and these will be interpretted as thousandths of a
    pound (i.e., 123 = 0.123 lb.). This is intended for very light items. In Verbatim mode the cashier\'s
    entry is taken exactly as entered, with or without decimal. This provides greater flexibility but cannot
    enforce a range of valid values.'); ?>
    </p>
</td>
</tr>
<tr>
    <td style="width: 30%;">
        <b><?php echo _('Entry Mode'); ?></b>
    </td>
    <td>
    <?php
    $opts = array(1=>_('Verbatim'), 0=>_('Three Digit Thousandths'));
    echo $form->selectField('ManualWeightMode', $opts, 0);
    ?>
    </td>
</tr>
<tr>
    <td style="width: 30%;">
        <b><?php echo _('EANs'); ?></b>
    </td>
    <td>
    <?php
    echo $form->selectField('EanIncludeCheckDigits', $checkOpts, 0);
    ?>
    </td>
</tr>
<tr>
    <td colspan="2"><hr /></td>
</tr>
<tr>
<tr>
    <td><b><?php echo _('Unknown Item Handler'); ?></b></td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ItemNotFound', true);
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    ?>
    <td><?php echo $form->selectField('ItemNotFound', $mods, 'ItemNotFound'); ?>
    <?php
    $val = str_replace('-', '\\', CoreLocal::get('ItemNotFound'));
    InstallUtilities::paramSave('ItemNotFound', $val);
    ?>
    <span class='noteTxt'><?php echo _('Module called when a UPC does not match any item or Special UPC handler'); ?></span>
    </td>
</tr>
    <td style="width:30%;">
    <b><?php echo _('Special UPCs'); ?></b>:<br />
    <p><?php echo _('Special handling modules for UPCs that aren\'t products (e.g., coupons)'); ?></p>
    </td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\Scanning\\SpecialUPC');
    $mods = array_map(function($i){ return str_replace('\\', '-', $i); }, $mods);
    echo $form->selectField('SpecialUpcClasses',
        $mods,
        array(),
        Conf::EITHER_SETTING,
        true,
        array('multiple'=>'multiple', 'size'=>10)
    );
    CoreLocal::set('SpecialUpcClasses', array_map(function($i){ return str_replace('-', '\\', $i); }, CoreLocal::get('SpecialUpcClasses')));
    InstallUtilities::paramSave('SpecialUpcClasses', CoreLocal::get('SpecialUpcClasses'));
    ?>
    </td>
</tr>
<tr>
    <td><b><?php echo _('House Coupon Prefix'); ?></b></td>
    <td><?php echo $form->textField('houseCouponPrefix', '00499999'); ?>
    <span class='noteTxt'><?php echo _('Set the barcode prefix for houseCoupons.  Should be 8 digits starting with 004. Default is 00499999.'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Coupons & Sales Tax'); ?></b>:</td>
    <td>
    <?php
    $couponTax = array(1=>_('Tax pre-coupon total'), 0=>_('Tax post-coupon total'));
    echo $form->selectField('CouponsAreTaxable', $couponTax, 1);
    ?>
    <span class='noteTxt'><?php echo _('Apply sales tax based on item price before any coupons, or
    apply sales tax to item price inclusive of coupons.'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Enforce coupons family codes'); ?></b>:</td>
    <td>
    <?php
    $familyCode = array(1=>_('Yes'), 0=>_('No'));
    echo $form->selectField('EnforceFamilyCode', $familyCode, 0);
    ?>
    <span class='noteTxt'><?php echo _('Match both manufacturer prefixes and family codes
    on coupons rather than just manufacturer prefixes.'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Equity Department(s)'); ?></b></td>
    <td>
    <?php
    // try to find a sane default automatically
    $default = array();
    $dbc = Database::pDataConnect();
    $lookup = $dbc->query("SELECT dept_no FROM departments WHERE dept_name LIKE '%EQUIT%'");
    if ($lookup && $dbc->num_rows($lookup) > 0) {
        while ($row = $dbc->fetch_row($lookup)) {
            $default[] = $row['dept_no'];
        }
    }
    echo $form->textField('EquityDepartments', $default, Conf::EITHER_SETTING, false);
    ?>
    <span class='noteTxt'><?php echo _('Set the department number(s) that are considered member equity'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Open Ring Min/Max Limits'); ?></b></td>
    <td>
    <?php
    echo $form->selectField('OpenRingHardMinMax', array(1=>_('Absolute Limit'), 0=>_('Warning Only')), 0);
    ?>
    <span class='noteTxt'>
    <?php echo _('Set whether open ring department limits are bypassable warnings or complete blocks.'); ?>
    </span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('AR Department(s)'); ?></b></td>
    <td>
    <?php
    // try to find a sane default automatically
    $default = array();
    $dbc = Database::pDataConnect();
    $lookup = $dbc->query("SELECT dept_no FROM departments WHERE dept_name LIKE '%PAYMENT%'");
    if ($lookup && $dbc->num_rows($lookup) > 0) {
        while ($row = $dbc->fetch_row($lookup)) {
            $default[] = $row['dept_no'];
        }
    }
    echo $form->textField('ArDepartments', $default, Conf::EITHER_SETTING, false);
    ?>
    <span class='noteTxt'><?php echo _('Set the department number(s) that are store charge balance payments. 
        Also known as AR or accounts receivable.'); ?></span>
    </td>
</tr>
<tr>
    <td><b><?php echo _('Donation Department'); ?></b></td>
    <td>
    <?php
    // try to find a sane default automatically
    $default = 701;
    $dbc = Database::pDataConnect();
    $lookup = $dbc->query("SELECT dept_no FROM departments WHERE dept_name LIKE '%DONAT%'");
    if ($lookup && $dbc->num_rows($lookup) > 0) {
        $row = $dbc->fetch_row($lookup);
        $default = $row['dept_no'];
    }
    echo $form->textField('roundUpDept', $default);
    ?>
    <span class='noteTxt'><?php echo _('Set the department number for lines entered via the "round up" donation function.'); ?></span>
    </td>
</tr>
<tr>
    <td colspan="2">
    <hr />
    <p><?php echo _('Discount type modules control how sale discounts are calculated.'); ?></p>
    </td>
</tr>
<tr><td>
<b><?php echo _('Default Discounts'); ?></b>:</td><td>
<?php
foreach (DiscountType::$MAP as $id => $name) {
    echo '[' . $id . '] => ' . $name . '<br />';
}
?>
</td></tr>
<tr><td>
<b><?php echo _('Custom Discount Mapping'); ?></b>:</td><td>
<?php
if (is_array(FormLib::get('DT_MODS'))) {
    $new_val = array();
    foreach (FormLib::get('DT_MODS') as $r) {
        if ($r !== '' && !in_array($r, DiscountType::$MAP)) {
            $new_val[] = $r;
        }
    }
    CoreLocal::set('DiscountTypeClasses', $new_val);
}
if (!is_array(CoreLocal::get('DiscountTypeClasses'))) {
    CoreLocal::set('DiscountTypeClasses', array(), true);
}
$discounts = AutoLoader::listModules('COREPOS\\pos\\lib\\Scanning\\DiscountType');
$dt_conf = CoreLocal::get("DiscountTypeClasses");
$dt_conf[] = ''; // add blank slot for adding another discounttype
$i = 64;
foreach ($dt_conf as $entry) {
    echo '[' . $i . '] => ';
    echo "<select name=DT_MODS[]>";
    echo '<option value="">' . _('[None]') . '</option>';
    foreach($discounts as $d) {
        if (in_array($d, DiscountType::$MAP)) {
            continue;
        }
        echo "<option";
        if ($entry == $d)
            echo " selected";
        echo ">$d</option>";
    }
    echo "</select><br />";
    $i++;
}
$save = array();
foreach(CoreLocal::get("DiscountTypeClasses") as $r){
    if ($r !== '' && !in_array($r, DiscountType::$MAP)) {
        $save[] = $r;
    }
}
InstallUtilities::paramSave('DiscountTypeClasses',$save);
?></td></tr>

<tr><td colspan=2>
<hr />    <p><?php echo _('Price Methods dictate how item prices are calculated.
    There\'s some overlap with Discount Types, but <i>often</i>
    price methods deal with grouped items.'); ?></p></td></tr>
</td></tr>
<tr><td>
<b><?php echo _('Default Methods'); ?></b>:</td><td>
<?php
foreach (PriceMethod::$MAP as $id => $name) {
    echo '[' . $id . '] => ' . $name . '<br />';
}
?>
</td></tr>
<tr><td>
<b><?php echo _('Custom Method Mapping'); ?></b>:</td><td>
<?php
if (is_array(FormLib::get('PM_MODS'))) {
    $new_val = array();
    foreach (FormLib::get('PM_MODS') as $r) {
        if ($r !== '' && !in_array($r, PriceMethod::$MAP)) {
            $new_val[] = $r;
        }
    }
    CoreLocal::set('PriceMethodClasses', $new_val);
}
if (!is_array(CoreLocal::get('PriceMethodClasses'))){
    CoreLocal::set('PriceMethodClasses', array(), true);
}
$pms = AutoLoader::listModules('COREPOS\\pos\\lib\\Scanning\\PriceMethod');
$pm_conf = CoreLocal::get("PriceMethodClasses");
$pm_conf[] = ''; // add blank slot for adding another method
$i = 100;
foreach ($pm_conf as $entry) {
    echo "[$i] => ";
    echo "<select name=PM_MODS[]>";
    echo '<option value="">' . _('[None]') . '</option>';
    foreach($pms as $p) {
        if (in_array($p, PRiceMethod::$MAP)) {
            continue;
        }
        echo "<option";
        if ($entry == $p)
            echo " selected";
        echo ">$p</option>";
    }
    echo "</select><br />";
    $i++;
}
$save = array();
foreach(CoreLocal::get("PriceMethodClasses") as $r){
    if ($r !== '' && !in_array($r, PriceMethod::$MAP)) {
        $save[] = $r;
    }
}
InstallUtilities::paramSave('PriceMethodClasses',$save);
?></td></tr>
<tr><td>
<b><?php echo _('Sale Items Are Discountable'); ?></b>:</td><td>
<?php
if (FormLib::get('SALEDISC') !== '') CoreLocal::set('DiscountableSaleItems', FormLib::get('SALEDISC'));
if (CoreLocal::get('DiscountableSaleItems') === '') CoreLocal::set('DiscountableSaleItems', 1);
echo '<select name="SALEDISC">';
if (CoreLocal::get('DiscountableSaleItems') == 0) {
    echo '<option value="1">' . _('Yes') . '</option>';
    echo '<option value="0" selected>' . _('No') . '</option>';
} else {
    echo '<option value="1" selected>' . _('Yes') . '</option>';
    echo '<option value="0">' . _('No') . '</option>';
}
echo '</select>';
InstallUtilities::paramSave('DiscountableSaleItems', CoreLocal::get('DiscountableSaleItems'));
?>
<span class='noteTxt'><?php echo _('
Items that are on sale are eligible for transaction-level discounts - e.g., members
save 5%.'); ?>
</span>
</td></tr>
<tr><td colspan=2>
<hr />    <p><?php echo _('Special Department modules add extra steps to open rings in specific departments.
    Enter department number(s) that each module should apply to.*'); ?></p>
</td></tr>
<tr><td>
<?php
$sdepts = AutoLoader::listModules('COREPOS\\pos\\lib\\Scanning\\SpecialDept');
$sdepts = array_map(function($i){ return str_replace('\\', '-', $i); }, $sdepts);
$dbc = Database::pDataConnect();
$specialDeptMapExists = $dbc->table_exists('SpecialDeptMap');
$mapModel = new \COREPOS\pos\lib\models\op\SpecialDeptMapModel($dbc);
$sconf = CoreLocal::get('SpecialDeptMap');
/**
  If a mapping exists and the new table is available,
  migrate existing settings to the table and remove
  the setting from ini 
*/
if (is_array($sconf) && $specialDeptMapExists) {
    $mapModel->initTable($sconf);
}
if (!is_array($sconf)) $sconf = array();
if (is_array(FormLib::get('SDEPT_MAP_LIST'))) {
    if ($specialDeptMapExists) {
        $dbc->query('TRUNCATE TABLE SpecialDeptMap');
    } else {
        $sconf = array();
    }
    $SDEPT_MAP_LIST = FormLib::get('SDEPT_MAP_LIST');
    $SDEPT_MAP_NAME = FormLib::get('SDEPT_MAP_NAME');
    for ($i=0;$i<count($SDEPT_MAP_NAME);$i++) {
        if (!isset($SDEPT_MAP_LIST[$i])) continue;
        if (empty($SDEPT_MAP_LIST[$i])) continue;

        $class = str_replace('-', '\\', $SDEPT_MAP_NAME[$i]);
        $ids = preg_split('/\D+/',$SDEPT_MAP_LIST[$i]);
        foreach ($ids as $id) {
            if ($specialDeptMapExists) {
                $mapModel->reset();
                $mapModel->specialDeptModuleName($class);
                $mapModel->dept_no($id);
                $mapModel->save();
            } else {
                $obj = new $class();
                $sconf = $obj->register($id,$sconf);
            }
        }
    }
    if (!$specialDeptMapExists) {
        CoreLocal::set('SpecialDeptMap',$sconf);
    }
}
if ($specialDeptMapExists) {
    $mapModel->reset();
    $sconf = $mapModel->buildMap();
} else {
    $sconf = CoreLocal::get('SpecialDeptMap');
}
$session = new WrappedStorage();
foreach ($sdepts as $sd) {
    $sclass = str_replace('-', '\\', $sd);
    $obj = new $sclass($session);
    $list = '';
    foreach($sconf as $id => $mods){
        if (in_array($sclass,$mods))
            $list .= $id.', ';
    }
    $list = rtrim($list,', ');
    printf('<tr><td title="%s">%s</td><td>
        <input type="text" name="SDEPT_MAP_LIST[]" value="%s" />
        <input type="hidden" name="SDEPT_MAP_NAME[]" value="%s" />
        </td></tr>',
        $obj->help_summary,$sd,$list,$sd);
}
if (!$specialDeptMapExists) {
    $saveStr = 'array(';
    foreach($sconf as $id => $mods){
        if (empty($mods)) continue;
        $saveStr .= $id.'=>array(';
        foreach($mods as $m)
            $saveStr .= '\''.$m.'\',';
        $saveStr = rtrim($saveStr,',').'),';
    }
    $saveStr = rtrim($saveStr,',').')';
    Conf::save('SpecialDeptMap',$saveStr);
}
?>
</td></tr>
<tr><td colspan=2>
<hr />
</td></tr>
<tr>
    <td colspan=2>
    <b><?php echo _('Variable Weight Item Mapping'); ?></b> <?php echo _('(UPC Prefix "2")'); ?>:<br />
    <?php echo _('Variable-weight items do not have identical barcodes because the
    price is encoded in the barcode. A translator is required to map
    these different barcodes back to one logical product.'); ?>
    </td>
</tr>
<tr>
    <td>
    <b><?php echo _('Translator'); ?></b>:
    </td>
    <td>
    <?php
    $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\Scanning\\VariableWeightReWrite');
    echo $form->selectField('VariableWeightReWriter', $mods, 'ZeroedPriceReWrite');
    ?>
    </td>
</tr>
<tr><td colspan=2>
<hr />
</td></tr>
<tr><td>
<input type=submit name=scansubmit value="<?php echo _('Save Changes'); ?>" />
</td></tr></table>
</form>
</div> <!--    wrapper -->
</body>
</html>
