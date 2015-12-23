<?php
if (!isset($FANNIE_LANES))
    include('/var/www/html/git/fannie/config.php');
$lanes = array();
$dbs = array();
$types = array();
$users = array();
$pws = array();
foreach($FANNIE_LANES as $LANE){
    $lanes[] = $LANE['host'];
    $dbs[] = $LANE['op'];
    $types[] = $LANE['type'];
    $users[] = $LANE['user'];
    $pws[] = $LANE['pw'];
}
$numlanes = count($lanes);
/*
$lanes = array("129.103.2.11","129.103.2.22","129.103.2.23","129.103.2.24","129.103.2.21","129.103.2.26");
$numlanes = count($lanes);
$dbs = array("opdata","opdata","opdata","opdata","opdata","opdata");
$types = array("MYSQL","MYSQL","MYSQL","MYSQL","MYSQL","MYSQL");
$users = array("root","root","root","root","root","root");
$pws = array("is4c","is4c","is4c","is4c","is4c","is4c");
*/

