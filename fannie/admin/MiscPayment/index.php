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

if (isset($_REQUEST['init'])){
    $errors = "";
    if (!isset($_REQUEST['desc']) || empty($_REQUEST['desc'])){
        $errors .= "Error: Description required<br />";
    }
    if (!isset($_REQUEST['amount']) || !is_numeric($_REQUEST['amount'])){
        $errors .= "Error: amount is required<br />";
    }
    if (!isset($_REQUEST['dept'])){
        $errors .= "Error: department is required<br />";
    }
    if (!isset($_REQUEST['tender'])){
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
elseif (isset($_REQUEST['confirm'])){
    // these tests should always pass unless someone is
    // POST-ing data without using the form
    $errors = "";
    if (!isset($_REQUEST['desc']) || empty($_REQUEST['desc'])){
        $errors .= "Error: Description required<br />";
    }
    if (!isset($_REQUEST['amount']) || !is_numeric($_REQUEST['amount'])){
        $errors .= "Error: amount is required<br />";
    }
    if (!isset($_REQUEST['dept'])){
        $errors .= "Error: department is required<br />";
    }
    if (!isset($_REQUEST['tender'])){
        $errors .= "Error: tender is required<br />";
    }

    if (empty($errors)){
        bill($_REQUEST['amount'],$_REQUEST['desc'],
            $_REQUEST['dept'],$_REQUEST['tender']);
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
    $numsQ = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments 
        ORDER BY dept_no");
    $numsR = $dbc->exec_statement($numsQ);
    while($numsW = $dbc->fetch_row($numsR)){
        printf("<option value=%d %s>%d %s</option>",
            $numsW[0],
            ($numsW[0]==$DEFAULT_DEPT?'selected':''),
            $numsW[0],$numsW[1]);   
    }
    echo "</select></td></tr>
        <tr><td><b>Tender Type</b></td>
        <td><select name=tender>";
    $numsQ = $dbc->prepare_statement("SELECT TenderCode,TenderName FROM tenders 
        ORDER BY TenderName");
    $numsR = $dbc->exec_statement($numsQ);
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
        $_REQUEST['desc'],$_REQUEST['desc'],
        $_REQUEST['dept'],$_REQUEST['dept'],
        $_REQUEST['tender'],$_REQUEST['tender'],
        $_REQUEST['amount'],$_REQUEST['amount']);
}

function bill($amt,$desc,$dept,$tender){
    global $FANNIE_OP_DB,$EMP_NO,$LANE_NO,$CARD_NO, $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $tnQ = $dbc->prepare_statement("SELECT TenderName FROM tenders WHERE TenderCode=?");
    $tnR = $dbc->exec_statement($tnQ,array($tender));
    $tn = array_pop($dbc->fetch_array($tnR));

    $dbc = FannieDB::get($FANNIE_TRANS_DB);

    $transQ = $dbc->prepare_statement("SELECT MAX(trans_no) FROM dtransactions
        WHERE emp_no=? AND register_no=?");
    $transR = $dbc->exec_statement($transQ,array($EMP_NO,$LANE_NO));
    $t_no = array_pop($dbc->fetch_array($transR));
    if ($t_no == "") $t_no = 1;
    else $t_no++;

    $insQ = $dbc->prepare_statement("INSERT INTO dtransactions VALUES (
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
    $insQ2 = $dbc->prepare_statement("INSERT INTO dtransactions VALUES (
        ".$dbc->now().",0,0,?,?,?,
        0,?,'T',?,0,0,
        0.0,0,0.00,.0,?,.0,0,0,.0,.0,
        0,0,0,NULL,0.0,0,0,.0,0,0,0,0,0,'',
        ?,2)");
    $args2 = array($LANE_NO,$EMP_NO,$t_no,$tn,$tender,$amt,$CARD_NO);
    $dbc->exec_statement($insQ,$args);
    $dbc->exec_statement($insQ2,$args2);

    printf("Receipt is %d-%d-%d.",
        $EMP_NO,$LANE_NO,$t_no);
}

include($FANNIE_ROOT.'src/footer.html');
?>
