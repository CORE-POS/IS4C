<?
include_once("connect.php");

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
        <title></title>
    </head>
    <body>
        <form action='configure.php' method='post'>
            <table border='0' cellpadding='0' cellspacing='0'>
                <tr>
                    <td height='40' width='100' valign='center' bgcolor='#FFCC00' align='center'>
                        <font face='arial' size='-1'>
                            <b>I S 4 C</b>
                        </font>
                    </td>
                    <td>
                        <font face='arial' size='4'>
                            <b>&nbsp;Lane Configuration</b>
                        </font>
                    </td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>

                <?php
                    foreach($_POST AS $key => $value){
                        $$key = $value;    
                    }

                    if(isset($_POST['submit'])){
                        if($printer <> 1){
                            $printer=0;
                        }
                        if($discountEnforced <> 1){
                            $discountEnforce=0;
                        }
                        if($lockScreen <> 1){
                            $lockScreen = 0;
                        }
                        if($ddNotify <> 1){
                            $ddNotify = 0;
                        }
                        if($promoMsg <> 1){
                            $promoMsg = 0;
                        }
                        if($memlistNonMember<>1){
                            $memlistNonMember = 0;
                        }
                        if($cashOverLimit<>1){
                            $cashOverLimit=0;
                        }
                        if($inputMasked<>1){
                            $inputMasked=0;
                        }
                        if($CCintegrate<>1){
                            $CCintegrated=0;
                        }

                        if($password==" "){
                            $db = sql_connect("$localhost", "$username");
                        }
                        else{    
                            $db = sql_connect("$localhost", "$username", "$password");
                        }

                        sql_select_db("$pDatabase",$db);

                        $trunQuery = "TRUNCATE TABLE configure";
                        $trunResult = sql_query($trunQuery,$db);

                        $insQuery = "INSERT INTO configure VALUES('$username',
                            '$password',
                            '$os',
                            '$store',
                            '$mServer',
                            '$mDatabase',
                            '$tDatabase',
                            '$pDatabase',
                            '$laneNo',
                            '$localhost',
                            $printer,
                            '$receiptHeader1',
                            '$receiptHeader2',
                            '$receiptHeader3',
                            '$receiptFooter1',
                            '$receiptFooter2',
                            '$receiptFooter3',
                            '$receiptFooter4',
                            '$ckEndorse1',
                            '$ckEndorse2',
                            '$ckEndorse3',
                            '$ckEndorse4',
                            '$chargeSlip1',
                            '$chargeSlip2',
                            '$welcomeMsg1',
                            '$welcomeMsg2',
                            '$welcomeMsg3',
                            '$trainingMsg1',
                            '$trainingMsg2',
                            '$farewellMsg1',
                            '$farewellMsg2',
                            '$farewellMsg3',
                            '$alertBar',
                            '$discountEnforced',
                            '$lockScreen',
                            '$ddNotify',
                            '$promoMsg',
                            '$memlistNonMember',
                            '$cashOverLimit',
                            '$inputMasked',
                            '$CCintegrate')";    

                        $insResult = sql_query($insQuery,$db);

                        $query = "SELECT * FROM configure";
                        $result = sql_query($query,$db);
                        $row = sql_fetch_array($result);

                        foreach( $array as $key => $value ) {
                            $num = $value[3];
                            if($value[1] == "text"){
                                echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                <td><input type='$value[1]' value='" . $row[$num]."' size='$value[2]' name='$value[3]'></td></tr>"; 
                            }
                            elseif($row[$num]==1){
                                echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                <td><input type='$value[1]' value='1' checked='$value[2]' name='$value[3]'></td></tr>";
                            }
                            else{
                                echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                <td><input type='$value[1]' value='1' name='$value[3]'></td></tr>";
                            }
                        }    
                    }
                    else{
                        $db = pDataconnect(); 
                        sql_select_db("opData",$db);

                        $query = "SELECT * FROM configure";
                        $result = sql_query($query,$db);
                        $row = sql_fetch_array($result);
                        $numRow = sql_num_rows($result);

                        if($numRow != 0){
                            foreach( $array as $key => $value ) {
                                $num = $value[3];
                                if($value[1] == "text"){
                                    echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                        <td><input type='$value[1]' value='" . $row[$num] . "' size='$value[2]' name='$value[3]'></td></tr>"; 
                                }
                                elseif($row[$num]==1){
                                    echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                    <td><input type='$value[1]' value='1' checked='$value[2]' name='$value[3]'></td></tr>";
                                }
                                else{
                                    echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                    <td><input type='$value[1]' value='1' name='$value[3]'></td></tr>";
                                }
                            }
                        }
                        else{
                            foreach( $array as $key => $value ) {
                                if($value[1] == "text"){
                                   echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                    <td><input type='$value[1]' size='$value[2]' name='$value[3]'></td></tr>"; 
                                }
                                elseif($value[2]=="checked"){
                                    echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                    <td><input type='$value[1]' value='1' checked='$value[2]' name='$value[3]'></td></tr>";
                                }
                                else{
                                    echo "<tr><td>&nbsp;</td><td align='right'><font face='arial'>$value[0]:</font></td>
                                    <td><input type='$value[1]' value='1' name='$value[3]'></td></tr>";
                                }
                            }
                        }
                    }
                ?>
                    <td>&nbsp;</td>
                    <td>
                        <input type='submit' value='submit' name='submit'>
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>
