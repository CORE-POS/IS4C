<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

namespace COREPOS\Fannie\Plugin\CoreWarehouse\sources {

use COREPOS\Fannie\Plugin\CoreWarehouse;

/**
  @class CwReportDataSource
  Base class for extracting transaction data
  from Core Warehouse Plugin tables and feeding
  that data back into default reports
*/
class GeneralDayDataSource extends CoreWarehouse\CwReportDataSource
{
    protected $valid_reports = array('GeneralDayReport');

    /**
      Fetch data for the specified report
      @param [string] $report_class_name name of report
      @param [FannieConfig] $config current configuration
      @param [SQLManager] $connection database connection
      @return [array] report records or [boolean] false
        if this source cannot handle the request
    */
    public function fetchReportData($report_class_name, \FannieConfig $config, \SQLManager $connection)
    {
        $date1 = \FormLib::get_form_value('date1',date('Y-m-d'));
        if ($date1 == date('Y-m-d')) {
            // warehouse cannot handle current day requests
            return false;
        }

        $originalDB = $connection->defaultDatabase();
        $plugin_settings = $config->get('PLUGIN_SETTINGS');
        $connection->selectDB($plugin_settings['WarehouseDatabase']);
        $args = array($this->dateToID($date1));

        $reconciliation = array(
            array('Tenders', 0.0),
            array('Sales', 0.0),
            array('Discounts', 0.0),
            array('Tax', 0.0),
        );

        $prep = $connection->prepare('
            SELECT t.TenderName,
                s.quantity,
                s.total
            FROM sumTendersByDay AS s
                LEFT JOIN ' . $config->get('OP_DB') . $connection->sep() . 'tenders AS t
                    ON s.trans_subtype=t.TenderCode
            WHERE date_id=?
            ORDER BY t.TenderName');
        $res = $connection->execute($prep, $args);
        $tenders = array();
        while ($w = $connection->fetchRow($res)) {
            $tenders[] = array($w['TenderName'], $w['quantity'], $w['total']);
            $reconciliation[0][1] += $w['total'];
        }

        /**
          Always join into department settings twice
          but swap priority depening on user request
        */
        $then_prefix = 'a';
        $now_prefix = 'b';
        if (\FormLib::get('report-departments') == 'Current') {
            $then_prefix = 'b';
            $now_prefix = 'a';
        }
        $prep = $connection->prepare('
            SELECT COALESCE(a.super_name, b.super_name) AS super_name,
                SUM(s.quantity) AS quantity,
                SUM(s.total) AS total
            FROM sumRingSalesByDay AS s
                LEFT JOIN ' . $config->get('OP_DB') . $connection->sep() . 'products AS p
                    ON s.upc=p.upc
                LEFT JOIN ' . $config->get('OP_DB') . $connection->sep() . 'MasterSuperDepts AS ' . $then_prefix . '
                    ON s.department=' . $then_prefix . '.dept_ID
                LEFT JOIN ' . $config->get('OP_DB') . $connection->sep() . 'MasterSuperDepts AS ' . $now_prefix . '
                    ON p.department=' . $now_prefix . '.dept_ID
            WHERE date_id=?
            GROUP BY COALESCE(a.super_name, b.super_name)
            ORDER BY COALESCE(a.super_name, b.super_name)');
        $res = $connection->execute($prep, $args);
        $sales = array();
        while ($w = $connection->fetchRow($res)) {
            $sales[] = array($w['super_name'], $w['quantity'], $w['total']);
            $reconciliation[1][1] += $w['total'];
        }

        $prep = $connection->prepare('
            SELECT m.memDesc,
                s.transCount AS quantity,
                s.total AS total
            FROM sumDiscountsByDay AS s
                LEFT JOIN ' . $config->get('OP_DB') . $connection->sep() . 'memtype AS m
                    ON s.memType=m.memtype
            WHERE s.date_id=?
            ORDER BY m.memDesc');

        $res = $connection->execute($prep, $args);
        $discounts = array();
        while ($w = $connection->fetchRow($res)) {
            $discounts[] = array($w['memDesc'], $w['quantity'], $w['total']);
            $reconciliation[2][1] += $w['total'];
        }

        $dtrans = \DTransactionsModel::selectDTrans($date1);
        $dlog = \DTransactionsModel::selectDlog($date1);
        $dates = array($date1 . ' 00:00:00', $date1 . ' 23:59:59');
        $lineItemQ = $connection->prepare("
            SELECT description,
                SUM(regPrice) AS ttl
            FROM $dtrans AS d
            WHERE datetime BETWEEN ? AND ?
                AND d.upc='TAXLINEITEM'
                AND " . \DTrans::isNotTesting('d') . "
            GROUP BY d.description
        ");
        $lineItemR = $connection->execute($lineItemQ, $dates);
        $taxes = array();
        while ($lineItemW = $connection->fetchRow($lineItemR)) {
            $taxes[] = array($lineItemW['description'] . ' (est. owed)', sprintf('%.2f', $lineItemW['ttl']));
        }

        $taxSumQ = $connection->prepare("SELECT  sum(total) as tax_collected
            FROM $dlog as d 
            WHERE d.tdate BETWEEN ? AND ?
                AND (d.upc = 'tax')
            GROUP BY d.upc");
        $taxR = $connection->execute($taxSumQ,$dates);
        while ($taxW = $connection->fetch_row($taxR)) {
            $taxes[] = array('Total Tax Collected',round($taxW['tax_collected'],2));
            $reconciliation[3][1] += $taxW['tax_collected'];
        }

        $prep = $connection->prepare('
            SELECT m.memDesc,
                COUNT(*) AS numTrans,
                SUM(retailQty + nonRetailQty) AS totalItems,  
                AVG(retailQty + nonRetailQty) AS avgItems,  
                SUM(retailTotal + nonRetailTotal) AS total,
                AVG(retailTotal + nonRetailTotal) AS avg
            FROM transactionSummary AS t
                LEFT JOIN ' . $config->get('OP_DB') . $connection->sep() . 'memtype AS m
                    ON t.memType=m.memtype
            WHERE date_id=?
            GROUP BY m.memDesc
            ORDER BY m.memDesc');
        $res = $connection->execute($prep, $args);
        $transactions = array();
        while ($w = $connection->fetchRow($res)) {
            $transactions[] = array(
                $w['memDesc'],
                $w['numTrans'],
                sprintf('%.2f', $w['totalItems']),
                sprintf('%.2f', $w['avgItems']),
                sprintf('%.2f', $w['total']),
                sprintf('%.2f', $w['avg']),
            );
        }

        $ret = preg_match_all("/[0-9]+/",$config->get('EQUITY_DEPARTMENTS'),$depts);
        $equity = array();
        if ($ret != 0){
            /* equity departments exist */
            $depts = array_pop($depts);
            $dlist = "(";
            foreach ($depts as $d) {
                $dates[] = $d; // add query param
                $dlist .= '?,';
            }
            $dlist = substr($dlist,0,strlen($dlist)-1).")";

            $equityQ = $connection->prepare("
                SELECT d.card_no,
                    t.dept_name, 
                    sum(total) as total 
                FROM $dlog as d
                    INNER JOIN " . $config->get('OP_DB') . $connection->sep() . "departments as t ON d.department = t.dept_no
                WHERE d.tdate BETWEEN ? AND ?
                    AND d.department IN $dlist
                GROUP BY d.card_no, 
                    t.dept_name 
                ORDER BY d.card_no, 
                    t.dept_name");
            $equityR = $connection->execute($equityQ,$dates);
            while ($equityW = $connection->fetchRow($equityR)) {
                $record = array($equityW['card_no'],$equityW['dept_name'],
                        sprintf('%.2f',$equityW['total']));
                $equity[] = $record;
            }
        }

        $connection->setDefaultDB($originalDB);

        return array(
            $tenders,
            $sales,
            $discounts,
            $taxes,
            $reconciliation,
            $transactions,
            $equity,
        );
    }

    /**
      Get list of additional fields, if any,
      that can be used with this data source and
      the specified report
      @param [string] $report_class_name name of report
      @return [array] of CwReportField objects
    */
    public function additionalFields($report_class_name)
    {
        $field = new CoreWarehouse\CwReportField();
        $field->name = 'report-departments';
        $field->label = 'Department settings';
        $field->type = CoreWarehouse\CwReportField::FIELD_TYPE_SELECT;
        $field->options = array('At Time of Sale', 'Current');

        return array($field);
    }
}

}

