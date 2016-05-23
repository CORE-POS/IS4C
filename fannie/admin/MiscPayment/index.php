<?php
include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$header = "Miscellaneous Payment";
$page_title = "Fannie :: Misc Payment";
include($FANNIE_ROOT.'src/header.html');

$LANE_NO=30;
$EMP_NO=1001;
$DEFAULT_DEPT=703;
$CARD_NO=11;

if (isset($_POST['init'])){
    $errors = "";
    if (!isset($_POST['desc']) || empty($_POST['desc'])){
        $errors .= "Error: Description required<br />";
    }
    if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])){
        $errors .= "Error: amount is required<br />";
    }
    if (!isset($_POST['dept'])){
        $errors .= "Error: department is required<br />";
    }
    if (!isset($_POST['tender'])){
        $errors .= "Error: tender is required<br />";
    }

    if (empty($errors)){
        billingDisplay();
    }
    else {
        echo "<blockquote><i>".$errors."</i></blockquote>";
        regularDisplay();
    }
}
elseif (isset($_POST['confirm'])){
    // these tests should always pass unless someone is
    // POST-ing data without using the form
    $errors = "";
    if (!isset($_POST['desc']) || empty($_POST['desc'])){
        $errors .= "Error: Description required<br />";
    }
    if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])){
        $errors .= "Error: amount is required<br />";
    }
    if (!isset($_POST['dept'])){
        $errors .= "Error: department is required<br />";
    }
    if (!isset($_POST['tender'])){
        $errors .= "Error: tender is required<br />";
    }

    if (empty($errors)){
        bill($_POST['amount'],$_POST['desc'],
            $_POST['dept'],$_POST['tender']);
    }
    else {
        echo "<blockquote><i>".$errors."</i></blockquote>";
        regularDisplay();
    }
}
else {
    regularDisplay();
}

function regularDisplay()
{
    global $FANNIE_OP_DB,$DEFAULT_DEPT;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    echo "<form action=index.php method=post>
        <table><tr><td>
        <b>Description</b></td><td>
        <input maxlength=30 type=text id=desc name=desc />
        </td></tr><tr><td><b>Amount</b></td><td>
        \$<input type=text name=amount /></td></tr>
        <tr><td><b>Department</b></td>
        <td><select name=dept>";
    $numsQ = $dbc->prepare("SELECT dept_no,dept_name FROM departments 
        ORDER BY dept_no");
    $numsR = $dbc->execute($numsQ);
    while($numsW = $dbc->fetch_row($numsR)){
        printf("<option value=%d %s>%d %s</option>",
            $numsW[0],
            ($numsW[0]==$DEFAULT_DEPT?'selected':''),
            $numsW[0],$numsW[1]);   
    }
    echo "</select></td></tr>
        <tr><td><b>Tender Type</b></td>
        <td><select name=tender>";
    $numsQ = $dbc->prepare("SELECT TenderCode,TenderName FROM tenders 
        ORDER BY TenderName");
    $numsR = $dbc->execute($numsQ);
    while($numsW = $dbc->fetch_row($numsR)){
        printf("<option value=%s>%s</option>",$numsW[0],$numsW[1]); 
    }
    echo "</select></td></tr><tr><td>
        <input type=submit name=init value=Submit />
        </td></tr></table></form>";
}

function billingDisplay(){
    printf("<form action=index.php method=post>
        <table cellpadding=4 cellspacing=0 border=1>
        <tr>
            <th>Description</th>
            <td>%s<input type=hidden name=desc value=\"%s\" /></td>
            <th>Department</th>
            <td>%d<input type=hidden name=dept value=\"%d\" /></td>
        </tr>
        <tr>
            <th>Tender</th>
            <td>%s<input type=hidden name=tender value=\"%s\" /></td>
            <th>Amount</th>
            <td>%.2f<input type=hidden name=amount value=\"%.2f\" /></td>
        </tr>
        </table>
        <input type=submit value=\"Make Payment\" name=confirm />
        </form>",
        $_POST['desc'],$_POST['desc'],
        $_POST['dept'],$_POST['dept'],
        $_POST['tender'],$_POST['tender'],
        $_POST['amount'],$_POST['amount']);
}

function bill($amt,$desc,$dept,$tender){
    global $FANNIE_OP_DB,$EMP_NO,$LANE_NO,$CARD_NO, $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $tnQ = $dbc->prepare("SELECT TenderName FROM tenders WHERE TenderCode=?");
    $tnR = $dbc->execute($tnQ,array($tender));
    $tname = array_pop($dbc->fetchRow($tnR));

    $dbc = FannieDB::get($FANNIE_TRANS_DB);

    $transQ = $dbc->prepare("SELECT MAX(trans_no) FROM dtransactions
        WHERE emp_no=? AND register_no=?");
    $transR = $dbc->execute($transQ,array($EMP_NO,$LANE_NO));
    $t_no = array_pop($dbc->fetchRow($transR));
    if ($t_no == "") $t_no = 1;
    else $t_no++;

    $insQ = $dbc->prepare("INSERT INTO dtransactions VALUES (
        ".$dbc->now().",0,0,?,?,?,
        ?,?,'D','','',?,
        1.0,0,0.00,?,?,?,0,0,.0,.0,
        0,0,0,NULL,0.0,0,0,.0,0,0,0,0,0,'',
        ?,1)");
    $amt = sprintf('%.2f',$amt);
    $args = array($LANE_NO,$EMP_NO,$t_no,$amt.'DP'.$dept,$desc,
        $dept,$amt,$amt,$amt,$CARD_NO);

    $amt *= -1;
    $amt = sprintf('%.2f',$amt);
    $insQ2 = $dbc->prepare("INSERT INTO dtransactions VALUES (
        ".$dbc->now().",0,0,?,?,?,
        0,?,'T',?,0,0,
        0.0,0,0.00,.0,?,.0,0,0,.0,.0,
        0,0,0,NULL,0.0,0,0,.0,0,0,0,0,0,'',
        ?,2)");
    $args2 = array($LANE_NO,$EMP_NO,$t_no,$tname,$tender,$amt,$CARD_NO);
    $dbc->execute($insQ,$args);
    $dbc->execute($insQ2,$args2);

    printf("Receipt is %d-%d-%d.",
        $EMP_NO,$LANE_NO,$t_no);
}

include($FANNIE_ROOT.'src/footer.html');

