<?php # messages.php - This will let your average user modify the greeting, farewell, and receipt footers for all lanes.
$page_title = 'Fannie - Admin Module';
$header = 'Message Manager';
include ('../src/header.html');

if (isset($_POST['submitted'])) {
    // new line for fwrite "\n"
    require_once('../src/mysql_connect.php');
    foreach ($_POST['id'] AS $id => $msg) {
        $query = "UPDATE messages SET message = '" . escape_data($msg) . "' WHERE id='$id'";
        $result = mysql_query($query);
    }
    
}

require_once('../src/mysql_connect.php');

echo '<form action="messages.php" method="POST">';

$query = "SELECT * FROM messages ORDER BY id ASC";
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
    if ($row['id'] == 'receiptFooter1') {echo "<p><b>Receipt Footer:</b></p>\n";}
    elseif ($row['id'] == 'farewellMsg1') {echo "<p><b>Farewell Message:</b></p>\n";}
    elseif ($row['id'] == 'welcomeMsg1') {echo "<p><b>Welcome Message:</b></p>\n";}
    echo "<input type=\"text\" name=\"id[{$row['id']}]\" value=\"{$row['message']}\" size=\"50\" maxlength=\"50\" /><br />\n";
}

echo '<br /><button name="submit" type="submit">Save</button>
<input type="hidden" name="submitted" value="TRUE" />
</form>';

include ('../src/footer.html');
?>