<?php
include('../../config.php');

if (!class_exists("FannieAPI")) require_once($FANNIE_ROOT."classlib2.0/FannieAPI.php");
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');
$sql->query("use is4c_trans");

/* delete is easy
 * just delete from staffID and staffAR
 */
if (isset($_POST['remove'])){
    $cardno = $_POST['cardno'];
    $delQ = $sql->prepare("delete from staffID where cardno=?");
    $delR = $sql->execute($delQ, array($cardno));
    $delQ = $sql->prepare("delete from staffAR where cardNo=?");
    $delQ = $sql->execute($delQ, array($cardno));
    echo "Member #$cardno removed from staff AR<p />";
}
/* add an employee to staffAR
 * this requires an ADP ID, which I attempt to find using
 * first and last names, otherwise the user is prompted to
 * enter the ADP ID 
 * redeux: just lastname. employees on nexus tends to have full[er] names
 * and middle initials
 */
if (isset($_POST['add'])){
    $cardno = $_POST['cardno'];
    
    $namesQ = $sql->prepare("select FirstName,LastName from is4c_op.custdata where CardNo=? and personNum=1");
    $namesR = $sql->execute($namesQ, array($cardno));
    $namesW = $sql->fetchRow($namesR);
    $fname = $namesW[0];
    $lname = $namesW[1];
    
    echo "Enter the employee's ADP ID#<br />";
    echo "<form method=post action=staffARmanager.php>";
    echo "<input type=text name=adpID value=100 /> ";
    echo "<input type=submit value=Submit />";
    echo "<input type=hidden name=cardno value=$cardno />";
    echo "</form>";
    return; // not done adding yet
}
/* adp id wasn't found, so a form of
 * some kind was submitted to fill it in
 */
 if (isset($_POST['adpID'])){
    $cardno = $_POST['cardno'];
    $adpID = $_POST['adpID'];
    // the user provided an adp id
    if ($adpID != 'None of these'){
        $delQ = $sql->prepare("delete from staffID where cardno=?");
        $delR = $sql->execute($delQ, array($cardno));
        $insQ = $sql->prepare("insert into staffID values (?,?,1)");
        $insR = $sql->execute($insQ, array($cardno, $adpID));
        balance($cardno);
        echo "Member #$cardno added to staff AR";
    }
    // the user didn't like the possible choices presented, give
    // manual entry form
    else {
        echo "Enter the employee's ADP ID#<br />";
        echo "<form method=post action=staffARmanager.php>";
        echo "<input type=text name=adpID value=100 /> ";
        echo "<input type=submit value=Submit />";
        echo "<input type=hidden name=cardno value=$cardno />";
        echo "</form>";
        return; // not done adding yet
    }
}

// add the correct balance for the cardno to staffAR
function balance($cardno){
    global $sql;
    $balanceQ = $sql->prepare("INSERT INTO staffAR (cardNo, lastName, firstName, adjust)
                     SELECT
                     CardNo,
                     LastName,
                     FirstName,
                     Balance as Ending_Balance
                     from is4c_op.custdata where CardNo=? and personNum=1");
    $balanceR = $sql->execute($balanceQ, array($cardno));
}

// main insert / delete form follows
?>
<form action=staffARmanager.php method=post>
<b>Add employee</b>:<br />
Member number: <input type=text name=cardno /> 
<input type=hidden name=add value=add />
<input type=submit value=Add />
</form>
<hr />
<form action=staffARmanager.php method=post>
<b>Remove employee</b>:<br />
Member number: <input type=text name=cardno /> 
<input type=hidden name=remove value=remove />
<input type=submit value=Remove />
</form>
