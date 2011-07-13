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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!isset($CORE_LOCAL))
	require_once($CORE_PATH."lib/LocalStorage/conf.php");

/************************************************************************************
General Settings
************************************************************************************/

<<<<<<< HEAD
$IS4C_LOCAL->set("OS",'other');
$IS4C_LOCAL->set("browserOnly",0);
$IS4C_LOCAL->set("store",'wfc');
$IS4C_LOCAL->set("laneno",12);
=======
$CORE_LOCAL->set("OS",'other');
$CORE_LOCAL->set("browserOnly",0);
$CORE_LOCAL->set("store",'wfc');
$CORE_LOCAL->set("laneno",99);
>>>>>>> d5b41c9903566cbbca1af7471f6ff9c3033333ba

/************************************************************************************
Data Connection Settings
************************************************************************************/
/*
$CORE_LOCAL["mServer"] = "nexus.wfco-op.store";
$CORE_LOCAL["mDatabase"] = "wfc_pos";
$CORE_LOCAL["mDBMS"] = "pgsql";	// type of central server database server. 
				// Options: mssql, mysql, pgsql
$CORE_LOCAL["mUser"] = "wfc_pos";
$CORE_LOCAL["mPass"] = "is4c";
 */
<<<<<<< HEAD
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
=======
$CORE_LOCAL->set("mServer",'');
$CORE_LOCAL->set("mDatabase",'');
$CORE_LOCAL->set("mDBMS",'');
				// Options: mssql, mysql, pgsql
$CORE_LOCAL->set("mUser",'');
$CORE_LOCAL->set("mPass",'');

$CORE_LOCAL->set("DBMS",'mysql');
$CORE_LOCAL->set("tDatabase",'translog');
$CORE_LOCAL->set("pDatabase",'opdata');
$CORE_LOCAL->set("localhost",'');
$CORE_LOCAL->set("localUser",'');
$CORE_LOCAL->set("localPass",'');
>>>>>>> d5b41c9903566cbbca1af7471f6ff9c3033333ba

/***********************************************************************************
Receipt & Printer Settings
************************************************************************************/

$CORE_LOCAL->set("print",1);
$CORE_LOCAL->set("newReceipt",1);

//$CORE_LOCAL->set("printerPort","LPT1:");
//$CORE_LOCAL->set("printerPort","/dev/lp0");
$CORE_LOCAL->set("printerPort",'fakereceipt.txt');

<<<<<<< HEAD
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
=======
$CORE_LOCAL->set("receiptHeader1",'WHOLE FOODS COMMUNITY CO-OP');
$CORE_LOCAL->set("receiptHeader2",'(218) 728-0884');
$CORE_LOCAL->set("receiptHeader3",'MEMBER OWNED SINCE 1970');

$CORE_LOCAL->set("receiptFooter1",'Returns accepted with receipt');
$CORE_LOCAL->set("receiptFooter2",'within 30 days of purchase');
$CORE_LOCAL->set("receiptFooter3",'Visit us at www.wholefoods.coop');

$CORE_LOCAL->set("ckEndorse1",'FOR DEPOSIT ONLY');
$CORE_LOCAL->set("ckEndorse2",'TO MCCU');
$CORE_LOCAL->set("ckEndorse3",'ACCOUNT #');
$CORE_LOCAL->set("ckEndorse4",'WHOLE FOODS COMMUNITY CO-OP');

$CORE_LOCAL->set("chargeSlip1",'WHOLE FOODS COMMUNITY CO-OP');
$CORE_LOCAL->set("chargeSlip2",'W F C   C O P Y');
$CORE_LOCAL->set("chargeSlip3",'610 E 4th St.');
$CORE_LOCAL->set("chargeSlip4",'Duluth, MN 55805');
$CORE_LOCAL->set("chargeSlip5",'');
>>>>>>> d5b41c9903566cbbca1af7471f6ff9c3033333ba


/***********************************************************************************
Screen Message Settings
************************************************************************************/

<<<<<<< HEAD
$IS4C_LOCAL->set("welcomeMsg1",'welcome to the New Pi ');
$IS4C_LOCAL->set("welcomeMsg2",'Keepin It Real Since 1971');

$IS4C_LOCAL->set("trainingMsg1",'welcome to the New Pi Front End');
$IS4C_LOCAL->set("trainingMsg2",'training mode is on');

$IS4C_LOCAL->set("farewellMsg1",'Thanks for shopping at the New Pi');
$IS4C_LOCAL->set("farewellMsg2",'Returns accepted with receipt within 30 days');
$IS4C_LOCAL->set("farewellMsg3",'(319) 338 - 9441');

$IS4C_LOCAL->set("alertBar",'New Pi - Alert');
=======
$CORE_LOCAL->set("welcomeMsg1",'welcome to the wfc');
$CORE_LOCAL->set("welcomeMsg2",'member owned since 1970');

$CORE_LOCAL->set("trainingMsg1",'welcome to the wfc frontend');
$CORE_LOCAL->set("trainingMsg2",'training mode is on');

$CORE_LOCAL->set("farewellMsg1",'Thanks for shopping at the WFC');
$CORE_LOCAL->set("farewellMsg2",'Returns accepted with receipt within 30 days');
$CORE_LOCAL->set("farewellMsg3",'(218) 728-0884');

$CORE_LOCAL->set("alertBar",'WFC - Alert');
>>>>>>> d5b41c9903566cbbca1af7471f6ff9c3033333ba

/***********************************************************************************
Credit Card
************************************************************************************/

<<<<<<< HEAD
$IS4C_LOCAL->set("CCintegrate",1);
$IS4C_LOCAL->set("gcIntegrate",1);
$IS4C_LOCAL->set("ccLive",1); 			// credit card integration live or testing. live = 1, testing = 0
$IS4C_LOCAL->set("RegisteredPaycardClasses",array('MercuryGift','GoEMerchant'));
=======
$CORE_LOCAL->set("CCintegrate",1);
$CORE_LOCAL->set("gcIntegrate",1);
$CORE_LOCAL->set("ccLive",1); 			// credit card integration live or testing. live = 1, testing = 0
$CORE_LOCAL->set("RegisteredPaycardClasses",array('GoEMerchant','MercuryGift'));
>>>>>>> d5b41c9903566cbbca1af7471f6ff9c3033333ba

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

<<<<<<< HEAD
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
=======
//$CORE_LOCAL->set("SigCapture",'COM1');
$CORE_LOCAL->set("SigCapture",'');
>>>>>>> d5b41c9903566cbbca1af7471f6ff9c3033333ba
?>
