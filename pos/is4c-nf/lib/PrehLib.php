<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	13Feb2013 Andy Theuninck visitingMem support for memdiscountadd
	13Jan2013 Eric Lee New omtr_ttl() based on ttl() for Ontario Meal Tax Rebate.
	18Sep2012 Eric Lee In setMember support for not displaying subtotal.

*/

/**
  @class PrehLib
  A horrible, horrible catch-all clutter of functions
*/
class PrehLib extends LibraryClass 
{

/**
  Remove member number from current transaction
*/
static public function clearMember()
{
	global $CORE_LOCAL;

	CoreState::memberReset();
	$db = Database::tDataConnect();
	$db->query("UPDATE localtemptrans SET card_no=0,percentDiscount=NULL");
	$CORE_LOCAL->set("ttlflag",0);	
}

/**
  Set member number for transaction
  @param $member_number CardNo from custdata
  @return An array. See Parser::default_json()
   for format.

  This function will either assign the number
  to the current transaction or return a redirect
  request to get more input. If you want the
  cashier to verify member name from a list, use
  this function. If you want to force the number
  to be set immediately, use setMember().
*/
static public function memberID($member_number) 
{
	global $CORE_LOCAL;

	$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
		ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
		SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
		where CardNo = '".$member_number."'";

	$ret = array(
		"main_frame"=>false,
		"output"=>"",
		"target"=>".baseHeight",
		"redraw_footer"=>false
	);
	
	$db = Database::pDataConnect();
	$result = $db->query($query);

	$num_rows = $db->num_rows($result);

	if ($num_rows == 1 && 
		$member_number == $CORE_LOCAL->get("defaultNonMem")) {
           	$row = $db->fetch_array($result);
	     	self::setMember($row["CardNo"], $row["personNum"],$row);
		$ret['redraw_footer'] = True;
		$ret['output'] = DisplayLib::lastpage();
		return $ret;
	} 

	// special hard coding for member 5607 WFC 
	// needs to go away
	if ($member_number == "5607") {
		$ret['main_frame'] = MiscLib::baseURL()."gui-modules/requestInfo.php?class=PrehLib";
	}

	$CORE_LOCAL->set("memberID","0");
	$CORE_LOCAL->set("memType",0);
	$CORE_LOCAL->set("percentDiscount",0);
	$CORE_LOCAL->set("memMsg","");

	if (empty($ret['output']) && $ret['main_frame'] == false) {
		$ret['main_frame'] = MiscLib::base_url()."gui-modules/memlist.php?idSearch=".$member_number;
    }
	
	if ($CORE_LOCAL->get("verifyName") != 1) {
		$ret['udpmsg'] = 'goodBeep';
	}

	return $ret;
}

static public $requestInfoHeader = 'member gift';
static public $requestInfoMsg = 'Card for which member?';
static public function requestInfoCallback($info)
{
	TransRecord::addcomment("CARD FOR #".$info);

	$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
		ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
		SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
		where CardNo = 5607";
	$db = Database::pDataConnect();
	$result = $db->query($query);
	$row = $db->fetch_row($result);
	self::setMember($row["CardNo"], $row["personNum"],$row);

	return true;
}

/**
  Assign a member number to a transaction
  @param $member CardNo from custdata
  @param $personNumber personNum from custdata
  @param $row a record from custdata

  See memberID() for more information.
*/
static public function setMember($member, $personNumber, $row) 
{
	global $CORE_LOCAL;

	$conn = Database::pDataConnect();

    $memMsg = '#'.$member;
    if (isset($row['blueLine'])) {
        $memMsg = $row['blueLine'];
    }
	$CORE_LOCAL->set("memMsg",$memMsg);

	$chargeOk = self::chargeOk();
	if ($CORE_LOCAL->get("balance") != 0 && $member != $CORE_LOCAL->get("defaultNonMem")) {
	      $CORE_LOCAL->set("memMsg",$CORE_LOCAL->get("memMsg")." AR");
    }
      
	$CORE_LOCAL->set("memberID",$member);
	$CORE_LOCAL->set("memType",$row["memType"]);
	$CORE_LOCAL->set("lname",$row["LastName"]);
	$CORE_LOCAL->set("fname",$row["FirstName"]);
	$CORE_LOCAL->set("Type",$row["Type"]);
	$CORE_LOCAL->set("percentDiscount",$row["Discount"]);

	/**
	  Use discount module to calculate modified percentDiscount
	*/
	$handler_class = $CORE_LOCAL->get('DiscountModule');
	if ($handler_class === '') $handler_class = 'DiscountModule';
	elseif (!class_exists($handler_class)) $handler_class = 'DiscountModule';
	if (class_exists($handler_class)){
		$module = new $handler_class();
		$CORE_LOCAL->set('percentDiscount', $module->percentage($row['Discount']));
	}

	if ($CORE_LOCAL->get("Type") == "PC") {
		$CORE_LOCAL->set("isMember",1);
	} else {
        $CORE_LOCAL->set("isMember",0);
	}

	$CORE_LOCAL->set("isStaff",$row["staff"]);
	$CORE_LOCAL->set("SSI",$row["SSI"]);

	if ($CORE_LOCAL->get("SSI") == 1) {
		$CORE_LOCAL->set("memMsg",$CORE_LOCAL->get("memMsg")." #");
    }

	$conn2 = Database::tDataConnect();
	$memquery = "update localtemptrans set card_no = '".$member."',
	      				memType = ".sprintf("%d",$CORE_LOCAL->get("memType")).",
					staff = ".sprintf("%d",$CORE_LOCAL->get("isStaff"));
	if ($CORE_LOCAL->get("DBMS") == "mssql" && $CORE_LOCAL->get("store") == "wfc") {
		$memquery = str_replace("staff","isStaff",$memquery);
    }

	if ($CORE_LOCAL->get("store") == "wedge") {
		if ($CORE_LOCAL->get("isMember") == 0 && $CORE_LOCAL->get("percentDiscount") == 10) {
			$memquery .= " , percentDiscount = 0 ";
		} elseif ($CORE_LOCAL->get("isStaff") != 1 && $CORE_LOCAL->get("percentDiscount") == 15) {
			$memquery .= " , percentDiscount = 0 ";
		}
	}

	if ($CORE_LOCAL->get("discountEnforced") != 0) {
		$memquery .= " , percentDiscount = ".$CORE_LOCAL->get("percentDiscount")." ";
	} else if ($CORE_LOCAL->get("discountEnforced") == 0 && $CORE_LOCAL->get("tenderTotal") == 0) {
		$memquery .= " , percentDiscount = 0 ";
	}

	$conn2->query($memquery);

	$opts = array('upc'=>'MEMENTRY','description'=>'CARDNO IN NUMFLAG','numflag'=>$member);
	TransRecord::add_log_record($opts);

	if ($CORE_LOCAL->get("isStaff") == 0) {
		$CORE_LOCAL->set("staffSpecial",0);
	}

	// 16Sep12 Eric Lee Allow  not append Subtotal at this point.
	if ( $CORE_LOCAL->get("member_subtotal") === false ) {
		$noop = "";
	} else {
		self::ttl();
	} 
}

/**
  Check if the member has overdue store charge balance
  @param $cardno member number
  @return True or False

  The logic for what constitutes past due has to be built
  into the unpaid_ar_today view. Without that this function
  doesn't really do much.
*/
static public function checkUnpaidAR($cardno)
{
	global $CORE_LOCAL;

	// only attempt if server is available
	// and not the default non-member
	if ($cardno == $CORE_LOCAL->get("defaultNonMem")) return false;
	if ($CORE_LOCAL->get("balance") == 0) return false;

	$db = Database::mDataConnect();

	if (!$db->table_exists("unpaid_ar_today")) return false;

	$query = "SELECT old_balance,recent_payments FROM unpaid_ar_today
		WHERE card_no = $cardno";
	$result = $db->query($query);

	// should always be a row, but just in case
	if ($db->num_rows($result) == 0) return false;
	$row = $db->fetch_row($result);

	$bal = $row["old_balance"];
	$paid = $row["recent_payments"];
	if ($CORE_LOCAL->get("memChargeTotal") > 0) {
		$paid += $CORE_LOCAL->get("memChargeTotal");
    }
	
	if ($bal <= 0) return false;
	if ($paid >= $bal) return false;

	// only case where customer prompt should appear
	if ($bal > 0 && $paid < $bal){
		$CORE_LOCAL->set("old_ar_balance",$bal - $paid);
		return true;
	}

	// just in case i forgot anything...
	return false;
}

static public function check_unpaid_ar($cardno)
{
    return self::checkUnpaidAR($cardno);
}

/**
  Check if an item is voided or a refund
  @param $num item trans_id in localtemptrans
  @return array of status information with keys:
   - voided (int)
   - scaleprice (numeric)
   - discountable (int)
   - discounttype (int)
   - caseprice (numeric)
   - refund (boolean)
   - status (string)
*/
static public function checkstatus($num) 
{
	global $CORE_LOCAL;

	$ret = array(
		'voided' => 0,
		'scaleprice' => 0,
		'discountable' => 0,
		'discounttype' => 0,
		'caseprice' => 0,
		'refund' => False,
		'status' => ''
	);

	if (!$num) {
		$num = 0;
	}

	$query = "select voided,unitPrice,discountable,
		discounttype,trans_status
		from localtemptrans where trans_id = ".$num;

	$db = Database::tDataConnect();
	$result = $db->query($query);


	$num_rows = $db->num_rows($result);

	if ($num_rows > 0) {
		$row = $db->fetch_array($result);

		$ret['voided'] = $row['voided'];
		$ret['scaleprice'] = $row['unitPrice'];
		$ret['discountable'] = $row['discountable'];
		$ret['discounttype'] = $row['discounttype'];
		$ret['caseprice'] = $row['unitPrice'];

		if ($row["trans_status"] == "V") {
			$ret['status'] = 'V';
		}

        // added by apbw 6/04/05 to correct voiding of refunded items 
		if ($row["trans_status"] == "R") {
			$CORE_LOCAL->set("refund",1);
			$CORE_LOCAL->set("autoReprint",1);
			$ret['status'] = 'R';
			$ret['refund'] = True;
		}
	}
	
	return $ret;
}

/**
  Add a tender to the transaction

  @right tender amount in cents (100 = $1)
  @strl tender code from tenders table
  @return An array see Parser::default_json()
   for format explanation.

  This function will automatically end a transaction
  if the amount due becomes <= zero.
*/
static public function tender($right, $strl)
{
	global $CORE_LOCAL;
	$ret = array('main_frame'=>false,
		'redraw_footer'=>false,
		'target'=>'.baseHeight',
		'output'=>"");

	/* when processing as strings, weird things happen
	 * in excess of 1000, so use floating point */
	$strl .= ""; // force type to string
	$mult = 1;
	if ($strl[0] == "-") {
		$mult = -1;
		$strl = substr($strl,1,strlen($strl));
	}
	$dollars = (int)substr($strl,0,strlen($strl)-2);
	$cents = ((int)substr($strl,-2))/100.0;
	$strl = (double)($dollars+round($cents,2));
	$strl *= $mult;

	/**
	  First use base module to check for error
	  conditions common to all tenders
	*/
	$base_object = new TenderModule($right, $strl);
	Database::getsubtotals();
	$ec = $base_object->ErrorCheck();
	if ($ec !== true) {
		$ret['output'] = $ec;
		return $ret;
	}
	$pr = $base_object->PreReqCheck();
	if ($pr !== true) {
		$ret['main_frame'] = $pr;
		return $ret;
	}

	/**
	  Get a tender-specific module if
	  one has been configured
	*/
	$tender_object = 0;
	$map = $CORE_LOCAL->get("TenderMap");
	if (is_array($map) && isset($map[$right])) {
		$class = $map[$right];
		$tender_object = new $class($right, $strl);
	} else {
		$tender_object = $base_object;
	}

	if (!is_object($tender_object)) {
		$ret['output'] = DisplayLib::boxMsg(_('tender is misconfigured'));
		return $ret;
	} else if (get_class($tender_object) != 'TenderModule') {
		/**
		  Do tender-specific error checking and prereqs
		*/
		$ec = $tender_object->ErrorCheck();
		if ($ec !== true) {
			$ret['output'] = $ec;
			return $ret;
		}
		$pr = $tender_object->PreReqCheck();
		if ($pr !== true) {
			$ret['main_frame'] = $pr;
			return $ret;
		}
	}

	// add the tender record
	$tender_object->Add();
	Database::getsubtotals();

	// see if transaction has ended
	if ($CORE_LOCAL->get("amtdue") <= 0.005) {

		$CORE_LOCAL->set("change",-1 * $CORE_LOCAL->get("amtdue"));
		$cash_return = $CORE_LOCAL->get("change");
		TransRecord::addchange($cash_return,$tender_object->ChangeType());
					
		$CORE_LOCAL->set("End",1);
		$ret['receipt'] = 'full';
		$ret['output'] = DisplayLib::printReceiptFooter();
	} else {
		$CORE_LOCAL->set("change",0);
		$CORE_LOCAL->set("fntlflag",0);
		$chk = self::ttl();
		if ($chk === true) {
			$ret['output'] = DisplayLib::lastpage();
		} else {
			$ret['main_frame'] = $chk;
        }
	}
	$ret['redraw_footer'] = true;

	return $ret;
}

/**
  Add an open ring to a department
  @param $price amount in cents (100 = $1)
  @param $dept POS department
  @ret an array of return values
  @returns An array. See Parser::default_json()
   for format explanation.
*/
static public function deptkey($price, $dept,$ret=array()) 
{
	global $CORE_LOCAL;

	$intvoided = 0;

	if ($CORE_LOCAL->get("quantity") == 0 && $CORE_LOCAL->get("multiple") == 0) {
		$CORE_LOCAL->set("quantity",1);
	}

	$ringAsCoupon = False;
	if (substr($price,0,2) == 'MC') {
		$ringAsCoupon = true;
		$price = substr($price,2);
	}
		
	if (!is_numeric($dept) || !is_numeric($price) || strlen($price) < 1 || strlen($dept) < 2) {
		$ret['output'] = DisplayLib::inputUnknown();
		$CORE_LOCAL->set("quantity",1);
		$ret['udpmsg'] = 'errorBeep';
		return $ret;
	}

	$strprice = $price;
	$strdept = $dept;
	$price = $price/100;
	$dept = $dept/10;

	if ($CORE_LOCAL->get("casediscount") > 0 && $CORE_LOCAL->get("casediscount") <= 100) {
		$case_discount = (100 - $CORE_LOCAL->get("casediscount"))/100;
		$price = $case_discount * $price;
	}
	$total = $price * $CORE_LOCAL->get("quantity");
	$intdept = $dept;

	$query = "select dept_no,dept_name,dept_tax,dept_fs,dept_limit,
		dept_minimum,dept_discount,";
	$db = Database::pDataConnect();
	$table = $db->table_definition('departments');
	if (isset($table['dept_see_id'])) {
		$query .= 'dept_see_id';
	} else {
		$query .= '0 as dept_see_id';
    }
	$query .= " from departments where dept_no = ".$intdept;
	$result = $db->query($query);

	$num_rows = $db->num_rows($result);
	if ($num_rows == 0) {
		$ret['output'] = DisplayLib::boxMsg(_("department unknown"));
		$ret['udpmsg'] = 'errorBeep';
		$CORE_LOCAL->set("quantity",1);
	} elseif ($ringAsCoupon) {
		$row = $db->fetch_array($result);
		$query2 = "select department, sum(total) as total from localtemptrans where department = "
			.$dept." group by department";

		$db2 = Database::tDataConnect();
		$result2 = $db2->query($query2);

		$num_rows2 = $db2->num_rows($result2);
		if ($num_rows2 == 0) {
			$ret['output'] = DisplayLib::boxMsg(_("no item found in")."<br />".$row["dept_name"]);
			$ret['udpmsg'] = 'errorBeep';
		} else {
			$row2 = $db2->fetch_array($result2);
			if ($price > $row2["total"]) {
				$ret['output'] = DisplayLib::boxMsg(_("coupon amount greater than department total"));
				$ret['udpmsg'] = 'errorBeep';
			} else {
				TransRecord::addItem("", $row["dept_name"]." Coupon", "I", "CP", "C", $dept, 1, -1 * $price, -1 * $price, -1 * $price, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, $intvoided);
				$CORE_LOCAL->set("ttlflag",0);
				$ret['output'] = DisplayLib::lastpage();
				$ret['redraw_footer'] = True;
				$ret['udpmsg'] = 'goodBeep';
			}
		}
	} else {
		$row = $db->fetch_array($result);

		if ($row['dept_see_id'] > 0) {

			$my_url = MiscLib::baseURL();

			if ($CORE_LOCAL->get("cashierAge") < 18 && $CORE_LOCAL->get("cashierAgeOverride") != 1) {
				$ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=AgeApproveAdminLogin";
				return $ret;
			}

			if ($CORE_LOCAL->get("memAge")=="") {
				$CORE_LOCAL->set("memAge",date('Ymd'));
            }
			$diff = time() - ((int)strtotime($CORE_LOCAL->get("memAge")));
			$age = floor($diff / (365*60*60*24));
			if ($age < $row['dept_see_id']) {
				$ret['udpmsg'] = 'twoPairs';
				$ret['main_frame'] = $my_url.'gui-modules/requestInfo.php?class=UPC';
				return $ret;
			}
		}

		if (!$row["dept_limit"]) {
            $deptmax = 0;
		} else {
            $deptmax = $row["dept_limit"];
        }

		if (!$row["dept_minimum"]) {
            $deptmin = 0;
		} else {
            $deptmin = $row["dept_minimum"];
        }
		$tax = $row["dept_tax"];

		if ($row["dept_fs"] != 0) {
            $foodstamp = 1;
		} else {
            $foodstamp = 0;
        }

		$deptDiscount = $row["dept_discount"];

		if ($CORE_LOCAL->get("toggleDiscountable") == 1) {
			$CORE_LOCAL->set("toggleDiscountable",0);
			if  ($deptDiscount == 0) {
				$deptDiscount = 1;
			} else {
				$deptDiscount = 0;
			}
		}

		if ($CORE_LOCAL->get("togglefoodstamp") == 1) {
			$foodstamp = ($foodstamp + 1) % 2;
			$CORE_LOCAL->set("togglefoodstamp",0);
		}

		if ($CORE_LOCAL->get("ddNotify") != 0 &&  $CORE_LOCAL->get("itemPD") == 10) {  
			$CORE_LOCAL->set("itemPD",0);
			$deptDiscount = 7;
			$intvoided = 22;
		}

		if ($price > $deptmax && $CORE_LOCAL->get("msgrepeat") == 0) {

			$CORE_LOCAL->set("boxMsg","$".$price." "._("is greater than department limit")."<p>"
					."<font size='-1'>"._("clear to cancel").", "._("enter to proceed")."</font>");
			$ret['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
		} elseif ($price < $deptmin && $CORE_LOCAL->get("msgrepeat") == 0) {
			$CORE_LOCAL->set("boxMsg","$".$price." "._("is lower than department minimum")."<p>"
				."<font size='-1'>"._("clear to cancel").", "._("enter to proceed")."</font>");
			$ret['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
		} else {
			if ($CORE_LOCAL->get("casediscount") > 0) {
				TransRecord::addcdnotify();
				$CORE_LOCAL->set("casediscount",0);
			}
			
			if ($CORE_LOCAL->get("toggletax") == 1) {
				if ($tax > 0) $tax = 0;
				else $tax = 1;
				$CORE_LOCAL->set("toggletax",0);
			}

			if ($dept == "77") {
				$db2 = Database::tDataConnect();
				$taxratesQ = "SELECT rate FROM taxrates WHERE id=$tax";
				$taxratesR = $db2->query($taxratesQ);
				$rate = array_pop($db2->fetch_row($taxratesR));

				$price /= (1+$rate);
				$price = MiscLib::truncate2($price);
				$total = $price * $CORE_LOCAL->get("quantity");
			}

			TransRecord::addItem($price."DP".$dept, $row["dept_name"], "D", " ", " ", $dept, $CORE_LOCAL->get("quantity"), $price, $total, $price, 0 ,$tax, $foodstamp, 0, 0, $deptDiscount, 0, $CORE_LOCAL->get("quantity"), 0, 0, 0, 0, 0, $intvoided);
			$CORE_LOCAL->set("ttlflag",0);
			//$CORE_LOCAL->set("ttlrequested",0);
			$ret['output'] = DisplayLib::lastpage();
			$ret['redraw_footer'] = true;
			$ret['udpmsg'] = 'goodBeep';
			$CORE_LOCAL->set("msgrepeat",0);
		}
	}

	$CORE_LOCAL->set("quantity",0);
	$CORE_LOCAL->set("itemPD",0);

	return $ret;
}

/**
  Total the transaction
  @return
   True - total successfully
   String - URL

  If ttl() returns a string, go to that URL for
  more information on the error or to resolve the
  problem. 

  The most common error, by far, is no 
  member number in which case the return value
  is the member-entry page.
*/
static public function ttl() 
{
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("memberID") == "0") {
		return MiscLib::base_url()."gui-modules/memlist.php";
	} else {
		$mconn = Database::tDataConnect();
		$query = "";
		$query2 = "";
		if ($CORE_LOCAL->get("isMember") == 1 || $CORE_LOCAL->get("memberID") == $CORE_LOCAL->get("visitingMem")) {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","memdiscountadd");
			$query = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountadd";
		} else {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","memdiscountremove");
			$query = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountremove";
		}

		if ($CORE_LOCAL->get("isStaff") != 0) {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","staffdiscountadd");
			$query2 = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountadd";
		} else {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","staffdiscountremove");
			$query2 = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountremove";
		}

		$result = $mconn->query($query);
		$result2 = $mconn->query($query2);

		$CORE_LOCAL->set("ttlflag",1);
		Database::setglobalvalue("TTLFlag", 1);

        // if total is called before records have been added to the transaction,
        // Database::getsubtotals will zero out the discount
        $savePD = $CORE_LOCAL->get('percentDiscount');

		// Refresh totals after staff and member discounts.
		Database::getsubtotals();

        $ttlHooks = $CORE_LOCAL->get('TotalActions');
        if (is_array($ttlHooks)) {
            foreach($ttlHooks as $ttl_class) {
                if (!class_exists($ttl_class)) continue;
                $mod = new $ttl_class();
                $result = $mod->apply();
                if ($result !== true && is_string($result)) {
                    return $result; // redirect URL
                }
            }
        }

		// Refresh totals after total actions
		Database::getsubtotals();

        $CORE_LOCAL->set('percentDiscount', $savePD);

		if ($CORE_LOCAL->get("percentDiscount") > 0) {
			if ($CORE_LOCAL->get("member_subtotal") === false) {
				TransRecord::addItem("", "Subtotal", "", "", "D", 0, 0, MiscLib::truncate2($CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("subtotal")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);
			}
			TransRecord::discountnotify($CORE_LOCAL->get("percentDiscount"));
			TransRecord::addItem("", $CORE_LOCAL->get("percentDiscount")."% Discount", "C", "", "D", 0, 0, MiscLib::truncate2(-1 * $CORE_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5);
		}

		$temp = self::chargeOk();
		if ($CORE_LOCAL->get("balance") < $CORE_LOCAL->get("memChargeTotal") && $CORE_LOCAL->get("memChargeTotal") > 0) {
			if ($CORE_LOCAL->get('msgrepeat') == 0) {
				$CORE_LOCAL->set("boxMsg",sprintf("<b>A/R Imbalance</b><br />
					Total AR payments $%.2f exceeds AR balance %.2f<br />
					<font size=-1>[enter] to continue, [clear] to cancel</font>",
					$CORE_LOCAL->get("memChargeTotal"),
					$CORE_LOCAL->get("balance")));
				$CORE_LOCAL->set("strEntered","TL");
				return MiscLib::baseURL()."gui-modules/boxMsg2.php?quiet=1";
			}
		}

		$amtDue = str_replace(",", "", $CORE_LOCAL->get("amtdue"));

		// check in case something else like an
		// approval code is already being sent
		// to the cc terminal
		//if ($CORE_LOCAL->get("ccTermOut")=="idle"){
		$CORE_LOCAL->set("ccTermOut","total:".
			str_replace(".","",sprintf("%.2f",$amtDue)));
		/*
		$st = sigTermObject();
		if (is_object($st))
			$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
		*/
		//}
		$memline = "";
		if($CORE_LOCAL->get("memberID") != $CORE_LOCAL->get("defaultNonMem")) {
			$memline = " #" . $CORE_LOCAL->get("memberID");
		} 
		// temporary fix Andy 13Feb13
		// my cashiers don't like the behavior; not configurable yet
		if ($CORE_LOCAL->get("store") == "wfc") $memline="";
		$peek = self::peekItem();
		if (true || substr($peek,0,9) != "Subtotal ") {
			TransRecord::addItem("", "Subtotal ".MiscLib::truncate2($CORE_LOCAL->get("subtotal")).", Tax ".MiscLib::truncate2($CORE_LOCAL->get("taxTotal")).$memline, "C", "", "D", 0, 0, $amtDue, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3);
		}
	
		if ($CORE_LOCAL->get("fntlflag") == 1) {
			TransRecord::addItem("", "Foodstamps Eligible", "", "", "D", 0, 0, MiscLib::truncate2($CORE_LOCAL->get("fsEligible")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);
		}

	}

	return true;
}

/**
  Total the transaction, which the cashier thinks may be eligible for the
	 Ontario Meal Tax Rebate.
  @return
   True - total successfully
   String - URL

  If ttl() returns a string, go to that URL for
  more information on the error or to resolve the
  problem. 

  The most common error, by far, is no 
  member number in which case the return value
  is the member-entry page.

  The Ontario Meal Tax Rebate refunds the provincial part of the
  Harmonized Sales Tax if the total of the transaction is not more
  than a certain amount.

  If the transaction qualifies,
   change the tax status for each item at the higher rate to the lower rate.
   Display a message that a change was made.
  Otherwise display a message about that.
  Total the transaction as usual.

*/
static public function omtr_ttl() 
{
	global $CORE_LOCAL;

	// Must have gotten member number before totaling.
	if ($CORE_LOCAL->get("memberID") == "0") {
		return MiscLib::base_url()."gui-modules/memlist.php";
	}
	else {
		$mconn = Database::tDataConnect();
		$query = "";
		$query2 = "";
		// Apply or remove any member discounts as appropriate.
		if ($CORE_LOCAL->get("isMember") == 1 || $CORE_LOCAL->get("memberID") == $CORE_LOCAL->get("visitingMem")) {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","memdiscountadd");
			$query = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountadd";
		} else {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","memdiscountremove");
			$query = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountremove";
		}

		// Apply or remove any staff discounts as appropriate.
		if ($CORE_LOCAL->get("isStaff") != 0) {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","staffdiscountadd");
			$query2 = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountadd";
		} else {
			$cols = Database::localMatchingColumns($mconn,"localtemptrans","staffdiscountremove");
			$query2 = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountremove";
		}

		$result = $mconn->query($query);
		$result2 = $mconn->query($query2);

		$CORE_LOCAL->set("ttlflag",1);
		Database::setglobalvalue("TTLFlag", 1);

		// Refresh totals after staff and member discounts.
		Database::getsubtotals();

		// Is the before-tax total within range?
		if ($CORE_LOCAL->get("runningTotal") <= 4.00 ) {
			$totalBefore = $CORE_LOCAL->get("amtdue");
			$ret = Database::changeLttTaxCode("HST","GST");
			if ( $ret !== True ) {
				TransRecord::addcomment("$ret");
			} else {
				Database::getsubtotals();
				$saved = ($totalBefore - $CORE_LOCAL->get("amtdue"));
				$comment = sprintf("OMTR OK. You saved: $%.2f", $saved);
				TransRecord::addcomment("$comment");
			}
		}
		else {
			TransRecord::addcomment("Does NOT qualify for OMTR");
		}

		/* If member can do Store Charge, warn on certain conditions.
		 * Important preliminary is to refresh totals.
		*/
		$temp = self::chargeOk();
		if ($CORE_LOCAL->get("balance") < $CORE_LOCAL->get("memChargeTotal") && $CORE_LOCAL->get("memChargeTotal") > 0){
			if ($CORE_LOCAL->get('msgrepeat') == 0){
				$CORE_LOCAL->set("boxMsg",sprintf("<b>A/R Imbalance</b><br />
					Total AR payments $%.2f exceeds AR balance %.2f<br />
					<font size=-1>[enter] to continue, [clear] to cancel</font>",
					$CORE_LOCAL->get("memChargeTotal"),
					$CORE_LOCAL->get("balance")));
				$CORE_LOCAL->set("strEntered","TL");
				return MiscLib::base_url()."gui-modules/boxMsg2.php?quiet=1";
			}
		}

		// Display discount.
		if ($CORE_LOCAL->get("percentDiscount") > 0) {
			TransRecord::addItem("", $CORE_LOCAL->get("percentDiscount")."% Discount", "C", "", "D", 0, 0, MiscLib::truncate2(-1 * $CORE_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5);
		}

		$amtDue = str_replace(",", "", $CORE_LOCAL->get("amtdue"));

		// check in case something else like an
		// approval code is already being sent
		// to the cc terminal
		//if ($CORE_LOCAL->get("ccTermOut")=="idle"){

		$CORE_LOCAL->set("ccTermOut","total:".
			str_replace(".","",sprintf("%.2f",$amtDue)));

		/*
		$st = sigTermObject();
		if (is_object($st))
			$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
		*/
		//}

		// Compose the member ID string for the description.
		if($CORE_LOCAL->get("memberID") != $CORE_LOCAL->get("defaultNonMem")) {
			$memline = " #" . $CORE_LOCAL->get("memberID");
		} 
		else {
			$memline = "";
		}

		// Put out the Subtotal line.
		$peek = self::peekItem();
		if (True || substr($peek,0,9) != "Subtotal "){
			TransRecord::addItem("", "Subtotal ".MiscLib::truncate2($CORE_LOCAL->get("subtotal")).", Tax ".MiscLib::truncate2($CORE_LOCAL->get("taxTotal")).$memline, "C", "", "D", 0, 0, $amtDue, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3);
		}
	
		if ($CORE_LOCAL->get("fntlflag") == 1) {
			TransRecord::addItem("", "Foodstamps Eligible", "", "", "D", 0, 0, MiscLib::truncate2($CORE_LOCAL->get("fsEligible")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);
		}

	}

	return True;

// omtr_ttl
}

/**
  See what the last item in the transaction is currently
  @return localtemptrans.description for the last item
*/
static public function peekItem()
{
	$db = Database::tDataConnect();
	$q = "SELECT description FROM localtemptrans ORDER BY trans_id DESC";
	$r = $db->query($q);
	$w = $db->fetch_row($r);

	return (isset($w['description'])?$w['description']:'');
}

/**
  Add tax and transaction discount records.
  This is called at the end of a transaction.
  There's probably no other place where calling
  this function is appropriate.
*/
static public function finalttl() 
{
	global $CORE_LOCAL;
	if ($CORE_LOCAL->get("percentDiscount") > 0) {
		TransRecord::addItem("", "Discount", "C", "", "D", 0, 0, MiscLib::truncate2(-1 * $CORE_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5);
	}

	TransRecord::addItem("Subtotal", "Subtotal", "C", "", "D", 0, 0, MiscLib::truncate2($CORE_LOCAL->get("taxTotal") - $CORE_LOCAL->get("fsTaxExempt")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11);


	if ($CORE_LOCAL->get("fsTaxExempt")  != 0) {
		TransRecord::addItem("Tax", "FS Taxable", "C", "", "D", 0, 0, MiscLib::truncate2($CORE_LOCAL->get("fsTaxExempt")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);
	}

	TransRecord::addItem("Total", "Total", "C", "", "D", 0, 0, MiscLib::truncate2($CORE_LOCAL->get("amtdue")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11);

}

/**
  Add foodstamp elgibile total record
*/
static public function fsEligible() 
{
	global $CORE_LOCAL;
	Database::getsubtotals();
	if ($CORE_LOCAL->get("fsEligible") < 0 && False) {
		$CORE_LOCAL->set("boxMsg","Foodstamp eligible amount inapplicable<P>Please void out earlier tender and apply foodstamp first");
		return MiscLib::baseURL()."gui-modules/boxMsg2.php";
	} else {
		$CORE_LOCAL->set("fntlflag",1);
		Database::setglobalvalue("FntlFlag", 1);
		if ($CORE_LOCAL->get("ttlflag") != 1) return self::ttl();
		else TransRecord::addItem("", "Foodstamps Eligible", "" , "", "D", 0, 0, MiscLib::truncate2($CORE_LOCAL->get("fsEligible")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);

		return true;
	}
}

/**
  Add a percent discount notification
  @param $strl discount percentage
  @param $json keyed array
  @return An array see Parser::default_json()
  @deprecated
  Use discountnotify() instead. This just adds
  hard-coded percentages and PLUs that likely
  aren't applicable anywhere but the Wedge.
*/
static public function percentDiscount($strl,$json=array()) 
{
	if ($strl == 10.01) $strl = 10;

	if (!is_numeric($strl) || $strl > 100 || $strl < 0) $json['output'] = DisplayLib::boxMsg("discount invalid");
	else {
		$query = "select sum(total) as total from localtemptrans where upc = '0000000008005' group by upc";

		$db = Database::tDataConnect();
		$result = $db->query($query);

		$num_rows = $db->num_rows($result);
			if ($num_rows == 0) $couponTotal = 0;
		else {
			$row = $db->fetch_array($result);
			$couponTotal = MiscLib::nullwrap($row["total"]);
		}
			if ($couponTotal == 0 || $strl == 0) {

				if ($strl != 0) TransRecord::discountnotify($strl);
				$db->query("update localtemptrans set percentDiscount = ".$strl);
			$chk = self::ttl();
			if ($chk !== True)
				$json['main_frame'] = $chk;
			$json['output'] = DisplayLib::lastpage();
		}
		else $json['output'] = DisplayLib::xboxMsg("10% discount already applied");
	}
	return $json;
}

/**
  Check whether the current member has store
  charge balance available.
  @return
   1 - Yes
   0 - No

  Sets current balance in $CORE_LOCAL as "balance".
  Sets available balance in $CORE_LOCAL as "availBal".
*/
static public function chargeOk() 
{
	global $CORE_LOCAL;

	Database::getsubtotals();

	$conn = Database::pDataConnect();
	$query = "SELECT c.ChargeLimit - c.Balance AS availBal,
        c.Balance, c.ChargeOk
		FROM custdata AS c 
		WHERE c.personNum=1 AND c.CardNo = " . ((int)$CORE_LOCAL->get("memberID"));
    $table_def = $conn->table_definition('custdata');
    // 3Jan14 schema may not have been updated
    if (!isset($table_def['ChargeLimit'])) {
        $query = str_replace('c.ChargeLimit', 'c.MemDiscountLimit', $query);
    }

	$result = $conn->query($query);
	$num_rows = $conn->num_rows($result);
	$row = $conn->fetch_array($result);

	$availBal = $row["availBal"] + $CORE_LOCAL->get("memChargeTotal");
	
	$CORE_LOCAL->set("balance",$row["Balance"]);
	$CORE_LOCAL->set("availBal",number_format($availBal,2,'.',''));	
	
	$chargeOk = 1;
	if ($num_rows == 0 || !$row["ChargeOk"]) {
		$chargeOk = 0;
	} elseif ( $row["ChargeOk"] == 0 ) {
		$chargeOk = 0;	
	}

	return $chargeOk;
}

}

