<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class LocalMixReport extends FannieReportPage 
{
    public $description = '[Small Basket Report] lists sales for transactions containing a limited
    number of items - i.e., what do people buy when they\'re only purchasing one or two things?';
    public $themed = true;
    public $required_fields = array();
    protected $title = "Fannie : Local Item Mix Report";
    protected $header = "Local Item Mix Report";

    private $localSettings = array();
    protected $report_headers = array('Vendor', 'Brand', '# Non-Local');

    public function preprocess()
    {
        global $FANNIE_OP_DB;
        $o = new OriginsModel(FannieDB::get($FANNIE_OP_DB));
        $o->local(1);
        foreach ($o->find('originID') as $origin) {
            $this->localSettings[$origin->originID()] = $origin->shortName();
            $this->report_headers[] = '# ' . $origin->shortName();
        }

        return parent::preprocess();
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $data = array();
        
        $localCols = '';
        foreach ($this->localSettings as $id => $name) {
            $localCols .= sprintf('SUM(CASE WHEN p.local=%d THEN 1 ELSE 0 END) as local%d,', $id, $id);
        }

        $vendorQ = '
            SELECT ' . $localCols . '
                COALESCE(v.vendorName, CASE WHEN x.distributor IS NULL THEN \'\' ELSE x.distributor END) AS vendor,
                SUM(CASE WHEN p.local=0 THEN 1 ELSE 0 END) AS nonLocal,
                COUNT(*) AS numItems
            FROM products AS p
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN prodExtra AS x ON p.upc=x.upc
            GROUP BY COALESCE(v.vendorName, CASE WHEN x.distributor IS NULL THEN \'\' ELSE x.distributor END)
            ORDER BY COUNT(*) DESC';
        $vendorR = $dbc->query($vendorQ);

        $brandP = $dbc->prepare('
            SELECT ' . $localCols . '
                COALESCE(p.brand, CASE WHEN x.manufacturer IS NULL THEN \'\' ELSE x.manufacturer END) AS brand,
                SUM(CASE WHEN p.local=0 THEN 1 ELSE 0 END) AS nonLocal
            FROM products AS p
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN prodExtra AS x on p.upc=x.upc
            WHERE (v.vendorName=? OR x.distributor=?)
            GROUP BY COALESCE(p.brand, CASE WHEN x.manufacturer IS NULL THEN \'\' ELSE x.manufacturer END)
            ORDER BY COALESCE(p.brand, CASE WHEN x.manufacturer IS NULL THEN \'\' ELSE x.manufacturer END)');
        $altBrandP = $dbc->prepare('
            SELECT ' . $localCols . '
                COALESCE(p.brand, CASE WHEN x.manufacturer IS NULL THEN \'\' ELSE x.manufacturer END) AS brand,
                SUM(CASE WHEN p.local=0 THEN 1 ELSE 0 END) AS nonLocal
            FROM products AS p
                LEFT JOIN prodExtra AS x on p.upc=x.upc
            WHERE p.default_vendor_id=0 
                AND (x.distributor IS NULL OR x.distributor=\'\')
            GROUP BY COALESCE(p.brand, CASE WHEN x.manufacturer IS NULL THEN \'\' ELSE x.manufacturer END)
            ORDER BY COALESCE(p.brand, CASE WHEN x.manufacturer IS NULL THEN \'\' ELSE x.manufacturer END)');

        $first = true;
        while ($vendorW = $dbc->fetch_row($vendorR)) {
            $has_local = false;
            foreach ($this->localSettings as $id => $name) {
                if ($vendorW['local' . $id] > 0) {
                    $has_local = true;
                    break;
                }
            }
            if (!$has_local) {
                continue;
            }

            if (!$first) {
                $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
            } else {
                $first = false;
            }

            $record = array(
                (empty($vendorW['vendor']) ? '(blank)' : $vendorW['vendor']),
                '', // blank brand on vendor row
                $vendorW['nonLocal'],
            );
            foreach ($this->localSettings as $id => $name) {
                $record[] = $vendorW['local' . $id];
            }
            $record['meta'] = FannieReportPage::META_BOLD;
            $data[] = $record;
            if (!empty($vendorW['vendor'])) {
                $brandR = $dbc->execute($brandP, array($vendorW['vendor'], $vendorW['vendor']));
            } else {
                $brandR = $dbc->execute($altBrandP);
            }
            while ($brandW = $dbc->fetch_row($brandR)) {
                $has_local = false;
                foreach ($this->localSettings as $id => $name) {
                    if ($brandW['local' . $id] > 0) {
                        $has_local = true;
                        break;
                    }
                }
                if (!$has_local) {
                    continue;
                }
                $record = array(
                    '', // blank vendor on brand row
                    (empty($brandW['brand']) ? '(blank)' : $brandW['brand']),
                    $brandW['nonLocal'],
                );
                foreach ($this->localSettings as $id => $name) {
                    $record[] = $brandW['local' . $id];
                }
                $data[] = $record;
            }
        }

        return $data;
    }

}

FannieDispatch::conditionalExec();

