<?php

use COREPOS\Fannie\API\item\ItemText;

class RaPriceFeed
{
    private $dbc;
    private $name = 'prices.txt';

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
                CASE WHEN p.discounttype=1 AND t.datedSigns=1 THEN p.special_price ELSE p.normal_price END as price,
                1 AS multiple,
                CASE WHEN t.datedSigns<>1 THEN 0 ELSE b.startDate END as startDate,
                CASE WHEN t.datedSigns<>1 THEN 0 ELSE b.endDate END as endDate,
                p.mixmatchcode,
                0 AS min,
                99 AS max
            FROM products AS p
                LEFT JOIN batches AS b ON p.batchID=b.batchID
                LEFT JOIN batchType AS t ON b.batchType=t.batchTypeID
            WHERE p.store_id=?
                AND p.pricemethod=0
                AND p.specialpricemethod=0";
        $prep = $this->dbc->prepare($query);
        $res = $this->dbc->execute($prep, array($storeID));
        fwrite($fptr, 'UPC|Price|Multiple|Effective Date|End Date|Mix-Match Code|Minimum Quantity|Limit Quantity' . $term);
        var_dump($this->dbc->numRows($res));
        while ($row = $this->dbc->fetchRow($res)) {
            $upc = ltrim($row['upc'], '0');
            if (strlen($upc) > 12) continue;
            $upc = str_pad($upc, 12, '0', STR_PAD_LEFT);

            $file = $upc . '|';
            $file .= $row['price'] . '|';
            $file .= $row['multiple'] . '|';
            if ($row['startDate']) {
                list($row['startDate'], ) = explode(' ', $row['startDate'], 2);
            }
            $file .= $row['startDate'] . '|';
            if ($row['endDate']) {
                list($row['endDate'], ) = explode(' ', $row['endDate'], 2);
            }
            $file .= $row['endDate'] . '|';
            $file .= (!empty($row['mixmatchcode']) ? $row['mixmatchcode'] : '') . '|';
            $file .= $row['min'] . '|';
            $file .= $row['max'];
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
    $rai = new RaPriceFeed($dbc);
    echo $rai->get(1, './');
}

