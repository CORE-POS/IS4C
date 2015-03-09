<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class GumIssueDividendPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Issue Dividend] creates a dividend entry based on equity held.';

    function preprocess()
    {
        $this->header = 'Issue Dividend';
        $this->title = 'Issue Dividend';
        $this->__routes[] = 'post<endDate><rate>';
        $this->__routes[] = 'post<confirmEndDate><confirmRate>';
        $this->__routes[] = 'get<done>';
        $this->__routes[] = 'get<printFY>';

        return parent::preprocess();
    }

    function get_printFY_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_ROOT;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        if (!class_exists('FPDF')) {
            include($FANNIE_ROOT.'src/fpdf/fpdf.php');
            define('FPDF_FONTPATH','font/');
        }

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);

        $map = new GumDividendPayoffMapModel($dbc);
        $bridge = GumLib::getSetting('posLayer');
        $dividends = new GumDividendsModel($dbc);
        $dividends->yearEndDate($this->printFY);
        $acc = array();
        $prevCN = -1;
        $combined = array();
        foreach ($dividends->find('card_no') as $dividend) {
            if (!isset($combined[$dividend->card_no()])) {
                $combined[$dividend->card_no()] = array();
            }
            $combined[$dividend->card_no()][] = $dividend;
        }

        foreach ($combined as $card_no => $acc) {
            if (!empty($acc)) {
                $ttl = 0.0;
                // roll up totals to a single amount
                foreach ($acc as $a) {
                    $ttl += $a->dividendAmount();
                }

                // lookup check
                $map->reset();
                $map->gumDividendID($acc[0]->gumDividendID());
                $checkID = false;
                foreach ($map->find('gumPayoffID', true) as $obj) {
                    $checkID = $obj->gumPayoffID();
                    break;
                }

                if ($checkID) {
                    $check = new GumPayoffsModel($dbc);
                    $check->gumPayoffID($checkID);
                    $check->load();
                } else {
                    // allocate a new check if needed
                    $checkID = GumLib::allocateCheck($map);
                    $check = new GumPayoffsModel($dbc);
                    $check->gumPayoffID($checkID);
                    $check->load();
                    $check->amount($ttl);
                    $check->save();
                    // map rolled up dividends to same check
                    foreach ($acc as $a) {
                        $map->gumDividendID($a->gumDividendID());
                        $map->gumPayoffID($checkID);
                        $map->save();
                    }
                }
                $pdf->AddPage();

                $custdata = $bridge::getCustdata($card_no);
                $meminfo = $bridge::getMeminfo($card_no);
                // bridge may change selected database
                $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

                $template = new GumCheckTemplate($custdata, $meminfo, $ttl, 'Dividend Payment', $check->checkNumber());
                $template->renderAsPDF($pdf);

                $check->checkIssued(1);
                $check->issueDate(date('Y-m-d H:i:s'));
                $check->save();

                $pdf->Image('img/new_letterhead.png', 10, 10, 50);

                if (!isset($pdf->fonts['gillsansmtpro-book'])) {
                    $pdf->AddFont('GillSansMTPro-Book', '', 'GillSansMTPro-Book.php');
                }
                $pdf->SetFont('GillSansMTPro-Book', '', 11);

                $l = 65;
                $pdf->SetXY($l, 20);
                $pdf->Cell(0, 5, date('j F Y'), 0, 1);
                $pdf->Ln(15);
                $pdf->SetX($l);
                $pdf->Cell(0, 5, 'Dear Owner:', 0, 1);
                $pdf->Ln(5);
                $pdf->SetX($l);
                $pdf->MultiCell(135, 5, 'Based on the Co-op\'s profitability in Fiscal Year 2014 (July 1, 2013-June 30, 2014), the Board of Directors approved a four percent (4%) dividend on your Class C equity investment. Your dividend is pro-rated based on when you made your investment during that fiscal year. As this check represents an annual return on your investment, the amount cannot be compounded.');
                $pdf->Ln(5);
                $pdf->SetX($l);
                $pdf->MultiCell(135, 5, 'You are welcome to cash your check toward a purchase at the Co-op. Thank you for investing in Whole Foods Co-op');
                $pdf->Ln(10);
                $pdf->SetX($l);
                $pdf->Cell(0, 5, 'Thank you,', 0, 1);
                $pdf->Ln(20);
                $pdf->SetX($l);
                $pdf->Cell(0, 5, 'Sharon Murphy', 0, 1);
                $pdf->Ln(5);
                $pdf->SetX($l);
                $pdf->Cell(0, 5, 'General Manager', 0, 1);

                $pdf->Image('img/sig.png', $l, 100, 63.5);
            }
        }

        $pdf->Output('Dividends.pdf', 'I');

        return false;
    }

    function get_done_view()
    {
        return '
            Dividend Issued!
        ';
    }

    function post_confirmEndDate_confirmRate_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        
        $num_days = $this->fyLength($this->confirmEndDate);
        $info = $this->calculateHoldings($this->confirmEndDate);

        $model = new GumDividendsModel($dbc);
        foreach ($info as $cn => $shares) {
            foreach ($shares as $days => $value) {
                $model->reset();
                $model->card_no($cn);
                $model->yearEndDate($this->confirmEndDate);
                $model->equityAmount($value);
                $model->daysHeld($days);
                $model->dividendRate($this->confirmRate / 100.0);
                $dividend = $value * ($this->confirmRate/100.0) * ($days/((float)$num_days));
                $model->dividendAmount($dividend);
                $model->save();
            }
        }

        header('Location: GumIssueDividendPage.php?done=1');

        return false;
    }

    function post_endDate_rate_view()
    {
        $info = $this->calculateHoldings($this->endDate);
        $ret = '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>#</th><th>Days</th><th>Equity</th><th>Dividend</td></tr>';
        $num_days = $this->fyLength($this->endDate);
        foreach ($info as $cn => $shares) {
            foreach ($shares as $days => $value) {
                $ret .= '<tr><td>' . $cn . '</td>';
                $ret .= '<td>' . $days . '</td>';
                $ret .= '<td>' . number_format($value, 2) . '</td>';
                $dividend = $value * ($this->rate/100.0) * ($days/((float)$num_days));
                $ret .= '<td>' . number_format($dividend, 2) . '</td>';
                $ret .= '</tr>';
            }
        }
        $ret .= '</table>';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<input type="hidden" name="confirmRate" value="' . $this->rate . '" />';
        $ret .= '<input type="hidden" name="confirmEndDate" value="' . $this->endDate . '" />';
        $ret .= '<input type="submit" name="confirm" value="Issue Dividend" />';
        $ret .= '</form>';
        
        return $ret; 
    }

    private function fyLength($endDate)
    {
        $end = strtotime($endDate);
        $endDT = new DateTime(date('Y-m-d', $end));
        $start = mktime(0, 0, 0, date('n', $end), date('j', $end)+1, date('Y', $end)-1);
        $startDT = new DateTime(date('Y-m-d', $start));
        $num_days = $startDT->diff($endDT)->format('%r%a') + 1;

        return $num_days;
    }

    /**
      Determine how long shares were held
      @param $endDate [string] end of fiscal year
      @return [keyed array]
        card_no => [keyed array]
            days_held => share value
    */
    private function calculateHoldings($endDate)
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        
        $end = strtotime($endDate);
        $endDT = new DateTime(date('Y-m-d', $end));
        $start = mktime(0, 0, 0, date('n', $end), date('j', $end)+1, date('Y', $end)-1);
        $startDT = new DateTime(date('Y-m-d', $start));
        $num_days = $this->fyLength($endDate);

        $shares = new GumEquitySharesModel($dbc);
        // accumulate per-member purchases and payoffs
        // in date order
        $buys = array();
        $sells = array();
        foreach ($shares->find(array('card_no', 'tdate')) as $share) {
            $cn = $share->card_no();
            if (!isset($buys[$cn])) {
                $buys[$cn] = array();
            }
            if (!isset($sells[$cn])) {
                $sells[$cn] = array();
            }
            if ($share->shares() > 0) {
                $buys[$cn][] = $share;
            } else {
                $sells[$cn][] = $share;
            }
        }

        // go through payoffs and decrement oldest
        // purchases
        foreach ($sells as $cn => $sales) {
            foreach ($sales as $sale) {
                $num_sold = abs($sale->shares());
                $amt_sold = abs($sale->value());
                while ($num_sold > 0) {
                    for ($i=0; $i<count($buys[$cn]); $i++) {
                        if ($num_sold > $buys[$cn][$i]->shares()) {
                            $num_sold -= $buys[$cn][$i]->shares();
                            $buys[$cn][$i]->shares(0);
                            $amt_sold -= $buys[$cn][$i]->value();
                            $buys[$cn][$i]->value(0);
                        } else {
                            $buys[$cn][$i]->shares($buys[$cn][$i]->shares() - $num_sold);
                            $num_sold = 0;
                            $buys[$cn][$i]->value($buys[$cn][$i]->value() - $amt_sold);
                            $amt_sold = 0;
                            break;
                        }
                    }
                    // if number sold isn't zero after
                    // examining all purchases, there's a data
                    // problem. force while loop to end.
                    $num_sold = 0;
                }
            }
        }

        $holdings = array();
        // calculate days held for each share
        // combine shares held an equal number of days
        // (e.g., a full year) into one record
        foreach ($buys as $cn => $shares) {
            $holdings[$cn] = array();
            foreach ($shares as $share) {
                $purchased = strtotime($share->tdate());
                $tempDT = new DateTime(date('Y-m-d', $purchased));
                $held = $num_days;
                if ($startDT->diff($tempDT)->format('%r%a') > 0) {
                    $held = $tempDT->diff($endDT)->format('%r%a') + 1;
                    if ($held <= 0) {
                        continue;
                    }
                }
                if (!isset($holdings[$cn][$held])) {
                    $holdings[$cn][$held] = 0.0;
                }
                $holdings[$cn][$held] += $share->value();
            }
        }

        return $holdings;
    }

    function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        ob_start();
        ?>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <h3>Allocate New Dividend</h3>
        <table>
            <tr>
                <th>FY End Date</th>
                <td><input type="text" name="endDate" id="endDate" /></td>
            </tr>
            <tr>
                <th>Interest Rate</th>
                <td><input type="text" name="rate" size="5" />%</td>
            </tr>
            <tr>
                <td colspan="2">
                <input type="submit" value="Preview" name="preview" />
                </td>
            </tr>
        </table>
        </form>
        <hr />
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <h3>Print Checks</h3>
        FY Ending: <select name="printFY">
        <?php

        $years = $dbc->query('SELECT yearEndDate
                              FROM GumDividends
                              GROUP BY yearEndDate
                              ORDER BY yearEndDate DESC');
        while ($row = $dbc->fetch_row($years)) {
            printf('<option>%s</option>',
                date('Y-m-d', strtotime($row['yearEndDate'])));
        }
        ?>
        </select>
        <input type="submit" name="print" value="Print Checks" />
        </form>
        <?php

        $this->add_onload_command('$(\'#endDate\').datepicker();');

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();
