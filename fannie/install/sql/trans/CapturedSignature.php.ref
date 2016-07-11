<?php
/*
Table: CapturedSignature

Columns:
    capturedSignatureID int
    tdate datetime
    emp_no int
    register_no int
    trans_no int
    trans_id int
    filetype varchar
    filecontents binary data

Depends on:
    none

Use:
This table contains digital images of customer signatures.
The standard dtransactions columns indicate what transaction
line the signature goes with. Filetype is a three letter extension
indicating what kind of image it is, and filecontents is the
raw image data. This data is in the database because it's the
only existing pathway to transfer information from the lane
to the server.
*/
$CREATE['trans.CapturedSignature'] = "
    CREATE TABLE CapturedSignature (
        capturedSignatureID INT NOT NULL AUTO_INCREMENT,
        tdate datetime,
        emp_no int,
        register_no int,
        trans_no int,
        trans_id int,
        filetype char(3),
        filecontents blob,
        PRIMARY KEY (capturedSignatureID),
        INDEX(tdate),
        INDEX(register_no)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['trans.CapturedSignature'] = str_replace("blob","image",$CREATE['trans.CapturedSignature']);
    $CREATE['trans.CapturedSignature'] = str_replace("AUTO_INCREMENT","IDENTITY(1, 1)",$CREATE['trans.CapturedSignature']);
}

