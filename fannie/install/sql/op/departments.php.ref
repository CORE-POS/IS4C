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
    dept_see_id tinyint
    modified datetime
    modifiedby int
    margin double
    salesCode int
    memberOnly smallint

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
them to speed up new item entry. dept_see_id is for
departments where customers should show ID (e.g., alcohol).
The value is the age required for purchase.

dept_limit and dept_minimum are the highest and lowest
sales allowed in the department. These also affect open
rings. The prompt presented if limits are exceeded is
ONLY a warning, not a full stop.

margin is desired margin for products in the department.
It can be used for calculating retail pricing based
on costs. By convention, values are less than one.
A value of 0.35 means 35% margin. This value has
no meaning on the lane.

salesCode is yet another way of categorizing items.
It is typically used for chart of account numbers.
Often the financial accounting side of the business
wants to look at sales figures differently than
the operational side of the business. It's an organizational
and reporting field with no meaning on the lane.

memberOnly restricts sales based on customer membership
status. Values 0 through 99 are reserved. 100 and above
may be used for custom settings. Currently defined values:
    0 => No restrictions
    1 => Active members only (custdata.Type = 'PC')
    2 => Active members only but cashier can override
    3 => Any custdata account *except* the default non-member account
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
      `dept_see_id` tinyint(4) default NULL,
      `modified` datetime default NULL,
      `modifiedby` int(11) default NULL,
      `margin` double default 0,
      `salesCode` int default 0,
      `memberOnly` smallint default 0,
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
            [dept_see_id] [tinyint] NULL ,
            [modified] [smalldatetime] NULL ,
            [modifiedby] [int] NULL ,
            [margin] [double] NULL,
            [salesCode] [int] NULL,
            [memberOnly] [smallint] NULL
        )";
}

?>
