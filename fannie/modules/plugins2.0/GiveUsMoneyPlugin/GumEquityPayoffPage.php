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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class GumEquityPayoffPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Equity Payoff] generates a check and statement for buying back equity shares.';

    public function preprocess()
    {
        $this->header = 'Class C Payoff';
        $this->title = 'Class C Payoff';
        $this->__routes[] = 'get<id><pdf>';

        return parent::preprocess();
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $this->payoff = new GumEquitySharesModel($dbc);
        $this->payoff->gumEquityShareID($this->id);
        if (!$this->payoff->load()) {
            echo _('Error: payoff') . ' #' . $this->id . ' ' . _('does not exist');
            return false;
        }

        $this->all = new GumEquitySharesModel($dbc);
        $this->all->card_no($this->payoff->card_no());

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->payoff->card_no());
        $this->meminfo = $bridge::getMeminfo($this->payoff->card_no());

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $this->taxid = new GumTaxIdentifiersModel($dbc);
        $this->taxid->card_no($this->payoff->card_no());

        $this->check_info = new GumPayoffsModel($dbc);
        $map = new GumEquityPayoffMapModel($dbc);
        $map->gumEquityShareID($this->id);
        $payoff_id = false;
        foreach($map->find('gumPayoffID', true) as $obj) {
            // get highest matching ID
            $payoff_id = $obj->gumPayoffID();
            break;
        }
        // none found, allocate new check
        if ($payoff_id === false) {
            $payoff_id = GumLib::allocateCheck($map);
            if ($payoff_id) {
                $this->check_info->gumPayoffID($payoff_id);
                $this->check_info->amount(-1*$this->payoff->value());
                $this->check_info->issueDate(date('Y-m-d'));
                $this->check_info->save();
                $this->check_info->load();
            }
        } else {
            $this->check_info->gumPayoffID($payoff_id);
            $this->check_info->load();
        }

        $this->settings = new GumSettingsModel($dbc);

        return true;
    }

    public function get_id_pdf_handler()
    {
        global $FANNIE_ROOT;
        if (!class_exists('FPDF')) {
            include($FANNIE_ROOT.'src/fpdf/fpdf.php');
            define('FPDF_FONTPATH','font/');
        }

        $this->get_id_handler(); // load models

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pdf->SetXY(0, 0);
        $pdf->Image('img/letterhead.png', null, null, 203); // scale to 8"

        $pdf->SetFont('Arial', '', 10);
        $line_height = 5;
        $pdf->SetXY(6.35, 50);
        $pdf->Write($line_height, 'Based on the terms of Wholefoods Community Coop Class C Stock you Payout Request has been received and approved by our Board of Directors. Please find attached a schedule of your most recent Class C activity as well as a check for the class C payout. Thank you for your continued support.');
        $pdf->Ln();
        $pdf->Ln();

        $y = $pdf->GetY();
        $col_width = 30;
        $col1 = 60;
        $col2 = $col1 + $col_width;
        $col3 = $col2 + $col_width;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width * 3, $line_height, 'Class C Schedule', 0, 0, 'C');
        $y += $line_height;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'Date', 0, 0, 'C');
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, 'Shares', 0, 0, 'C');
        $pdf->SetXY($col3, $y);
        $pdf->Cell($col_width, $line_height, 'Total', 0, 0, 'C');
        $y += $line_height;

        foreach($this->all->find('tdate') as $obj) {
            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, date('m/d/Y', strtotime($obj->tdate())), 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, $obj->shares(), 0, 0, 'C');
            $pdf->SetXY($col3, $y);
            $pdf->Cell($col_width, $line_height, $obj->value(), 0, 0, 'C');
            $y += $line_height;

            if ($obj->gumEquityShareID() == $this->id) {
                break;
            }
        }

        $check = new GumCheckTemplate($this->custdata, $this->meminfo, $this->payoff->value()*-1, 'Class C Payout', $this->check_info->checkNumber());
        $check->renderAsPDF($pdf);

        $pdf->Output('EquityPayoff.pdf', 'I');

        if (FormLib::get('issued') == '1') {
            $this->check_info->checkIssued(1);
            $this->check_info->issueDate(date('Y-m-d H:i:s'));
            $this->check_info->save();
        }

        return false;
    }

    public function css_content()
    {
        return '
            table#infoTable td {
                text-align: center;
                padding: 0 40px 0 40px;
            }
            table #infoTable {
                margin-left: auto;
                margin-right: auto;
            }
            table#infoTable tr.red td {
                color: red;
            }
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $ret .= '<input onclick="location=\'GumEquityPayoffPage.php?id='.$this->id.'&pdf=1\'; return false;"
                    type="button" value="Print" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= sprintf('<label for="issueCheckbox">Check has been issued</label> 
                        <input type="checkbox" onclick="return issueWarning();"
                        onchange="issueCheck(\'%s\');" id="issueCheckbox" %s />
                        <span id="issueDate">%s</span>',
                        $this->id,
                        ($this->check_info->checkIssued() ? 'checked disabled' : ''),
                        ($this->check_info->checkIssued() ? $this->check_info->issueDate() : '')
        );
        $this->add_script('js/equity_payoff.js');

        if (file_exists('img/letterhead.png')) {
            $ret .= '<img src="img/letterhead.png" style="width: 100%;" />';
        }

        $ret .= '<p>
            Based on the terms of Wholefoods Community Coop Class C Stock you Payout Request has been received and approved by  our Board of Directors.  Please find attached a schedule of your most recent Class C activity as well as a check for the class C payout.  Thankyou for your continued support.  
            </p>';

        $ret .= '<table id="infoTable" cellspacing="0" cellpadding="4">';
        $ret .= '<tr><td colspan="3">Class C Schedule</td></tr>';
        $ret .= '<tr><td>Date</td><td>Shares</td><td>Total</td></tr>';
        foreach($this->all->find('tdate') as $obj) {
            $ret .= sprintf('<tr class="%s">
                            <td>%s</td>
                            <td>%d</td>
                            <td>%s</td>
                            </tr>',
                            $obj->shares() < 0 ? 'red' : '',
                            date('m/d/Y', strtotime($obj->tdate())),
                            $obj->shares(),
                            number_format($obj->value(), 2)
            );
            if ($obj->gumEquityShareID() == $this->id) {
                break;
            }
        }
        $ret .= '</table>';

        $ret .= '<hr />';
        $check = new GumCheckTemplate($this->custdata, $this->meminfo, $this->payoff->value()*-1, 'Class C Payout', $this->check_info->checkNumber());
        $ret .= $check->renderAsHTML();

        return $ret;
    }
}

FannieDispatch::conditionalExec();

