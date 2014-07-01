<?php
/*
Table: meminfo

Columns:
    card_no int
    last_name varchar
    first_name varchar
    othlast_name varchar
    othfirst_name varchar
    street varchar
    city varchar
    state varchar
    zip varchar 10
    phone varchar
    email_1 varchar
    email_2 varchar
    ads_OK tinyint

Depends on:
    custdata (table)

Use:
This table has contact information for a member,
i.e. it extends custdata on card_no.
See also: memContact.

Usage doesn't have to match mine (AT). The member section of
fannie should be modular enough to support alternate
usage of some fields.

card_no key to custdata and other customer tables.

Straightforward:
- street varchar 255
- city
- state
- zip
- phone
  long enough to include extension but don't put more than
  one number in it.

The name fields are for two different people.
This approach will work if your co-op allows only
1 or 2 people per membership, but custdata can hold
the same information in a more future-proof way,
i.e. support any number of people per membership,
so better to not use them in favour of custdata.

- email_1 for email
- email_2 for second phone

- ads_OK EL: Perhaps: flag for whether OK to send ads.
  Don't know whether implemented for this or any purpose.

--COMMENTS - - - - - - - - - - - - - - - - - - - -

26Jun12 EL Reformatted and rephrased Use section
           Added zip to columns list
           Added note about ads_OK
epoch   AT Original notes by Andy Theuninck.

*/
$CREATE['op.meminfo'] = "
    CREATE TABLE `meminfo` (
      `card_no` int(11) default NULL,
      `last_name` varchar(30) default NULL,
      `first_name` varchar(30) default NULL,
      `othlast_name` varchar(30) default NULL,
      `othfirst_name` varchar(30) default NULL,
      `street` varchar(255) default NULL,
      `city` varchar(20) default NULL,
      `state` varchar(2) default NULL,
      `zip` varchar(10) default NULL,
      `phone` varchar(30) default NULL,
      `email_1` varchar(50) default NULL,
      `email_2` varchar(50) default NULL,
      `ads_OK` tinyint(1) default '1',
      PRIMARY KEY (`card_no`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.meminfo'] = "
        CREATE TABLE meminfo (
          card_no int ,
          last_name varchar(30) ,
          first_name varchar(30) ,
          othlast_name varchar(30) ,
          othfirst_name varchar(30) ,
          street varchar(255) ,
          city varchar(20) ,
          state varchar(2) ,
          zip varchar(10) ,
          phone varchar(30) ,
          email_1 varchar(50) ,
          email_2 varchar(50) ,
          ads_OK tinyint 
        )
    ";
}
?>
