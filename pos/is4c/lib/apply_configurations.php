<?php
    include_once("conf.php");

    // An array of regular expressions that will be replaced by $replacements.
    $patterns = array
    (
        "/SESSION\[\"OS\"\]\s=\s\".*\";/",
        "/SESSION\[\"store\"\]\s=\s\".*\";/",
        "/SESSION\[\"mServer\"\]\s=\s\".*\";/",
        "/SESSION\[\"mDatabase\"\]\s=\s\".*\";/",
        "/SESSION\[\"remoteDBMS\"\]\s=\s\".*\";/",
        "/SESSION\[\"mUser\"\]\s=\s\".*\";/",
        "/SESSION\[\"mPass\"\]\s=\s\".*\";/",
        "/SESSION\[\"localhost\"\]\s=\s\".*\";/",
        "/SESSION\[\"pDatabase\"\]\s=\s\".*\";/",
        "/SESSION\[\"tDatabase\"\]\s=\s\".*\";/",
        "/SESSION\[\"DBMS\"\]\s=\s\".*\";/",
        "/SESSION\[\"localUser\"\]\s=\s\".*\";/",
        "/SESSION\[\"localPass\"\]\s=\s\".*\";/",
        "/SESSION\[\"laneno\"\]\s=\s\".*\";/",
        "/SESSION\[\"print\"\]\s=\s.*/",
        "/SESSION\[\"printerPort\"\]\s=\s\".*\";/",
        "/SESSION\[\"receiptHeader1\"\]\s=\s\".*\";/",
        "/SESSION\[\"receiptHeader2\"\]\s=\s\".*\";/",
        "/SESSION\[\"receiptHeader3\"\]\s=\s\".*\";/",
        "/SESSION\[\"receiptFooter1\"\]\s=\s\".*\";/",
        "/SESSION\[\"receiptFooter2\"\]\s=\s\".*\";/",
        "/SESSION\[\"receiptFooter3\"\]\s=\s\".*\";/",
        "/SESSION\[\"receiptFooter4\"\]\s=\s\".*\";/",
        "/SESSION\[\"ckEndorse1\"\]\s=\s\".*\";/",
        "/SESSION\[\"ckEndorse2\"\]\s=\s\".*\";/",
        "/SESSION\[\"ckEndorse3\"\]\s=\s\".*\";/",
        "/SESSION\[\"ckEndorse4\"\]\s=\s\".*\";/",
        "/SESSION\[\"chargeSlip1\"\]\s=\s\".*\";/",
        "/SESSION\[\"chargeSlip2\"\]\s=\s\".*\";/",
        "/SESSION\[\"welcomeMsg1\"\]\s=\s\".*\";/",
        "/SESSION\[\"welcomeMsg2\"\]\s=\s\".*\";/",
        "/SESSION\[\"welcomeMsg3\"\]\s=\s\".*\";/",
        "/SESSION\[\"trainingMsg1\"\]\s=\s\".*\";/",
        "/SESSION\[\"trainingMsg2\"\]\s=\s\".*\";/",
        "/SESSION\[\"farewellMsg1\"\]\s=\s\".*\";/",
        "/SESSION\[\"farewellMsg2\"\]\s=\s\".*\";/",
        "/SESSION\[\"farewellMsg3\"\]\s=\s\".*\";/",
        "/SESSION\[\"alertBar\"\]\s=\s\".*\";/",
        "/SESSION\[\"ccLive\"\]\s=\s.*/",
        "/SESSION\[\"ccServer\"\]\s=\s\".*\";/",
        "/SESSION\[\"ccShare\"\]\s=\s\".*\";/",
        "/SESSION\[\"lockScreen\"\]\s=\s.*/",
        "/SESSION\[\"timedlogout\"\]\s=\s.*/"
    );
    $replacements = array
    (
        'SESSION["OS"] = "' . $_POST['general_os'] . '";',
        'SESSION["store"] = "' . $_POST['general_store'] . '";',
        'SESSION["mServer"] = "' . $_POST['server_ip'] . '";',
        'SESSION["mDatabase"] = "' . $_POST['server_type'] . '";',
        'SESSION["remoteDBMS"] = "' . $_POST['server_database'] . '";',
        'SESSION["mUser"] = "' . $_POST['server_username'] . '";',
        'SESSION["mPass"] = "' . $_POST['server_password'] . '";',
        'SESSION["localhost"] = "' . $_POST['local_ip'] . '";',
        'SESSION["pDatabase"] = "' . $_POST['local_ops'] . '";',
        'SESSION["tDatabase"] = "' . $_POST['local_trans'] . '";',
        'SESSION["DBMS"] = "' . $_POST['local_type'] . '";',
        'SESSION["localUser"] = "' . $_POST['local_username'] . '";',
        'SESSION["localPass"] = "' . $_POST['local_password'] . '";',
        'SESSION["laneno"] = "' . $_POST['general_lane'] . '";',
        'SESSION["print"] = ' . (isset($_POST['printer_active'])?1:0) . ';',
        'SESSION["printerPort"] = "' . $_POST['printer_port'] . '";',
        'SESSION["receiptHeader1"] = "' . $_POST['receipt_header_1'] . '";',
        'SESSION["receiptHeader2"] = "' . $_POST['receipt_header_2'] . '";',
        'SESSION["receiptHeader3"] = "' . $_POST['receipt_header_3'] . '";',
        'SESSION["receiptFooter1"] = "' . $_POST['receipt_footer_1'] . '";',
        'SESSION["receiptFooter2"] = "' . $_POST['receipt_footer_2'] . '";',
        'SESSION["receiptFooter3"] = "' . $_POST['receipt_footer_3'] . '";',
        'SESSION["receiptFooter4"] = "' . $_POST['receipt_footer_4'] . '";',
        'SESSION["ckEndorse1"] = "' . $_POST['check_endorse_1'] . '";',
        'SESSION["ckEndorse2"] = "' . $_POST['check_endorse_2'] . '";',
        'SESSION["ckEndorse3"] = "' . $_POST['check_endorse_3'] . '";',
        'SESSION["ckEndorse4"] = "' . $_POST['check_endorse_4'] . '";',
        'SESSION["chargeSlip1"] = "' . $_POST['charge_slip_1'] . '";',
        'SESSION["chargeSlip2"] = "' . $_POST['charge_slip_2'] . '";',
        'SESSION["welcomeMsg1"] = "' . $_POST['welcome_message_1'] . '";',
        'SESSION["welcomeMsg2"] = "' . $_POST['welcome_message_2'] . '";',
        'SESSION["welcomeMsg3"] = "' . $_POST['welcome_message_3'] . '";',
        'SESSION["trainingMsg1"] = "' . $_POST['training_message_1'] . '";',
        'SESSION["trainingMsg2"] = "' . $_POST['training_message_2'] . '";',
        'SESSION["farewellMsg1"] = "' . $_POST['farewell_message_1'] . '";',
        'SESSION["farewellMsg2"] = "' . $_POST['farewell_message_2'] . '";',
        'SESSION["farewellMsg3"] = "' . $_POST['farewell_message_3'] . '";',
        'SESSION["alertBar"] = "' . $_POST['alert_bar'] . '";',
        'SESSION["ccLive"] = ' . (isset($_POST['credit_card_flag'])?1:0) . ';',
        'SESSION["ccServer"] = "' . $_POST['credit_card_server'] . '";',
        'SESSION["ccShare"] = "' . $_POST['credit_card_share'] . '";',
        'SESSION["lockScreen"] = ' . (isset($_POST['screen_lock_flag'])?1:0) . ';',
        'SESSION["timedlogout"] = ' . $_POST['logout_time']
    );
    $contents = preg_replace($patterns, $replacements, $contents);

    $handle = fopen("/pos/is4c/ini/ini.php", "w");
    if ($handle) {
        fwrite($handle, $contents);
        echo 'Contents successfully saved.';
    }
    fclose($handle);

