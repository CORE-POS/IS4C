<?php
include('../../../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include($FANNIE_ROOT.'src/Credentials/OutsideDB.is4c.php');

class AnnualMeetingDetailPage extends FannieRESTfulPage {
    protected $header = "Annual Meeting Registration";
    protected $title = "Annual Meeting Registration";

/*
else if (isset($_REQUEST['memnum'])){
    if (!empty($_REQUEST['memnum'])){
        $q1 = sprintf("SELECT CardNo FROM custdata WHERE CardNo=%d",$_REQUEST['memnum']);
        $r1 = $dbc->query($q1);
        $cn = -1;
        if ($dbc->num_rows($r1) == 0){
            $upc = str_pad($_REQUEST['memnum'],13,'0',STR_PAD_LEFT);
            $q2 = sprintf("SELECT card_no membercards WHERE upc=%s",$dbc->escape($upc));
            $r2 = $dbc->query($q2);
            if ($dbc->num_rows($r2)==0){
                echo 'Account not found<br /><br />';
                echo '<input type="submit" 
                    onclick="location=\'index.php\';return false;"
                    value="Go Back" />';
            }
            else
                $cn = array_pop($dbc->fetch_row($r2));
        }
        else
            $cn = array_pop($dbc->fetch_row($r1));
        if ($cn != -1)
            showForm($cn);
    }
    else if (!empty($_REQUEST['ln'])){
        $q1 = sprintf("SELECT CardNo,LastName,FirstName FROM custdata WHERE LastName LIKE %s",
            $dbc->escape($_REQUEST['ln'].'%'));
        if (!empty($_REQUEST['fn']))
            $q1 .= sprintf(" AND FirstName LIKE %s",$dbc->escape($_REQUEST['fn'].'%'));
        $r1 = $dbc->query($q1);
        if ($dbc->num_rows($r1) == 1){
            showForm(array_pop($dbc->fetch_row($r1)));
        }
        else if ($dbc->num_rows($r1) == 0){
            $q2 = sprintf("SELECT CardNo,LastName,FirstName FROM custdata WHERE LastName LIKE %s",
                $dbc->escape('%'.$_REQUEST['ln'].'%'));
            if (!empty($_REQUEST['fn']))
                $q2 .= sprintf(" AND FirstName LIKE %s",$dbc->escape('%'.$_REQUEST['fn'].'%'));
            $r2 = $dbc->query($q2);
            if ($dbc->num_rows($r2) == 1){
                showForm(array_pop($dbc->fetch_row($r2)));
            }
            else if ($dbc->num_rows($r2) == 0){
                echo 'Account not found<br /><br />';
                echo '<input type="submit" 
                    onclick="location=\'index.php\';return false;"
                    value="Go Back" />';
            }
            else
                multipleMatches($r2);
        }
        else
            multipleMatches($r1);
    }
}
*/

    function get_id_handler(){
        global $FANNIE_TRANS_DB, $FANNIE_OP_DB, $dbc;
        $fannie = FannieDB::get($FANNIE_OP_DB);
        
        $cardno = $this->id;
        $upc = str_pad($this->id,13,'0',STR_PAD_LEFT);
        $matches = array();
        if ($this->id !== ''){
            $cardP = $fannie->prepare_statement('SELECT CardNo FROM
                custdata WHERE CardNo=? AND personNum=1 AND Type=\'PC\'');
            $cardR = $fannie->exec_statement($cardP, array($cardno));
            if ($fannie->num_rows($cardR) == 0){
                $upcP = $fannie->prepare_statement('SELECT card_no
                    FROM memberCards WHERE upc=?');
                $upcR = $fannie->exec_statement($upcP, array($upc));
                if ($fannie->num_rows($upcR) > 0){
                    $upcW = $fannie->fetch_row($upcR);
                    $cardno = $upcW['card_no'];
                }
                else
                    $cardno = False;
            }
        }
        elseif (FormLib::get_form_value('ln') !== ''){
            $nameQ = "SELECT CardNo, FirstName, LastName FROM custdata
                WHERE personNum=1 AND Type='PC' AND LastName LIKE ?";
            $args = array('%'.FormLib::get_form_value('ln').'%');
            if (FormLib::get_form_value('fn') !== ''){
                $args[] = '%'.FormLib::get_form_value('fn').'%';
                $nameQ .= ' AND FirstName LIKE ?';
            }
            $nameP = $fannie->prepare_statement($nameQ);
            $nameR = $fannie->exec_statement($nameP, $args);
            while($w = $fannie->fetch_row($nameR))
                $matches[$w['CardNo']] = $w['FirstName'].' '.$w['LastName'];
            $cardno = False;
        }

        if (count($matches) == 1){
            list($cardno) = array_keys($matches);
            $matches = array();
        }

        $this->card_no = $cardno;
        $this->matches = $matches;
        return True;
    }

    function get_view(){
        $ret = '<form action="AnnualMeetingDetailPage.php" method="get">';
        $ret .= '<b># or UPC</b> <input type="text" name="id" /><br /><br />';
        $ret .= '<b>Last Name</b> <input type="text" name="ln" /> ';
        $ret .= '<b>First Name</b> <input type="text" name="fn" /> ';
        $ret .= '<br /><br />';
        $ret .= '<input type="submit" value="Submit" />';
        $ret .= '</form>';
        return $ret;
    }

    function get_id_view(){
        if ($this->card_no)
            return $this->showRegistration($this->card_no);
        elseif(count($this->matches) > 0)
            return $this->showMultiple($this->matches);
        else {
            $ret = '<em>No results found</em><hr />';
            $ret .= $this->get_view();
            return $ret;
        }
    }

    function showRegistration($cn){
        global $dbc, $FANNIE_OP_DB, $FANNIE_TRANS_DB;

        $fannieDB = FannieDB::get($FANNIE_OP_DB);
        // POS registrations from today
        $hereQ = "SELECT MIN(tdate) AS tdate,d.card_no,".
            $fannieDB->concat('c.FirstName',' ','c.LastName','')." as name,
            m.phone, m.email_1 as email,
            SUM(CASE WHEN charflag IN ('M','V','S') THEN quantity ELSE 0 END)-1 as guest_count,
            SUM(CASE WHEN charflag IN ('K') THEN quantity ELSE 0 END) as child_count,
            SUM(CASE WHEN charflag = 'M' THEN quantity ELSE 0 END) as chicken,
            SUM(CASE WHEN charflag = 'V' THEN quantity ELSE 0 END) as veg,
            SUM(CASE WHEN charflag = 'S' THEN quantity ELSE 0 END) as vegan,
            'pos' AS source
            FROM ".$FANNIE_TRANS_DB.$fannieDB->sep()."dlog AS d
            LEFT JOIN custdata AS c ON c.CardNo=d.card_no AND c.personNum=1
            LEFT JOIN meminfo AS m ON d.card_no=c.card_no
            WHERE upc IN ('0000000001041','0000000001042')
            AND d.card_no = ?
            ORDER BY MIN(tdate)";
        $records = array();
        $hereP = $fannieDB->prepare_statement($hereQ);
        $hereR = $fannieDB->exec_statement($hereP, array($cn));
        while($hereW = $fannieDB->fetch_row($hereR)){
            $records[] = $hereW;
        }

        // POS registrations from last 90 days
        $hereQ = str_replace('dlog ','dlog_90_view ',$hereQ);
        $hereP = $fannieDB->prepare_statement($hereQ);
        $hereR = $fannieDB->exec_statement($hereP, array($cn));
        while($hereW = $fannieDB->fetch_row($hereR)){
            $records[] = $hereW;
        }

        // online registrations
        $q = "SELECT tdate,r.card_no,name,email,
            phone,guest_count,child_count,
            SUM(CASE WHEN m.subtype=1 THEN 1 ELSE 0 END) as chicken,
            SUM(CASE WHEN m.subtype=2 THEN 1 ELSE 0 END) as veg,
            SUM(CASE WHEN m.subtype=3 THEN 1 ELSE 0 END) as vegan,
            'website' AS source
            FROM registrations AS r LEFT JOIN
            regMeals AS m ON r.card_no=m.card_no
            WHERE r.card_no = ?
            GROUP BY tdate,r.card_no,name,email,
            phone,guest_count,child_count
            ORDER BY tdate";
        $p = $dbc->prepare_statement($q);
        $r = $dbc->exec_statement($p, array($cn));
        while($w = $dbc->fetch_row($r)){
            $records[] = $w;
        }

        if (count($records) == 0){
            return 'Owner #'.$cn.' is not currently registered
                <input type="submit" 
                onclick="location=\'AnnualMeetingDetailPage.php\';return false;"
                value="Go Back" />';
        }
        
        $ret = '<table><tr><th>Date</th><th>Chicken</th><th>Veg</th><th>Vegan</th><th>Kids</th><th>Source</th></tr>';
        foreach($records as $r){
            $ret .= sprintf('<tr><td>%s</td><td>%d</td><td>%d</td>
                    <td>%d</td><td>%d</td><td>%s</td></tr>',
                    $r['tdate'], $r['chicken'], $r['veg'],
                    $r['vegan'], $r['child_count'], $r['source']
            );
        }
        $ret .= '</table>';

        $ret .= sprintf('%d %s<br />Ph: %s<br />Email: %s<br />',
                $records[0]['card_no'],$records[0]['phone'],
                $records[0]['email']);
        return $ret;
    }

    function showMultiple($matches){
        $ret = '<b>Multiple matching accounts: </b>';
        $ret .= '<select onchange="location=\'AnnualMeetingDetailPage.php?id=\'+this.value;">';
        $ret .= '<option>Choose...</option>';
        foreach($matches as $cn => $name){
            $ret .= sprintf("<option value=%d>%d %s</option>",$cn, $cn, $name);
        }
        $ret .= '</select><br /><br />';
        $ret .= '<input type="submit" 
            onclick="location=\'AnnualMeetingDetailPage.php\';return false;"
            value="Go Back" />';
        return $ret;
    }

}

FannieDispatch::conditionalExec();

?>
