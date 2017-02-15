<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class InstaCartTask extends FannieTask 
{
    public $name = 'Submit InstaCart data';

    public $description = 'Submits product data to InstaCart via FTP';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '2',
    );

    public function run()
    {
        $FANNIE_PLUGIN_SETTINGS = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($this->config->get('OP_DB'));

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
                LEFT JOIN products AS d ON p.deposit=d.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE m.superID <> 0';
        $result = $dbc->query($query);

        $csvfile = tempnam(sys_get_temp_dir(), 'ICT');
        $csv = fopen($csvfile, 'w');
        // reatailer_code is misspelled in the spec
        fwrite($csv, 'lookup_code,reatailer_code,item_name,size,cost_price_per_unit,price_unit,bottle_deposit,department,taxable,available,alcoholic,brand_name,sale_price,sale_start_at,sale_end_at' . "\r\n");
        while ($row = $dbc->fetchRow($result)) {
            // UPC or PLU with added check digits
            $plu = ltrim($row['upc'], '0'); 
            if (strlen($plu) == 13) {
                fwrite($csv, $plu . ',,'); // EAN-13 with check
            } elseif (strlen($plu == 12)) {
                // probably EAN-13 w/o check
                fwrite($csv, $plu . BarcodeLib::EAN13CheckDigit($plu) . ',,');
            } elseif (strlen($plu > 7)) {
                // probably UPC-A w/o check 
                $plu = str_pad($plu, 11, '0', STR_PAD_LEFT);
                fwrite($csv, $plu . BarcodeLib::UPCACheckDigit($plu) . ',');
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

        /**
          Upload export via (S)FTP
        */

        unlink($csvfile);
    }
}

