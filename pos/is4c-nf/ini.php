<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!isset($IS4C_LOCAL))
	require_once($IS4C_PATH."lib/LocalStorage/conf.php");

/************************************************************************************
General Settings
************************************************************************************/

$IS4C_LOCAL->set("OS",'other');
$IS4C_LOCAL->set("browserOnly",0);
$IS4C_LOCAL->set("store",'wfc');
$IS4C_LOCAL->set("laneno",12);

/************************************************************************************
Data Connection Settings
************************************************************************************/
/*
$IS4C_LOCAL["mServer"] = "nexus.wfco-op.store";
$IS4C_LOCAL["mDatabase"] = "wfc_pos";
$IS4C_LOCAL["mDBMS"] = "pgsql";	// type of central server database server. 
				// Options: mssql, mysql, pgsql
$IS4C_LOCAL["mUser"] = "wfc_pos";
$IS4C_LOCAL["mPass"] = "is4c";
 */
$IS4C_LOCAL->set("mServer",'localhost');
$IS4C_LOCAL->set("mDatabase",'is4cserver');
$IS4C_LOCAL->set("mDBMS",'mysql');
				// Options: mssql, mysql, pgsql
$IS4C_LOCAL->set("mUser",'is4cserver');
$IS4C_LOCAL->set("mPass",'is4cserver');

$IS4C_LOCAL->set("DBMS",'mysql');
$IS4C_LOCAL->set("tDatabase",'translog');
$IS4C_LOCAL->set("pDatabase",'opdata');
$IS4C_LOCAL->set("localhost",'localhost');
$IS4C_LOCAL->set("localUser",'is4clane');
$IS4C_LOCAL->set("localPass",'is4clane');

/***********************************************************************************
Receipt & Printer Settings
************************************************************************************/

$IS4C_LOCAL->set("print",1);
$IS4C_LOCAL->set("newReceipt",1);

//$IS4C_LOCAL->set("printerPort","LPT1:");
//$IS4C_LOCAL->set("printerPort","/dev/lp0");
$IS4C_LOCAL->set("printerPort",'fakereceipt.txt');

$IS4C_LOCAL->set("receiptHeader1",'New Pioneer Food Co-op');
$IS4C_LOCAL->set("receiptHeader2",'319 - 338 - 9441');
$IS4C_LOCAL->set("receiptHeader3",'Keepin It Real Since 1971');

$IS4C_LOCAL->set("receiptFooter1",'Returns accepted with receipt');
$IS4C_LOCAL->set("receiptFooter2",'within 30 days of purchase');
$IS4C_LOCAL->set("receiptFooter3",'Visit us at www.newpi.coop');

$IS4C_LOCAL->set("ckEndorse1",'FOR DEPOSIT ONLY');
$IS4C_LOCAL->set("ckEndorse2",'TO MCCU');
$IS4C_LOCAL->set("ckEndorse3",'ACCOUNT #');
$IS4C_LOCAL->set("ckEndorse4",'NEW PIONEER FOOD CO-OP');

$IS4C_LOCAL->set("chargeSlip1",'NEW PIONEER FOOD CO-OP');
$IS4C_LOCAL->set("chargeSlip2",'NEW PI COPY');
$IS4C_LOCAL->set("chargeSlip3",'22 S. Van Buren St.');
$IS4C_LOCAL->set("chargeSlip4",'Iowa City, IA 52240');
$IS4C_LOCAL->set("chargeSlip5",'');


/***********************************************************************************
Screen Message Settings
************************************************************************************/

$IS4C_LOCAL->set("welcomeMsg1",'welcome to the New Pi ');
$IS4C_LOCAL->set("welcomeMsg2",'Keepin It Real Since 1971');

$IS4C_LOCAL->set("trainingMsg1",'welcome to the New Pi Front End');
$IS4C_LOCAL->set("trainingMsg2",'training mode is on');

$IS4C_LOCAL->set("farewellMsg1",'Thanks for shopping at the New Pi');
$IS4C_LOCAL->set("farewellMsg2",'Returns accepted with receipt within 30 days');
$IS4C_LOCAL->set("farewellMsg3",'(319) 338 - 9441');

$IS4C_LOCAL->set("alertBar",'New Pi - Alert');

/***********************************************************************************
Credit Card
************************************************************************************/

$IS4C_LOCAL->set("CCintegrate",1);
$IS4C_LOCAL->set("gcIntegrate",1);
$IS4C_LOCAL->set("ccLive",1); 			// credit card integration live or testing. live = 1, testing = 0
$IS4C_LOCAL->set("RegisteredPaycardClasses",array('MercuryGift','GoEMerchant'));

/***********************************************************************************
Other Settings
************************************************************************************/

$IS4C_LOCAL->set("discountEnforced",1);
$IS4C_LOCAL->set("lockScreen",1);
$IS4C_LOCAL->set("ddNotify",0); 
$IS4C_LOCAL->set("promoMsg",0);

$IS4C_LOCAL->set("memlistNonMember",0);
$IS4C_LOCAL->set("cashOverLimit",1);
$IS4C_LOCAL->set("dollarOver",50);
$IS4C_LOCAL->set("defaultNonMem",'11');

if ($IS4C_LOCAL->get("inputMasked") == "")
	$IS4C_LOCAL->set("inputMasked",0);

$IS4C_LOCAL->set("SCReceipt",1);			/***staff charge receipt - print default for each lane--apbw 1/31/05***/
$IS4C_LOCAL->set("CustomerDisplay",0);
$IS4C_LOCAL->set("touchscreen",False);

//$IS4C_LOCAL->set("SigCapture",'COM1');
$IS4C_LOCAL->set("SigCapture",'');
$IS4C_LOCAL->set("visitingMem",'');
$IS4C_LOCAL->set("scalePort",'');
$IS4C_LOCAL->set("scaleDriver",'');
$IS4C_LOCAL->set("CCSigLimit",0);
$IS4C_LOCAL->set("SpecialUpcClasses",array());
$IS4C_LOCAL->set("DiscountTypeCount",5);
$IS4C_LOCAL->set("DiscountTypeClasses",array('NormalPricing','EveryoneSale','MemberSale','CaseDiscount','StaffSale'));
$IS4C_LOCAL->set("PriceMethodCount",3);
$IS4C_LOCAL->set("PriceMethodClasses",array('BasicPM','GroupPM','QttyEnforcedGroupPM'));
?>
