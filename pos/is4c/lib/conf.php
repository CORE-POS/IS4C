<?php
    
    $handle = fopen("/pos/is4c/ini/ini.php", "r");
    $contents = '';
    if ($handle) {
        while (!feof($handle)) {
          $contents .= fread($handle, 8192);
        }
    }
    fclose($handle);

    function get_config_auto($config_item){
        switch ($config_item){
            case "OS":
                return ("<input type='button' value='Auto' onclick=\"$('#OS').attr('value', '" . strtolower(PHP_OS) . "'); return false\" />");
                break;
            case "localhost":
                return ("<input type='button' value='Auto' onclick=\"$('#localhost').attr('value', '" . $_SERVER['SERVER_ADDR'] . "'); return false\" />");
                break;
            default:
                return "";
        }
    }

    function get_configuration_groups(){
        $conf_groups = get_configuration_groups_query();
        return $conf_groups;
    }

    function get_configuration_settings($configuration_group){
        $conf_settings = get_configuration_group_settings_query($configuration_group);
        return $conf_settings;
    }

    function get_os($contents) {
        preg_match("/SESSION\[\"OS\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 17));
    }

    function get_store_name($contents) {
        preg_match("/SESSION\[\"store\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 20));
    }

    function get_server_ip($contents) {
        preg_match("/SESSION\[\"mServer\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 22));
    }

    function get_server_database($contents) {
        preg_match("/SESSION\[\"mDatabase\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 24));
    }

    function get_server_database_type($contents) {
        preg_match("/SESSION\[\"remoteDBMS\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 25));
    }

    function get_server_username($contents) {
        preg_match("/SESSION\[\"mUser\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 20));
    }

    function get_server_password($contents) {
        preg_match("/SESSION\[\"mPass\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 20));
    }

    function get_local_ip($contents) {
        preg_match("/SESSION\[\"localhost\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 24));
    }

    function get_local_op_database($contents) {
        preg_match("/SESSION\[\"pDatabase\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 24));
    }

    function get_local_trans_database($contents) {
        preg_match("/SESSION\[\"tDatabase\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 24));
    }

    function get_local_database_type($contents) {
        preg_match("/SESSION\[\"DBMS\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 19));
    }

    function get_local_username($contents) {
        preg_match("/SESSION\[\"localUser\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 24));
    }

    function get_local_password($contents) {
        preg_match("/SESSION\[\"localPass\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 24));
    }

    function get_lane_number($contents) {
        preg_match("/SESSION\[\"laneno\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 21));
    }

    function get_print_flag($contents) {
        preg_match("/SESSION\[\"print\"\]\s=\s.*/", $contents, $val);
        return str_replace('";', "", substr($val[0], 19));
    }

    function get_printer_port($contents) {
        preg_match("/SESSION\[\"printerPort\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 26));
    }

    function get_receipt_header_1($contents) {
        preg_match("/SESSION\[\"receiptHeader1\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 29));
    }

    function get_receipt_header_2($contents) {
        preg_match("/SESSION\[\"receiptHeader2\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 29));
    }

    function get_receipt_header_3($contents) {
        preg_match("/SESSION\[\"receiptHeader3\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 29));
    }

    function get_receipt_footer_1($contents) {
        preg_match("/SESSION\[\"receiptFooter1\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 29));
    }

    function get_receipt_footer_2($contents) {
        preg_match("/SESSION\[\"receiptFooter2\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 29));
    }

    function get_receipt_footer_3($contents) {
        preg_match("/SESSION\[\"receiptFooter3\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 29));
    }

    function get_receipt_footer_4($contents) {
        preg_match("/SESSION\[\"receiptFooter4\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 29));
    }

    function get_check_endorse_1($contents) {
        preg_match("/SESSION\[\"ckEndorse1\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 25));
    }

    function get_check_endorse_2($contents) {
        preg_match("/SESSION\[\"ckEndorse2\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 25));
    }

    function get_check_endorse_3($contents) {
        preg_match("/SESSION\[\"ckEndorse3\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 25));
    }

    function get_check_endorse_4($contents) {
        preg_match("/SESSION\[\"ckEndorse4\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 25));
    }

    function get_charge_slip_1($contents) {
        preg_match("/SESSION\[\"chargeSlip1\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 26));
    }

    function get_charge_slip_2($contents) {
        return str_replace('";', "", substr($val[0], 26));
    }
        preg_match("/SESSION\[\"chargeSlip2\"\]\s=\s\".*\";/", $contents, $val);

    function get_welcome_message_1($contents) {
        preg_match("/SESSION\[\"welcomeMsg1\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 26));
    }

    function get_welcome_message_2($contents) {
        preg_match("/SESSION\[\"welcomeMsg2\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 26));
    }

    function get_welcome_message_3($contents) {
        preg_match("/SESSION\[\"welcomeMsg3\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 26));
    }

    function get_training_message_1($contents) {
        preg_match("/SESSION\[\"trainingMsg1\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 27));
    }

    function get_training_message_2($contents) {
        preg_match("/SESSION\[\"trainingMsg2\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 27));
    }

    function get_farewell_message_1($contents) {
        preg_match("/SESSION\[\"farewellMsg1\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 27));
    }

    function get_farewell_message_2($contents) {
        preg_match("/SESSION\[\"farewellMsg2\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 27));
    }

    function get_farewell_message_3($contents) {
        preg_match("/SESSION\[\"farewellMsg3\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 27));
    }

    function get_alert_bar($contents) {
        preg_match("/SESSION\[\"alertBar\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 23));
    }

    function get_credit_card_active($contents) {
        preg_match("/SESSION\[\"ccLive\"\]\s=\s.*/", $contents, $val);
        return str_replace('";', "", substr($val[0], 20));
    }

    function get_credit_card_server($contents) {
        preg_match("/SESSION\[\"ccServer\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 23));
    }

    function get_credit_card_share($contents) {
        preg_match("/SESSION\[\"ccShare\"\]\s=\s\".*\";/", $contents, $val);
        return str_replace('";', "", substr($val[0], 22));
    }

    function get_screen_lock($contents) {
        preg_match("/SESSION\[\"lockScreen\"\]\s=\s.*/", $contents, $val);
        return str_replace('";', "", substr($val[0], 24));
    }

    function get_logout_time($contents) {
        preg_match("/SESSION\[\"timedlogout\"\]\s=\s.*;/", $contents, $val);
        return str_replace(';', "", substr($val[0], 25));
    }

