<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

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

/**
  @class TenderModule
  Base class for modular tenders
*/
class TenderModule {

	var $tender_code;
	var $amount;

	var $name_string;
	var $change_string;
	var $min_limit;
	var $max_limit;

	/**
	  Constructor
	  @param $code two letter tender code
	  @param $amt tender amount
	*/
	function TenderModule($code, $amt){
		$this->tender_code = $code;
		$this->amount = $amt;

		$db = Database::pDataConnect();
		$query = "select TenderID,TenderCode,TenderName,TenderType,
			ChangeMessage,MinAmount,MaxAmount,MaxRefund from 
			tenders where tendercode = '".$this->tender_code."'";
		$result = $db->query($query);

		if ($db->num_rows($result) > 0){
			$row = $db->fetch_array($result);
			$this->name_string = $row['TenderName'];
			$this->change_string = $row['ChangeMessage'];
			$this->min_limit = $row['MinAmount'];
			$this->max_limit = $row['MaxAmount'];
		}
		else {
			$this->name_string = "";
			$this->change_string = "";
			$this->min_limit = 0;
			$this->max_limit = 0;
		}

		$db->close();
	}

	/**
	  Check for errors
	  @return True or an error message string
	*/
	function ErrorCheck(){
		global $CORE_LOCAL;

		if ($CORE_LOCAL->get("LastID") == 0){
			return DisplayLib::boxMsg("No transaction in progress");
		}
		elseif ($this->amount > 9999.99){
			return DisplayLib::boxMsg("tender amount of ".$this->amount."<br />exceeds allowable limit");
		}
		elseif ($CORE_LOCAL->get("ttlflag") == 0) {
			return DisplayLib::boxMsg("transaction must be totaled before tender can be accepted");
		}
		else if ($this->name_string === ""){
			return DisplayLib::inputUnknown();
		}

		return True;
	}
	
	/**
	  Set up state and redirect if needed
	  @return True or a URL to redirect
	*/
	function PreReqCheck(){
		global $CORE_LOCAL;
		if ($this->amount > $this->max_limit && $CORE_LOCAL->get("msgrepeat") == 0){
			$CORE_LOCAL->set("boxMsg","$".$this->amount." is greater than tender limit "
			."for ".$row['TenderName']."<p>"
			."<font size='-1'>[clear] to cancel, [enter] to proceed</font>");
			return MiscLib::base_url().'gui-modules/boxMsg2.php';
		}

		if ($this->amount - $CORE_LOCAL->get("amtdue") > 0) {
			$CORE_LOCAL->set("change",$this->amount - $CORE_LOCAL->get("amtdue"));
		}
		else {
			$CORE_LOCAL->set("change",0);
		}
		return True;
	}

	/**
	  Add tender to the transaction
	*/
	function Add(){
		TransRecord::addItem('', $this->name_string, "T", $this->tender_code, 
			"", 0, 0, 0, -1*$this->amount, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}

}

?>
