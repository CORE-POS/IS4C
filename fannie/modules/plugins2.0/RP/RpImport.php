<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpImport extends FannieRESTfulPage
{
    protected $header = 'RP Import';
    protected $title = 'RP Import';

    public function changeCosts($changes)
    {
        $actual = array();
        $prodP = $this->connection->prepare("SELECT cost FROM products WHERE upc=?");
        $lcP = $this->connection->prepare("SELECT upc FROM upcLike WHERE likeCode=?");
        $upP = $this->connection->prepare("UPDATE products SET cost=? WHERE upc=?");
        foreach ($changes as $lc => $cost) {
            $upcs = $this->connection->getAllValues($lcP, array($lc));
            foreach ($upcs as $upc) {
                $current = $this->connection->getValue($prodP, array($upc));
                if ($current === false) {
                    continue; // no such product
                } elseif (abs($cost - $current) > 0.005) {
                    $actual[] = $upc;
                    echo "$lc: $upc changed from $current to $cost\n";
                    //$this->connection->execute($upP, array($cost, $upc));
                }
            }
        }
        /*
        $model = new ProdUpdateModel($this->connection);
        $model->logManyUpdates($actual, 'EDIT');
         */
    }

    /**
     * Assign active status to likecodes based on incoming
     * Excel data
     */
    public function updateActive($data)
    {
        $this->connection->query("UPDATE LikeCodeActiveMap SET inUse=0 WHERE likeCode <= 999"); 
        $upP = $this->connection->prepare("UPDATE LikeCodeActiveMap SET inUse=1 WHERE likeCode=? AND storeID=?");
        $this->connection->startTransaction();
        $map = new LikeCodeActiveMapModel($this->connection);
        $activated = array();
        foreach ($data as $lc => $info) {
            if (strpos($lc, '-')) {
                list($lc, $rest) = explode('-', $lc, 2);
            }
            if (isset($activated[$lc])) {
                continue;
            }
            switch (strtoupper(trim($info['active']))) {
                case 'ACTIVEHD':
                    $map->likeCode($lc);
                    $map->storeID(1);
                    $map->inUse(1);
                    $map->save();
                    $map->storeID(2);
                    $map->save();
                    $activated[$lc] = true;
                    break;
                case 'ACTIVEH':
                    $map->likeCode($lc);
                    $map->storeID(1);
                    $map->inUse(1);
                    $map->save();
                    $activated[$lc] = true;
                    break;
                case 'ACTIVED':
                    $map->likeCode($lc);
                    $map->storeID(2);
                    $map->inUse(1);
                    $map->save();
                    $activated[$lc] = true;
                    break;
                case '0': // normal disabled status
                    break;
                default:
                    echo "Unknown status: " . $info['active'] . "\n";
            }
        }
        $this->connection->commitTransaction();
    }

    /**
     * Build out order guide tables
     * @param $data - likecode keyed array of RP data
     *
     * Some likecodes occur multiple times in the source
     * data so for key-uniqueness subsequent data is keyed
     * by likecode plus a random unique string. Often only
     * one of the entries for a likecode is actually active
     * so there's some juggling of this extra random string
     * to try and use the actual likecode in the order guide
     * record for the one entry in the dataset that's active.
     * However, if more than one entry for a given likecode
     * is active the random appended strings will bleed into
     * the order guide.
     */
    public function updateVendors($data)
    {
        $vendLC = new VendorLikeCodeMapModel($this->connection);
        $activeP = $this->connection->prepare("SELECT storeID FROM LikeCodeActiveMap WHERE inUse=1 AND likeCode=?");
        $catP = $this->connection->prepare("
            SELECT rpOrderCategoryID
            FROM likeCodes AS l
                LEFT JOIN RpOrderCategories AS c ON l.sortRetail=c.name
            WHERE l.likeCode=?");
        $catP2 = $this->connection->prepare("SELECT rpOrderCategoryID FROM RpOrderCategories WHERE name=?");
        $lcSortP = $this->connection->prepare("UPDATE likeCodes SET sortRetail=? WHERE likeCode=?");
        $makeP = $this->connection->prepare("INSERT INTO RpOrderCategories (name) VALUES (?)");
        $insP = $this->connection->prepare("INSERT INTO RpOrderItems
            (upc, storeID, categoryID, vendorID, vendorSKU, vendorItem, backupID, backupSKU, backupItem, caseSize)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $this->connection->query('TRUNCATE TABLE RpOrderItems');
        $this->connection->startTransaction();
        $added = array();
        foreach ($data as $lc => $info) {
            if (!$info['active']) {
                continue;
            }
            $stores = $this->connection->getAllValues($activeP, array($lc));
            if (count($stores) == 0) {
                continue;
            }
            $realLC = $lc;
            if (strpos($lc, '-')) {
                list($lc, $rest) = explode('-', $lc, 2);
                if (!isset($added[$lc])) {
                    $realLC = $lc;
                }
            }
            $added[$lc] = true;
            $catID = $this->connection->getValue($catP, array($lc));
            if (!$catID) {
                $catID = $this->connection->getValue($catP2, array($info['sort']));
                if (!$catID) {
                    $this->connection->execute($makeP, array($info['sort']));
                    $catID = $this->connection->insertID();
                }
                $this->connection->execute($lcSortP, array($info['sort'], $lc));
            }
            $vendorID = $this->vendorToID($info['primary']);
            if (!$vendorID) {
                $vendorID = $this->guessVendor($info);
            }
            $name = $this->getItemName($vendorID, $info);
            if ($name === 'Unknown') {
                echo $lc . ":\n";
                var_dump($info);
            }
            $mainCatalog = false;
            if ($vendorID) {
                $mainCatalog = $this->findItem($vendorID, $name);
                if ($mainCatalog && $vendorID > 0) {
                    $vendLC->likeCode($lc);
                    $vendLC->vendorID($vendorID);
                    $mapped = $vendLC->find();
                    if (count($mapped)) {
                        $obj = array_pop($mapped);
                        $obj->sku($mainCatalog['sku']);
                        $obj->save();
                    } else {
                        $vendLC->sku($mainCatalog['sku']);
                        $vendLC->save();
                    }
                }
            }
            $backupID = $this->vendorToID($info['secondary']);
            $backupName = $this->getItemName($backupID, $info);
            $backupCatalog = false;
            if ($backupID) {
                $backupCatalog = $this->findItem($backupID, $backupName);
            }
            foreach ($stores as $storeID) {
                $args = array(
                    'LC' . $realLC,
                    $storeID,
                    $catID,
                    $vendorID,
                    ($mainCatalog ? $mainCatalog['sku'] : null),
                    ($mainCatalog ? $mainCatalog['description'] : $name),
                    $backupID,
                    ($backupCatalog ? $backupCatalog['sku'] : null),
                    ($backupCatalog ? $backupCatalog['description'] : $backupName),
                    $info['units'],
                );
                $this->connection->execute($insP, $args);
            }
        }
        $this->connection->commitTransaction();
    }

    private function findItem($vendorID, $name)
    {
        switch ($vendorID) {
            case -2:
                return array('sku' => 'DIRECT', 'description' => $name);
            case 292: // Alberts
                list($realName, $size) = explode('\\', $name, 2);
                $realName = substr(trim($realName), 0, 50);
                if (strstr($size, 'x')) {
                    list($caseSize, $unitSize) = explode('x', $size, 2);
                } elseif (strstr($size, ' ')) {
                    list($caseSize, $unitSize) = explode(' ', $size, 2);
                    $unitSize = trim($unitSize);
                    if (substr($unitSize, 0, 2) == 'lb') {
                        $unitSize = 'lb';
                    } elseif (substr($unitSize, 0, 2) == 'ct') {
                        $unitSize = 'ea';
                    }
                } else {
                    $caseSize = $size;
                    $unitSize = '';
                }
                $albP = $this->connection->prepare("SELECT sku, description FROM vendorItems
                    WHERE vendorID=?
                        AND description LIKE ?
                        AND units=?
                        AND size LIKE ?");
                return $this->connection->getRow($albP, array(
                    $vendorID,
                    '%' . $realName . '%',
                    $caseSize,
                    '%' . $unitSize . '%',
                ));
            case 136:
                if (strstr($name, ':')) {
                    list($sku, $realName) = explode(':', $name, 2);
                    $rdwP = $this->connection->prepare("SELECT sku, description FROM vendorItems WHERE vendorID=? AND sku=?");
                    return $this->connection->getRow($rdwP, array($vendorID, $sku));

                }
                // intentional fallthrough
                
            default:
                $name = substr(trim($name), 0, 50);
                $defaultP = $this->connection->prepare('SELECT sku, description FROM vendorItems WHERE vendorID=? AND description LIKE ?');
                return $this->connection->getRow($defaultP, array($vendorID, '%' . trim($name) . '%'));
        }
    }

    private function getItemName($vendorID, $info)
    {
        switch ($vendorID) {
            case 292:
                return $info['alberts'];
            case 293:
                return $info['cpw'];
            case 136:
                return $info['rdwSKU'] . ':' . $info['rdw'];
            case 1:
                return $info['unfi'];
            case -2:
                return $info['direct'];
        }

        return 'Unknown';
    }

    public function cliWrapper()
    {
        $out = $this->post_view();
        $out = str_replace('<tr>', '', $out);
        $out = str_replace('<td>', '', $out);
        $out = str_replace('<th>', '', $out);
        $out = str_replace('<table class="table table-bordered">', '', $out);
        $out = str_replace('</table>', '', $out);
        $out = str_replace('</tr>', "\n", $out);
        $out = str_replace("</td>", "\t", $out);
        $out = str_replace("</th>", "\t", $out);

        echo $out;
    }

    protected function post_view()
    {
        $items = array();
        foreach (explode("\n", $this->form->in) as $line) {
            if (preg_match('/(\d+)\](.)\[(.+){(.+)}(.+)\|(.+)_/', $line, $matches)) {
                list($type,$origin) = explode('\\', $matches[5]);
                $items[] = array(
                    'lc' => $matches[1],
                    'organic' => strtolower($matches[2]) == 'c' ? true : false,
                    'name' => $matches[3],
                    'price' => $matches[4],
                    'scale' => strtolower($type) == 'lb' ? true : false,
                    'origin' => $origin,
                    'vendor' => $matches[6],
                );
            }
        }

        $dbc = $this->connection;
        $lcP = $dbc->prepare('UPDATE likeCodes SET organic=?, preferredVendorID=?, origin=? WHERE likeCode=?');
        $orgP = $dbc->prepare('
            UPDATE upcLike AS u
                INNER JOIN products AS p ON u.upc=p.upc
            SET p.numflag = p.numflag | ?
            WHERE u.likeCode=?'); 
        $nonP = $dbc->prepare('
            UPDATE upcLike AS u
                INNER JOIN products AS p ON u.upc=p.upc
            SET p.numflag = p.numflag & ?
            WHERE u.likeCode=?'); 
        $orgBits = 1 << (17 - 1);
        $nonBits = 0xffffffff ^ $orgBits;

        $ret = '<table class="table table-bordered">
            <tr><th>LC</th><th>Name</th><th>Retail</th><th>Vendor</th><th>Origin</th><th>Organic</th><th>Scale</th></tr>';
        $dbc->startTransaction();
        foreach ($items as $i) {
            $ret .= sprintf('<tr><td>%d</td><td>%s</td><td>%.2f</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $i['lc'], $i['name'], $i['price'], $i['vendor'], $i['origin'],
                ($i['organic'] ? 'Yes' : 'No'),
                ($i['scale'] ? 'Yes' : 'No')
            );
            $args = array(
                $i['organic'] ? 1 : 0,
                $this->vendorToID($i['vendor']),
                $i['origin'],
                $i['lc'],
            );
            $dbc->execute($lcP, $args);
            if ($i['organic']) {
                $dbc->execute($orgP, array($orgBits, $i['lc']));
            } else {
                $dbc->execute($nonP, array($nonBits, $i['lc']));
            }
        }
        $ret .= '</table>';
        $dbc->commitTransaction();

        return $ret;
    }

    private function vendorToID($vendor)
    {
        switch (strtolower($vendor)) {
            case 'alberts':
                return 292;
            case 'cpw':
                return 293;
            case 'rdw':
                return 136;
            case 'unfi':
                return 1;
            case 'direct':
                return -2;
            default:
                return 0;
        }
    }

    private function guessVendor($info)
    {
        if (isset($info['alberts']) && !empty($info['alberts'])) {
            return 292;
        } elseif (isset($info['cpw']) && !empty($info['cpw'])) {
            return 293;
        } elseif (isset($info['rdw']) && !empty($info['rdw'])) {
            return 136;
        } elseif (isset($info['unfi']) && !empty($info['unfi'])) {
            return 1;
        } elseif (isset($info['direct']) && !empty($info['direct'])) {
            return -2;
        }

        return 0;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Excel Columns</label>
        <textarea name="in" rows="25" class="form-control"></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Import</button>
    </div>
</form>
HTML;
    }
}

/**
 * Locate the appropriate file, exract all its data,
 * pull out the piece that's needed, run update through the page
 * class, then finally clean up files that were created
 *
 * jxl is a java tool to more efficiently pull data out of
 * large-ish excel files
 * https://github.com/gohanman/JXL
 */
if (php_sapi_name() == 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $config = FannieConfig::factory();
    $settings = $config->get('PLUGIN_SETTINGS');
    $path = $settings['RpDirectory'];
    $dir = opendir($path);
    $found = false;
    while (($file=readdir($dir)) !== false) {
        if (substr($file, 0, 2) == 'RP') {
            $found = $path . $file;
        }
    }
    if (isset($argv[1])) {
        $found = file_exists($argv[1]) ? $argv[1] : false;
    }
    if ($found) {
        $tempfile = tempnam('rpx', sys_get_temp_dir());
        copy($found, $tempfile);
        $cmd = 'java -cp jxl-1.0-SNAPSHOT-jar-with-dependencies.jar coop.wholefoods.jxl.App -i ' . $tempfile . ' -o /tmp/';
        exec($cmd);
        $dir = opendir('/tmp/');
        $otherData = array();
        while (($file=readdir($dir)) !== false) {
            if ($file == 'Comparison.tsv') {
                $fp = fopen('/tmp/Comparison.tsv', 'r');
                $input = '';
                $dupes = array();
                while (!feof($fp)) {
                    $line = fgets($fp);
                    $data = explode("\t", $line);

                    $info = isset($data[107]) ? $data[107] : '';
                    if (strstr($info, ']')) {
                        $input .= $info . "\n";
                    }

                    $lc = isset($data[8]) && is_numeric($data[8]) && $data[8] ? $data[8] : false;
                    if ($lc) {
                        if (!isset($otherData[$lc])) {
                            $otherData[$lc] = array();
                        } else { 
                            if (!in_array($lc, $dupes)) {
                                $dupes[] = $lc;
                            }
                            $lcPlus = $lc . '-' . uniqid();
                            while (isset($otherData[$lcPlus])) {
                                $lcPlus = $lc . '-' . uniqid();
                            }
                            $lc = $lcPlus;
                            $otherData[$lc] = array();
                        }
                        $otherData[$lc]['active'] = $data[10];
                        $otherData[$lc]['primary'] = $data[34];
                        $otherData[$lc]['secondary'] = $data[35];
                        $otherData[$lc]['alberts'] = $data[12];
                        $otherData[$lc]['cpw'] = $data[13];
                        $otherData[$lc]['rdw'] = $data[14];
                        $otherData[$lc]['unfi'] = $data[15];
                        $otherData[$lc]['direct'] = $data[16];
                        $otherData[$lc]['rdwSKU'] = (int)$data[23];
                        $otherData[$lc]['sort'] = $data[11];
                        $otherData[$lc]['units'] = $data[42];
                    }

                }
                $page = new RpImport();
                $logger = FannieLogger::factory();
                $dbc = FannieDB::get($config->get('OP_DB'));
                $page->setConfig($config);
                $page->setLogger($logger);
                $page->setConnection($dbc);
                $form = new COREPOS\common\mvc\ValueContainer();
                $form->in = $input;
                $page->setForm($form);
                $page->cliWrapper();
                $page->updateActive($otherData);
                $page->updateVendors($otherData);
            }

            if (substr($file, -4) == '.tsv') {
                unlink('/tmp/' . $file);
            }
        }
        unlink($tempfile);
    }
    exit(0);
}

FannieDispatch::conditionalExec();

