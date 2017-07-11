<?php

class InstaFileV2
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
        $csv = fopen($filename, 'w');
        $query = '
            SELECT p.upc,
                p.description,
                p.brand,
                u.description AS goodDescription,
                u.brand AS goodBrand,
                p.size,
                p.unitofmeasure,
                v.size AS vendorSize,
                p.scale, 
                d.normal_price AS deposit,
                m.super_name,
                p.tax,
                p.idEnforced,
                p.normal_price,
                p.special_price,
                p.discounttype,
                p.start_date,
                p.end_date,
                p.tax,
                p.inUse
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN products AS d ON p.deposit=d.upc AND p.store_id=d.store_id
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE m.superID <> 0';
        $args = array();
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=?';
            $args[] = $this->config->get('STORE_ID');
        }
        $prep = $this->dbc->prepare($prep);
        $result = $this->dbc->execute($prep, $args);

        // reatailer_code is misspelled in the spec
        fwrite($csv, 'lookup_code,reatailer_code,item_name,size,cost_price_per_unit,price_unit,bottle_deposit,department,taxable,available,alcoholic,brand_name,sale_price,sale_start_at,sale_end_at' . "\r\n");
        while ($row = $this->dbc->fetchRow($result)) {
            // UPC or PLU with added check digits
            $plu = ltrim($row['upc'], '0'); 
            if (strlen($plu) == 13) {
                fwrite($csv, $plu . ',,'); // EAN-13 with check
            } elseif (strlen($plu) == 12) {
                // probably EAN-13 w/o check
                fwrite($csv, BarcodeLib::EAN13CheckDigit($plu) . ',,');
            } elseif (strlen($plu) > 7) {
                // probably UPC-A w/o check 
                $plu = str_pad($plu, 11, '0', STR_PAD_LEFT);
                fwrite($csv, BarcodeLib::UPCACheckDigit($plu) . ',');
            } else {
                fwrite($csv, ',' . $plu . ','); 
            }

            // item_name
            fwrite($csv, '"' . (!empty($row['goodDescription']) ? $row['goodDescription'] : $row['description']) . '",');

            // size
            $size = $row['scale'] == 1 ? 'per lb' : 'each';
            if (!empty($row['size'])) {
                $size = $row['size'];
                if (is_numeric($size) && !empty($row['unitofmeasure'])) {
                    $size .= $row['unitofmeasure'];
                }
            } elseif (!empty($row['vendorSize'])) {
                $size = $row['vendorSize'];
            }
            fwrite($csv, '"' . $size . '",');

            // cost_price_per_unit
            fwrite($csv, $row['normal_price'] . ',');

            // price_unit
            fwrite($csv, ($row['scale'] ? 'lb' : 'each') . ',');

            // bottle_deposit
            fwrite($csv, ($row['deposit'] ? $row['deposit'] : 0.00) . ',');

            // department
            fwrite($csv, '"' . $row['super_name'] . '",');

            // taxable
            fwrite($csv, ($row['scale'] ? 'true' : 'false') . ',');

            // available
            fwrite($csv, ($row['inUse'] ? 'true' : 'false') . ',');

            // alcoholic
            fwrite($csv, ($row['idEnforced'] == 21 ? 'true' : 'false') . ',');

            // brand_name
            fwrite($csv, '"' . (!empty($row['goodBrand']) ? $row['goodBrand'] : $row['brand']) . '",');

            if ($row['discounttype'] == 1 && $row['special_price'] != 0) {
                fwrite($csv, $row['special_price'] . ',');
                fwrite($csv, date('m/d/Y', strtotime($row['start_date'])) . ',');
                fwrite($csv, date('m/d/Y', strtotime($row['end_date'])) . "\r\n");
            } else {
                fwrite($csv, ",,\r\n");
            }
        }
        fclose($csv);

        return true;
    }
}

