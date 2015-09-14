<?php
/*
Table: SpecialOrders

Columns:
    specialOrderID int (auto increment)
    statusFlag int
    subStatus int
    notes text
    noteSuperID int
    firstName varchar
    lastName varchar
    street varchar
    city varchar
    state varchar
    zip varchar
    phone varchar
    altPhone varchar
    email varchar

This table just exists as an accumulator
so that IDs in PendingSpecialOrder and
CompletedSpecialOrder never conflict

*/

$CREATE['trans.SpecialOrders'] = "
    CREATE TABLE `SpecialOrders` (
          `specialOrderID` int(11) NOT NULL auto_increment,
          `statusFlag` int,
          `subStatus` bigint,
          `notes` text,
          `noteSuperID` int,
          `firstName` varchar(30),
          `lastName` varchar(30),
          `street` varchar(255),
          `city` varchar(20),
          `state` varchar(2),
          `zip` varchar(10),
          `phone` varchar(30),
          `altPhone` varchar(30),
          `email` varchar(50),
          PRIMARY KEY  (`specialOrderID`)
    )
";
if ($dbms == "MSSQL") {
    $CREATE['trans.SpecialOrders'] = "
        CREATE TABLE [SpecialOrders] (
            [specialOrderID] [int] IDENTITY (1, 1) NOT NULL ,
            [statusFlag] [int] ,
            [subStatus] [bigint] ,
            [notes] [text] ,
            [noteSuperID] [int] ,
            [firstName] [varchar(30)] ,
            [lastName] [varchar(30)] ,
            [street] [varchar(255)] ,
            [city] [varchar(20)] ,
            [state] [varchar(2)] ,
            [zip] [varchar(10)] ,
            [phone] [varchar(30)] ,
            [altPhone] [varchar(30)] ,
            [email] [varchar(50)] ,
            PRIMARY KEY ([specialOrderID]) )
    ";
}

