<?php
use COREPOS\Fannie\API\item\StandardAccounting;
include('../../../../config.php');
if (!class_exists('FannieAPI')) {
    include( __DIR__ .'/../../../../classlib2.0/FannieAPI.php');
}

class EOMTaxReport extends FannieReportPage
{
    protected $header = 'EOM Tax Report';
    protected $title = 'EOM Tax Report';

    public function report_description_content()
    {
        $year = date('Y');
        $month = date('n');
        $stamp = mktime(0,0,0,$month-1,1,$year);
        $start = date("Y-m-01",$stamp);
        $end = date("Y-m-t",$stamp);
        $storeInfo = FormLib::storePicker();
        $store = FormLib::get('store', false);
        $storeName = 'Consolidated';
        if ($store === false) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        if ($store != 0) {
            $storeP = $this->connection->prepare("SELECT description FROM Stores WHERE storeID=?");
            $found = $this->connection->getValue($storeP, array($store));
            if ($found) {
                $storeName = $found . ' Store';
            }
        }

        return array(
            "For $start to $end",
            $storeName,
            $this->report_format == 'html' ?
            '<form action="EOMTaxReport.php" method="get">'
            . $storeInfo['html'] . 
            '<input type="submit" value="Change" />
            </form>' : '',
        );
    }

    public function fetch_report_data()
    {
        /*
        $storeInfo = FormLib::storePicker();
        echo '<form action="EOMTaxReport.php" method="get">'
            . $storeInfo['html'] . 
            '<input type="submit" value="Change" />
            </form>';
        echo '<p><a href="../../../../modules/plugins2.0/CoreWarehouse/reports/EOMReport.php">Or use the newer one</a></p>';
         */

        $store = FormLib::get('store', false);
        if ($store === false) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }

        $today = date("m/j/y");
        $uoutput = '<br>Report run ' . $today; 

        $year = date('Y');
        $month = date('n');
        $stamp = mktime(0,0,0,$month-1,1,$year);
        $start = date("Y-m-01",$stamp);
        $end = date("Y-m-t",$stamp);
        $args = array($start.' 00:00:00',$end.' 23:59:59', $store);
        $dlog = DTransactionsModel::selectDlog($start, $end);

