<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
 // session_start(); 
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!function_exists("tDataConnect")) include($CORE_PATH."lib/connect.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

$CORE_LOCAL->set("away",1);

/*
 * DDD is WFC lingo for unsaleable goods (dropped, dented, damaged, etc)
 * Functionally this works like canceling a transaction, but marks
 * items with a different trans_status (Z) so these items can
 * be pulled out in later reports
 */

$db = tDataConnect();
$query = "UPDATE localtemptrans SET trans_status='Z'";
$db->query($query);
$query = "INSERT INTO dtransactions SELECT * from localtemptrans";
$db->query($query);
$query = "INSERT INTO localtrans SELECT * from localtemptrans";
$db->query($query);

$CORE_LOCAL->set("plainmsg","items marked ddd");
$CORE_LOCAL->set("beep","rePoll");
$CORE_LOCAL->set("msg",2);
$CORE_LOCAL->set("End",2);

$_REQUEST['receiptType'] = 'ddd';
ob_start();
include($CORE_PATH.'ajax-callbacks/ajax-end.php');
header("Location: {$CORE_PATH}gui-modules/pos2.php");

?>
