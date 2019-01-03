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
        $PHOTOS = $settings['InstaCartImgLocal'];
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
                CASE WHEN (numflag & (1<<17)) <> 0 THEN 1 ELSE 0 END AS glutenfree,
                f.sections,
                m.superID,
                p.department,
                p.subdept,
                u.photo
            FROM products AS p
                LEFT JOIN productUser AS u on p.upc=u.upc
                LEFT JOIN taxrates AS t ON p.tax=t.id
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN batches AS b ON p.batchID=b.batchID
                LEFT JOIN batchType AS y on b.batchType=y.batchTypeID
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN FloorSectionsListTable AS f ON p.upc=f.upc AND p.store_id=f.storeID
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
        fwrite($csv, "lookup_code,price,cost_unit,item_name,size,brand_name,unit_count,department,retailer_reference_code,organic,gluten_free,tax_rate,bottle_deposit,location_data,remote_image_URL,sale_price,sale_start_at,sale_end_at,alcoholic\r\n");
        $repeats = array();
        $skips = array('date'=>0, 'ex'=>0, 'lc'=>0, 'in'=>0, 'price'=>0, 'rp'=>0);
        echo $this->dbc->numRows($res) . "\n";
        while ($row = $this->dbc->fetchRow($res)) {
            if ($row['normal_price'] <= 0.01 || $row['normal_price'] >= 500) {
                $skips['price']++;
                continue;
            }

            if ($instaMode == 1) {
                $included = $this->dbc->getValue($includeP, array($row['upc']));
                if ($included === false) {
                    $skips['ex']++;
                    continue;
                }
            } else {
                $excluded = $this->dbc->getValue($excludeP, array($row['upc']));
                if ($excluded == $row['upc']) {
                    $skips['ex']++;
                    continue;
                }
                $deptEx = $this->departmentFilter($row);
                if ($deptEx) {
                    $skips['ex']++;
                    continue;
                }
            }
            
            if ($this->skipDates($row['upc'], $settings['InstaCartNewCutoff'], $settings['InstaCartSalesCutoff'])) {
                $skips['date']++;
                continue;
            }

            if ($this->likeCodeFilter($row['upc'])) {
                $skips['lc']++;
                continue;
            }

            if (isset($repeats[$row['upc']])) {
                $skips['rp']++;
                continue;
            }
            $repeats[$row['upc']] = true;
            if ($row['description'] == strtoupper($row['description'])) {
                echo "{$row['upc']} {$row['brand']} {$row['description']}\n";
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
                if ($row['organic'] && strlen($upc) == 4 && (substr($upc, 0, 1) =='4' || substr($upc, 0, 1) == '3')) {
                    $upc = '9' . $upc;
                }
                fwrite($csv, $upc . $sep); 
            }
            fprintf($csv, '%.2f%s', $row['normal_price'], $sep);
            fwrite($csv, ($row['scale'] ? 'lb' : 'each') . $sep);

            $desc = str_replace('"', '', $row['description']);
            $desc = str_replace("\r", '', $desc);
            $desc = str_replace("\n", ' ', $desc);
            $desc = trim($desc);
            if ($row['organic']) {
                if (!stristr($row['brand'], 'organic') && !stristr($desc, 'organic')) {
                    $desc = 'Organic ' . $desc;
                }
            }
            $desc = str_replace(',', '', $desc);
            $desc = substr($desc, 0, 100);
            fwrite($csv, $desc . $sep);

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
            $brand = str_replace(',', '', $brand);
            $brand = trim($brand);
            $brand = substr($brand, 0, 100);
            if (strtolower($brand) == 'bulk' || strtolower($brand) == 'produce') {
                $brand = '';
            }
            fwrite($csv, $brand . $sep);

            fwrite($csv, $units . $sep);

            $dept = str_replace('"', '', $row['super_name']);
            $dept = str_replace(',', '', $dept);
            fwrite($csv, $dept . $sep);

            fwrite($csv, $row['upc'] . $sep);
            fwrite($csv, ($row['organic'] ? 'TRUE' : 'FALSE') . $sep);
            fwrite($csv, ($row['glutenfree'] ? 'TRUE' : 'FALSE') . $sep);

            fprintf($csv, '%.5f%s', $row['rate'], $sep);

            if ($row['deposit'] > 0) {
                $row['deposit'] = $this->dbc->getValue($depositP, array(BarcodeLib::padUPC($row['deposit'])));
            }
            fprintf($csv, '%.2f%s', $row['deposit'], $sep);

            $location = "";
            if (strlen($row['sections']) > 0) {
                $location = $row['sections'];
                if (strpos($location, ',')) {
                    list($location,) = explode(',', $location, 2);
                    $location = trim($location);
                }
                $location = '"{""Aisle"": ""' . $location . '""}"';
            }
            fprintf($csv, '%s%s', $location, $sep);
            if ($row['photo'] && file_exists($PHOTOS . $row['photo'])) {
                fprintf($csv, '%s%s,', $settings['InstaCartImgRemote'], $row['photo']);
            } else {
                fprintf($csv, $sep);
            }

            if (!$settings['InstaSalePrices'] || $row['special_price'] == 0 || $row['special_price'] >= $row['normal_price'] || !$row['datedSigns'] || $row['specialpricemethod'] != 0 || $row['discounttype'] != 1) {
                fwrite($csv, $sep . $sep . $sep);
            } else {
                fprintf($csv, '%.2f%s', $row['special_price'], $sep);
                fwrite($csv, date('m/d/Y', strtotime($row['start_date'])) . $sep);
                $ts = strtotime($row['end_date']);
                $next = mktime(0,0,0, date('n',$ts), date('j',$ts)+1, date('Y', $ts));
                fwrite($csv, date('m/d/Y', $next) . $sep);
            }

            fwrite($csv, ($row['idEnforced'] == 21 ? 'TRUE' : 'FALSE') . $newline);
        }
        fclose($csv);
        print_r($skips);

        return true;
    }

    private function departmentFilter($row)
    {
        if (!isset($this->dFilter)) {
            $this->dFilter = array(
                'super' => array(),
                'dept' => array(),
                'sub' => array(),
            );
            $res = $this->dbc->query('SELECT instaSuperID FROM ' . FannieDB::fqn('InstaSupers', 'plugin:InstaCartDB'));
            while ($row = $this->dbc->fetchRow($res)) {
                $this->dFilter['super'][$row['instaSuperID']] = true;
            }
            $res = $this->dbc->query('SELECT instaDeptID FROM ' . FannieDB::fqn('InstaDepts', 'plugin:InstaCartDB'));
            while ($row = $this->dbc->fetchRow($res)) {
                $this->dFilter['dept'][$row['instaDeptID']] = true;
            }
            $res = $this->dbc->query('SELECT instaSubID FROM ' . FannieDB::fqn('InstaSubs', 'plugin:InstaCartDB'));
            while ($row = $this->dbc->fetchRow($res)) {
                $this->dFilter['sub'][$row['instaSubID']] = true;
            }
        }

        if (isset($this->dFilter['super'][$row['superID']])) {
            return true;
        } elseif (isset($this->dFilter['dept'][$row['department']])) {
            return true;
        } elseif (isset($this->dFilter['sub'][$row['subdept']])) {
            return true;
        }

        return false;
    }

    /**
      Only include one item from each *strict* like code
      @return [boolean] skip item
    */
    private function likeCodeFilter($upc)
    {
        $prep = $this->dbc->prepare('SELECT u.likeCode, l.strict
                FROM ' . FannieDB::fqn('upcLike', 'op') . ' AS u
                    INNER JOIN ' . FannieDB::fqn('likeCodes', 'l') . ' AS l ON u.likeCode=l.likeCode
                WHERE u.upc=?');
        $info = $this->dbc->getRow($prep, array($upc));
        if ($info == false || !$info['strict']) {
            return false;
        }

        if (!isset($this->lcCache)) {
            $this->lcCache = array();
        }

        if (!isset($this->lcCache[$info['likeCode']])) {
            $this->lcCache[$info['likeCode']] = true;
            return false;
        }

        return true;
    }

    /**
      Skip items based on dates. Allows:
        - Items created in the last $created days that
          may or may not have sales
        - Items with sales at all stores in the last $sold days
      
      NULL-ness is checked separately from sales. Applicable
      UPC lists are cached for performance

      @return [boolean] skip this item
    */
    private function skipDates($upc, $created, $sold)
    {
        if ($created <= 0 && $sold <= 0) {
            return false;
        }

        if ($created) {
            if (!isset($this->cCache)) {
                $this->cCache = array();
                $cutoff = date('Y-m-d', strtotime($created . ' days ago'));
                $prep = $this->dbc->prepare("SELECT upc
                    FROM " . FannieDB::fqn('products', 'op') . "
                    GROUP BY upc
                    HAVING MIN(created) >= ?");
                $res = $this->dbc->execute($prep, array($cutoff));
                while ($row = $this->dbc->fetchRow($res)) {
                    $this->cCache[$row['upc']] = true;
                }
            }
            if (isset($this->cCache[$upc])) {
                return false;
            }
        }
        if ($sold) {
            if (!isset($this->nCache)) {
                $this->nCache = array();
                $res = $this->dbc->query('SELECT upc
                    FROM ' . FannieDB::fqn('products', 'op') . '
                    WHERE last_sold IS NULL
                    GROUP BY upc');
                while ($row = $this->dbc->fetchRow($res)) {
                    $this->nCache[$row['upc']] = true;
                }
            }
            if (isset($this->nCache[$upc])) {
                return true;
            }
            if (!isset($this->sCache)) {
                $this->sCache = array();
                $cutoff = date('Y-m-d', strtotime($sold . ' days ago'));
                $prep = $this->dbc->prepare('SELECT upc
                    FROM ' . FannieDB::fqn('products', 'op') . '
                    GROUP BY upc
                    HAVING MIN(last_sold) >= ?');
                $res = $this->dbc->execute($prep, array($cutoff));
                while ($row = $this->dbc->fetchRow($res)) {
                    $this->sCache[$row['upc']] = true;
                }
            }
            
            return isset($this->sCache[$upc]) ? false : true;
        }

        return false;
    }
}