        // not simplified for the sake of exactly matching historical report
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $query3 = "SELECT c.salesCode,s.superID,sum(l.total) as total 
        FROM $dlog as l left join MasterSuperDepts AS s ON
        l.department = s.dept_ID INNER JOIN departments AS c
        ON l.department = c.dept_no
        WHERE l.tdate BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'l') . "
        AND l.department < 600 AND l.department <> 0
        AND l.trans_type <> 'T'
        AND l.trans_type IN ('I','D')
        GROUP BY c.salesCode,s.superID
        order by c.salesCode,s.superID";
        $prep = $dbc->prepare($query3);
        $res = $dbc->execute($query3, $args);
        $grandTTL = 0.0;
        while ($w = $dbc->fetchRow($res)) {
            $grandTTL += $w[2];
        }
        
        $sql = FannieDB::get($this->config->get('OP_DB'));
        $newTaxQ = 'SELECT MAX(description) AS description,
                        SUM(regPrice) AS ttl,
                        numflag AS taxID
                    FROM trans_archive.bigArchive AS t
                    WHERE datetime BETWEEN ? AND ?
                        AND ' . DTrans::isStoreID($store, 't') . '
                        AND upc=\'TAXLINEITEM\'
                        AND trans_status <> \'X\'
                        AND ' . DTrans::isNotTesting() . '
                    GROUP BY taxID';
        $prep = $sql->prepare($newTaxQ);
        $res = $sql->execute($prep, $args);
        $collected = array(1 => 0.00, 2=>0.00);
        while ($row = $sql->fetch_row($res)) {
            $collected[$row['taxID']] = $row['ttl'];
        }
        $state = 0.06875;
        $city = 0.015;
        $deli = 0.0225;
        $county = 0.005;
        $canna = 0.15;
        $startDT = new DateTime($start);
        $noCounty = new DateTime('2017-10-01');
        if ($startDT >= $noCounty) {
            //$county = 0;
        }
        $data = array();
        $data[] = array('', '', '', sprintf('%.2f', $grandTTL), '');
        $data[] = array(
            'Tax Collected on Regular rate items',
            sprintf('%.2f', $collected[1]),
            'Regular Taxable Sales',
            sprintf('%.2f', $collected[1]/($state+$city+$county)),
            '',
        );

        $stateTax = $collected[1] * ($state/($state+$city+$county));
        $cityTax = $collected[1] * ($city/($state+$city+$county));
        $countyTax = $collected[1] * ($county/($state+$city+$county));
        $data[] = array( 
            'State Tax Amount',
            sprintf('%.2f', $stateTax),
            'State Taxable Sales',
            sprintf('%.2f', $stateTax / $state),
            '',
        );
        $data[] = array(
            'City Tax Amount',
            sprintf('%.2f', $cityTax),
            'City Taxable Sales',
            sprintf('%.2f', $cityTax / $city),
            '',
        );
        $data[] = array(
            'County Tax Amount',
            sprintf('%.2f', $countyTax),
            'County Taxable Sales',
            sprintf('%.2f', $countyTax / $county),
            '',
        );

        $data[] = array(
            'Tax Collected on Deli rate items',
            sprintf('%.2f', $collected[2]),
            'Deli Taxable Sales',
            sprintf('%.2f', $collected[2]/($state+$city+$deli+$county)),
            '',
        );

        $stateTax = $collected[2] * ($state/($state+$city+$deli+$county));
        $cityTax = $collected[2] * ($city/($state+$city+$deli+$county));
        $deliTax = $collected[2] * ($deli/($state+$city+$deli+$county));
        $countyTax = $collected[2] * ($county/($state+$city+$deli+$county));
        $data[] = array(
            'State Tax Amount',
            sprintf('%.2f', $stateTax),
            'State Taxable Sales',
            sprintf('%.2f', $stateTax / $state),
            '',
        );
        $data[] = array(
            'City Tax Amount',
            sprintf('%.2f', $cityTax),
            'City Taxable Sales',
            sprintf('%.2f', $cityTax / $city),
            '',
        );
        $data[] = array(
            'County Tax Amount',
            sprintf('%.2f', $countyTax),
            'County Taxable Sales',
            sprintf('%.2f', $countyTax / $county),
            '',
        );
        $data[] = array(
            'Deli Tax Amount',
            sprintf('%.2f', $deliTax),
            'Deli Taxable Sales',
            sprintf('%.2f', $deliTax / $deli),
            '',
        );
        
        $data[] = array(
            'Tax Collected on Cannabis rate items',
            sprintf('%.2f', $collected[3]),
            'Cannabis Taxable Sales',
            sprintf('%.2f', $collected[3]/$canna),
            '',
        );

        $stateTax = ($collected[1] * ($state/($state+$city+$county))) 
                    + ($collected[2] * ($state/($state+$city+$deli+$county)));
        $cityTax = ($collected[1] * ($city/($state+$city+$county))) 
                    + ($collected[2] * ($city/($state+$city+$deli+$county)));
        $countyTax = ($collected[1] * ($county/($state+$city+$county))) 
                    + ($collected[2] * ($county/($state+$city+$deli+$county)));
        $deliTax = $collected[2] * ($deli/($state+$city+$deli+$county));
        $taxTTL = 0;
        $data[] = array('State Totals', null, null, null, null);
        $data[] = array(
            'Tax Collected',
            sprintf('%.2f', $stateTax),
            'Taxable Sales',
            sprintf('%.2f', $stateTax / $state),
            '',
        );
        $taxTTL += $stateTax;
        $data[] = array('City Totals', null, null, null, null);
        $data[] = array(
            'Tax Collected',
            sprintf('%.2f', $cityTax),
            'Taxable Sales',
            sprintf('%.2f', $cityTax / $city),
            '',
        );
        $taxTTL += $cityTax;
        $data[] = array('County Totals', null, null, null, null);
        $data[] = array(
            'Tax Collected',
            sprintf('%.2f', $countyTax),
            'Taxable Sales',
            sprintf('%.2f', $countyTax / $county),
            '',
        );
        $taxTTL += $countyTax;
        $data[] = array('Deli Totals', null, null, null, null);
        $data[] = array(
            'Tax Collected',
            sprintf('%.2f', $deliTax),
            'Taxable Sales',
            sprintf('%.2f', $deliTax / $deli),
            sprintf('%.2f', $grandTTL - ($deliTax / $deli)),
        );
        $taxTTL += $deliTax;

        $data[] = array('', sprintf('%.2f', $taxTTL), '', '', '');

        return $data;
    }
}

FannieDispatch::conditionalExec();

