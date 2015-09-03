<?php
/*
Table: usageStats

Columns:
    usageID int
    tdate datetime
    pageName varchar
    referrer varchar
    userHash varchar
    ipHash varchar

Depends on:
    none

Use:
Internal usage metrics. Tracks visits
to different Fannie pages.
*/
$CREATE['op.usageStats'] = "
    create table usageStats (
        usageID INT NOT NULL AUTO_INCREMENT,
        tdate DATETIME,
        pageName VARCHAR(100),
        referrer VARCHAR(100),
        userHash VARCHAR(40),
        ipHash VARCHAR(40),
        PRIMARY KEY (usageID),
        INDEX(tdate),
        INDEX(pageName)
    )
";

