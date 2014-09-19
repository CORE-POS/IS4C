<?php

include(dirname(__FILE__) . '/../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:0;

$header = "Email List";
$page_title = "Fannie :: Email List";
include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['types'])){
    $tstr = "(";
    $args = array();
    foreach($_REQUEST['types'] as $t){
        $tstr .= "?,";
        $args[] = (int)$t;
    }
    $tstr = rtrim($tstr,",").")";

    $q = "SELECT m.email_1 FROM
        meminfo AS m LEFT JOIN
        memContact as c ON m.card_no=c.card_no
        LEFT JOIN custdata AS a
        ON m.card_no=a.CardNo AND a.personNum=1";
    if (isset($_REQUEST['inactives'])){
        $q .= " LEFT JOIN suspensions AS s ON
            m.card_no=s.cardno";
    }
    $q .= " WHERE ";
    if (isset($_REQUEST['all']) && $_REQUEST['all'] != 'All Accounts')
        $q .= "c.pref IN (2) AND ";
    $q .= "(a.memType IN $tstr ";
    if (isset($_REQUEST['inactives'])){
        $q .= "OR (s.memType1 IN $tstr AND s.type='I')";
        /* double up arguments */
        $temp = $args;
        foreach($args as $a) $temp[] = $a;
        $args = $temp;
    }
    $q .= ") AND email_1 LIKE '%@%.%'";
    $p = $dbc->prepare_statement($q);
    $r = $dbc->exec_statement($p,$args);

    echo 'Matched '.$dbc->num_rows($r).' accounts';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;';
    echo '<input type="submit" value="Select All"
        onclick="$(\'#emailListing\').focus();$(\'#emailListing\').select();"
        /><br />';
    echo '<textarea id="emailListing" style="width:100%;height:400px;background:#cccccc;border=solid 1px black;padding:10px;">';
    while($w = $dbc->fetch_row($r)){
        echo $w[0]."\n";
    }   
    echo '</textarea>';
}
else {
    echo '<form action="index.php" method="get">';
    echo '<div style="float:left;"><fieldset><legend>Include Types</legend>';
    $p = $dbc->prepare_statement("SELECT memtype,memDesc FROM memtype ORDER BY memtype");
    $r = $dbc->exec_statement($p);
    while($w = $dbc->fetch_row($r)){
        printf('<input type="checkbox" value="%d" name="types[]" /> %s <br />',
            $w['memtype'],$w['memDesc']);
    }
    echo '</fieldset></div>';
    echo '<div style="float:left;margin-left:20px;line-height:300%;">';
    echo '<input type="checkbox" name="inactives" /> Include Inactive Accounts<br />';
    echo '<select name="all"><option>Accounts that prefer Email</option><option>All Accounts</option></select><br />';
    echo '<br /><input type="submit" name="btn" value="Get Emails" />';
    echo '</div><div style="clear:left;"></div>';
    echo '</form>';
}


include($FANNIE_ROOT.'src/footer.html');

?>
