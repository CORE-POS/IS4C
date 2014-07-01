<?php

include('../../../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

$header = "External Email List";
$page_title = "Fannie :: Email List";
include($FANNIE_ROOT.'src/header.html');

require($FANNIE_ROOT.'src/Credentials/OutsideDB.wfc.php');
$q = $dbc->prepare_statement("SELECT email FROM userData WHERE email LIKE '%@%.%'");
$r = $dbc->exec_statement($q);

echo '<input type="submit" value="Select All"
    onclick="$(\'#emailListing\').focus();$(\'#emailListing\').select();"
    /><br />';
echo '<textarea id="emailListing" style="width:100%;height:400px;background:#cccccc;border=solid 1px black;padding:10px;">';
while($w = $dbc->fetch_row($r)){
    echo $w[0]."\n";
}   
echo '</textarea>';

include($FANNIE_ROOT.'src/footer.html');

?>
