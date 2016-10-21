<?php
/*
Table: weeksLastQuarter

Columns:
    weekLastQuarterID int
    weekStart datetime
    weekEnd datetime

Depends on:
    none

Use:
Keep track of weeks in the last quarter.
This imposes several conventions:
* Weeks start on Monday and end on Sunday, ISO-style
* The current week is ID zero. The previous week is
  ID one. The week before that is ID two, etc.
* The Last Quarter is week IDs one through thirteen

Week #0 is provided for completeness in information.
The other thirteen weeks are used for the last quarter
so any comparisions are between full, 7-day weeks.
*/
$CREATE['arch.weeksLastQuarter'] = "
    CREATE TABLE weeksLastQuarter (
    weekLastQuarterID INT NOT NULL AUTO_INCREMENT,
    weekStart DATETIME,
    weekEnd DATETIME,
    PRIMARY KEY (weekLastQuarterID)
    )
";

