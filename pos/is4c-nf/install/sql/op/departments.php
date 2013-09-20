<?php
/*
Table: departments

Columns:
	dept_no smallint
	dept_name varchar
	dept_tax tinyint
	dept_fs tinyint
	dept_limit dbms currency
	dept_minimum dbms currency
	dept_discount tinyint
	modified datetime
	modifiedby int

Depends on:
	none

Use:
Departments are the primary level of granularity
for products. Each product may belong to one department,
and when items are rung up the department setting
is what's saved in the transaction log

dept_no and dept_name identify a department

dept_tax,dept_fs, and dept_discount indicate whether
items in that department are taxable, foodstampable,
and discountable (respectively). Mostly these affect
open rings at the register, although WFC also uses
them to speed up new item entry.

dept_limit and dept_minimum are the highest and lowest
sales allowed in the department. These also affect open
rings. The prompt presented if limits are exceeded is
ONLY a warning, not a full stop.
*/

$CREATE['op.departments'] = "
	CREATE TABLE `departments` (
	  `dept_no` smallint(6) default NULL,
	  `dept_name` varchar(30) default NULL,
	  `dept_tax` tinyint(4) default NULL,
	  `dept_fs` tinyint(4) default NULL,  
	  `dept_limit` double default NULL,
	  `dept_minimum` double default NULL,
	  `dept_discount` tinyint(4) default NULL,
	  `modified` datetime default NULL,
	  `modifiedby` int(11) default NULL,
	  PRIMARY KEY (`dept_no`),
	  KEY `dept_name` (`dept_name`)
	);
";
if ($dbms == "MSSQL"){
	$CREATE['op.departments'] = "
		CREATE TABLE [departments] (
			[dept_no] [smallint] NULL ,
			[dept_name] [nvarchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[dept_tax] [tinyint] NULL ,
			[dept_fs] [bit] NOT NULL ,
			[dept_limit] [money] NULL ,
			[dept_minimum] [money] NULL ,
			[dept_discount] [smallint] NULL ,
			[modified] [smalldatetime] NULL ,
			[modifiedby] [int] NULL 
		)";
}
elseif ($dbms == 'PDOLITE'){
	$CREATE['op.departments'] = "
		CREATE TABLE `departments` (
		  `dept_no` smallint(6) default NULL,
		  `dept_name` varchar(30) default NULL,
		  `dept_tax` tinyint(4) default NULL,
		  `dept_fs` tinyint(4) default NULL,  
		  `dept_limit` double default NULL,
		  `dept_minimum` double default NULL,
		  `dept_discount` tinyint(4) default NULL,
		  `modified` datetime default NULL,
		  `modifiedby` int(11) default NULL,
		  PRIMARY KEY (`dept_no`)
		)";
}

?>
