<?php
/*
View: MasterSuperDepts

Columns:
    superID int
    super_name var_char
    dept_ID int

Depends on:
    SuperMinIdView (view)
    superDeptNames (table)

Use:
A department may belong to more than one superdepartment, but
has one "master" superdepartment. This avoids duplicating
rows in some reports. By convention, a department's
"master" superdepartment is the one with the lowest superID.
*/
$CREATE['op.MasterSuperDepts'] = "
    CREATE VIEW MasterSuperDepts AS
    SELECT n.superID,n.super_name,s.dept_ID
    FROM superMinIdView AS s
    LEFT JOIN superDeptNames AS n
    on s.superID=n.superID
";
?>
