<?php

function audit($dept_sub,$uid,$upc,$desc,$price,$tax,$fs,$scale,$discount,$likecode=False){
	$taxes = array("NoTax","Reg","Deli");
	$tos = array(	0=>"andy",
			1=>"jim, lisa",
			2=>"jesse, lisa",
			3=>"debbie, eric, justin, vicky",
			4=>"joeu, lisa",
			5=>"jillhall, lisa",
			6=>"michael, alex",
			7=>"shannon",
			8=>"jesse, lisa",
			9=>"raelynn, lisa"
	);	

	$subject = "Item Update notification: ".$upc;
	$message = "Item $upc ($desc) has been changed\n";	
	$message .= "Price: $price\n";
	$message .= "Tax: ".$taxes[$tax]."\n";
	$message .= "Foodstampable: ".($fs==1?"Yes":"No")."\n";
	$message .= "Scale: ".($scale==1?"Yes":"No")."\n";
	$message .= "Discountable: ".($discount==1?"Yes":"No")."\n";
	if ($likecode != False){
		if ($likecode == -1)
			$message .= "This item is not in a like code\n";
		else
			$message .= "All items in this likecode ($likecode) were changed\n";
	}
	$message .= "\n";
	$message .= "Adjust this item?\n";
	$message .= "http://key/git/fannie/legacy/queries/productTest.php?upc=$upc\n";
	$message .= "\n";
	$message .= "This change was made by user $uid\n";

	$from = "From: automail\r\n";

	mail($tos[$dept_sub],$subject,$message,$from);
}

?>
