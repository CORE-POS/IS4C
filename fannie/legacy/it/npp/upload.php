<?php
if (isset($_POST['MAX_FILE_SIZE'])){
    move_uploaded_file($_FILES['upload']['tmp_name'],"csv/unfi.zip");
    $output = system("unzip csv/unfi.zip -d csv/ &> /dev/null");
    unlink("csv/unfi.zip");
    header("Location: readall.php");
}
else {
?>

<html>
<head>
<title>Upload UNFI Invoice</title>
</head>
<body onload="document.getElementById('file').value = '/tmp/unfi.zip';">
<form enctype="multipart/form-data" action="upload.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
</body>
</html>
<?php
}

