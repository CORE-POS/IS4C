<?php
/*
Table: prodUpdate

Columns:
    prodUpdateID int
    updateType varchar
    upc int or varchar, dbms dependent
    description varchar
    price dbms currency
    salePrice dbms currency
    cost dbms currency
    dept int
    tax bit
    fs bit
    scale bit
    likeCode int
    modified datetime
    user int
    forceQty bit
    noDisc bit
    inUse bit

Depends on:
    products (table)

Use:
In theory, every time a product is change in fannie,
the update is logged here. In practice, not all
tools/cron jobs/sprocs/etc actually do. They probably
should though, ideally.
*/
$CREATE['op.prodUpdate'] = "
    CREATE TABLE `prodUpdate` (
      `prodUpdateID` bigint unsigned not null auto_increment,
      `updateType` varchar(20) default NULL,
      `upc` varchar(13) default NULL,
      `description` varchar(50) default NULL,
      `price` decimal(10,2) default NULL,
      `salePrice` decimal(10,2) default NULL,
      `cost` decimal(10,2) default NULL,
      `dept` int(6) default NULL,
      `tax` tinyint default NULL,
      `fs` tinyint default NULL,
      `scale` bit(1) default NULL,
      `likeCode` int(6) default NULL,
      `modified` date default NULL,
      `user` int(8) default NULL,
      `forceQty` bit(1) default NULL,
      `noDisc` bit(1) default NULL,
      `inUse` bit(1) default NULL,
      PRIMARY KEY (prodUpdateID),
      INDEX(upc)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.prodUpdate'] = "
        CREATE TABLE prodUpdate (
            prodUpdateID bigint IDENTITY (1, 1) NOT NULL ,
            updateType varchar(20),
            upc varchar(13),
            description varchar(50),
            price money,
            salePrice money,
            cost money,
            dept int,
            tax smallint,
            fs smallint,
            scale smallint,
            likeCode int,
            modified datetime,
            [user] int,
            forceQty smallint,
            noDisc smallint,
            inUse smallint
        )
    ";
}
