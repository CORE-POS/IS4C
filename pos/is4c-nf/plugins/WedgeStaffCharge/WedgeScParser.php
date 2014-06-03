<?php
/*******************************************************************************

    Copyright 2007,2013 Whole Foods Co-op

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

class WedgeScParser extends Parser 
{
	private $left;

	function check($str)
    {
		if (substr($str,-2) == "SC") {
			$left = substr($str,0,strlen($str)-2);
			$left = str_replace($left,"."," ");
			$left = str_replace($left,","," ");
			if (!is_numeric($left) || strlen($left != 6)) {
				return false;
            }
			$this->left = $left;

			return true;
		}

		return false;
	}

	function parse($str)
    {
		global $CORE_LOCAL;
		$json = $this->default_json();
		$arg = $this->left;

		$CORE_LOCAL->set("sc",1);
		$staffID = substr($arg, 0, 4);

		$pQuery = "select staffID,chargecode,blueLine from chargecodeview where chargecode = '".$arg."'";
		$pConn = Database::pDataConnect();
		$result = $pConn->query($pQuery);
		$num_rows = $pConn->num_rows($result);
		$row = $pConn->fetch_array($result);

		if ($num_rows == 0) {
			$json['output'] = DisplayLib::xboxMsg("unable to authenticate staff ".$staffID);
			$CORE_LOCAL->set("isStaff",0);			// apbw 03/05/05 SCR
			return $json;
		} else {
			$CORE_LOCAL->set("isStaff",1);			// apbw 03/05/05 SCR
			$CORE_LOCAL->set("memMsg",$row["blueLine"]);
			$tQuery = "update localtemptrans set card_no = '".$staffID."', percentDiscount = 15";
			$tConn = Database::tDataConnect();

			$this->addscDiscount();
			TransRecord::discountnotify(15);
			$tConn->query($tQuery);
			Database::getsubtotals();

			$chk = self::ttl();
			if ($chk !== True){
				$json['main_frame'] = $chk;
				return $json;
			}
			$CORE_LOCAL->set("runningTotal",$CORE_LOCAL->get("amtdue"));
			return self::tender("MI", $CORE_LOCAL->get("runningTotal") * 100);
		}
	}

    private function addscDiscount() 
    {
        global $CORE_LOCAL;

        if ($CORE_LOCAL->get("scDiscount") != 0) {
            TransRecord::addRecord(array(
                'upc' => "DISCOUNT", 
                'description' => "** 10% Deli Discount **", 
                'trans_type' => "I",
                'quantity' => 1, 
                'ItemQtty' => 1, 
                'unitPrice' => MiscLib::truncate2(-1 * $CORE_LOCAL->get("scDiscount")), 
                'total' => MiscLib::truncate2(-1 * $CORE_LOCAL->get("scDiscount")), 
                'discountable' => 1,
                'voided' => 2,
            ));
        }
    }

    private function addStaffCoffeeDiscount() 
    {
        global $CORE_LOCAL;

        if ($CORE_LOCAL->get("staffCoffeeDiscount") != 0) {
            self::addItem("DISCOUNT", "** Coffee Discount **", "I", "", "", 0, 1, MiscLib::truncate2(-1 * $CORE_LOCAL->get("staffCoffeeDiscount")), MiscLib::truncate2(-1 * $CORE_LOCAL->get("staffCoffeeDiscount")), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2);
        }
    }

	function doc()
    {
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>amount</i>SC</td>
				<td>Tender <i>amount</i> to staff
				charge</td>
			</tr>
			</table>";
	}
}

