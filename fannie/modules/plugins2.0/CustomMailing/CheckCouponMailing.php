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

include_once(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('FPDF')) {
    include(dirname(__FILE__) . '/../../../src/fpdf/fpdf.php');
    define('FPDF_FONTPATH','font/');
}

/**
*/
class CheckCouponMailing extends FannieRESTfulPage
{

    protected $title = 'Check Coupon Mailing';
    protected $header = 'Check Coupon Mailing';
    public $themed = true;
    public $description = '[Check Coupon Mailing] generates a custom coupon designed to print
    on tri-fold check paper.';

    public function preprocess()
    {
        $this->__routes[] = 'post<id><upc><terms>';

        return parent::preprocess();
    }

    public function post_id_upc_terms_handler()
    {
        $this->id = preg_split('/[^\d]/', $this->id, 0, PREG_SPLIT_NO_EMPTY);

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
        $margins = $pdf->GetMargins();
        $margins['top'] = 0.0; 
        $check_left_x = ($margins['left'] > 3.175) ? $margins['right'] : 3.175 - $margins['left'];
        $real_check_top_y = 183.675 - $margins['top'];
        $check_right_x = 203.2 - $margins['left'];
        $real_check_bottom_y = 255.112 - $margins['top'];
        $line_height = 5;
        $envelope_window_tab = 15;
        $right_col1 = 130;
        $right_col2 = 170;

        $my_address = array(
            '610 E 4th St',
            'Duluth, MN 55805',
            'Tel: 218.728.0884',
            'www.wholefoods.coop',
        );
        $check_date = date('F j, Y');

        $dbc = $this->connection;
        $dbc->setDefaultDB($this->config->get('OP_DB'));

        $signage = new COREPOS\Fannie\API\item\FannieSignage(array());
        foreach ($this->id as $card_no) {
            $pdf->AddPage();
            $account = \COREPOS\Fannie\API\member\MemberREST::get($card_no);
            $primary = array();
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $primary = $c;
                    break;
                }
            }

            $check_number = rand(100000, 999995);

            for ($i=0; $i<3; $i++) {
                $pdf->SetFont('Gill', '', 10);
                $check_top_y = $real_check_top_y - ($i*90);
                $check_bottom_y = $real_check_bottom_y - ($i*90);
                $pdf->SetXY($check_left_x, $check_top_y);
                $pdf->Ln($line_height*4.25);
                foreach ($my_address as $line) {
                    $pdf->SetX($check_left_x + $envelope_window_tab+20);
                    if ($line == 'www.wholefoods.coop') {
                        $pdf->SetFont('Gill', 'B', 9);
                    }
                    $pdf->Cell(0, $line_height, $line, 0, 1);
                }

                $pdf->SetFont('Gill', 'B', 10);
                $pdf->SetXY($check_left_x + $right_col1, $check_top_y);
                $pdf->Cell(30, $line_height, 'Check Number:', 0, 0, 'R');
                $pdf->SetFont('Gill', '', 10);
                $pdf->SetTextColor(0xff, 0x58, 0);
                $pdf->SetX($check_left_x + $right_col2);
                $pdf->Cell(30, $line_height, $check_number, 0, 0, 'R');
                $pdf->SetFont('Gill', 'B', 10);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY($check_left_x + $right_col1, $check_top_y+(1.5*$line_height));
                $pdf->Cell(30, $line_height, 'Date:', 0, 0, 'R');
                $pdf->SetFont('Gill', '', 10);
                $pdf->SetTextColor(0xff, 0x58, 0);
                $pdf->SetX($check_left_x + $right_col2);
                $pdf->Cell(30, $line_height, $check_date, 0, 0, 'R');
                $pdf->SetXY($check_left_x + $right_col1, $check_top_y+(3*$line_height));
                $pdf->SetFont('Gill', 'B', 10);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell(30, $line_height, 'Amount:', 0, 0, 'R');
                $pdf->SetXY($check_left_x + $right_col1 + 30, $check_top_y+(3*$line_height));
                $pdf->SetFont('Gill', '', 10);
                $pdf->SetTextColor(0xff, 0x58, 0);
                $pdf->MultiCell(40, $line_height, str_repeat(' ', 2) . $this->terms, 0, 'R');
                $pdf->SetTextColor(0, 0, 0);

                $their_address = array(
                    $primary['firstName'] . ' ' . $primary['lastName'],
                );
                $their_address[] = $account['addressFirstLine'];
                if ($account['addressSecondLine']) {
                    $their_address[] = $account['addressSecondLine'];
                }
                $their_address[] = $account['city'] . ', ' . $account['state'] . ' ' . $account['zip'];
                $pdf->SetXY($check_left_x + $envelope_window_tab, $check_top_y + (11*$line_height));
                $pdf->SetFont('Gill', 'B', 10);
                foreach($their_address as $line) {
                    $pdf->SetX($check_left_x + $envelope_window_tab);
                    $pdf->Cell(0, $line_height, $line, 0, 1);
                }
                $pdf->SetFont('Gill', '', 10);

                $pdf->SetXY($check_left_x, $check_bottom_y + $line_height - 1);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Gill', 'B', 10);
                $pdf->Cell(0, $line_height, 'Redeemable only at Whole Foods Co-op', 0, 0, 'C');
                $pdf->SetFont('Gill', '', 10);

                $pdf->SetXY($check_left_x+145, $check_top_y+(6*$line_height)+1);
                $pdf->SetFont('Gill', '', 8);
                $pdf->MultiCell(50, $line_height-2, 'Limit one per day. Cannot be applied to previous purchases. No cash value.');
                $pdf->SetFont('Gill', '', 10);

                $pdf->SetFillColor(0xCC, 0xCC, 0xCC);
                $pdf->Rect($check_left_x+84, $check_top_y+28, 39, 15, 'F');
                $pdf->SetFillColor(0, 0, 0);
                $signage->drawBarcode(ltrim($this->upc, '0'), $pdf, $check_left_x+87, $check_top_y+30, array('height'=>11, 'fontsize'=>0));

                $pdf->Image('logo.rgb.noalpha.png', $check_left_x+$envelope_window_tab, $check_top_y+20, 20);

                $pdf->SetFont('Gill', 'B', '31');
                $pdf->SetXY($check_left_x + $envelope_window_tab, $check_top_y + (0.5*$line_height));
                $pdf->SetTextColor(0xff, 0x58, 0);
                $pdf->Cell(0, 3*$line_height, 'We Appreciate You!');

                $pdf->SetFont('Gill', '', '10');
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Image(dirname(__FILE__) . '/../GiveUsMoneyPlugin/img/sig.png', $check_right_x - 63.5, $check_top_y + (9*$line_height), 63.5);
                $pdf->SetXY($check_right_x - 63.5, $check_top_y + (12*$line_height));
                $pdf->Cell(63.5, $line_height, 'Authorized By Signature', 'T');

                $check_number++;
            }

        }
        $pdf->Output();
    }

    public function get_view()
    {
        return '<form method="post">
            <div class="form-group">
                <label>Coupon UPC</label>
                <input type="text" name="upc" class="form-control" />
            </div>
            <div class="form-group">
                <label>Deal Text</label>
                <textarea name="terms" rows="10" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label>Member #s</label>
                <textarea name="id" rows="10" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
            </form>';
    }

}

FannieDispatch::conditionalExec();

