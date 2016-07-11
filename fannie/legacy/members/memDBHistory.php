<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include($FANNIE_ROOT.'auth/login.php');

$username=validateUserQuiet('editmembers');

if (!isset($_GET['memID'])){
    echo "<form action=memDBHistory.php method=get>";
    echo "Enter member number: <input type=text name=memID /><br />";
    echo "<input type=submit value=Submit />";
    echo "</form>";
}
else if (isset($_GET['edit'])){
    $memID = $_GET['memID'];
    
    $fetchQ = $sql->prepare("select * from custdata where cardno=? order by personnum");
    $fetchR = $sql->execute($fetchQ, array($memID));

    echo "<form action=memDBHistory.php method=get>";
    echo "<input type=hidden name=doneEditing value=yes />";
    echo "<table cellspacing=0 cellpadding=3 border=1>";
    echo "<tr><th>Card</th><th>Person</th>";
    echo "<th>Lastname</th><th>Firstname</th><th>Cashback</th><th>Balance</th><th>Discount</th>";
    echo "<th>Limit</th><th>Charge</th><th>Checks</th><th>StoreCP</th><th>type</th><th>MemType</th>";
    echo "<th>Staff</th><th>SSI</th><th>Purchases</th><th>NumChecks</th><th>MemCP</th>";
    echo "<th>Blueline</th><th>Shown</th></tr>";

    $personnum=0;
    while ($fetchW = $sql->fetchRow($fetchR)){
        $cardno = $fetchW[0];
        $personnum = $fetchW[1];
        echo "<tr>";
        echo "<td>$cardno</td><td>$personnum</td>";
        echo "<td><input type=text name=lastname$personnum value=\"$fetchW[2]\" /></td>";
        echo "<td><input type=text name=firstname$personnum value=\"$fetchW[3]\" /></td>";
        echo "<td><input type=text size=5 name=cashback$personnum value=\"$fetchW[4]\" /></td>";
        echo "<td>$fetchW[5]&nbsp;</td>";
        echo "<td><input type=text size=3 name=discount$personnum value=\"$fetchW[6]\" /></td>";
        echo "<td><input type=text size=4 name=limit$personnum value=\"$fetchW[7]\" /></td>";
        echo "<td><input type=text size=2 name=charge$personnum value=\"$fetchW[8]\" /></td>";
        echo "<td><input type=text size=2 name=check$personnum value=\"$fetchW[9]\" /></td>";
        echo "<td><input type=text size=2 name=storecp$personnum value=\"$fetchW[10]\" /></td>";
        echo "<td><input type=text size=4 name=type$personnum value=\"$fetchW[11]\" /></td>";
        echo "<td><input type=text size=2 name=memtype$personnum value=\"$fetchW[12]\" /></td>";
        echo "<td><input type=text size=2 name=staff$personnum value=\"$fetchW[13]\" /></td>";
        echo "<td><input type=text size=2 name=ssi$personnum value=\"$fetchW[14]\" /></td>";
        echo "<td>$fetchW[15]&nbsp;</td>";
        echo "<td><input type=text size=4 name=numchecks$personnum value=\"$fetchW[16]\" /></td>";
        echo "<td>$fetchW[17]&nbsp;</td>";
        echo "<td>$fetchW[18]&nbsp;</td>";
        echo "<td><input type=text size=2 name=shown$personnum value=\"$fetchW[19]\" /></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<input type=hidden name=memID value=$memID />";
    echo "<input type=hidden name=maxpersonnum value=$personnum />";
    echo "<input type=submit value=Submit />";
    echo "</form>";
}
else {
    $memID = $_GET['memID'];
    if (!$username){
        header("Location: /auth/ui/loginform.php?redirect=/members/memDBHistory.php?memID=$memID");
        return;
    }

    if (isset($_GET['stamp'])){
        $stamp = $_GET['stamp'];
        $revertQ = $sql->prepare("update custdata set
                lastname=b.lastname,
                firstname=b.firstname,
                cashback=b.cashback,
                discount=b.discount,
                memdiscountlimit=b.memdiscountlimit,
                chargeok=b.chargeok,
                writechecks=b.writechecks,
                storecoupons=b.storecoupons,
                type=b.type,
                memtype=b.memtype,
                staff=b.staff,
                ssi=b.ssi,
                numberofchecks=b.numberofchecks,
                shown=b.shown
                from custdata as c, custUpdate as b
                where c.cardno=b.cardno and c.cardno=?
                and c.personnum=b.personnum and
                datediff(mi,?,tdate) = 0");
        echo $revertQ."<br />";

        $uid=getUID($username);
        $auditQ = $sql->prepare("insert custUpdate select now(),?,1,* from custdata where cardno=?");

        $sql->execute($auditQ, array($uid, $memID));
        $sql->execute($revertQ, array($memID, $stamp));
        
    } else if (isset($_GET['doneEditing'])){
        foreach(array_keys($_GET) as $key){
            $_GET[$key] = rtrim($_GET[$key]);
            if ($_GET[$key] == "")
                $_GET[$key] = "NULL";
        }

        $uid=getUID($username);
        $auditQ = $sql->prepare("insert custUpdate select now(),?,1,* from custdata where cardno=?");
        $sql->execute($auditQ, array($uid, $memID));

        $upQs = array($_GET["maxpersonnum"]);
        for ($i = 1; $i <= $_GET['maxpersonnum']; $i++){
            $upQ = $sql->prepare("update custdata set
                lastname=?,
                firstname=?,
                cashback=?,
                discount=?,
                memdiscountlimit=?,
                chargeok=?,
                writechecks=?,
                storecoupons=?,
                type=?,
                memtype=?,
                staff=?,
                ssi=?,
                numberofchecks=?,
                shown=?
                where cardno=?
                and personnum=?");
            $sql->execute($upQ, 
                    array(
                        $_GET['lastname'.$i],
                        $_GET['firstname'.$i],
                        $_GET['cashback'.$i],
                        $_GET['discount'.$i],
                        $_GET['limit'.$i],
                        $_GET['charge'.$i],
                        $_GET['check'.$i],
                        $_GET['storecp'.$i],
                        $_GET['type'.$i],
                        $_GET['memtype'.$i],
                        $_GET['staff'.$i],
                        $_GET['ssi'.$i],
                        $_GET['numchecks'.$i],
                        $_GET['shown'.$i],
                        $_GET['memID'.$i],
                        $i
                    )
                );
        }

    }

    $fetchQ = $sql->prepare("select now() as tdate,0,2 as optype,*,'current' from custdata where cardno=?
           union
           select c.*,u.name from custUpdate as c left join users as u on c.uid=u.uid where cardno=? order by tdate desc, personnum, optype desc");
    $fetchR = $sql->execute($fetchQ, array($memID, $memID));
    
    echo "<a href=memDBHistory.php?memID=$memID&edit=yes>Edit</a><br />";
    echo "<table cellspacing=0 cellpadding=3 border=1>";
    echo "<tr><th>Date</th><th>User ID</th><th>Status</th><th>Card</th><th>Person</th>";
    echo "<th>Lastname</th><th>Firstname</th><th>Cashback</th><th>Balance</th><th>Discount</th>";
    echo "<th>Limit</th><th>Charge</th><th>Checks</th><th>StoreCP</th><th>type</th><th>MemType</th>";
    echo "<th>Staff</th><th>SSI</th><th>Purchases</th><th>NumChecks</th><th>MemCP</th>";
    echo "<th>Blueline</th><th>Shown</th></tr>";
    
    $ops = array('Original','Edit','<b>Current</b>');
    $colors = array('#ffffcc','#ffffff');
    $c = 1;
    while ($fetchW = $sql->fetchRow($fetchR)){
        if ($fetchW[4] == 1)
            $c = ($c+1) % 2;
        echo "<tr>";
        echo "<td bgcolor=$colors[$c]>$fetchW[0]</td>";
        echo "<td bgcolor=$colors[$c]>$fetchW[1]</tD>";
        echo "<td bgcolor=$colors[$c]>".$ops[$fetchW[2]]."</td>";
        for ($i=3; $i<24; $i++)
            echo "<td bgcolor=$colors[$c]>".$fetchW[$i]."&nbsp;</td>";
        if ($fetchW[4] == 1)
            echo "<td><a href=memDBHistory.php?memID=$memID&stamp=".urlencode(rtrim($fetchW[0])).">Revert</a></td>";
        echo "</tr>";    
    }
    echo "</table>";
}

