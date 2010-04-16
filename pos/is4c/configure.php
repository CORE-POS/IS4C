<?php
    include_once("connect.php");
    include_once("lib/query.php");
    include_once("lib/conf.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Lane Configuration</title>
        <link rel="stylesheet" type="text/css" href="css/is4c.css" />
    </head>
    <script type="text/javascript" src="/js/jquery-1.4.2.min.js"></script>
    <body>
        <table id='login'>
            <tr>
                <td id='is4c_header'>
                    <b>I S 4 C</b>
                </td>
                <td id='full_header'>
                    L A N E &nbsp; C O N F I G U R A T I O N
                </td>
            </tr>
            <tr>
                <td id='line' colspan='2'></td>
            </tr>
            <tr>
                <td id='welcome'>
                   S E T U P
                </td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <div class='config_forms'>
                        <form action='/lib/apply_configurations.php' method='post'>
                            <input type='submit' value='Save Changes' />
                            <?php
                                $groups = get_configuration_groups();
                                foreach ($groups as $group) {
                            ?>
                            <fieldset>
                                <legend><?=$group["group_name"]?></legend>
                                <?php
                                    $configs = get_configuration_settings($group["group_id"]);
                                    foreach ($configs as $config) {
                                ?>
                                <label for='<?=$config["key"]?>'><?=$config["key"]?>:</label>
                                <input type='<?=$config["type"]=="flag"?'checkbox':'text'?>' <?=$config["type"]=="flag"&&$config["value"]==1?'checked=\'checked\'':''?> value='<?=$config["value"]?>' name='<?=$config["key"]?>' id='<?=$config["key"]?>' /> <?=get_config_auto($config["key"])?> <br />
                                <?php } ?>
                                    
                            </fieldset>
                                <?php
                                }
                            ?>
<!--                            <fieldset>
                                <legend>General:</legend>
                                <label for='general_os'>Operating System:</label>
                                <input type='text' value="<?=get_os($contents)?>" name='general_os' id='general_os' size='6' /> <input type='button' value='Auto' onclick="$('#general_os').attr('value', '<?=strtolower(PHP_OS)?>'); return false" /><br />
                                <label for='general_store'>Store Name:</label>
                                <input type='text' value="<?=get_store_name($contents)?>" name='general_store' id='general_store' size='20' /><br />
                                <label for='general_lane'>Lane Number:</label>
                                <input type='text' value="<?=get_lane_number($contents)?>" name='general_lane' id='general_lane' size='1' />
                            </fieldset>
                            <fieldset>
                                <legend>Server Database</legend>
                                <label for='server_ip'>IP Address:</label>
                                <input type='text' value="<?=get_server_ip($contents)?>" name='server_ip' id='server_ip' size='10' /><br />
                                <label for='server_type'>Database Type:</label>
                                <input type='text' value="<?=get_server_database_type($contents)?>" name='server_type' id='server_type' size='6' /><br />
                                <label for='server_database'>Log Database:</label>
                                <input type='text' value="<?=get_server_database($contents)?>" name='server_database' id='server_database' size='6' /><br />
                                <label for='server_username'>User Name:</label>
                                <input type='text' value="<?=get_server_username($contents)?>" name='server_username' id='server_username' size='6' /><br />
                                <label for='server_password'>Password:</label>
                                <input type='password' value="<?=get_server_password($contents)?>" name='server_password' id='server_password' size='6' /><br />                                
                            </fieldset>
                            <fieldset>
                                <legend>Local Database:</legend>
                                <label for='local_ip'>IP Address:</label>
                                <input type='text' value="<?=get_local_ip($contents)?>" name='local_ip' id='local_ip' size='10' /> <input type='button' value='Auto' onclick="$('#local_ip').attr('value', '<?=$_SERVER['SERVER_ADDR']?>'); return false" /><br />
                                <label for='local_type'>Database Type:</label>
                                <input type='text' value="<?=get_local_database_type($contents)?>" name='local_type' id='local_type' size='6' /><br />
                                <label for='local_ops'>Operations Database:</label>
                                <input type='text' value="<?=get_local_op_database($contents)?>" name='local_ops' id='local_ops' size='6' /><br />
                                <label for='local_trans'>Transaction Database:</label>
                                <input type='text' value="<?=get_local_trans_database($contents)?>" name='local_trans' id='local_trans' size='6' /><br />
                                <label for='local_username'>User Name:</label>
                                <input type='text' value="<?=get_local_username($contents)?>" name='local_username' id='local_username' size='6' /><br />
                                <label for='local_password'>Password:</label>
                                <input type='password' value="<?=get_local_password($contents)?>" name='local_password' id='local_password' size='6' /><br />
                            </fieldset>
                            <fieldset>
                                <legend>Receipt and Printer Settings</legend>
                                <label for='printer_active'>Printer Active:</label>
                                <input type='checkbox' value='1' <?=get_print_flag($contents) != 0 ? "checked='checked'" : " " ?> name='printer_active' id='printer_active' /> <br />
                                <label for='printer_port'>Printer Port:</label>
                                <input type='text' value="<?=get_printer_port($contents)?>" name='printer_port' id='printer_port' /> <br />
                                <label for='receipt_header_1'>Receipt Header Line 1:</label>
                                <input type='text' value="<?=get_receipt_header_1($contents)?>" name='receipt_header_1' id='receipt_header_1' /> <br />
                                <label for='receipt_header_2'>Receipt Header Line 2:</label>
                                <input type='text' value="<?=get_receipt_header_2($contents)?>" name='receipt_header_2' id='receipt_header_2' /> <br />
                                <label for='receipt_header_3'>Receipt Header Line 3:</label>
                                <input type='text' value="<?=get_receipt_header_3($contents)?>" name='receipt_header_3' id='receipt_header_3' /> <br />
                                <label for='receipt_footer_1'>Receipt Footer Line 1:</label>
                                <input type='text' value="<?=get_receipt_footer_1($contents)?>" name='receipt_footer_1' id='receipt_footer_1' /> <br />
                                <label for='receipt_footer_2'>Receipt Footer Line 2:</label>
                                <input type='text' value="<?=get_receipt_footer_2($contents)?>" name='receipt_footer_2' id='receipt_footer_2' /> <br />
                                <label for='receipt_footer_3'>Receipt Footer Line 3:</label>
                                <input type='text' value="<?=get_receipt_footer_3($contents)?>" name='receipt_footer_3' id='receipt_footer_3' /> <br />
                                <label for='receipt_footer_4'>Receipt Footer Line 4:</label>
                                <input type='text' value="<?=get_receipt_footer_4($contents)?>" name='receipt_footer_4' id='receipt_footer_4' /> <br />
                            </fieldset>
                            <fieldset>
                                <legend>Check and Charge Slip Settings</legend>
                                <label for='check_endorse_1'>Check Endorse Line 1:</label>
                                <input type='text' value="<?=get_check_endorse_1($contents)?>" name='check_endorse_1' id='check_endorse_1' /> <br />
                                <label for='check_endorse_2'>Check Endorse Line 2:</label>
                                <input type='text' value="<?=get_check_endorse_2($contents)?>" name='check_endorse_2' id='check_endorse_2' /> <br />
                                <label for='check_endorse_3'>Check Endorse Line 3:</label>
                                <input type='text' value="<?=get_check_endorse_3($contents)?>" name='check_endorse_3' id='check_endorse_3' /> <br />
                                <label for='check_endorse_4'>Check Endorse Line 4:</label>
                                <input type='text' value="<?=get_check_endorse_4($contents)?>" name='check_endorse_4' id='check_endorse_4' /> <br />
                                <label for='charge_slip_1'>Charge Slip Line 1:</label>
                                <input type='text' value="<?=get_charge_slip_1($contents)?>" name='charge_slip_1' id='charge_slip_1' /> <br />
                                <label for='charge_slip_2'>Charge Slip Line 2:</label>
                                <input type='text' value="<?=get_charge_slip_2($contents)?>" name='charge_slip_2' id='charge_slip_2' /> <br />
                            </fieldset>
                            <fieldset>
                                <legend>Screen Message Settings</legend>
                                <label for='welcome_message_1'>Welcome Message Line 1:</label>
                                <input type='text' value="<?=get_welcome_message_1($contents)?>" name='welcome_message_1' id='welcome_message_1' /> <br />
                                <label for='welcome_message_2'>Welcome Message Line 2:</label>
                                <input type='text' value="<?=get_welcome_message_2($contents)?>" name='welcome_message_2' id='welcome_message_2' /> <br />
                                <label for='welcome_message_3'>Welcome Message Line 3:</label>
                                <input type='text' value="<?=get_welcome_message_3($contents)?>" name='welcome_message_3' id='welcome_message_3' /> <br />
                                <label for='training_message_1'>Training Message Line 1:</label>
                                <input type='text' value="<?=get_training_message_1($contents)?>" name='training_message_1' id='training_message_1' /> <br />
                                <label for='training_message_2'>Training Message Line 2:</label>
                                <input type='text' value="<?=get_training_message_2($contents)?>" name='training_message_2' id='training_message_2' /> <br />
                                <label for='farewell_message_1'>Farewell Message Line 1:</label>
                                <input type='text' value="<?=get_farewell_message_1($contents)?>" name='farewell_message_1' id='farewell_message_1' /> <br />
                                <label for='farewell_message_2'>Farewell Message Line 2:</label>
                                <input type='text' value="<?=get_farewell_message_2($contents)?>" name='farewell_message_2' id='farewell_message_2' /> <br />
                                <label for='farewell_message_3'>Farewell Message Line 3:</label>
                                <input type='text' value="<?=get_farewell_message_3($contents)?>" name='farewell_message_3' id='farewell_message_3' /> <br />
                                <label for='alert_bar'>Alert Bar Message:</label>
                                <input type='text' value="<?=get_alert_bar($contents)?>" name='alert_bar' id='alert_bar' /> <br />
                            </fieldset>
                            <fieldset>
                                <legend>Credit Card Transaction Settings</legend>
                                <label for='credit_card_flag'>Process Credit Card Transactions:</label>
                                <input type='checkbox' value='1' <?=get_credit_card_active($contents) != 0 ? "checked='checked'" : " " ?> name='credit_card_flag' id='credit_card_flag' /> <br />
                                <label for='credit_card_server'>Credit Card Server:</label>
                                <input type='text' value="<?=get_credit_card_server($contents)?>" name='credit_card_server' id='credit_card_server' /> <br />
                                <label for='credit_card_share'>Credit Card Share:</label>
                                <input type='text' value="<?=get_credit_card_share($contents)?>" name='credit_card_share' id='credit_card_share' /><br />
                            </fieldset>
                            <fieldset>
                                <legend>Miscellaneous Settings</legend>
                                <label for='screen_lock_flag'>Lock screen after inactivity:</label>
                                <input type='checkbox' value='1' <?=get_screen_lock($contents) != 0 ? "checked='checked'" : " " ?> name='screen_lock_flag' id='screen_lock_flag' /> <br />
                                <label for='logout_time'>Logout time (ms):</label>
                                <input type='text' value="<?=get_logout_time($contents)?>" name='logout_time' id='logout_time' />
                            </fieldset>
                            <input type='submit' value='Save Changes' />
                            -->
                        </form>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>
