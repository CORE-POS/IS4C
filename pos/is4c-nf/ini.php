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
$CORE_LOCAL->set("store",'George Street Co-op');
/* Lane # moved to ini-local.php */

/************************************************************************************
Data Connection Settings
************************************************************************************/

/* All moved to ini-local.php */


/***********************************************************************************
Receipt & Printer Settings
************************************************************************************/

$CORE_LOCAL->set("print",1);
$CORE_LOCAL->set("newReceipt",1);

/* PrinterPort moved to ini-local.php */

$CORE_LOCAL->set("receiptHeader1",'George Street Co-op');
$CORE_LOCAL->set("receiptHeader2",'732-247-8280');
$CORE_LOCAL->set("receiptHeader3",'Healthy food - Healthy planet - Cooperation');

$CORE_LOCAL->set("receiptFooter1",'Store credit within 30 days with receipt');
$CORE_LOCAL->set("receiptFooter2",'Visit us at www.georgestreetcoop.com');
$CORE_LOCAL->set("receiptFooter3",'');

$CORE_LOCAL->set("ckEndorse1",'FOR DEPOSIT ONLY');
$CORE_LOCAL->set("ckEndorse2",'TO NEW MILLENNIUM BANK');
$CORE_LOCAL->set("ckEndorse3",'ACCOUNT #');
$CORE_LOCAL->set("ckEndorse4",'GEORGE STREET CO-OP');

$CORE_LOCAL->set("chargeSlip1",'GEORGE STREET CO-OP');
$CORE_LOCAL->set("chargeSlip2",'GEORGE STREET CO-OP COPY');
$CORE_LOCAL->set("chargeSlip3",'89 Morris Street');
$CORE_LOCAL->set("chargeSlip4",'New Brunswick, NJ 08901');
$CORE_LOCAL->set("chargeSlip5",'');

/***********************************************************************************
Screen Message Settings
************************************************************************************/

$CORE_LOCAL->set("welcomeMsg1",'Welcome to George Street Co-op!');
$CORE_LOCAL->set("welcomeMsg2",'Healthy food - Healthy planet - Cooperation');

$CORE_LOCAL->set("trainingMsg1",'Welcome to the IS4C Point of Sale system!');
$CORE_LOCAL->set("trainingMsg2",'*** TRAINING MODE IS ON ***');

$CORE_LOCAL->set("farewellMsg1",'Thanks for shopping at George Street Co-op');
$CORE_LOCAL->set("farewellMsg2",'Store credit within 30 days with receipt');
$CORE_LOCAL->set("farewellMsg3",'732-247-8280');

$CORE_LOCAL->set("alertBar",'Alert');

/***********************************************************************************
Credit Card
************************************************************************************/

$CORE_LOCAL->set("CCintegrate",1);
$CORE_LOCAL->set("gcIntegrate",1);
$CORE_LOCAL->set("ccLive",1); // credit card integration live or testing. live = 1, testing = 0
$CORE_LOCAL->set("RegisteredPaycardClasses",array('GoEMerchant','AuthorizeDotNet'));

/***********************************************************************************
Other Settings
************************************************************************************/

$CORE_LOCAL->set("discountEnforced",1);
$CORE_LOCAL->set("lockScreen",1);
$CORE_LOCAL->set("ddNotify",0); 
$CORE_LOCAL->set("promoMsg",0);

$CORE_LOCAL->set("memlistNonMember",0);
$CORE_LOCAL->set("cashOverLimit",0);
$CORE_LOCAL->set("dollarOver",50);
$CORE_LOCAL->set("defaultNonMem",'11');

if ($CORE_LOCAL->get("inputMasked") == "")
$CORE_LOCAL->set("inputMasked",0);

$CORE_LOCAL->set("SCReceipt",1);/***staff charge receipt - print default for each lane--apbw 1/31/05***/
$CORE_LOCAL->set("CustomerDisplay",0);
$CORE_LOCAL->set("touchscreen",False);

//$CORE_LOCAL->set("SigCapture",'COM1');
$CORE_LOCAL->set("SigCapture",'');
$CORE_LOCAL->set("visitingMem",'55');
/* ScalePort moved to ini-local.php */
$CORE_LOCAL->set("scaleDriver",'ssd');
$CORE_LOCAL->set("CCSigLimit",0);
$CORE_LOCAL->set("SpecialUpcClasses",array('CouponCode','HouseCoupon','MemberCard','SpecialOrder'));
$CORE_LOCAL->set("DiscountTypeCount",5);
$CORE_LOCAL->set("DiscountTypeClasses",array('NormalPricing','PercentMemSale','PercentMemSale','PercentMemSale','PercentMemSale'));
$CORE_LOCAL->set("PriceMethodCount",3);
$CORE_LOCAL->set("PriceMethodClasses",array('BasicPM','GroupPM','QttyEnforcedGroupPM'));


@include_once($CORE_PATH.'ini-local.php');
?>