<?php
/* configuration for your module - Important */
include("_ini_.php");
/* html header, including navbar */
include(FANNIE_ABS_PATH."/display/html/header.php");
?>
<style type=text/css>
img {
	border: solid 1px black;
}
</style>
Step 1: Obtain a UNFI price file. The zip file I got had three files in it, only
one has pricing info and that's the one we need. Open it up in Excel
and save it as filename <i>unfi.csv</i>, format <i>CSV (Windows)</i>.<br />
<img src=images/saveas.png />
<br />
<hr />
<br />
Step 2: That file is probably too big. Right click on it and select
<i>Create archive</i> to make a zip file.<br />
<img src=images/archive.png />
<br />
<hr />
<br />
Step 3: Go to the <a href=uploadPriceSheet.php>upload page</a>, click Browse, and select
the zip file you just made (if done as above, it should be named 
<i>unfi.csv.zip</i>). Click Upload File and wait. It can take a while
for a big price file.<br />
<br />
<hr />
<br />
Step 4: If everything goes correctly, you'll get output something like this
(it doesn't matter if there are more or less UNFISPLIT files). If you get
anything drastically different, tell Andy.<br />
<img src=images/results.png />
<br />
<hr />
<br />
Step 5 (optional): track down a dedicated professional to help<br />
<img src=images/techsupport.jpg />

<?php

/* html footer */
include(FANNIE_ABS_PATH."/display/html/footer.php");

?>
