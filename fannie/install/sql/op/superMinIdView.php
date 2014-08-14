<?php
/*
View: superMinIdView

Columns:
    superID int
    dept_ID

Depends on:
    superdepts

Use:
This view exists soley because MySQL won't let
me use a subquery in the FROM clause of a VIEW.
*/
$CREATE['op.superMinIdView'] = "
    CREATE VIEW superMinIdView AS
    SELECT MIN(superID) as superID,dept_ID
    FROM superdepts
    GROUP BY dept_ID
";
?>
