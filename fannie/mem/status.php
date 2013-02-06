<?php 
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include_once($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('editmembers')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}mem/");
	exit;
}

$header = "Customer Status";
$page_title = "Fannie :: Customer Status";

$cardno = isset($_REQUEST['memID']) ? $_REQUEST['memID'] : 0;


if ($cardno == 0){
	include($FANNIE_ROOT.'src/header.html');
	echo '<i>Error - no member specified</i>';
}
else {

	if (isset($_REQUEST['savebtn'])){
		$reason = 0;
		if (isset($_REQUEST['rcode']) && is_array($_REQUEST['rcode'])){
			foreach($_REQUEST['rcode'] as $r)
				$reason = $reason | $r;
		}

		if ($reason == 0)
			reactivate_account($cardno);
		else
			deactivate_account($cardno, $reason, $_REQUEST['type']);
		
		header("Location: edit.php?memNum=".$cardno);
		exit;
	}

	include($FANNIE_ROOT.'src/header.html');
	printf('<h3>Account #%d</h3>',$cardno);
	echo '<form action="status.php" method="post">';
	printf('<input type="hidden" value="%d" name="memID" />',$cardno);

	$statusQ = sprintf("SELECT Type FROM custdata WHERE CardNo=%d",$cardno);
	$statusR = $dbc->query($statusQ);
	$status_string = array_pop($dbc->fetch_row($statusR));

	$reasonQ = "SELECT textStr,mask,
		CASE WHEN cardno IS NULL THEN 0 ELSE 1 END as checked
		FROM reasoncodes AS r LEFT JOIN suspensions AS s
		ON s.cardno=$cardno AND r.mask & s.reasoncode <> 0
		ORDER BY mask";
	$reasonR = $dbc->query($reasonQ);
	echo '<table cellpadding="4" cellspacing="0" border="1">';
	echo '<tr><td colspan="2">Mode <select name="type">';
	echo '<option value="INACT">Inactive</option>';
	echo '<option value="TERM" '.($status_string=='TERM'?'selected':'').'>Terminated</option>';
	echo '</select></td></tr>';
	while($reasonW = $dbc->fetch_row($reasonR)){
		printf('<tr><td><input type="checkbox" name="rcode[]" value="%d" %s</td>
			<td>%s</td></tr>',
			$reasonW['mask'],
			($reasonW['checked']==1?'checked':''),
			$reasonW['textStr']
		);
	}
	echo '</table><br />';
	echo '<input type="submit" value="Save" name="savebtn" />';
	echo '</form>';
}

include($FANNIE_ROOT.'src/footer.html');

function reactivate_account($cardno){
	global $dbc;

	// fetch stored values
	$valQ = "SELECT memtype1,memtype2,mailflag,discount,chargelimit
		FROM suspensions WHERE cardno=".((int)$cardno);
	$valR = $dbc->query($valQ);
	$valW = $dbc->fetch_row($valR);

	// restore stored values
	$fixQ = sprintf("UPDATE custdata SET Type=%s, memType=%d,
			Discount=%d, memDiscountLimit=%.2f
			WHERE CardNo=%d",
			$dbc->escape($valW['memtype2']),$valW['memtype1'],
			$valW['discount'],$valW['chargelimit'],$cardno);
	$fixR = $dbc->query($fixQ);

	$mailQ = sprintf("UPDATE meminfo SET ads_OK=%d WHERE card_no=%d",
			$valW['mailflag'],$cardno);
	$mailR = $dbc->query($mailQ);

	// remove suspension and log action to history
	$delQ = "DELETE FROM suspensions WHERE cardno=".((int)$cardno);
	$delR = $dbc->query($delQ);

	$username = validateUserQuiet('editmembers');
	$now = date('Y-m-d h:i:s');
	$histQ = sprintf("INSERT INTO suspension_history (username, postdate,
		post, cardno, reasoncode) VALUES (%s,%s,'Account reactivated',%d,-1)",
		$dbc->escape($username),$dbc->escape($now),$cardno);
	$histR = $dbc->query($histQ);

}

function deactivate_account($cardno, $reason, $type){
	global $dbc;

	$chkQ = "SELECT cardno FROM suspensions WHERE cardno=".$cardno;
	$chkR = $dbc->query($chkQ);
	if ($dbc->num_rows($chkR)>0){
		// if account is already suspended, just update the reason
		$upQ = sprintf("UPDATE suspensions SET reasoncode=%d, type=%s
			WHERE cardno=%d",$reason,$dbc->escape(substr($type,0,1)),
			$cardno);
		$upR = $dbc->query($upQ);
	}
	else {
		// new suspension
		// get current values and save them in suspensions table
		$cdQ = "SELECT memType,Type,Discount,memDiscountLimit,
			ads_OK FROM custdata AS c LEFT JOIN meminfo AS m
			ON c.CardNo=m.card_no AND c.personNum=1
			WHERE c.CardNo=".((int)$cardno);
		$cdR = $dbc->query($cdQ);
		$cdW = $dbc->fetch_row($cdR);	

		$now = date('Y-m-d H:i:s');
		$insQ = sprintf("INSERT INTO suspensions (cardno, type, memtype1,
			memtype2, reason, suspDate, mailflag, discount, chargelimit,
			reasoncode) VALUES (%d,%s,%d,%s,'',%s,%d,%d,%.2f,%d)",
			$cardno, $dbc->escape(substr($type,0,1)), $cdW['memType'],
			$dbc->escape($cdW['Type']), $dbc->escape($now), $cdW['ads_OK'],
			$cdW['Discount'],$cdW['memDiscountLimit'],$reason);
		$insR = $dbc->query($insQ);	

		// log action
		$username = validateUserQuiet('editmembers');
		$histQ = sprintf("INSERT INTO suspension_history (username, postdate,
			post, cardno, reasoncode) VALUES (%s,%s,'',%d,%d)",
			$dbc->escape($username),$dbc->escape($now),$cardno,$reason);
		$histR = $dbc->query($histQ);
	}

	// remove account privileges in custdata
	$deactivateQ = sprintf("UPDATE custdata SET Type=%s,memType=0,Discount=0,
			memDiscountLimit=0 WHERE CardNo=%d",
			$dbc->escape($type), $cardno);
	$deactivateR = $dbc->query($deactivateQ);

	$mailingQ = sprintf("UPDATE meminfo SET ads_OK=0 WHERE card_no=%d",
			$cardno);
	$mailingR = $dbc->query($mailingQ);
}

?>
