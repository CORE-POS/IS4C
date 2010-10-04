<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

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
require_once('../src/mysql_connect.php');

if (isset($_REQUEST['memNum'])){
	$cn = sprintf("%d",$_REQUEST['memNum']);
	$addrQ = "SELECT street,city,state,zip,phone,
		email_1 as email,email_2 as alt_phone
		FROM memInfo WHERE card_no=$cn";
	$addrR = $dbc->query($addrQ);
	if ($dbc->num_rows($addrR) == 0){
		echo "Error: member $cn not found";
		return;
	}

	$addrW = $dbc->fetch_row($addrR);

	$namesQ = "SELECT lastname,firstname,type FROM custdata
		WHERE cardno=$cn ORDER BY personNum";
	$namesR = $dbc->query($namesQ);
	$names = array();
	$type = 'REG';
	while($w = $dbc->fetch_row($namesR)){
		$names[] = array(
			'full'=>$w['lastname'].", ".$w['firstname'],
			'fn'=>$w['firstname'],
			'ln'=>$w['lastname']
		);
		$type = $w['type'];
	}

	$types = array('REG'=>'Non Member','PC'=>'Member','INACT'=>'Inactive',
		'INACT2'=>'Term Pending','TERM'=>'Terminated');

	$out = "<table cellspacing=0 cellpadding=4 border=1>";
	$out .= "<tr><th>Name</th><td colspan=3><select name=fullname>";
	foreach($names as $n)
		$out .= "<option>".$n['full']."</option>";
	$out .= "</select></td>";
	$out .= "<th>Status</th><td colspan=2>".$types[$type]."</td></tr>";

	$addr2 = "";
	if (strstr($addrW['street'],"\n") !== False){
		$tmp = explode("\n",$addrW['street']);
		$addr2 = $tmp[1];
		$addrW['street'] = $tmp[0];
	}
	$out .= "<tr><th>Address</th><td colspan=5><input size=35 class=\"required\" title=\"1st address line\" 
		type=text name=street1 value=\"".$addrW['street']."\" /></td></tr>";
	$out .= "<tr><th>2nd line</th><td colspan=5><input size=35 type=text name=street2 value=\"".$addr2."\" /></td></tr>";
	$out .= "<tr><th>City</th><td><input type=text class=\"required\" title=\"City\" size=6 name=city value=\"".$addrW['city']."\" /></td>";
	$out .= "<th>State</th><td><input type=text size=2 class=\"required\" title=\"State\" name=state value=\"".$addrW['state']."\" /></td>";
	$out .= "<th>Zip</th><td><input type=text size=5 class=\"required\" title=\"Zipcode\" name=zip value=\"".$addrW['zip']."\" /></td></tr>";
	$out .= "<tr><th>Phone</th><td colspan=2><input type=text class=\"required\" title=\"Primary phone number\" 
		size=12 name=ph value=\"".$addrW['phone']."\" /></td>";
	$out .= "<th>Alt.</th><td colspan=2><input type=text size=12 name=ph2 value=\"".$addrW['alt_phone']."\" /></td></tr>";
	$out .= "<tr><th>E-mail</th><td colspan=5><input size=35 type=text name=email value=\"".$addrW['email']."\" /></td></tr>";
	$out .= "</table>";

	$idQ = "SELECT id FROM SpecialOrderUser WHERE card_no=$cn";
	$idR = $dbc->query($idQ);
	$id = "NEW";
	if ($dbc->num_rows($idR) > 0){
		$id = array_pop($dbc->fetch_row($idR));
	}
	$out .= "<input type=hidden name=uid value=\"$id\" />";
	$out .= "<input type=hidden name=cardno value=\"$cn\" />";

	echo $out;
}
else if (isset($_REQUEST['term'])){
	/* jquery autocomplete on last name */
	$search = $dbc->escape($_REQUEST['term'].'%');
	$q = "SELECT lastname FROM SpecialOrderUser	
		WHERE card_no=0 AND lastname like $search
		ORDER BY lastname";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) == 0){
		echo "[]";
		return;
	}
	$out = "[";
	$count = 0;
	while($w = $dbc->fetch_row($r)){
		$out .= "\"".$w[0]."\",";
		$count++;
		if ($count > 10) break;
	}
	$out = substr($out,0,strlen($out)-1)."]";	
	echo $out;
}
else if (isset($_REQUEST['ln-select'])){
	$search = $dbc->escape($_REQUEST['ln-select']);
	$q = "SELECT id,firstname FROM SpecialOrderUser	
		WHERE card_no=0 AND lastname = $search
		ORDER BY firstname";
	$r = $dbc->query($q);
	$out = "";
	while($w = $dbc->fetch_row($r))
		$out .= sprintf("<option value=%d>%s</option>",$w[0],$w[1]);	
	$out .= "<option value=NEW>New customer</option>";
	echo $out;
}
else if (isset($_REQUEST['ln-form'])){
	$ln = $dbc->escape($_REQUEST['ln-form']);
	$fn = "";
	$id = $_REQUEST['uid'];
	$street1 = "";
	$street2 = "";
	$ph = "";
	$alt_ph = "";
	$email = "";
	$city = "DULUTH";
	$state = "MN";
	$zip = "";
	$cn = 0;
	if (is_numeric($_REQUEST['uid'])){
		$q = "SELECT firstname,street1,street2,city,state,zip,phone,alt_phone,email
			FROM SpecialOrderUser WHERE id=".$_REQUEST['uid'];
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0){
			$w = $dbc->fetch_row($r);
			$fn = $w['firstname'];
			$street1 = $w['street1'];
			$street2 = $w['street2'];
			$city = $w['city'];
			$state = $w['state'];
			$zip = $w['zip'];
			$ph = $w['phone'];
			$alt_ph = $w['alt_phone'];
			$email = $w['email'];
		}
	}

	$out = "<table cellspacing=0 cellpadding=4 border=1>";

	$out .= "<tr><th>First Name</th><td colspan=2><input type=text name=fn value=\"".$fn."\" /></td>";
	$out .= "<th>Last Name</th><td colspan=2><input type=text name=ln value=\"".$_REQUEST['ln-form']."\" /></td></tr>";
	$out .= "<tr><th>Address</th><td colspan=5><input size=35 type=text name=street1 value=\"".$street1."\" /></td></tr>";
	$out .= "<tr><th>2nd line</th><td colspan=5><input size=35 type=text name=street2 value=\"".$street2."\" /></td></tr>";
	$out .= "<tr><th>City</th><td><input type=text size=6 name=city value=\"".$city."\" /></td>";
	$out .= "<th>State</th><td><input type=text size=2 name=state value=\"".$state."\" /></td>";
	$out .= "<th>Zip</th><td><input type=text size=5 name=zip value=\"".$zip."\" /></td></tr>";
	$out .= "<tr><th>Phone</th><td colspan=2><input type=text size=12 name=ph value=\"".$ph."\" /></td>";
	$out .= "<th>Alt.</th><td colspan=2><input type=text size=12 name=ph2 value=\"".$alt_ph."\" /></td></tr>";
	$out .= "<tr><th>E-mail</th><td colspan=5><input size=35 type=text name=email value=\"".$email."\" /></td></tr>";
	$out .= "</table>";

	$out .= "<input type=hidden name=uid value=\"$id\" />";
	$out .= "<input type=hidden name=cardno value=\"0\" />";

	echo $out;
}

?>
