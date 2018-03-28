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
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $instaDB = $settings['InstaCartDB'];
        $includeP = $this->dbc->prepare('SELECT upc FROM ' . $instaDB . $this->dbc->sep() . 'InstaIncludes WHERE upc=?');
        $excludeP = $this->dbc->prepare('SELECT upc FROM ' . $instaDB . $this->dbc->sep() . 'InstaExcludes WHERE upc=?');
        $instaMode = $settings['InstaCartMode'];
        $sep = ',';
        $newline = "\r\n";

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
            WHERE m.superID <> 0
                AND p.inUse=1";
        $args = array();
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $args[] = $this->config->get('STORE_ID');
            $query .= ' AND p.store_id=?';
        }
        $prep = $this->dbc->prepare($query);
        $res = $this->dbc->execute($prep, $args);
        $csv = fopen($filename, 'w');
        fwrite($csv, "lookup_code,price,cost_unit,item_name,size,brand_name,unit_count,department,available,alcoholic,retailer_reference_code,organic,gluten_free,tax_rate,bottle_deposit,sale_price,sale_start_at,sale_end_at\r\n");
        while ($row = $this->dbc->fetchRow($res)) {
            if ($row['normal_price'] <= 0.01 || $row['normal_price'] >= 500) {
                continue;
            }

            if ($instaMode == 1) {
                $included = $this->dbc->getValue($includeP, array($row['upc']));
                if ($included === false) {
                    continue;
                }
            } else {
                $excluded = $this->dbc->getValue($excludeP, array($row['upc']));
                if ($excluded == $row['upc']) {
                    continue;
                }
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
                fwrite($csv, $upc . $sep); 
            }
            fprintf($csv, '%.2f%s', $row['normal_price'], $sep);
            fwrite($csv, ($row['scale'] ? 'lb' : 'each') . $sep);

            $desc = str_replace('"', '', $row['description']);
            $desc = str_replace("\r", '', $desc);
            $desc = str_replace("\n", ' ', $desc);
            $desc = substr($desc, 0, 100);
            fwrite($csv, '"' . $desc . '"' . $sep);

            $size = $row['size'] ? $row['size'] : 1;
            if ($row['scale']) {
                $size = 'per lb';
            } elseif (trim($size) == '#') {
                $size = 'per lb';
            }
            $units = 1;
            if (strstr($size, '/') && !$row['scale']) {
                list($units, $size) = explode('/', $size, 2);
            } elseif (strstr($size, '-')) {
                list($units, $size) = explode('-', $size, 2);
            }
            if (!is_numeric($units)) {
                $units = 1;
            }
            if (is_numeric($size)) {
                $size .= $row['unitofmeasure'];
            }
            $size = str_replace('#', 'lb', $size);
            if (is_numeric(trim($size))) {
                $size = 'each';
            }
            if ($size != 'each' && $size != 'per lb' && !preg_match('/\d+/', $size)) {
                $size = 'each';
            }
            fwrite($csv, $size . $sep);

            $brand = str_replace('"', '', $row['brand']);
            $brand = substr($brand, 0, 100);
            fwrite($csv, '"' . $brand . '"' . $sep);

            fwrite($csv, $units . $sep);

            $dept = str_replace('"', '', $row['super_name']);
            fwrite($csv, '"' . $dept . '"' . $sep);

            fwrite($csv, ($row['inUse'] ? 'TRUE' : 'FALSE') . $sep);

            fwrite($csv, ($row['idEnforced'] == 21 ? 'TRUE' : 'FALSE') . $sep);

            fwrite($csv, $row['upc'] . $sep);
            fwrite($csv, ($row['organic'] ? 'TRUE' : 'FALSE') . $sep);
            fwrite($csv, ($row['glutenfree'] ? 'TRUE' : 'FALSE') . $sep);

            fprintf($csv, '%.5f%s', $row['rate'], $sep);

            if ($row['deposit'] > 0) {
                $row['deposit'] = $this->dbc->getValue($depositP, array(BarcodeLib::padUPC($row['deposit'])));
            }
            fprintf($csv, '%.2f%s', $row['deposit'], $sep);

            if ($row['special_price'] == 0 || $row['special_price'] >= $row['normal_price'] || !$row['datedSigns'] || $row['specialpricemethod'] != 0 || $row['discounttype'] != 1) {
                fwrite($csv, $sep . $sep . $newline);
            } else {
                fprintf($csv, '%.2f%s', $row['special_price'], $sep);
                fwrite($csv, date('m/d/Y', strtotime($row['start_date'])) . $sep);
                $ts = strtotime($row['end_date']);
                $next = mktime(0,0,0, date('n',$ts), date('j',$ts)+1, date('Y', $ts));
                fwrite($csv, date('m/d/Y', $next) . $newline);
            }
        }
        fclose($csv);

        return true;
    }
}

