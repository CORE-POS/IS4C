<?php

$args = $argv;
include('/srv/www/htdocs/sql/SQLManager.php');
$sql = 0;

$pid = pcntl_fork();
if ($pid)
	exit(0);
else {
	$sid = posix_setsid();

	chdir("/");
	umask(0);
	
	fclose(STDIN);
	fclose(STDOUT);
	fclose(STDERR);

	$sql = db();
	if (!isset($sql->connections['WedgePOS']) || $sql->connections['WedgePOS'] === False){
		echo "Dead main DB!\n";
		exit(0);	
	}

	if (count($args) < 2) exit(0);

	switch($args[1]){
	case 'laneUpdates':
		if (count($args) < 3) exit(0);
		$upc = $args[2];
		include('/srv/www/htdocs/queries/laneUpdates.php');
		updateProductAllLanes($upc);
		break;
	case 'sync':
		if (count($args) < 3) exit(0);
		$table = $args[2];
		tableSync($table);
		break;
	}
}

function db(){
	include('/srv/www/htdocs/db.php');
	return $sql;
}

function tableSync($table){
	global $sql;

	$lanes = array("129.103.2.16","129.103.2.12","129.103.2.15","129.103.2.13","129.103.2.14","129.103.2.11");
	switch(strtolower($table)){
	case 'products':
		include('/srv/www/htdocs/queries/laneUpdates.php');
		syncProductsAllLanes();
		break;	

	case 'departments':
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','opdata','root','is4c'))
				break;
			
			$sql->query("TRUNCATE TABLE Departments","opdata");
			$sql->query("TRUNCATE TABLE subdepts","opdata");
			
			$selQ = "SELECT dept_no,dept_name,dept_tax,dept_fs,dept_limit,dept_minimum,
				dept_discount,modified,modifiedby FROM departments";
			$ins = "INSERT INTO departments";
			$sql->transfer("WedgePOS",$selQ,"opdata",$ins);

			$selQ = "SELECT * FROM MasterSuperDepts";
			$ins = "INSERT INTO subdepts";
			$sql->transfer("WedgePOS",$selQ,"opdata",$ins);
		}	
		break;

	case 'employees':
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','opdata','root','is4c'))
				break;
			$sql->query("TRUNCATE TABLE Employees","opdata");

			$selQ = "SELECT emp_no,CashierPassword,AdminPassword,FirstName,
				LastName,JobTitle,EmpActive,frontendsecurity,
				backendsecurity FROM employees";
			$ins = "INSERT INTO employees";
			$sql->transfer("WedgePOS",$selQ,"opdata",$ins);
		}
		break;

	case 'custdata':
		$sql->query("exec master..xp_cmdshell 'dtsrun /S IS4CSERV\IS4CSERV /U sa /P is4c /N CSV_custdata',no_output","WedgePOS");
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','opdata','root','is4c'))
				continue;

			if (!is_readable('/pos/csvs/custdata.csv')) break;

			$sql->query("TRUNCATE TABLE custdata","opdata");

			$sql->query("LOAD DATA LOCAL INFILE '/pos/csvs/custdata.csv' INTO TABLE
				custdata FIELDS TERMINATED BY ',' OPTIONALLY
				ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n'","opdata");

			if ($lane != "129.103.2.16"){
				$sql->query("DELETE FROM custdata WHERE type NOT IN ('PC','REG')","opdata");
			}
			else {
				$sql->query("DELETE FROM custdata WHERE type IN ('TERM')","opdata");
			}
		}
		break;

	case 'valutec':
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','translog','root','is4c'))
				break;

			$sql->transfer("translog","select * from valutecRequest",
					"WedgePOS","insert into valutecRequest");
			$sql->query("TRUNCATE TABLE valutecRequest","translog");

			$sql->transfer("translog","select * from valutecRequestMod",
					"WedgePOS","insert into valutecRequestMod");
			$sql->query("TRUNCATE TABLE valutecRequestMod","translog");

			$sql->transfer("translog","select * from valutecResponse",
					"WedgePOS","insert into valutecResponse");
			$sql->query("TRUNCATE TABLE valutecRequestMod","translog");
		}
		break;
	case 'efsnet':
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','translog','root','is4c'))
				break;

			$sql->transfer("translog","select * from efsnetRequest",
					"WedgePOS","insert into efsnetRequest");
			$sql->query("TRUNCATE TABLE efsnetRequest","translog");

			$sql->transfer("translog","select * from efsnetResponse",
					"WedgePOS","insert into efsnetResponse");
			$sql->query("TRUNCATE TABLE efsnetResponse","translog");

			$sql->transfer("translog","select * from efsnetRequestMod",
					"WedgePOS","insert into efsnetRequestMod");
			$sql->query("TRUNCATE TABLE efsnetRequestMod","translog");
		}
		break;

	case 'housecoupons':
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','opdata','root','is4c'))
				break;
			
			$sql->query("TRUNCATE TABLE houseCoupons","opdata");
			$sql->transfer("WedgePOS","select * from houseCoupons",
					"opdata","INSERT INTO houseCoupons");

			$sql->query("TRUNCATE TABLE houseCouponItems","opdata");
			$sql->transfer("WedgePOS","select * from houseCouponItems",
					"opdata","INSERT INTO houseCouponItems");
		}
		break;

	case 'memcards':
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','opdata','root','is4c'))
				break;
			
			$sql->query("TRUNCATE TABLE memberCards","opdata");
			$sql->transfer("WedgePOS","select * from memberCards",
					"opdata","INSERT INTO memberCards");

		}
		break;


	case 'manualdtrans':
		$dtcols = "datetime,register_no,emp_no,trans_no,upc,description,
			trans_type,trans_subtype,trans_status,department,quantity,
			Scale,cost,unitPrice,total,regPrice,tax,foodstamp,discount,
			memDiscount,discountable,discounttype,voided,percentDiscount,
			ItemQtty,volDiscType,volume,VolSpecial,mixMatch,matched,
			memType,isStaff,numflag,charflag,card_no,trans_id";
		$lanes = array('129.103.2.12');
		foreach($lanes as $lane){
			if(!$sql->add_connection($lane,'MYSQL','translog','root','is4c'))
				break;
			
			$sql->transfer("translog","select * from dtrancleanup",
					"WedgePOS","INSERT INTO transArchive ($dtcols)");
			break;
		}
	}
}


?>
