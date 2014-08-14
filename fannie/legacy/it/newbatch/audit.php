<?php
$tos = array(	0=>"andy@wholefoods.coop",
		1=>"jim@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		2=>"jesse@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		3=>"fkoenig@wholefoods.coop, aelliott@wholefoods.coop, justin@wholefoods.coop, rianna@wholefoods.coop",
		4=>"joeu@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		5=>"jillhall@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		6=>"michael@wholefoods.coop, alex@wholefoods.coop",
		7=>"shannon@wholefoods.coop",
		8=>"jesse@wholefoods.coop, lisa@wholefoods.coop, meales@wholefoods.coop",
		9=>"lisa@wholefoods.coop, meales@wholefoods.coop"
);	

function auditPriceChange($sql,$uid,$upc,$price,$batchID){
	global $tos;

	$query = $sql->prepare("select p.description,b.batchName,d.superID from products as p, batches as b,
		MasterSuperDepts as d where p.upc = ? and b.batchID=?
		and p.department=d.dept_ID");
	$result = $sql->execute($query, array($upc, $batchID));
	$row = $sql->fetch_row($result);
	$dept_sub = $row[2];

	$subject = "Batch Update notification: ".$row[1];
	$message = "Batch $row[1] has been changed\n";
	$message .= "Item $upc ($row[0]) has been added to the batch\n";	
	$message .= "Sale Price: $".$price."\n";
	$message .= "\n";
	$message .= "Go to the batch page:\n";
	$message .= "http://key.wfco-op.store/it/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}

function auditPriceChangeLC($sql,$uid,$upc,$price,$batchID){
	global $tos;

	$query = $sql->prepare("select l.likeCodeDesc,b.batchName from likeCodes as l, batches as b
		where b.batchID=? and l.likeCode=?");
	$result = $sql->execute($query, array($batchID, substr($upc,2)));
	$row = $sql->fetch_row($result);
	$deptQ = $sql->prepare("select d.superID from products as p left join
		upcLike as u on p.upc=u.upc left join MasterSuperDepts as d on p.department=d.dept_ID
		where u.likeCode=? and d.superID is not null
		group by d.superID order by count(*) desc");
	$deptR = $sql->execute($deptQ, array(substr($upc,2)));
	$dept_sub = array_pop($sql->fetch_row($deptR));

	$subject = "Batch Update notification: ".$row[1];
	$message = "Batch $row[1] has been changed\n";
	$message .= "Likecode $upc ($row[0]) has been added to the batch\n";	
	$message .= "Sale price: $".$price."\n";
	$message .= "\n";
	$message .= "Go to the batch page:\n";
	$message .= "http://key/it/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}

function auditSavePrice($sql,$uid,$upc,$price,$batchID){
	global $tos;

	$query = $sql->prepare("select p.description,b.batchName,d.superID from products as p, batches as b,
		MasterSuperDepts as d where p.upc = ? and b.batchID=?
		and p.department=d.dept_ID");
    $args = array($upc, $batchID);
	if (substr($upc,0,2) == "LC"){
		$query = $sql->prepare("select l.likeCodeDesc,b.batchName from likeCodes as l, batches as b
			where b.batchID=? and l.likeCode=?");
        $args = array($batchID, substr($upc,2));
	}
	$result = $sql->execute($query, array($args));
	$row = $sql->fetch_row($result);
	$dept_sub = 0;
	if (substr($upc,0,2) == "LC"){
		$deptQ = $sql->prepare("select d.superID,count(*) from products as p left join
			upcLike as u on p.upc=u.upc left join MasterSuperDepts as d on p.department=d.dept_ID
			where u.likeCode=? and d.superID is not null
			group by d.superID order by count(*) desc");
		$deptR = $sql->execute($deptQ, array(substr($upc,2)));
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
	$message .= "http://key/it/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}

function auditDelete($sql,$uid,$upc,$batchID){
	global $tos;

	$query = $sql->prepare("select p.description,b.batchName,d.superID from products as p, batches as b,
		MasterSuperDepts as d where p.upc = ? and b.batchID=?
		and p.department=d.dept_ID");
    $args = array($upc, $batchID);
	if (substr($upc,0,2) == "LC"){
		$query = $sql->prepare("select l.likeCodeDesc,b.batchName from likeCodes as l, batches as b
			where b.batchID=? and l.likeCode=?");
        $args = array($batchID, substr($upc,2));
	}
	$result = $sql->execute($query, array($args));
	$row = $sql->fetch_row($result);
	$dept_sub = 0;
	if (substr($upc,0,2) == "LC"){
		$deptQ = $sql->prepare("select d.superID from products as p left join
			upcLike as u on p.upc=u.upc left join MasterSuperDepts as d on p.department=d.dept_ID
			where u.likeCode=? and d.superID is not null
			group by d.superID order by count(*) desc");
		$deptR = $sql->execute($deptQ, array(substr($upc,2)));
		$dept_sub = array_pop($sql->fetch_row($deptR));
	}
	else
		$dept_sub = $row[2];

	$subject = "Batch Update notification: ".$row[1];
	$message = "Batch $row[1] has been changed\n";
	$message .= "Item $upc ($row[0]) has been deleted from the batch\n";	
	$message .= "\n";
	$message .= "Go to the batch page:\n";
	$message .= "http://key/it/newbatch/\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}
?>
