<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$tos = array(	0=>"andy@wholefoods.coop",
		1=>"jim@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		2=>"jesse@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		3=>"debbie@wholefoods.coop, aelliott@wholefoods.coop, justin@wholefoods.coop, rianna@wholefoods.coop",
		4=>"joeu@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		5=>"jillhall@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		6=>"michael@wholefoods.coop, alex@wholefoods.coop",
		7=>"shannon@wholefoods.coop",
		8=>"jesse@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		9=>"lisa@wholefoods.coop, meales@wholefoods.coop"
);	
$hostname = 'key.wfco-op.store';

function auditPriceChange($sql,$uid,$upc,$price,$batchID){
	global $tos, $hostname, $FANNIE_URL;

	$query = $sql->prepare_statement("select p.description,b.batchName,s.superID from products as p, batches as b,
		MasterSuperDepts AS s
		where p.upc = ? and b.batchID=? AND p.department=s.dept_ID");
	$result = $sql->exec_statement($query,array($upc,$batchID));
	$row = $sql->fetch_row($result);
	$dept_sub = $row[2];

	$subject = "Batch Update notification: ".$row[1];
	$message = "Batch $row[1] has been changed\n";
	$message .= "Item $upc ($row[0]) has been added to the batch\n";	
	$message .= "Sale Price: $".$price."\n";
	$message .= "\n";
	$message .= "Go to the batch page:\n";
	$message .= "http://{$hostname}{$FANNIE_URL}batches/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}

function auditPriceChangeLC($sql,$uid,$upc,$price,$batchID){
	global $tos,$hostname,$FANNIE_URL;

	$query = $sql->prepare_statement("select l.likeCodeDesc,b.batchName from likeCodes as l, batches as b
		where b.batchID=? and l.likecode=?");
	$result = $sql->exec_statement($query,array($batchID,substr($upc,2)));
	$row = $sql->fetch_row($result);
	$deptQ = $sql->prepare_statement("select s.superID from products as p left join
		upcLike as u on p.upc=u.upc left join
		MasterSuperDepts AS s ON p.department=s.dept_ID
		where u.likecode=?
		group by s.superID order by count(*) desc");
	$deptR = $sql->exec_statement($deptQ,array(substr($upc,2)));
	$dept_sub = array_pop($sql->fetch_row($deptR));

	$subject = "Batch Update notification: ".$row[1];
	$message = "Batch $row[1] has been changed\n";
	$message .= "Likecode $upc ($row[0]) has been added to the batch\n";	
	$message .= "Sale price: $".$price."\n";
	$message .= "\n";
	$message .= "Go to the batch page:\n";
	$message .= "http://{$hostname}{$FANNIE_URL}batches/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}

function auditSavePrice($sql,$uid,$upc,$price,$batchID){
	global $tos,$hostname,$FANNIE_URL;

	$query = $sql->prepare_statement("select p.description,b.batchName,s.superID from products as p, batches as b,
		MasterSuperDepts AS s
		where p.upc = ? and b.batchID=? AND s.dept_ID=p.department");
	$args = array($upc, $batchID);
	if (substr($upc,0,2) == "LC"){
		$query = $sql->prepare_statement("select l.likeCodeDesc,b.batchName from likeCodes as l, batches as b
			where b.batchID=? and l.likecode=?");
		$args = array($batchID, substr($upc,2));
	}
	$result = $sql->exec_statement($query,$args);
	$row = $sql->fetch_row($result);
	$dept_sub = 0;
	if (substr($upc,0,2) == "LC"){
		$deptQ = $sql->prepare_statement("select s.superID,count(*) from products as p left join
			upcLike as u on p.upc=u.upc left join
			MasterSuperDepts AS s ON p.department=s.dept_ID
			where u.likecode=?
			group by s.superID order by count(*) desc");
		$deptR = $sql->exec_statement($deptQ,array(substr($upc,2)));
		$dept_sub = array_pop($sql->fetch_row($deptR));
	}
	else
		$dept_sub = $row[2];

	$subject = "Batch Update notification: ".$row[1];
	$message = "Batch $row[1] has been changed\n";
	$message .= "Item $upc ($row[0]) has been re-priced\n";	
	$message .= "Sale Price: $".$price."\n";
	$message .= "\n";
	$message .= "Go to the batch page:\n";
	$message .= "http://{$hostname}{$FANNIE_URL}batches/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}

function auditDelete($sql,$uid,$upc,$batchID){
	global $tos,$hostname,$FANNIE_URL;

	$query = $sql->prepare_statement("select p.description,b.batchName,s.superID from products as p, batches as b,
		MasterSuperDepts AS s
		where p.upc = ? and b.batchID=? AND s.dept_ID=p.department");
	$args = array($upc, $batchID);
	if (substr($upc,0,2) == "LC"){
		$query = $sql->prepare_statement("select l.likeCodeDesc,b.batchName from likeCodes as l, batches as b
			where b.batchID=? and l.likecode=?");
		$args = array($batchID, substr($upc,2));
	}
	$result = $sql->exec_statement($query,$args);
	$row = $sql->fetch_row($result);
	$dept_sub = 0;
	if (substr($upc,0,2) == "LC"){
		$deptQ = $sql->prepare_statement("select s.superID from products as p left join
			upcLike as u on p.upc=u.upc  left join
			MasterSuperDepts AS s ON p.department=s.dept_ID
			where u.likecode=?
			group by s.superID order by count(*) desc");
		$deptR = $sql->exec_statement($deptQ,array(substr($upc,2)));
		$dept_sub = array_pop($sql->fetch_row($deptR));
	}
	else
		$dept_sub = $row[2];

	$subject = "Batch Update notification: ".$row[1];
	$message = "Batch $row[1] has been changed\n";
	$message .= "Item $upc ($row[0]) has been deleted from the batch\n";	
	$message .= "\n";
	$message .= "Go to the batch page:\n";
	$message .= "http://{$hostname}{$FANNIE_URL}batches/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}
?>
