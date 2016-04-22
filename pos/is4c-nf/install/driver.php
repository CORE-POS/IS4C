<!DOCTYPE html>
<html>
<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
include('InstallUtilities.php');
$known_good_modules = array(
    'SPH_Magellan_Scale' => array(
        'description' => 'Magellan Scanner/scale',
        'common-ports' => array('COM*', '/dev/ttyS*'),
    ),
    'SPH_SignAndPay_USB' => array(
        'description' => 'ID Tech Sign&Pay (recommended)',
        'common-ports' => array('USB', '/dev/hidraw*'),
    ),
    'SPH_Datacap_PDCX' => array(
        'description' => 'Datacap ActiveX compatible devices (Windows-only)',
        'common-ports' => array(
            'VX805XPI:*' => 'Verifone VX805 on COM*',
            'VX805XPI_MERCURY_E2E:*' => 'Verifone VX805 with Mercury encryption on COM*',
        ),
    ),
    'SPH_Datacap_EMVX' => array(
        'description' => 'Datacap ActiveX compatible devices with EMV support (Windows-only)',
        'common-ports' => array(
            'VX805XPI:*' => 'Verifone VX805 on COM*',
            'VX805XPI_MERCURY_E2E:*' => 'Verifone VX805 with Mercury encryption on COM*',
        ),
    ),
    'SPH_SignAndPay_Native' => array(
        'description' => 'ID Tech Sign&Pay (not recommended in Linux)',
        'common-ports' => array('USB', '/dev/ttyS*'),
    ),
    'SPH_SignAndPay_Auto' => array(
        'description' => 'ID Tech Sign&Pay (not recommended)',
        'common-ports' => array('USB', '/dev/hidraw*'),
    ),
);
function expand_port_list($list)
{
    $expanded = array();
    foreach ($list as $key => $val)
    {
        if (is_numeric($key)) {
            $key = $val;
        }
        if (substr($key, -1) == '*') {
            for ($i=0; $i<10;$i++) {
                $real_key = substr($key, 0, strlen($key)-1) . $i;
                if (substr($val, -1) == '*') {
                    $real_val = substr($val, 0, strlen($val)-1) . $i;
                } else {
                    $real_val = $val;
                }
                $expanded[$real_key] = $real_val;
            }
        } else {
            $expanded[$key] = $val;
        }
    }

    return $expanded;
}
?>
<head>
<title>IT CORE Lane Installation: NewMagellan Driver</title>
<link rel="stylesheet" href="../css/toggle-switch.css" type="text/css" />
<script type="text/javascript" src="../js/jquery.js"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">    
<h2>IT CORE Lane Installation: Hardware Driver Settings (NewMagellan)</h2>

<div class="alert"><?php InstallUtilities::checkWritable('../ini.json', false, 'JSON'); ?></div>

<form action=driver.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
    <td colspan=3 class="tblHeader"><h3>General Settings</h3></td>
</tr>
<tr>
    <td style="width: 30%;"><b>Add Device</b>:</td>
    <td>
    <select name="new-module" title="Stored in ini.json">
    <option value="n/a">Select device...</option>
    <?php
    foreach ($known_good_modules as $mod => $info) {
        printf('<option value="%s">%s</option>',
            $mod, (isset($info['description']) ? $info['description'] : $mod)
        );
    }
    ?>
    </select>
    </td>
</tr>
<tr>
    <td colspan=3 class="tblHeader"><h3>Current Devices</h3></td>
</tr>
<?php
$json = json_decode(file_get_contents('../ini.json'), true);
if (!is_array($json)) {
    $json = array();
}
if (!isset($json['NewMagellanPorts'])) {
    $json['NewMagellanPorts'] = array();
}
if (isset($_POST['new-module']) && isset($known_good_modules[$_POST['new-module']])) {
    $expanded = expand_port_list($known_good_modules[$_POST['new-module']]['common-ports']);
    $json['NewMagellanPorts'][] = array(
        'module' => $_POST['new-module'],
        'port'=> array_shift($expanded),
    );
}
$valid = array();
$i = 0;
$post_ports = isset($_POST['port']) ? $_POST['port'] : array();
$delete = isset($_POST['delete']) ? $_POST['delete'] : array();
foreach ($json['NewMagellanPorts'] as $port) {
    if (!is_array($port) || !isset($port['port']) || !isset($port['module'])) {
        continue;
    }
    if (in_array($i, $delete)) {
        // do not add as valid but advance the counter
        // so subsequent fields still line up
        $i++;
        continue;
    }
    if (isset($post_ports[$i])) {
        $port['port'] = $post_ports[$i];
    }
    $valid[] = $port;
    $name = $port['module'];
    if (isset($known_good_modules[$name]) && isset($known_good_modules[$name]['description'])) {
        $name = $known_good_modules[$name]['description'];
    }
    echo '<tr>';
    printf('<td style="width: 40%%;"><b>Device</b>: %s</td>', $name);
    printf('<input type="hidden" name="entryID[]" value="%d" />', $i);
    echo '<td><b>Port</b>: <select name="port[]" title="Stored in ini.json">';
    if (isset($known_good_modules[$port['module']])) {
        foreach (expand_port_list($known_good_modules[$port['module']]['common-ports']) as $k=>$v) {
            printf('<option %s value="%s">%s</option>',
                ($port['port'] == $k ? 'selected' : ''),
                $k, $v);
        }
    } else {
        echo '<option>' . $port['port'] . '</option>';
    }
    echo '</select></td>';
    printf('<td><label>Delete entry <input type="checkbox" name="delete[]" value="%d" /></label></td>', $i);
    echo '</tr>';
    $i++;
}
$json['NewMagellanPorts'] = $valid;
InstallUtilities::jsonConfSave('NewMagellanPorts', $json['NewMagellanPorts']);
?>
<tr>
    <td colspan=3 class="tblHeader">&nbsp;</td>
</tr>
</table>
<input type="submit" value="Save Changes" />
</form>
</body>
</html>
