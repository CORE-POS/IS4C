<?php

use COREPOS\Fannie\API\item\ItemText;

class InstaFileV3
{
    private $dbc;
    private $config;

    public function __construct($dbc, $config)
    {
        $this->dbc = $dbc;
        $this->config = $config;
    }

    public function getFile($filename)
    {
        $depositP = $this->dbc->prepare('SELECT normal_price FROM products WHERE upc=?');
        $query = "
            SELECT p.upc,
                p.normal_price,
                p.scale,
                " . ItemText::longDescriptionSQL() . ",
                " . ItemText::signSizeSQL() . ",
                p.unitofmeasure,
                " . ItemText::longBrandSQL() . ",
                m.super_name,
                p.inUse,
                p.last_sold,
                p.idEnforced,
                t.rate,
                p.deposit,
                p.special_price,
                p.start_date,
                p.end_date,
                p.discounttype,
                p.specialpricemethod,
                y.datedSigns,
                CASE WHEN (numflag & (1<<16)) <> 0 THEN 1 ELSE 0 END AS organic,
                CASE WHEN (numflag & (1<<17)) <> 0 THEN 1 ELSE 0 END AS glutenfree
            FROM products AS p
                LEFT JOIN productUser AS u on p.upc=u.upc
                LEFT JOIN taxrates AS t ON p.tax=t.id
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN batches AS b ON p.batchID=b.batchID
                LEFT JOIN batchType AS y on b.batchType=y.batchTypeID
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
            WHERE m.superID <> 0";
        $args = array();
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $args[] = $this->config->get('STORE_ID');
            $query .= ' AND p.store_id=?';
        }
        $prep = $this->dbc->prepare($query);
        $res = $this->dbc->execute($prep, $args);
        $csv = fopen($filename, 'w');
        fwrite($csv, "upc,price,cost_unit,item_name,size,brand_name,unit_count,department,available,last_sold,alcoholic,retailer_reference_code,organic,gluten_free,tax_rate,bottle_deposit,sale_price,sale_start_at,sale_end_at\r\n");
        while ($row = $this->dbc->fetchRow($res)) {
            if ($row['normal_price'] <= 0) {
                continue;
            }

            $upc = ltrim($row['upc'], '0');
            if (strlen($upc) == 13) {
                // should be EAN-13 w/ check
                fwrite($csv, $upc . ',');
            } elseif (substr($upc, 0, 1) == '2' && substr($upc, -6) == '000000') {
                // service scale item. treat it like a PLU
                fwrite($csv, $upc . ',');
            } elseif (strlen($upc) == 12) {
                // probably EAN-13 w/o check
                fwrite($csv, BarcodeLib::EAN13CheckDigit($upc) . ',');
            } elseif (strlen($upc) > 7) {
                // probably UPC-A w/o check 
                fwrite($csv, BarcodeLib::UPCACheckDigit($upc) . ',');
            } else {
                // probably a PLU
                fwrite($csv, $upc . ','); 
            }
            fprintf($csv, '%.2f,', $row['normal_price']);
            fwrite($csv, ($row['scale'] ? 'lb' : 'each') . ',');

            $desc = str_replace('"', '', $row['description']);
            $desc = substr($desc, 0, 100);
            fwrite($csv, '"' . $desc . '",');

            $size = $row['size'] ? $row['size'] : 1;
            if ($row['scale']) {
                $size = 'per lb';
            }
            $units = 1;
            if (strstr($size, '/')) {
                list($units, $size) = explode('/', $size, 2);
            } elseif (strstr($size, '-')) {
                list($units, $size) = explode('-', $size, 2);
            }
            if (is_numeric($size)) {
                $size .= $row['unitofmeasure'];
            }
            if (is_numeric(trim($size))) {
                $size = 'each';
            }
            fwrite($csv, $size . ',');

            $brand = str_replace('"', '', $row['brand']);
            fwrite($csv, '"' . $brand . '",');

            fwrite($csv, $units . ',');

            $dept = str_replace('"', '', $row['super_name']);
            fwrite($csv, '"' . $dept . '",');

            fwrite($csv, ($row['inUse'] ? 'TRUE' : 'FALSE') . ',');

            fwrite($csv, date('m/d/Y', strtotime($row['last_sold'])) . ',');

            fwrite($csv, ($row['idEnforced'] == 21 ? 'TRUE' : 'FALSE') . ',');

            fwrite($csv, $row['upc'] . ',');
            fwrite($csv, ($row['organic'] ? 'TRUE' : 'FALSE') . ',');
            fwrite($csv, ($row['glutenfree'] ? 'TRUE' : 'FALSE') . ',');

            fprintf($csv, '%.5f,', $row['rate']);

            if ($row['deposit'] > 0) {
                $row['deposit'] = $this->dbc->getValue($depositP, array(BarcodeLib::padUPC($row['deposit'])));
            }
            fprintf($csv, '%.2f,', $row['deposit']);

            if ($row['special_price'] == 0 || $row['special_price'] >= $row['normal_price'] || !$row['datedSigns'] || $row['specialpricemethod'] != 0 || $row['discounttype'] != 1) {
                fwrite($csv, ",,\r\n");
            } else {
                fprintf($csv, '%.2f,', $row['special_price']);
                fwrite($csv, date('m/d/Y', strtotime($row['start_date'])) . ',');
                fwrite($csv, date('m/d/Y', strtotime($row['end_date'])) . "\r\n");
            }
        }
        fclose($csv);

        return true;
    }
}

