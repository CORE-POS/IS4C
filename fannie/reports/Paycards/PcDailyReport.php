<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class PcDailyReport extends FannieReportPage 
{
    public $description = '[Integrated Card Reports] lists all integrated payment card transactions for a given day.';
    public $report_set = 'Tenders';
    public $themed = true;

    protected $report_headers = array('Processor', 'Transaction Type', 
                                    'Sales (#)', 'Sales ($)', 
                                    'Returns (#)', 'Returns ($)', 
                                    'Total (#)', 'Total ($)');
    protected $no_sort_but_style = true;
    protected $sortable = false;
    protected $title = "Fannie : Card Processing Report";
    protected $header = "Card Processing Report";
    protected $required_fields = array();
    protected $no_jquery = true;

    public function report_description_content()
    {
        $ret = array(''); // spacer line
        if ($this->report_format == 'html') {
            $ret[] = $this->form_content();
        }

        return $ret;
    }

    protected function getTransactions($date_id, $store, $processor, $invertReturns=false)
    {
        // voids have trans_id from original dtrans record, not void dtrans record
        $invert = $invertReturns ? "'VOID','Return'" : "'VOID'";
        $mercuryQ = "
            SELECT cardType,
                CASE 
                    WHEN transType='Sale' THEN 'Sales'
                    WHEN transType='Return' THEN 'Returns'
                    WHEN transType='VOID' THEN 'Sales'
                    ELSE 'Unknown'
                END AS transType,
                issuer AS cardIssuer,
                CASE WHEN transType IN ({$invert}) THEN -amount ELSE amount END AS ttl,
                CASE WHEN transType='VOID' THEN -1 ELSE 1 END AS num,
                empNo AS emp,
                registerNo AS reg,
                transNo AS trans,
                CASE WHEN transType='VOID' THEN transID+1 ELSE transID END AS tid,
                paycardTransactionID
            FROM PaycardTransactions
            WHERE dateID=?
                AND httpCode=200
                AND (xResultMessage LIKE '%approve%' OR xResultMessage LIKE '%PENDING%')
                AND xResultMessage not like '%declined%'
                AND empNo <> 9999
                AND registerNo <> 99
                AND processor=?";
        if ($store == 1) {
            $mercuryQ .= ' AND registerNo BETWEEN 1 AND 10 ';
        } elseif ($store == 2) {
            $mercuryQ .= ' AND registerNo BETWEEN 11 AND 20 ';
        }
        $mercuryP = $this->connection->prepare($mercuryQ);
        $mercuryR = $this->connection->execute($mercuryP, array($date_id, $processor));
        $proc = array();
        while($mercuryW = $this->connection->fetchRow($mercuryR)) {
            $pos_trans_id = $mercuryW['emp'].'-'.$mercuryW['reg'].'-'.$mercuryW['trans'].'-'.$mercuryW['tid'];
            $this->integratedIDs[$pos_trans_id] = true;
            $pt_id = $mercuryW['reg'] . '-' . $mercuryW['paycardTransactionID'];
            $this->ptIDs[$pt_id] = true;
            $cardType = $mercuryW['cardType'];
            if (!isset($proc[$cardType])) {
                $proc[$cardType] = array(
                            'Sales' => array('amt'=>0.0, 'num'=>0),
                            'Returns' => array('amt'=>0.0, 'num'=>0),
                            'Details' => array(),
                );
            }
            $transType = $mercuryW['transType'];
            $proc[$cardType][$transType]['amt'] += $mercuryW['ttl'];
            $proc[$cardType][$transType]['num'] += $mercuryW['num'];
            $issuer = $mercuryW['cardIssuer'];
            if (!isset($proc[$cardType]['Details'][$issuer])) {
                $proc[$cardType]['Details'][$issuer] = array(
                    'Sales' => array('amt'=>0.0, 'num'=>0),
                    'Returns' => array('amt'=>0.0, 'num'=>0),
                );
            }
            $proc[$cardType]['Details'][$issuer][$transType]['amt'] += $mercuryW['ttl'];
            $proc[$cardType]['Details'][$issuer][$transType]['num'] += $mercuryW['num'];
        }

        return $proc;
    }

    protected function procToDataset($dataset, $proc, $name)
    {
        foreach ($proc as $type => $info) {
            $record = array($name, 
                        $type, 
                        $info['Sales']['num'],
                            sprintf('%.2f', $info['Sales']['amt']),
                            $info['Returns']['num'],
                            sprintf('%.2f', $info['Returns']['amt']),
                            $info['Sales']['num'] + $info['Returns']['num'],
                            sprintf('%.2f', $info['Sales']['amt'] + $info['Returns']['amt']),
            );
            $record['meta'] = FannieReportPage::META_BOLD;
            $dataset[] = $record;
            foreach($info['Details'] as $issuer => $subinfo) {
                $record = array('', 
                                $issuer, 
                                $subinfo['Sales']['num'],
                                sprintf('%.2f', $subinfo['Sales']['amt']),
                                $subinfo['Returns']['num'],
                                sprintf('%.2f', $subinfo['Returns']['amt']),
                                $subinfo['Sales']['num'] + $subinfo['Returns']['num'],
                                sprintf('%.2f', $subinfo['Sales']['amt'] + $subinfo['Returns']['amt']),
                );
                $dataset[] = $record;
            }
        }

        return $dataset;
    }

    public function fetch_report_data()
    {
        $date_id = date('Ymd', strtotime(FormLib::get('date', date('Y-m-d'))));
        $date_str = date('Y-m-d', strtotime(FormLib::get('date', date('Y-m-d'))));
        $store = FormLib::get('store', false);
        if ($store === false) {
            $store = Store::getIdByIp();
        }

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $dataset = array();
        $this->integratedIDs = array();
        $this->ptIDs = array();

        $proc = $this->getTransactions($date_id, $store, 'MercuryE2E');
        $dataset = $this->procToDataset($dataset, $proc, 'Mercury');

        $proc = $this->getTransactions($date_id, $store, 'GoEMerchant', true);
        $dataset = $this->procToDataset($dataset, $proc, 'FAPS');

        $doubleCheckP = $dbc->prepare("
            SELECT transID
            FROM PaycardTransactions
            WHERE dateID=?
                AND registerNo=?
                AND empNo=?
                AND transNo=?
                AND amount=?
                AND (xResultMessage LIKE '%approved%' OR xResultMessage LIKE '%PENDING%')
                AND xResultMessage not like '%declined%'");

        /** now get POS transaction records and check which are integrated **/
        $dlog = DTransactionsModel::selectDlog(FormLib::get('date', date('Y-m-d')));
        $query = "SELECT
                    CASE WHEN trans_subtype IN ('CC', 'AX') AND description='Debit Card' THEN 'Debit'
                         WHEN trans_subtype IN ('CC', 'AX') AND description<>'Debit Card' THEN 'Credit'
                         WHEN trans_subtype = 'EF' THEN 'EBT Food'
                         WHEN trans_subtype = 'EC' THEN 'EBT Cash'
                         ELSE 'Unknown' END as cardType,
                     CASE 
                        WHEN trans_status='V' and total < 0 THEN 'Returns'
                        WHEN trans_status='V' AND total >= 0 THEN 'Sales'
                        WHEN total < 0 THEN 'Sales' 
                        ELSE 'Returns' 
                     END as transType,
                    'n/a' AS cardIssuer,
                    -total AS ttl,
                    CASE WHEN trans_status='V' THEN -1 ELSE 1 END AS num,
                    trans_num,
                    trans_id,
                    numflag,
                    charflag,
                    emp_no,
                    trans_no,
                    register_no
                  FROM $dlog AS d
                  WHERE tdate BETWEEN ? AND ?
                    AND trans_type = 'T'
                    AND total <> 0
                    AND " . DTrans::isStoreID($store, 'd') . "
                    AND trans_subtype IN ('CC', 'AX', 'EF', 'EC')";
        $prep = $dbc->prepare($query);
        $date = FormLib::get('date', date('Y-m-d'));
        $result = $dbc->execute($prep, array($date.' 00:00:00', $date.' 23:59:59', $store));
        $proc = array();
        while($row = $dbc->fetch_row($result)) {
            $cardType = $row['cardType'];
            if (!isset($proc[$cardType])) {
                $proc[$cardType] = array(
                            'Sales' => array('amt'=>0.0, 'num'=>0),
                            'Returns' => array('amt'=>0.0, 'num'=>0),
                            'Integrated' => array( 
                                'Sales' => array('amt'=>0.0, 'num'=>0),
                                'Returns' => array('amt'=>0.0, 'num'=>0),
                            ),
                            'Non' => array( 
                                'Sales' => array('amt'=>0.0, 'num'=>0),
                                'Returns' => array('amt'=>0.0, 'num'=>0),
                            ),
                );
            }
            $transType = $row['transType'];
            $proc[$cardType][$transType]['amt'] += $row['ttl'];
            $proc[$cardType][$transType]['num'] += $row['num'];
            $pos_trans_id = $row['trans_num'].'-'.$row['trans_id'];
            // ebt trans_id is off by one from fsEligible record
            if ($cardType == 'EBT Food') {
                $pos_trans_id = $row['trans_num'].'-'. ($row['trans_id']-1);
            }
            $pt_id = $row['register_no'] . '-' . $row['numflag'];
            if ($row['charflag'] == 'PT' && isset($this->ptIDs[$pt_id])) {
                $proc[$cardType]['Integrated'][$transType]['amt'] += $row['ttl'];
                $proc[$cardType]['Integrated'][$transType]['num'] += $row['num'];
                $this->integratedIDs[$pos_trans_id] = 'found';
            } elseif (isset($this->integratedIDs[$pos_trans_id])) {
                $proc[$cardType]['Integrated'][$transType]['amt'] += $row['ttl'];
                $proc[$cardType]['Integrated'][$transType]['num'] += $row['num'];
                $this->integratedIDs[$pos_trans_id] = 'found';
            } else {
                $dcR = $dbc->execute($doubleCheckP, array($date_id, $row['register_no'], $row['emp_no'], $row['trans_no'], $row['ttl']));
                if ($dbc->numRows($dcR) === 1 && $row['charflag'] == 'PT') {
                    $dcW = $dbc->fetchRow($dcR);
                    $pos_trans_id = $row['trans_num'] . '-' . $dcW['transID'];
                    $proc[$cardType]['Integrated'][$transType]['amt'] += $row['ttl'];
                    $proc[$cardType]['Integrated'][$transType]['num'] += $row['num'];
                    $this->integratedIDs[$pos_trans_id] = 'found';
                } else {
                    $proc[$cardType]['Non'][$transType]['amt'] += $row['ttl'];
                    $proc[$cardType]['Non'][$transType]['num'] += $row['num'];
                }
            }
        }
        foreach($proc as $type => $info) {
            $non = $info['Non'];
            if ($non['Sales']['amt'] == 0 && $non['Returns']['amt'] == 0) {
                continue;
            }
            $record = array('NON-INTEGRATED', 
                            $type,
                            $non['Sales']['num'],
                            sprintf('%.2f', $non['Sales']['amt']),
                            $non['Returns']['num'],
                            sprintf('%.2f', $non['Returns']['amt']),
                            $non['Sales']['num'] + $non['Returns']['num'],
                            sprintf('%.2f', $non['Sales']['amt'] + $non['Returns']['amt']),
            );
            $record['meta'] = FannieReportPage::META_BOLD;
            $dataset[] = $record;
        }

        $dataset[] = array('meta'=>FannieReportPage::META_BLANK);
        foreach ($this->integratedIDs as $pos_trans_id => $found) {
            if ($found === true) {
                $trans = rtrim($pos_trans_id, '0123456789');
                $trans = rtrim($trans, '-');
                $dataset[] = array(
                    'Suspect Transaction',
                    $date_str,
                    $trans,
                    $pos_trans_id,
                );
            }
        }

        $dataset[] = array('meta'=>FannieReportPage::META_BLANK);

        foreach($proc as $type => $info) {
            $record = array('POS Total', 
                            $type, 
                            $info['Sales']['num'],
                            sprintf('%.2f', $info['Sales']['amt']),
                            $info['Returns']['num'],
                            sprintf('%.2f', $info['Returns']['amt']),
                            $info['Sales']['num'] + $info['Returns']['num'],
                            sprintf('%.2f', $info['Sales']['amt'] + $info['Returns']['amt']),
            );
            $record['meta'] = FannieReportPage::META_BOLD;
            $dataset[] = $record;
            $int = $info['Integrated'];
            $record = array('', 
                            'Integrated',
                            $int['Sales']['num'],
                            sprintf('%.2f', $int['Sales']['amt']),
                            $int['Returns']['num'],
                            sprintf('%.2f', $int['Returns']['amt']),
                            $int['Sales']['num'] + $int['Returns']['num'],
                            sprintf('%.2f', $info['Sales']['amt'] + $int['Returns']['amt']),
            );
            $dataset[] = $record;
            $non = $info['Non'];
            $record = array('', 
                            'Non-Integrated',
                            $non['Sales']['num'],
                            sprintf('%.2f', $non['Sales']['amt']),
                            $non['Returns']['num'],
                            sprintf('%.2f', $non['Returns']['amt']),
                            $non['Sales']['num'] + $non['Returns']['num'],
                            sprintf('%.2f', $non['Sales']['amt'] + $non['Returns']['amt']),
            );
            $dataset[] = $record;
        }

        $dataset[] = array('meta'=>FannieReportPage::META_BLANK);

        return $dataset;
    }

    public function calculate_footers($data) {
        $pN = 0;
        $pS = 0.0;
        $tN = 0;
        $tS = 0.0;
        foreach($data as $row) {
            if (!isset($row['meta']) || $row['meta'] != FannieReportPage::META_BOLD) {
                continue;
            }
            if ($row[0] == 'POS Total') {
                $tN += $row[6];
                $tS += $row[7];
            } else {
                $pN += $row[6];
                $pS += $row[7];
            }
        }

        return array(
            '',
            'Submitted Total',
            $pN,
            sprintf('%.2f', $pS),
            '',
            'Tendered Total',
            $tN,
            sprintf('%.2f', $tS),
        );
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#date\').datepicker({dateFormat:\'yy-mm-dd\'});');
        $date = FormLib::get('date', date('Y-m-d'));
        $stores = FormLib::storePicker();
        return '<form method="get" action="PcDailyReport.php">
            <div class="col-sm-6">
            <div class="row form-group form-inline">
            <label>Change Date</label> <input type="text" name="date" id="date" 
                value="' . $date . '" class="form-control" required />
            ' . $stores['html'] . '
            <button type="submit" class="btn btn-default">Get Report</button>
            <a href="PcMonthlyReport.php">Switch to Monthly</a>
            </div>
            </div>
            </form>';
    }

}

FannieDispatch::conditionalExec();

