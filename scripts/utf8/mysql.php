<?php

/******
 * 
 * Simple test of handling utf8
 *
 * 1. Set connection character set to utf8 & verify
 * 2. Create table with character set utf8 & verify
 * 3. Insert utf8 byte sequence for ©. Read it back and verify.
 * 4. Insert iso-8559-1 (i.e. latin1) byte sequence for ©.
 *    Read it back and verify. This should fail if everything
 *    else worked.
 * 5. Drop the testing table
 *
 */

$config = __DIR__ . '/../../fannie/config.php';
if (!file_exists($config)) {
    echo "Fannie config.php not found!\n";
    exit;
}

include($config);
include(__DIR__ . '/../../fannie/classlib2.0/FannieAPI.php');

$dbc = FannieDB::get($FANNIE_OP_DB);

echo "Setting charset to utf-8\n";
$res = $dbc->setCharSet('utf-8');
echo ($res) ? "[OK]\n" : "[FAIL]\n";

echo "Reading back settings\n";
$res = $dbc->query("SHOW VARIABLES LIKE 'character_set_%'");
while ($row = $dbc->fetchRow($res)) {
    if (in_array($row[0], array('character_set_client', 'character_set_connection', 'character_set_results'))) {
        echo strstr($row[1], 'utf') ? "[OK]\t" : "[FAIL]\t";
        echo $row[0] . "\t" . $row[1] . "\n";
    }
}

echo "Creating table 'TestBytesCopyright'\n";
if ($dbc->tableExists('TestBytesCopyright')) {
    echo "[FAIL] Table already exists!\n";
}

$res = $dbc->query("CREATE TABLE TestBytesCopyright (
    id INT,
    string CHAR(2),
    PRIMARY KEY (id)
    )
    CHARACTER SET utf8
    COLLATE utf8_general_ci");
echo ($res) ? "[OK]\n" : "[FAIL]\n";

$def = $dbc->query("SHOW CREATE TABLE TestBytesCopyright");
$def = $dbc->fetchRow($def);
echo "Verifying table is utf-8\n";
echo (strstr($def[1], 'utf8')) ? "[OK]\n" : "[FAIL]\n";

$utf8 = pack("CC", 0xc2, 0xa9);
echo "Writing copyright symbol in utf8 encoding\n";
$prep = $dbc->prepare("INSERT INTO TestBytesCopyright (id, string) VALUES (1, ?)");
$res = $dbc->execute($prep, array($utf8));
echo ($res) ? "[OK]\n" : "[FAIL]\n";
echo "Reading back value\n";
$res = $dbc->query("SELECT string FROM TestBytesCopyright WHERE id=1");
echo ($res) ? "[OK]\n" : "[FAIL]\n";
echo "Verifying correct value\n";
$row = $dbc->fetchRow($res);
echo "\tLength is " . strlen($row[0]) . "\n";
for ($i=0; $i<strlen($row[0]); $i++) {
    echo "\tByte {$i} is " . ord($row[0][$i]) . "\n";
}
echo (strlen($row[0]) == 2 && ord($row[0][0]) == 0xc2 && ord($row[0][1]) == 0xa9) ? "[OK]\n" : "[FAIL]\n";

$iso85591 = pack("C", 0xa9);
echo "Writing copyright symbol in iso-8559-1 encoding\n";
$prep = $dbc->prepare("INSERT INTO TestBytesCopyright (id, string) VALUES (2, ?)");
$res = $dbc->execute($prep, array($iso85591));
echo ($res) ? "[OK]\n" : "[FAIL]\n";
echo "Reading back value\n";
$res = $dbc->query("SELECT string FROM TestBytesCopyright WHERE id=2");
echo ($res) ? "[OK]\n" : "[FAIL]\n";
echo "Verifying correct value (this should fail)\n";
$row = $dbc->fetchRow($res);
echo "\tLength is " . strlen($row[0]) . "\n";
for ($i=0; $i<strlen($row[0]); $i++) {
    echo "\tByte {$i} is " . ord($row[0][$i]) . "\n";
}
echo (strlen($row[0]) == 1 && ord($row[0][0]) == 0xa9) ? "[OK]\n" : "[FAIL]\n";

echo "Removing table 'TestBytesCopyright'\n";
$res = $dbc->query("DROP TABLE TestBytesCopyright");
echo ($res) ? "[OK]\n" : "[FAIL]\n";

