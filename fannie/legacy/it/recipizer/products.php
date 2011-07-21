<?php
/*
 * Functions for accessing the product table
 * Separating this out should [hopefully]
 * improve extensibility
 * 
 * Redfine functions as needed to work with a different
 * POS system
 */
 
 function productdb(){
	if (!class_exists("SQLManager")) require_once("../../sql/SQLManager.php");
	include('../../db.php');	
	return $sql;
 }
 
 function getPrice($upc){
 	$sql = productdb();
 	
 	$q = "select normal_price from products where upc='$upc'";
 	$r = $sql->query($q,$msdb);
 	
 	if ($sql->num_rows($r) == 0)
 		return false;
 	
 	$w = $sql->fetch_array($r);
 	return $w[0];
 }
 
 function setPrice($upc,$price){
 	$sql = productdb();	
 
 	$q = "update products set normal_price = $price where upc='$upc'";
 	$r = $sql->query($q,$msdb);
 }
 	
 
?>
