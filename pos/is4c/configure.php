<?php
    include_once("connect.php");
    include_once("lib/query.php");
    include_once("lib/conf.php");

    $username = array("Sql User","text","20","username");
    $password = array("Password","text","20","password");
    $os = array("OS Version","text","20","os");
    $store = array("Store","text","20","store");
    $mServer = array("Master Server Name","text","20","mServer");
    $mDatabase = array("Master Database","text","20","mDatabase");
    $tDatabase = array("Transaction Database","text","20","tDatabase");
    $pDatabase = array("Operating Database","text","20","pDatabase");
    $laneNo = array("Lane Number","text","5","laneNo");
    $localhost = array("Local IP","text","15","localhost");
    $printer = array("Printer?","checkbox","checked","printer");
    $receiptHeader1 = array("receipt Header 1","text","50","receiptHeader1");
    $receiptHeader2 = array("receipt Header 2","text","50","receiptHeader2");
    $receiptHeader3 = array("receipt Header 3","text","50","receiptHeader3");
    $receiptFooter1 = array("receipt Footer 1","text","50","receiptFooter1");
    $receiptFooter2 = array("receipt Footer 2","text","50","receiptFooter2");
    $receiptFooter3 = array("receipt Footer 3","text","50","receiptFooter3");
    $receiptFooter4 = array("receipt Footer 4","text","50","receiptFooter4");
    $ckEndorse1 = array("Check Endorse 1","text","30","ckEndorse1");
    $ckEndorse2 = array("Check Endorse 2","text","30","ckEndorse2");
    $ckEndorse3 = array("Check Endorse 3","text","30","ckEndorse3");
    $ckEndorse4 = array("Check Endorse 4","text","30","ckEndorse4");
    $chargeSlip1 = array("Charge Slip 1","text","30","chargeSlip1");
    $chargeSlip2 = array("Charge Slip 2","text","30","chargeSlip2");
    $welcomeMsg1 = array("Welcome Message 1","text","30","welcomeMsg1");
    $welcomeMsg2 = array("Welcome Message 2","text","30","welcomeMsg2");
    $welcomeMsg3 = array("Welcome Message 3","text","30","welcomeMsg3");
    $trainingMsg1 = array("Training Message 1","text","30","trainingMsg1");
    $trainingMsg2 = array("Training Message 2","text","30","trainingMsg2");
    $farewellMsg1 = array("Farewell Message 1","text","30","farewellMsg1");
    $farewellMsg2 = array("Farewell Message 2","text","30","farewellMsg2");
    $farewellMsg3 = array("Farewell Message 3","text","30","farewellMsg3");
    $alertBar = array("Alert Bar","text","20","alertBar");
    $discountEnforced = array("Discount Enforced","checkbox","checked","discountEnforced");
    $lockScreen = array("Lock Screen","checkbox","no","lockScreen");
    $ddNotify = array("Deli Discount Notify","checkbox","checked","ddNotify");
    $promoMsg = array("Promotional Message","checkbox","no","promoMsg");
    $memlistNonMember = array("Show Nonmember in list","checkbox","checked","memlistNonMember");
    $cashOverLimit = array("Limit Cash Over","checkbox","no","cashOverLimit");
    $inputMasked = array("Mask input?","checkbox","checked","inputMasked");
    $CCintegrate = array("Integrated Credit Cards?","checkbox","checked","CCintegrate");

    $array = array
        (
            $username,
            $password,
            $os,
            $store,
            $mServer,
            $mDatabase,
            $tDatabase,
            $pDatabase,
            $laneNo,
            $localhost,
            $printer,
            $receiptHeader1,
            $receiptHeader2,
            $receiptHeader3,
            $receiptFooter1,
            $receiptFooter2,
            $receiptFooter3,
            $receiptFooter4,
            $ckEndorse1,
            $ckEndorse2,
            $ckEndorse3,
            $ckEndorse4,
            $chargeSlip1,
            $chargeSlip2,
            $welcomeMsg1,
            $welcomeMsg2,
            $welcomeMsg3,
            $trainingMsg1,
            $trainingMsg2,
            $farewellMsg1,
            $farewellMsg2,
            $farewellMsg3,
            $alertBar,
            $discountEnforced,
            $lockScreen,
            $ddNotify,
            $promoMsg,
            $memlistNonMember,
            $cashOverLimit,
            $inputMasked,
            $CCintegrate
        );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Lane Configuration</title>
        <link rel="stylesheet" type="text/css" href="css/is4c.css" />
    </head>
    <body>
        <form action='configure.php' method='post'>
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
                        <div id="general_settings">
                            <b>General:</b><br />
                            Operating System: <input type="text" value="<?=get_os($contents)?>" size="6" /><br />
                            Store Name: <input type="text" value="<?=get_store_name($contents)?>" size="20" /><br />
                            Lane Number: <input type="text" value="<?=get_lane_number($contents)?>" size="1" />
                        </div>
                        <div id="database_settings">
                            <b>Server Database:</b><br />
                            IP Address: <input type="text" value="<?=get_server_ip($contents)?>" size="10" /><br />
                            Database Type: <input type="text" value="<?=get_server_database_type($contents)?>" size="6" /><br />
                            Log Database: <input type="text" value="<?=get_server_database($contents)?>" size="6" /><br />
                            User Name: <input type="text" value="<?=get_server_username($contents)?>" size="6" /><br />
                            Password: <input type="text" value="<?=get_server_password($contents)?>" size="6" /><br />
                            <b>Local Database:</b><br />
                            IP Address: <input type="text" value="<?=get_local_ip($contents)?>" size="10" /><br />
                            Database Type: <input type="text" value="<?=get_local_database_type($contents)?>" size="6" /><br />
                            Operations Database: <input type="text" value="<?=get_local_op_database($contents)?>" size="6" /><br />
                            Transaction Database: <input type="text" value="<?=get_local_trans_database($contents)?>" size="6" /><br />
                            User Name: <input type="text" value="<?=get_local_username($contents)?>" size="6" /><br />
                            Password: <input type="text" value="<?=get_local_password($contents)?>" size="6" /><br />
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>
