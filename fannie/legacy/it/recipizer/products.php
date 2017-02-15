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
     
     $q = $sql->prepare("select normal_price from products where upc=?");
     $r = $sql->execute($q,array($upc),$msdb);
     
     if ($sql->num_rows($r) == 0)
         return false;
     
     $w = $sql->fetchRow($r);
     return $w[0];
 }
 
 function setPrice($upc,$price){
     $sql = productdb();    
 
     $q = $sql->prepare("update products set normal_price = ? where upc=?");
     $r = $sql->execute($q,array($price, $upc),$msdb);
 }
     
 
