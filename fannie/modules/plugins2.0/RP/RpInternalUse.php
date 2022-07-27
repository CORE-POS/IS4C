<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpInternalUse extends FannieRESTfulPage
{
    protected $header = 'Internal Order Guide';
    protected $title = 'Internal Order Guide';

    protected function get_view()
    {
        $store = FormLib::get('store', false);
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }

        $prep = $this->connection->prepare('SELECT l.likeCode, likeCodeDesc, sortInternal
            FROM likeCodes AS l
                INNER JOIN LikeCodeActiveMap AS m ON l.likeCode=m.likeCode
            WHERE m.internalUse > 0
                AND m.storeID=?
            ORDER BY sortInternal, likeCodeDesc');
        $res = $this->connection->execute($prep, array($store));
        $category = false;
        $rpP = $this->connection->prepare("SELECT caseSize, cost, vendorID, backupID FROM RpOrderItems
                    WHERE upc=? AND storeID=?");
        $ret = '';
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['sortInternal'] != $category) {
                if ($category) {
                    $ret .= '</table>';
                } 
                $category = $row['sortInternal'];
                $ret .= '<h3>' . $category . '</h3>';
                $ret .= '<table class="table table-bordered table-striped">
                    <tr><th>Item</th><th>Primary</th><th>Case Size</th><th>Case Cost</th><th>Secondary</th></tr>';
            }
            $ret .= '<tr><td>' . $row['likeCodeDesc'] . '</td>';
            $rp = $this->connection->getRow($rpP, array('LC' . $row['likeCode'], $store));
            list($prime,$second) = $this->getVendors($rp);
            $ret .= '<td>' . $this->getVendorName($prime) . '</td>';
            $ret .= '<td>' . ($rp ? $rp['caseSize'] : 'n/a') . '</td>';
            $ret .= '<td>' . ($rp ? $rp['cost'] : 'n/a') . '</td>';
            $ret .= '<td>' . $this->getVendorName($second) . '</td>';
            $ret .= '</tr>';
        }

        $appleP = $this->connection->prepare("SELECT upc, vendorID, backupID, cost, caseSize
            FROM RpOrderItems
            WHERE categoryID=1
                AND storeID=?
                AND upc NOT IN ('LC551', 'LC569', 'LC101', 'LC102')
            ORDER BY cost / caseSize");
        $appleR = $this->connection->execute($appleP, array($store));
        $orgP = $this->connection->prepare("SELECT organic, likeCodeDesc FROM likeCodes WHERE likeCode=?");
        $count = 0;
        while ($row = $this->connection->fetchRow($appleR)) {
            $lc = substr($row['upc'], 2);
            $organic = $this->connection->getRow($orgP, array($lc));
            if (is_array($organic) && $organic['organic']) {
                $count++;
                $ret .= '<tr><td>' . $organic['likeCodeDesc'] . '</td>';
                list($prime,$second) = $this->getVendors($row);
                $ret .= '<td>' . $this->getVendorName($prime) . '</td>';
                $ret .= '<td>' . ($rp ? $row['caseSize'] : 'n/a') . '</td>';
                $ret .= '<td>' . ($rp ? $row['cost'] : 'n/a') . '</td>';
                $ret .= '<td>' . $this->getVendorName($second) . '</td>';
                $ret .= '</tr>';
            }
            if ($count > 1) {
                break;
            }
        }


        $ret .= '</table>';

        return $ret;
    }

    private function getVendors($row)
    {
        if (is_array($row) && isset($row['vendorID'])) {
            return array($row['vendorID'], $row['backupID']);
        }

        return array('n/a', 'n/a');
    }

    private function getVendorName($id)
    {
        switch ($id) {
        case -2: return 'Direct';
        case 136: return 'RDW';
        case 292: return 'Alberts';
        case 293: return 'CPW';
        default: return 'n/a';
        }
    }
}

FannieDispatch::conditionalExec();

