<?php

include(__DIR__ . '/../../../../config.php');
include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');

$dbc = FannieDB::get('is4c_op');

$STORE = 2;
$SECTION = 43;

$addP = $dbc->prepare("INSERT INTO FloorSectionProductMap (floorSectionID, upc) VALUES (?, ?)");
$mappingsP = $dbc->prepare("SELECT m.floorSectionID 
    FROM FloorSectionProductMap AS m
        INNER JOIN FloorSections AS f ON m.floorSectionID=f.floorSectionID
    WHERE m.upc=? AND f.storeID=?");
$halt = false;
$fp = fopen('subsections.csv', 'r');
while (!feof($fp)) {
    $data = fgetcsv($fp);
    if (!is_numeric($data[0])) continue;

    $upc = BarcodeLib::padUPC($data[0]);
    $maps = $dbc->getAllRows($mappingsP, array($upc, $STORE));
    if ($maps == false || count($maps) == 0) {
        $dbc->execute($addP, array($SECTION, $upc));
    } elseif (count($maps) == 1 && $maps[0]['floorSectionID'] != $SECTION) {
        echo "$upc has different location {$maps[0]['floorSectionID']}\n";
        $halt = true;
    } elseif (count($maps) > 1) {
        $found = false;
        foreach ($maps as $m) {
            if ($m['floorSectionID'] == $SECTION) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $halt = true;
            echo "$upc has different locations\n";
        }
    }
}
fclose($fp);

if ($halt) {
    echo "Bailing; fix locations first\n";
    exit;
}

$delP = $dbc->prepare("DELETE FROM FloorSubSections WHERE floorSectionID=?");
$dbc->execute($delP, array($SECTION));
$addP = $dbc->prepare("INSERT INTO FloorSubSections (upc, floorSectionID, subSection)
    VALUES (?, ?, ?)");
$fp = fopen('subsections.csv', 'r');
while (!feof($fp)) {
    $data = fgetcsv($fp);
    if (!is_numeric($data[0])) {
        echo "skipping {$data[0]}\n";
        continue;
    }

    $upc = BarcodeLib::padUPC($data[0]);
    $sub = strtolower(trim($data[2]));
    echo $upc . "\n";
    $dbc->execute($addP, array($upc, $SECTION, $sub));
}
fclose($fp);

