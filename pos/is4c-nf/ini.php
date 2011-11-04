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
    in the file license.txt along with CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!isset($CORE_LOCAL))
	require_once($CORE_PATH."lib/LocalStorage/conf.php");

/************************************************************************************
General Settings
************************************************************************************/

$CORE_LOCAL->set("OS",'other');
$CORE_LOCAL->set("browserOnly",1);
$CORE_LOCAL->set("store",'NewPI');
$CORE_LOCAL->set("laneno",1);

/************************************************************************************
Data Connection Settings
************************************************************************************/
/*
$CORE_LOCAL["mServer"] = "nexus.wfco-op.store";
$CORE_LOCAL["mDatabase"] = "wfc_pos";
$CORE_LOCAL["mDBMS"] = "pgsql";	// type of central server database server. 
				// Options: mssql, mysql, pgsql
$CORE_LOCAL["mUser"] = "wfc_pos";
$CORE_LOCAL["mPass"] = "CORE";
 */
$CORE_LOCAL->set("mServer",'localhost');
$CORE_LOCAL->set("mDatabase",'COREserver');
$CORE_LOCAL->set("mDBMS",'mysql');
				// Options: mssql, mysql, pgsql
$CORE_LOCAL->set("mUser",'COREserver');
$CORE_LOCAL->set("mPass",'coreserver');

$CORE_LOCAL->set("DBMS",'mysql');
$CORE_LOCAL->set("tDatabase",'translog');
$CORE_LOCAL->set("pDatabase",'opdata');
$CORE_LOCAL->set("localhost",'10.0.1.120');
$CORE_LOCAL->set("localUser",'COREserver');
$CORE_LOCAL->set("localPass",'coreserver');

/***********************************************************************************
Receipt & Printer Settings
************************************************************************************/

$CORE_LOCAL->set("print",1);
$CORE_LOCAL->set("newReceipt",1);

//$CORE_LOCAL->set("printerPort","LPT1:");
//$CORE_LOCAL->set("printerPort","/dev/lp0");
$CORE_LOCAL->set("printerPort",'fakereceipt.txt');

$CORE_LOCAL->set("receiptHeader1",'New Pioneer Food Co-op');
$CORE_LOCAL->set("receiptHeader2",'319 - 338 - 9441');
$CORE_LOCAL->set("receiptHeader3",'Keepin It Real Since 1971');

$CORE_LOCAL->set("receiptFooter1",'Returns accepted with receipt');
$CORE_LOCAL->set("receiptFooter2",'within 30 days of purchase');
$CORE_LOCAL->set("receiptFooter3",'Visit us at www.newpi.coop');

$CORE_LOCAL->set("ckEndorse1",'FOR DEPOSIT ONLY');
$CORE_LOCAL->set("ckEndorse2",'TO MCCU');
$CORE_LOCAL->set("ckEndorse3",'ACCOUNT #');
$CORE_LOCAL->set("ckEndorse4",'NEW PIONEER FOOD CO-OP');

$CORE_LOCAL->set("chargeSlip1",'NEW PIONEER FOOD CO-OP');
$CORE_LOCAL->set("chargeSlip2",'NEW PI COPY');
$CORE_LOCAL->set("chargeSlip3",'22 S. Van Buren St.');
$CORE_LOCAL->set("chargeSlip4",'Iowa City, IA 52240');
$CORE_LOCAL->set("chargeSlip5",'');

/***********************************************************************************
Screen Message Settings
************************************************************************************/

$CORE_LOCAL->set("welcomeMsg1",'welcome to the New Pi ');
$CORE_LOCAL->set("welcomeMsg2",'Keepin It Real Since 1971');

$CORE_LOCAL->set("trainingMsg1",'welcome to the New Pi Front End');
$CORE_LOCAL->set("trainingMsg2",'training mode is on');

$CORE_LOCAL->set("farewellMsg1",'Thanks for shopping at the New Pi');
$CORE_LOCAL->set("farewellMsg2",'Returns accepted with receipt within 30 days');
$CORE_LOCAL->set("farewellMsg3",'(319) 338 - 9441');

$CORE_LOCAL->set("alertBar",'New Pi - Alert');

/***********************************************************************************
Credit Card
************************************************************************************/

$CORE_LOCAL->set("CCintegrate",1);
$CORE_LOCAL->set("gcIntegrate",1);
$CORE_LOCAL->set("ccLive",1); 			// credit card integration live or testing. live = 1, testing = 0
$CORE_LOCAL->set("RegisteredPaycardClasses",array('MercuryGift','GoEMerchant'));

/***********************************************************************************
Other Settings
************************************************************************************/

$CORE_LOCAL->set("discountEnforced",1);
$CORE_LOCAL->set("lockScreen",1);
$CORE_LOCAL->set("ddNotify",0); 
$CORE_LOCAL->set("promoMsg",0);

$CORE_LOCAL->set("memlistNonMember",0);
$CORE_LOCAL->set("cashOverLimit",1);
$CORE_LOCAL->set("dollarOver",50);
$CORE_LOCAL->set("defaultNonMem",'11');

if ($CORE_LOCAL->get("inputMasked") == "")
	$CORE_LOCAL->set("inputMasked",0);

$CORE_LOCAL->set("SCReceipt",1);			/***staff charge receipt - print default for each lane--apbw 1/31/05***/
$CORE_LOCAL->set("CustomerDisplay",0);
$CORE_LOCAL->set("touchscreen",False);

//$CORE_LOCAL->set("SigCapture",'COM1');
$CORE_LOCAL->set("SigCapture",'');
$CORE_LOCAL->set("visitingMem",'');
$CORE_LOCAL->set("scalePort",'');
$CORE_LOCAL->set("scaleDriver",'');
$CORE_LOCAL->set("CCSigLimit",0);
$CORE_LOCAL->set("SpecialUpcClasses",array());
$CORE_LOCAL->set("DiscountTypeCount",5);
$CORE_LOCAL->set("DiscountTypeClasses",array('NormalPricing','EveryoneSale','MemberSale','CaseDiscount','StaffSale'));
$CORE_LOCAL->set("PriceMethodCount",3);
$CORE_LOCAL->set("PriceMethodClasses",array('BasicPM','GroupPM','QttyEnforcedGroupPM'));
?>
