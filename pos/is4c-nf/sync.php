<?php

require("C:\\IS4C/lib/SQLManager.php");
$sql = new SQLManager('129.103.2.10','MSSQL','WedgePOS','sa');
$sql->add_connection("localhost",'MYSQL','opdata','root','is4c');

/****************************************
* Sync Products table
****************************************/
echo "Syncing Products\n";
$sql->query("TRUNCATE TABLE Products","opdata");
$selQ = "SELECT upc,description,normal_price,pricemethod,groupprice,quantity,
	special_price,specialpricemethod,specialgroupprice,specialquantity,start_date,end_date,
	department,size,tax,foodstamp,scale,mixmatchcode,modified,advertised,tareweight,discount,
	discounttype,unitofmeasure,wicable,qttyEnforced,inUse FROM products";
$ins = "INSERT INTO products (upc,description,normal_price,pricemethod,groupprice,quantity,
	special_price,specialpricemethod,specialgroupprice,specialquantity,start_date,end_date,
	department,size,tax,foodstamp,scale,mixmatchcode,modified,advertised,tareweight,discount,
	discounttype,unitofmeasure,wicable,qttyEnforced,inUse)";
$sql->transfer('WedgePOS',$selQ,'opdata',$ins);

/****************************************
* Sync custdata table
****************************************/
echo "Syncing Customer data\n";
$sql->query("TRUNCATE TABLE custdata","opdata");
$selQ = "SELECT * FROM custdata WHERE Type <> 'TERM'";
$ins = "INSERT INTO custdata (CardNo,personNum,LastName,FirstName,
	CashBack,Balance,Discount,MemDiscountLimit,ChargeOK,
	WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
	NumberOfChecks,memCoupons,blueLine,Shown)";
$sql->transfer("WedgePOS",$selQ,"opdata",$ins);

/****************************************
* Sync Departments & subdepts tables
****************************************/
echo "Syncing departments\n";
$sql->query("TRUNCATE TABLE Departments","opdata");
$sql->query("TRUNCATE TABLE subdepts","opdata");
                        
$selQ = "SELECT dept_no,dept_name,dept_tax,dept_fs,dept_limit,dept_minimum,
	dept_discount,modified,modifiedby FROM departments";
$ins = "INSERT INTO departments";
$sql->transfer("WedgePOS",$selQ,"opdata",$ins);

$selQ = "SELECT dept_sub,
	CASE WHEN dept_sub=0 THEN 'MISC'
	when dept_sub = 1 then 'BULK'
	when dept_sub = 2 then 'COOL/FROZEN'
	when dept_sub = 3 then 'DELI'
	when dept_sub = 4 then 'GROCERY'
	when dept_sub = 5 then 'HBC'
	when dept_sub = 6 then 'PRODUCE'
	when dept_sub = 7 then 'WFC'
	when dept_sub = 8 then 'MEAT'
	when dept_sub = 9 then 'GEN MERCH'
	else '' end as subdept_name,
	dept_no FROM departments";
$ins = "INSERT INTO subdepts";
$sql->transfer("WedgePOS",$selQ,"opdata",$ins);

/****************************************
* Sync Employees table
****************************************/
echo "Syncing Employees\n";
$sql->query("TRUNCATE TABLE Employees","opdata");
$selQ = "SELECT emp_no,CashierPassword,AdminPassword,FirstName,
	LastName,JobTitle,EmpActive,frontendsecurity,
	backendsecurity FROM employees";
$ins = "INSERT INTO employees";
$sql->transfer("WedgePOS",$selQ,"opdata",$ins);

/****************************************
* Switch to translog & trim logs
****************************************/
echo "Trimming logs\n";
$sql->add_connection("localhost",'MYSQL','translog','root','is4c');
$sql->query("DELETE FROM localTrans_today WHERE
	datediff(curdate(),datetime) <> 0","translog");
$sql->query("DELETE FROM activitylog WHERE
	datediff(curdate(),datetime) > 45","translog");
$sql->query("DELETE FROM localTrans WHERE
	datediff(curdate(),datetime) > 45","translog");

/****************************************
* Sync efsnet tables
****************************************/
echo "Offloading efsnet data\n";
$sql->transfer("translog","SELECT * FROM efsnetrequest where
		datediff(curdate(),datetime) <> 0",
		"WedgePOS","INSERT INTO efsnetrequest");
$sql->query("DELETE FROM efsnetrequest WHERE
	datediff(curdate(),datetime) <> 0","translog");
$sql->transfer("translog","SELECT * FROM efsnetrequestmod where
		datediff(curdate(),datetime) <> 0",
		"WedgePOS","INSERT INTO efsnetrequestmod");
$sql->query("DELETE FROM efsnetrequestmod WHERE
	datediff(curdate(),datetime) <> 0","translog");
$sql->transfer("translog","SELECT * FROM efsnetresponse where
		datediff(curdate(),datetime) <> 0",
		"WedgePOS","INSERT INTO efsnetresponse");
$sql->query("DELETE FROM efsnetresponse WHERE
	datediff(curdate(),datetime) <> 0","translog");

/****************************************
* Sync valutec tables
****************************************/
echo "Offloading valutec data\n";
$sql->transfer("translog","SELECT * FROM valutecrequest where
		datediff(curdate(),datetime) <> 0",
		"WedgePOS","INSERT INTO valutecrequest");
$sql->query("DELETE FROM valutecrequest WHERE
	datediff(curdate(),datetime) <> 0","translog");
$sql->transfer("translog","SELECT * FROM valutecrequestmod where
		datediff(curdate(),datetime) <> 0",
		"WedgePOS","INSERT INTO valutecrequestmod");
$sql->query("DELETE FROM valutecrequestmod WHERE
	datediff(curdate(),datetime) <> 0","translog");
$sql->transfer("translog","SELECT * FROM valutecresponse where
		datediff(curdate(),datetime) <> 0",
		"WedgePOS","INSERT INTO valutecresponse");
$sql->query("DELETE FROM valutecresponse WHERE
	datediff(curdate(),datetime) <> 0","translog");

?>
