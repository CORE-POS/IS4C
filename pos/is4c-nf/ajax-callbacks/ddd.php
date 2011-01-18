<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

if (!function_exists("tDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

$IS4C_LOCAL->set("away",1);

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

$IS4C_LOCAL->set("plainmsg","items marked ddd");
$IS4C_LOCAL->set("beep","rePoll");
$IS4C_LOCAL->set("msg",2);
$IS4C_LOCAL->set("End",2);

$_REQUEST['receiptType'] = 'ddd';
ob_start();
include($IS4C_PATH.'ajax-callbacks/ajax-end.php');
header("Location: {$IS4C_PATH}gui-modules/pos2.php");

?>
