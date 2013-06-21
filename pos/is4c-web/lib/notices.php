<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

*********************************************************************************/
 // session_start();
 
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");
if (!function_exists("getRealName")) include($IS4C_PATH."auth/utilities.php");
if (!function_exists("tDataConnect")) include($IS4C_PATH."lib/connect.php");

define("STORE_EMAIL","orders@wholefoods.coop");
define("REPLY_EMAIL","andy@wholefoods.coop");
define("ADMIN_EMAIL","andy@wholefoods.coop");

function send_email($to,$subject,$msg){
	$headers = 'From: '.STORE_EMAIL."\r\n";
	$headers .= 'Reply-To: '.REPLY_EMAIL."\r\n";

	mail($to,$subject,$msg,$headers);
}

function customer_confirmation($uid,$email,$total){
	$msg = "Thank you for ordering from Whole Foods Co-op\n\n";
	$msg .= "Order Summary:\n";
	$cart = getcart($uid);
	$msg .= $cart."\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	send_email($email,"WFC Order Confirmation",$msg);

	return $cart;
}

function admin_notification($uid,$email,$ph,$total,$cart=""){
	$msg = "New online order\n\n";
	$msg .= getRealName($email)." (".$email.")\n";
	$msg .= "Phone # provided: ".$ph."\n\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	$msg .= "\nOrder Summary:\n";
	$msg .= $cart;
	
	send_email(ADMIN_EMAIL,"New Online Order",$msg);
}

function mgr_notification($addresses,$email,$ph,$total,$notes="",$cart=""){
	$msg = "New online order\n\n";
	$msg .= getRealName($email)." (".$email.")\n";
	$msg .= "Phone # provided: ".$ph."\n\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	$msg .= "\nOrder Summary:\n";
	$msg .= $cart;

	$msg .= "\n:Additional attendees\n";
	$msg .= (!empty($notes) ? $notes : 'none listed');
	
	$addr = "";
	foreach($addresses as $a)
		$addr .= $a.",";
	$addr = rtrim($addr,",");
	send_email($addr,"New Online Order",$msg);
}

function getcart($empno){
	$db = tDataConnect();
	$q = "SELECT description,quantity,total FROM
		cart WHERE emp_no=$empno";
	$r = $db->query($q);
	$ret = "";
	while($w = $db->fetch_row($r)){
		$ret .= $w['description']."\t\tx";
		$ret .= $w['quantity']."\t\$";
		$ret .= sprintf("%.2f",$w['total'])."\n";
	}

	$ret .= "\n";

	$taxQ = "SELECT taxes FROM taxTTL WHERE emp_no=$empno";
	$taxR = $db->query($taxQ);
	$taxes = round(array_pop($db->fetch_row($taxR)),2);
	$ret .= sprintf("Sales tax: \$%.2f\n",$taxes);

	return $ret;
}

?>
