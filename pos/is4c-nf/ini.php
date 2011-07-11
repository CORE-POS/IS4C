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
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."pos.css")) $IS4C_PATH .= "../"; }

if (!isset($IS4C_LOCAL))
	require_once($IS4C_PATH."lib/LocalStorage/conf.php");

/************************************************************************************
General Settings
************************************************************************************/

$IS4C_LOCAL->set("OS",'other');
$IS4C_LOCAL->set("browserOnly",0);
$IS4C_LOCAL->set("store",'wfc');
$IS4C_LOCAL->set("laneno",99);

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
$IS4C_LOCAL->set("mServer",'');
$IS4C_LOCAL->set("mDatabase",'');
$IS4C_LOCAL->set("mDBMS",'');
				// Options: mssql, mysql, pgsql
$IS4C_LOCAL->set("mUser",'');
$IS4C_LOCAL->set("mPass",'');

$IS4C_LOCAL->set("DBMS",'mysql');
$IS4C_LOCAL->set("tDatabase",'translog');
$IS4C_LOCAL->set("pDatabase",'opdata');
$IS4C_LOCAL->set("localhost",'');
$IS4C_LOCAL->set("localUser",'');
$IS4C_LOCAL->set("localPass",'');

/***********************************************************************************
Receipt & Printer Settings
************************************************************************************/

$IS4C_LOCAL->set("print",1);
$IS4C_LOCAL->set("newReceipt",1);

//$IS4C_LOCAL->set("printerPort","LPT1:");
//$IS4C_LOCAL->set("printerPort","/dev/lp0");
$IS4C_LOCAL->set("printerPort",'fakereceipt.txt');

$IS4C_LOCAL->set("receiptHeader1",'WHOLE FOODS COMMUNITY CO-OP');
$IS4C_LOCAL->set("receiptHeader2",'(218) 728-0884');
$IS4C_LOCAL->set("receiptHeader3",'MEMBER OWNED SINCE 1970');

$IS4C_LOCAL->set("receiptFooter1",'Returns accepted with receipt');
$IS4C_LOCAL->set("receiptFooter2",'within 30 days of purchase');
$IS4C_LOCAL->set("receiptFooter3",'Visit us at www.wholefoods.coop');

$IS4C_LOCAL->set("ckEndorse1",'FOR DEPOSIT ONLY');
$IS4C_LOCAL->set("ckEndorse2",'TO MCCU');
$IS4C_LOCAL->set("ckEndorse3",'ACCOUNT #');
$IS4C_LOCAL->set("ckEndorse4",'WHOLE FOODS COMMUNITY CO-OP');

$IS4C_LOCAL->set("chargeSlip1",'WHOLE FOODS COMMUNITY CO-OP');
$IS4C_LOCAL->set("chargeSlip2",'W F C   C O P Y');
$IS4C_LOCAL->set("chargeSlip3",'610 E 4th St.');
$IS4C_LOCAL->set("chargeSlip4",'Duluth, MN 55805');
$IS4C_LOCAL->set("chargeSlip5",'');


/***********************************************************************************
Screen Message Settings
************************************************************************************/

$IS4C_LOCAL->set("welcomeMsg1",'welcome to the wfc');
$IS4C_LOCAL->set("welcomeMsg2",'member owned since 1970');

$IS4C_LOCAL->set("trainingMsg1",'welcome to the wfc frontend');
$IS4C_LOCAL->set("trainingMsg2",'training mode is on');

$IS4C_LOCAL->set("farewellMsg1",'Thanks for shopping at the WFC');
$IS4C_LOCAL->set("farewellMsg2",'Returns accepted with receipt within 30 days');
$IS4C_LOCAL->set("farewellMsg3",'(218) 728-0884');

$IS4C_LOCAL->set("alertBar",'WFC - Alert');

/***********************************************************************************
Credit Card
************************************************************************************/

$IS4C_LOCAL->set("CCintegrate",1);
$IS4C_LOCAL->set("gcIntegrate",1);
$IS4C_LOCAL->set("ccLive",1); 			// credit card integration live or testing. live = 1, testing = 0
$IS4C_LOCAL->set("RegisteredPaycardClasses",array('GoEMerchant','MercuryGift'));

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
?>
