<?php
/*
View: originName

Columns:
    originID int
    fullName varchar
    shortName varchar
    local int

Depends on:
    origins
    originCountry
    originStateProv
    originCustomRegion

Use:
Joins origin tables to create 
consistent names
*/
$CREATE['op.originName'] = "
    CREATE VIEW originName AS
    SELECT o.originID,
    ".$con->concat(
        "CASE WHEN r.name IS NULL THEN '' ELSE ".$con->concat('r.name',"', '",'')." END",
        "CASE WHEN s.name IS NULL THEN '' ELSE ".$con->concat('s.name',"', '",'')." END",
        "CASE WHEN c.name IS NULL THEN '' ELSE c.name END",''
    )." as fullName,
    CASE
        WHEN r.customID IS NOT NULL THEN r.name
        WHEN s.stateProvID IS NOT NULL THEN s.name
        WHEN c.countryID IS NOT NULL THEN c.name
        ELSE ''
    END AS shortName,
    o.local
    FROM origins AS o
    LEFT JOIN originCountry AS c ON o.countryID=c.countryID
    LEFT JOIN originStateProv AS s ON o.stateProvID=s.stateProvID
    LEFT JOIN originCustomRegion AS r ON o.customID=r.customID
    WHERE
    r.customID IS NOT NULL OR
    s.stateProvID IS NOT NULL OR
    c.countryID IS NOT NULL
";

?>
