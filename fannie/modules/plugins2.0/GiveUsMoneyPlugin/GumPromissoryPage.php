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
class GumPromissoryPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Promissory Note] creates document to accompany loan paperwork.';

    private $paragraphs = array(

'For value received, the Borrower, a Minnesota cooperative corporation, hereby promises to pay the Lender, a current Owner of the Borrower, whose address is indicated above, or his or her successors, the principal sum indicated above together with interest thereon at the interest rate indicated above .  Interest shall be calculated and compounded annually.   Upon maturity of this Note on the date set forth above, interest and principal shall be paid in full.',

'There shall be no penalty for prepayment or early payment of this Note by the Borrower. Lender may request that Borrower prepay this Note before the Maturity Date. If he/she does so, Borrower may, in its sole discretion, prepay the Note provided that, if the Borrower does so, it shall discount the amount to be paid by six (6) months of interest if the prepayment request is made at any time prior to twelve (12) months before the Maturity Date or, if the request is made within twelve (12) months of the Maturity Date, the discount shall be three (3) months of interest.',

'All payments shall be made to the address of the Lender set forth above.  It is the responsibility of the Lender to inform the Borrower of any change in address.',

'Lender understands that there are other loans made to the Borrower that have a security interest in the assets of the cooperative and that are superior to the Note of the Lender.  Lender understands that there are unsecured creditors and other lenders to the cooperative that have interests that may be superior to that of the Lender.',

'Borrower shall be in default if it fails to make prompt payment of this Note and the compound interest thereon as of the above maturity date.  The Lender may proceed to enforce payment of the indebtedness and to exercise any or all rights afforded to the Lender under the law.',

'Lender may at his/her discretion waive any of the terms and conditions of this Note, including the final Maturity Date of the Note without the Borrower completing an amendment to this Note. However, no waiver of one part of this Note shall operate as a waiver of any other term or condition of this Note or of the same part of this Note on a future occasion.',

    );

    private $my_address = array();

    public function preprocess()
    {
        $acct = FormLib::get('id');
        $this->header = 'Promissory Note' . ' : ' . $acct;
        $this->title = 'Promissory Note' . ' : ' . $acct;
        $this->__routes[] = 'get<id><pdf>';

        return parent::preprocess();
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $this->loan = new GumLoanAccountsModel($dbc);
        $this->loan->accountNumber($this->id);
        if (!$this->loan->load()) {
            echo _('Error: account') . ' ' . $this->id . ' ' . _('does not exist');
            return false;
        }

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->loan->card_no());
        $this->meminfo = $bridge::getMeminfo($this->loan->card_no());

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $this->taxid = new GumTaxIdentifiersModel($dbc);
        $this->taxid->card_no($this->loan->card_no());

        $this->settings = new GumSettingsModel($dbc);

        $this->my_address[0] = 'Name of Co-op';
        $this->settings->key('storeName');
        if ($this->settings->load()) {
            $this->my_address[0] = $this->settings->value();
        }
        $this->my_address[1] = 'Street Address';
        $this->settings->key('storeAddress');
        if ($this->settings->load()) {
            $this->my_address[1] = $this->settings->value();
        }
        $this->my_address[2] = '';
        $this->settings->key('storeCity');
        if ($this->settings->load()) {
            $this->my_address[2] .= $this->settings->value() . ', ';
        } else {
            $this->my_address[2] .= 'City, ';
        }
        $this->settings->key('storeState');
        if ($this->settings->load()) {
            $this->my_address[2] .= $this->settings->value() . ' ';
        } else {
            $this->my_address[2] .= 'XX ';
        }
        $this->settings->key('storeZip');
        if ($this->settings->load()) {
            $this->my_address[2] .= $this->settings->value();
        } else {
            $this->my_address[2] .= '12345';
        }

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

        for($i=0; $i<2; $i++) {
            $pdf->AddPage();

            $pdf->SetXY(0, 0);
            $pdf->Image('img/letterhead.png', null, null, 203); // scale to 8"

            $pdf->SetFont('Arial', '', 10);
            $line_height = 5;
            $start_y = 43;
            $pdf->SetXY(6.35, $start_y);

            $col_width = 101.6;
            $col1 = 6.35;
            $col2 = $col1 + $col_width;
            $y = $start_y;

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, 'Lender', 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, 'Borrower', 0, 0, 'C');
            $y += $line_height;
            $pdf->SetFont('Arial', '', 10);

            if (strlen($this->custdata->FirstName()) + strlen($this->custdata->LastName()) > 50) {
                $pdf->SetFont('Arial', '', 8);
            }
            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, $this->custdata->FirstName() . ' ' . $this->custdata->LastName(), 0, 0, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, $this->my_address[0], 0, 0, 'C');
            $y += $line_height;

            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, $this->meminfo->street(), 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, $this->my_address[1], 0, 0, 'C');
            $y += $line_height;

            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, $this->meminfo->city() . ', ' . $this->meminfo->state() . ' ' . $this->meminfo->zip(), 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, $this->my_address[2], 0, 0, 'C');
            $y += $line_height;

            $pdf->SetXY($col1, $y);
            $ssn = 'Unknown';
            if ($this->taxid->load()) {
                $ssn = 'xxx-xx-' . $this->taxid->maskedTaxIdentifier();
            }
            $pdf->Cell($col_width, $line_height, $ssn, 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $myid = GumLib::getSetting('storeFederalID', 'xx-xxxxxxx');
            $pdf->Cell($col_width, $line_height, $myid, 0, 0, 'C');
            $y += $line_height;

            $y += $line_height; // spacer

            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, 'Owner #: ' . $this->loan->card_no(), 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, 'Account #: ' . $this->loan->accountNumber(), 0, 0, 'C');
            $y += $line_height;

            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, 'Loan Date: ' . date('m/d/Y', strtotime($this->loan->loanDate())), 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, 'Interest Rate: ' . number_format($this->loan->interestRate()*100, 2) . '%', 0, 0, 'C');
            $y += $line_height;

            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, 'Principal Sum: ' . number_format($this->loan->principal(), 2), 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $ld = strtotime($this->loan->loanDate());
            $ed = mktime(0, 0, 0, date('n', $ld)+$this->loan->termInMonths(), date('j', $ld), date('Y', $ld));
            $pdf->Cell($col_width, $line_height, 'Maturity Date: ' . date('m/d/Y', $ed), 0, 0, 'C');
            $y += $line_height;

            $y += $line_height; // spacer
            $page_text = '';
            foreach($this->paragraphs as $p) {
                $page_text .= $p . "\n\n";
            }
            $pdf->SetXY($col1, $y);
            $pdf->Write($line_height, $page_text);

            $sig_y = 225.9;
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY($col1, $sig_y);
            $pdf->Cell($col_width, $line_height, 'Lender Signature', 0, 0, 'C');
            $pdf->SetXY($col2, $sig_y);
            $pdf->Cell($col_width, $line_height, 'Borrower Signature', 0, 0, 'C');

            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Line($col1, $start_y, 203.2, $start_y);
            $pdf->Line($col1, $start_y, $col1, $start_y + 5*$line_height);
            $pdf->Line($col2, $start_y, $col2, $start_y + 5*$line_height);
            $pdf->Line(203.2, $start_y, 203.2, $start_y + 5*$line_height);
            $pdf->Line($col1, $start_y + 5*$line_height, 203.2, $start_y + 5*$line_height);

            $pdf->Line($col1, $start_y + 6*$line_height, 203.2, $start_y + 6*$line_height);
            $pdf->Line($col1, $start_y + 9*$line_height, 203.2, $start_y + 9*$line_height);
            $pdf->Line($col1, $start_y + 6*$line_height, $col1, $start_y + 9*$line_height);
            $pdf->Line(203.2, $start_y + 6*$line_height, 203.2, $start_y + 9*$line_height);

            $sig_h = 28.1;
            $pdf->Line($col1, $sig_y, 203.2, $sig_y);
            $pdf->Line($col1, $sig_y + $sig_h, 203.2, $sig_y + $sig_h);
            $pdf->Line($col1, $sig_y, $col1, $sig_y + $sig_h);
            $pdf->Line($col2, $sig_y, $col2, $sig_y + $sig_h);
            $pdf->Line(203.2, $sig_y, 203.2, $sig_y + $sig_h);
        }

        $pdf->AddPage();

        // Add loan schedule page
        $pdf->SetXY(0, 0);
        $pdf->Image('img/letterhead.png', null, null, 203); // scale to 8"

        $pdf->SetFont('Arial', '', 10);
        $line_height = 5;
        $start_y = 43;

        $col_width = 101.6;
        $col1 = 6.35;
        $col2 = $col1 + $col_width;
        $y = $start_y;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'First Name: ' . $this->custdata->FirstName(), 0, 0, 'C');
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, 'Social Security Number: xxx-xx-' . $this->taxid->maskedTaxIdentifier(), 0, 0, 'C');
        $y += $line_height;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'Last Name: ' . $this->custdata->LastName(), 0, 0, 'C');
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, 'Loan Amount: ' . number_format($this->loan->principal(), 2), 0, 0, 'C');
        $y += $line_height;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'Address: ' . $this->meminfo->street(), 0, 0, 'C');
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, 'Loan Date: ' . date('m/d/Y', strtotime($this->loan->loanDate())), 0, 0, 'C');
        $y += $line_height;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'City: ' . $this->meminfo->city(), 0, 0, 'C');
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, 'Term: ' . ($this->loan->termInMonths() / 12), 0, 0, 'C');
        $y += $line_height;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'State: ' . $this->meminfo->state(), 0, 0, 'C');
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, 'Interest Rate: ' . number_format($this->loan->interestRate() * 100, 2).'%', 0, 0, 'C');
        $y += $line_height;

        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'Zip Code: ' . $this->meminfo->zip(), 0, 0, 'C');
        $pdf->SetXY($col2, $y);
        $ld = strtotime($this->loan->loanDate());
        $ed = mktime(0, 0, 0, date('n', $ld)+$this->loan->termInMonths(), date('j', $ld), date('Y', $ld));
        $pdf->Cell($col_width, $line_height, 'Maturity Date: ' . date('m/d/Y', $ed), 0, 0, 'C');
        $y += $line_height;

        $y += $line_height;
        $col_width = $col_width / 2;
        $col2 = $col1 + $col_width;
        $col3 = $col2 + $col_width;
        $col4 = $col3 + $col_width;

        $pdf->SetXY($col1, $y);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(0xff, 0xff, 0xff);
        $pdf->Cell($col_width*4, $line_height, 'Schedule', 1, 0, 'C', true);
        $y += $line_height;

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(0xcc, 0xcc, 0xcc);
        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'Year Ending', 0, 0, 'C', true);
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, 'Days', 0, 0, 'C', true);
        $pdf->SetXY($col3, $y);
        $pdf->Cell($col_width, $line_height, 'Interest', 0, 0, 'C', true);
        $pdf->SetXY($col4, $y);
        $pdf->Cell($col_width, $line_height, 'Balance', 0, 0, 'C', true);
        $y += $line_height;

        $pdf->SetFont('Arial', '', 10);
        $loan_info = GumLib::loanSchedule($this->loan);
        foreach($loan_info['schedule'] as $period) {
            $pdf->SetXY($col1, $y);
            $pdf->Cell($col_width, $line_height, $period['end_date'], 0, 0, 'C');
            $pdf->SetXY($col2, $y);
            $pdf->Cell($col_width, $line_height, $period['days'], 0, 0, 'C');
            $pdf->SetXY($col3, $y);
            $pdf->Cell($col_width, $line_height, number_format($period['interest'], 2), 0, 0, 'R');
            $pdf->SetXY($col4, $y);
            $pdf->Cell($col_width, $line_height, number_format($period['balance'], 2), 0, 0, 'R');
            $y += $line_height;
        }
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($col1, $y);
        $pdf->Cell($col_width, $line_height, 'Balance', 0, 0, 'C', true);
        $pdf->SetXY($col2, $y);
        $pdf->Cell($col_width, $line_height, number_format($this->loan->principal(), 2), 0, 0, 'C', true);
        $pdf->SetXY($col3, $y);
        $pdf->Cell($col_width, $line_height, number_format($loan_info['total_interest'], 2), 0, 0, 'R', true);
        $pdf->SetXY($col4, $y);
        $pdf->Cell($col_width, $line_height, number_format($loan_info['balance'], 2), 0, 0, 'R', true);
        $y += $line_height;

        $pdf->Output('PromissoryNote.pdf', 'I');

        return false;
    }

    public function css_content()
    {
        return '
            table#infoTable td {
                text-align: center;
            }
            table#infoTable td.header {
                font-weight: bold;
            }
            table#infoTable td.top {
                border-top: solid 1px black;
            }
            table#infoTable td.left {
                border-left: solid 1px black;
            }
            table#infoTable td.right {
                border-right: solid 1px black;
            }
            table#infoTable td.bottom {
                border-bottom: solid 1px black;
            }
            table#infoTable td.noborder {
                border: 0;
                line-height: 2px;
            }
            table#infoTable td.paragraph {
                border: 0;
                text-align: left;
            }
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $ret .= '<input onclick="location=\'GumPromissoryPage.php?id='.$this->id.'&pdf=1\'; return false;"
                    type="button" value="Print" /><br />';

        if (file_exists('img/letterhead.png')) {
            $ret .= '<img src="img/letterhead.png" style="width: 100%;" />';
        }

        $ret .= '<table id="infoTable" cellspacing="0" cellpadding="4">';
        $ret .= '<tr>';
        $ret .= '<td class="header top left right">Lender</td>';
        $ret .= '<td class="header top right">Borrower</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right">'. $this->custdata->FirstName() . ' ' . $this->custdata->LastName() . '</td>';
        $ret .= '<td class="right">' . $this->my_address[0] .'</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right">'. $this->meminfo->street() . '</td>';
        $ret .= '<td class="right">' . $this->my_address[1] .'</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right">'. $this->meminfo->city() . ', ' . $this->meminfo->state() . ' ' . $this->meminfo->zip() . '</td>';
        $ret .= '<td class="right">' . $this->my_address[2] .'</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ssn = 'Unknown';
        if ($this->taxid->load()) {
            $ssn = 'xxx-xx-' . $this->taxid->maskedTaxIdentifier();
        }
        $ret .= '<td class="left right bottom">' . $ssn . '</td>';
        $tax_id = GumLib::getSetting('storeFederalID', 'xx-xxxxxxx');
        $ret .= '<td class="right bottom">' . $tax_id . '</td>';
        $ret .= '</tr>';
        $ret .= '<tr><td class="noborder" colspan="2">&nbsp;</td></tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left top">Owner #: ' . $this->loan->card_no() . '</td>';
        $ret .= '<td class="right top">Account #: ' . $this->loan->accountNumber() . '</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left">Loan Date: ' . date('m/d/Y', strtotime($this->loan->loanDate())) . '</td>';
        $ret .= '<td class="right">Interest Rate: ' . number_format($this->loan->interestRate() * 100, 2) . '%</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left bottom">Principal Sum: $' . number_format($this->loan->principal(), 2) . '</td>';
        $ld = strtotime($this->loan->loanDate());
        $ret .= '<td class="right bottom">Maturity Date: ' . date('m/d/Y', mktime(0, 0, 0, date('n', $ld)+$this->loan->termInMonths(), date('j', $ld), date('Y', $ld))) . '</td>';
        $ret .= '</tr>';

        foreach($this->paragraphs as $p) {
            $ret .= '<tr><td class="paragraph" colspan="2">' . $p . '</td></tr>';
        }

        $ret .= '<tr>';
        $ret .= '<td class="top left right header">Lender Signature</td>';
        $ret .= '<td class="top right header">Borrower Signature</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right bottom">&nbsp;<br />&nbsp;</td>';
        $ret .= '<td class="right bottom">&nbsp;<br />&nbsp;</td>';
        $ret .= '</tr>';

        $ret .= '</table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

