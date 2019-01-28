<?php

use COREPOS\Fannie\API\item\ItemText;

class RaItemFeed
{
    private $dbc;
    private $name = 'items.txt';

    public function __construct($dbc)
    {
        $this->dbc = $dbc;
    }

    public function get($storeID, $outputDir)
    {
        $filename = date('YmdHis') . '_' . $this->name;
        $fptr = fopen($outputDir . DIRECTORY_SEPARATOR . $filename, 'w');
        $term = "\r\n";
        $depositP = $this->dbc->prepare('SELECT normal_price FROM products WHERE upc=?');
        $query = "
            SELECT p.upc,
                m.superID,
                m.super_name,
                p.description AS shortDescription,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . ",
                t.rate,
                p.size,
                p.deposit,
                f.sections,
                p.idEnforced,
                p.mixmatchcode,
                p.inUse
            FROM products AS p
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN taxrates AS t ON t.id=p.tax
                LEFT JOIN FloorSectionsListView AS f ON p.upc=f.upc AND p.store_id=f.storeID
            WHERE p.store_id=?";
        $prep = $this->dbc->prepare($query);
        $res = $this->dbc->execute($prep, array($storeID));
        fwrite($fptr, 'UPC/EAN|Department ID|Department Name|Short Description|Extended Description|Tax Rate|Bottle Deposit|Aisle Location|Alcohol Flag|Tobacco Flag|Size|Unit of Measure|Temperature|Mix-Match Code|Active Item Flag' . $term);
        while ($row = $this->dbc->fetchRow($res)) {
            $upc = ltrim($row['upc'], '0');
            if (strlen($upc) > 12) continue;
            $upc = str_pad($upc, 12, '0', STR_PAD_LEFT);

            $file = $upc . '|';
            $file .= $row['superID'] . '|';
            $file .= $row['super_name'] . '|';
            $file .= $row['shortDescription'] . '|';
            $file .= !empty($row['brand']) ? $row['brand'] . ' ' : '';
            $row['description'] = str_replace("\r", " ", $row['description']);
            $file .= str_replace("\n", " ", $row['description']) . '|';
            $file .= $row['rate'];
            if ($row['deposit']) {
                $actual = $this->dbc->getValue($depositP, array(BarcodeLib::padUPC($row['deposit'])));
                $file .= $actual;
            }
            $file .= '|';
            $locations = explode(',', $row['sections']);
            $file .= (isset($locations[0]) ? $locations[0] : '') . '|';
            $file .= ($row['idEnforced'] == 21 ? 1 : 0) . '|';
            $file .= ($row['idEnforced'] == 18 ? 1 : 0) . '|';
            $file .= $row['size'] . '|';
            $file .= '|';
            $file .= '|';
            $file .= (!empty($row['mixmatchcode']) ? $row['mixmatchcode'] : '') . '|';
            $file .= $row['inUse'];
            $file .= $term;
            fwrite($fptr, $file);
        }
        fclose($fptr);

        return $filename;
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    include(__DIR__ . '/../../../config.php');
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
    $dbc = FannieDB::get('is4c_op');
    $rai = new RaItemFeed($dbc);
    echo $rai->get(1, './');
}

