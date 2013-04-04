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
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class MemStatusEditor extends FanniePage {
	protected $header = "Customer Status";
	protected $title = "Fannie :: Customer Status";
	protected $must_authenticate = True;
	protected $auth_classes =  array('editmembers');

	private $cardno;

	function preprocess(){
		$this->cardno = FormLib::get_form_value('memID',False);


		if (FormLib::get_form_value('savebtn',False) !== False){
			$reason = 0;
			$codes = FormLib::get_form_value('rcode',array());
			$type = FormLib::get_form_value('type','INACT');
			if (is_array($codes)){
				foreach($codes as $r)
					$reason = $reason | $r;
			}

			if ($reason == 0)
				$this->reactivate_account($this->cardno);
			else
				$this->deactivate_account($this->cardno, $reason, $type);
		
			header("Location: MemberEditor.php?memNum=".$this->cardno);
			return False;
		}
		return True;
	}

	function body_content(){
		global $FANNIE_OP_DB;

		if ($this->cardno === False){
			return '<i>Error - no member specified</i>';
		}

		$dbc = FannieDB::get($FANNIE_OP_DB);
		$ret = sprintf('<h3>Account #%d</h3>',$this->cardno);
		$ret .=  '<form action="MemStatusEditor.php" method="post">';
		$ret .= sprintf('<input type="hidden" value="%d" name="memID" />',$this->cardno);

		$statusQ = $dbc->prepare_statement("SELECT Type FROM custdata WHERE CardNo=?");
		$statusR = $dbc->exec_statement($statusQ,array($this->cardno));
		$status_string = array_pop($dbc->fetch_row($statusR));

		$reasonQ = $dbc->prepare_statement("SELECT textStr,mask,
			CASE WHEN cardno IS NULL THEN 0 ELSE 1 END as checked
			FROM reasoncodes AS r LEFT JOIN suspensions AS s
			ON s.cardno=? AND r.mask & s.reasoncode <> 0
			ORDER BY mask");
		$reasonR = $dbc->exec_statement($reasonQ,array($this->cardno));
		$ret .= '<table cellpadding="4" cellspacing="0" border="1">';
		$ret .= '<tr><td colspan="2">Mode <select name="type">';
		$ret .= '<option value="INACT">Inactive</option>';
		$ret .= '<option value="TERM" '.($status_string=='TERM'?'selected':'').'>Terminated</option>';
		$ret .= '</select></td></tr>';
		while($reasonW = $dbc->fetch_row($reasonR)){
			$ret .= sprintf('<tr><td><input type="checkbox" name="rcode[]" value="%d" %s</td>
				<td>%s</td></tr>',
				$reasonW['mask'],
				($reasonW['checked']==1?'checked':''),
				$reasonW['textStr']
			);
		}
		$ret .= '</table><br />';
		$ret .= '<input type="submit" value="Save" name="savebtn" />';
		$ret .= '</form>';

		return $ret;
	}
	function reactivate_account($cardno){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		// fetch stored values
		$valQ = $dbc->prepare_statement("SELECT memtype1,memtype2,mailflag,discount,chargelimit
			FROM suspensions WHERE cardno=?");
		$valR = $dbc->exec_statement($valQ,array($cardno));
		$valW = $dbc->fetch_row($valR);

		// restore stored values
		$fixQ = $dbc->prepare_statement("UPDATE custdata SET Type=?, memType=?,
				Discount=?, memDiscountLimit=?
				WHERE CardNo=?");
		$fixR = $dbc->exec_statement($fixQ,array($valW['memtype2'],$valW['memtype1'],
				$valW['discount'],$valW['chargelimit'],$cardno));

		$mailQ = $dbc->prepare_statement("UPDATE meminfo SET ads_OK=? WHERE card_no=?");
		$mailR = $dbc->exec_statement($mailQ,array($valW['mailflag'],$cardno));

		// remove suspension and log action to history
		$delQ = $dbc->prepare_statement("DELETE FROM suspensions WHERE cardno=?");
		$delR = $dbc->exec_statement($delQ,$cardno);

		$username = $this->current_user;
		$now = date('Y-m-d h:i:s');
		$histQ = $dbc->prepare_statement("INSERT INTO suspension_history (username, postdate,
			post, cardno, reasoncode) VALUES (?,".$dbc->now().",'Account reactivated',?,-1)");
		$histR = $dbc->exec_statement($histQ,array($username,$cardno));

	}

	function deactivate_account($cardno, $reason, $type){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$chkQ = $dbc->prepare_statement("SELECT cardno FROM suspensions WHERE cardno=?");
		$chkR = $dbc->exec_statement($chkQ,array($cardno));
		if ($dbc->num_rows($chkR)>0){
			// if account is already suspended, just update the reason
			$upQ = $dbc->prepare_statement("UPDATE suspensions SET reasoncode=?, type=?
				WHERE cardno=?");
			$upR = $dbc->exec_statement($upQ,array($reason,substr($type,0,1),$cardno));
		}
		else {
			// new suspension
			// get current values and save them in suspensions table
			$cdQ = $dbc->prepare_statement("SELECT memType,Type,Discount,memDiscountLimit,
				ads_OK FROM custdata AS c LEFT JOIN meminfo AS m
				ON c.CardNo=m.card_no AND c.personNum=1
				WHERE c.CardNo=?");
			$cdR = $dbc->exec_statement($cdQ,array($cardno));
			$cdW = $dbc->fetch_row($cdR);	

			$now = date('Y-m-d H:i:s');
			$insQ = $dbc->prepare_statement("INSERT INTO suspensions (cardno, type, memtype1,
				memtype2, reason, suspDate, mailflag, discount, chargelimit,
				reasoncode) VALUES (?,?,?,?,'',".$dbc->now().",?,?,?,?)");
			$insR = $dbc->exec_statement($insQ,array($cardno, substr($type,0,1), 
					$cdW['memType'],$cdW['Type'], $cdW['ads_OK'],
					$cdW['Discount'],$cdW['memDiscountLimit'],$reason));

			// log action
			$username = $this->current_user;
			$histQ = $dbc->prepare_statement("INSERT INTO suspension_history (username, postdate,
				post, cardno, reasoncode) VALUES (?,".$dbc->now().",'',?,?)");
			$histR = $dbc->exec_statement($histQ,array($username,$cardno,$reason));
		}

		// remove account privileges in custdata
		$deactivateQ = $dbc->prepare_statement("UPDATE custdata SET Type=?,memType=0,Discount=0,
				memDiscountLimit=0 WHERE CardNo=?");
		$deactivateR = $dbc->exec_statement($deactivateQ,array($type,$cardno));

		$mailingQ = $dbc->prepare_statement("UPDATE meminfo SET ads_OK=0 WHERE card_no=?");
		$mailingR = $dbc->exec_statement($mailingQ,array($cardno));
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new MemStatusEditor();
	$obj->draw_page();
}

?>
