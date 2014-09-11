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

class GumCheckTemplate 
{
    private $check_number;
    private $amount;
    private $amount_as_words;
    private $check_date;
    private $memo;
    private $my_address = array();
    private $their_address = array();
    private $bank_address = array(
        'Members Cooperative',
        'Credit Union',
        '215 N 40th Ave West',
        'Duluth, MN 55807',
    );

    private $routing_no = 'xxxxxxxxxx';
    private $checking_no = 'yyyyyyyyyyyy';
    
    public function __construct($custdata, $meminfo, $amount, $memo='', $check_number=false, $date=false)
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $settings = new GumSettingsModel($dbc);

        if (!$date) {
            $this->check_date = date('m/d/Y');
        } else {
            $this->check_date = $date;
        }

        $this->memo = $memo;
        $this->check_number = $check_number;

        $this->amount = $amount;
        $dollars = floor($amount);
        $cents = round(($amount - $dollars) * 100);
        $nf = new NumberFormatter('en_US', NumberFormatter::SPELLOUT);
        $this->amount_as_words = ucwords($nf->format($dollars)) . ' And ' . str_pad($cents, 2, '0', STR_PAD_LEFT) . '/100';

        $this->their_address[] = $custdata->FirstName() . ' ' . $custdata->LastName();
        $this->their_address[] = $meminfo->street();
        $this->their_address[] = $meminfo->city() . ', ' . $meminfo->state() . ' ' . $meminfo->zip();

        $settings->key('routingNo');
        if ($settings->load()) {
            $this->routing_no = $settings->value();
        }
        $settings->key('checkingNo');
        if ($settings->load()) {
            $this->checking_no = $settings->value();
        }

        $this->my_address[0] = 'Name of Co-op';
        $settings->key('storeName');
        if ($settings->load()) {
            $this->my_address[0] = $settings->value();
        }
        $this->my_address[1] = 'Street Address';
        $settings->key('storeAddress');
        if ($settings->load()) {
            $this->my_address[1] = $settings->value();
        }
        $this->my_address[2] = '';
        $settings->key('storeCity');
        if ($settings->load()) {
            $this->my_address[2] .= $settings->value() . ', ';
        } else {
            $this->my_address[2] .= 'City, ';
        }
        $settings->key('storeState');
        if ($settings->load()) {
            $this->my_address[2] .= $settings->value() . ' ';
        } else {
            $this->my_address[2] .= 'XX ';
        }
        $settings->key('storeZip');
        if ($settings->load()) {
            $this->my_address[2] .= $settings->value();
        } else {
            $this->my_address[2] .= '12345';
        }
        $this->my_address[3] = 'Tel: 555-867-5309';
        $settings->key('storePhone');
        if ($settings->load()) {
            $this->my_address[3] = 'Tel: ' . $settings->value();
        }
    }

    public function renderAsHTML()
    {
       $ret = '<div class="checkPreview" style="border: solid 1px black; 
                background-color: #ccc; padding: 3px; width: 100%;">'; 

       $ret .= '<div class="checkRowOne">';
       $ret .= '<div style="float:left; width: 30%; text-align:left;">';
       $ret .= '<b>' . $this->memo . '</b><br />';
       foreach($this->my_address as $line) {
           $ret .= $line . '<br />';
       }
       $ret .= '</div>';
       $ret .= '<div style="float:left; width: 20%; text-align:center;">';
       foreach($this->bank_address as $line) {
           $ret .= $line . '<br />';
       }
       $ret .= '</div>';
       $ret .= '<div style="float:left; width: 45%; text-align:right;">';
       $ret .= '<b>Check Number: ' . $this->check_number . '</b><br />';
       $ret .= '<b>Date: ' . $this->check_date . '</b><br />';
       $ret .= '<b>Amount: ' . str_pad(number_format($this->amount, 2), 25, '*', STR_PAD_LEFT).'</b>';
       $ret .= '</div>';
       $ret .= '</div>'; // end checkRowOne

       $ret .= '<div style="clear:left; width=100%; text-align:center;">This checkvoid after 90 days</div>';

       $ret .= '<div style="clear:left; width=100%; font-weight:bold; text-align:right;margin-right:30px;">';
       $ret .= $this->amount_as_words;
       $ret .= '</div>';

       $ret .= '<div class="checkRowTwo">';
       $ret .= '<div style="float: left; width: 60%; text-align: left; font-weight: bold;">';
       foreach($this->their_address as $line) {
            $ret .= $line . '<br />';
       }
       $ret .= '</div>';
       $ret .= '<div style="float: left; width: 30%; text-align: center;">';
       if (file_exists(dirname(__FILE__) . '/img/sig.png')) {
           $ret .= '<img src="img/sig.png" style="border-bottom: 1px solid black; width:200px;" /><br />';
       }
       $ret .= 'Authorized By Signature';
       $ret .= '</div>';
       $ret .= '</div>'; // end checkRowTwo;

       $ret .= '<div style="clear:left; width: 100%; text-align: center;">';
       $ret .= $this->check_number;
       $ret .= '<span style="margin-left:100px;">&nbsp;</span>';
       $ret .= str_repeat('*', strlen($this->routing_no)-4) . substr($this->routing_no, -4);
       $ret .= '<span style="margin-left:100px;">&nbsp;</span>';
       $ret .= str_repeat('*', strlen($this->checking_no)-4) . substr($this->checking_no, -4);
       $ret .= '</div>';

       $ret .= '</div>';

       return $ret;
    }

    public function renderAsPDF($pdf)
    {
        $margins = $pdf->GetMargins();
        // this was written BEFORE patching
        // fpdf to correctly return the top margin
        // set to zero to mimic old, broken fpdf
        $margins['top'] = 0.0; 
        $check_left_x = ($margins['left'] > 3.175) ? $margins['right'] : 3.175 - $margins['left'];
        $check_top_y = 193.675 - $margins['top'];
        $check_right_x = 203.2 - $margins['left'];
        $check_bottom_y = 265.112 - $margins['top'];
        $line_height = 5;

        $pdf->SetFont('Arial', 'B', 10);

        $pdf->SetXY($check_left_x, $check_top_y);
        $pdf->Cell(0, $line_height, $this->memo, 0, 1);
        $pdf->Ln($line_height);
        $pdf->SetFont('Arial', '', 10);
        $envelope_window_tab = 15;
        foreach($this->my_address as $line) {
            $pdf->SetX($check_left_x + $envelope_window_tab);
            $pdf->Cell(0, $line_height, $line, 0, 1);
        }

        $bank_offset = 90; 
        $pdf->SetXY($check_left_x + $bank_offset, $check_top_y);
        foreach($this->bank_address as $line) {
            $pdf->SetX($check_left_x + $bank_offset);
            $pdf->Cell(30, $line_height, $line, 0, 1, 'C');
        }

        $right_col1 = 130;
        $right_col2 = 170;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($check_left_x + $right_col1, $check_top_y);
        $pdf->Cell(30, $line_height, 'Check Number:', 0, 0, 'R');
        $pdf->SetX($check_left_x + $right_col2);
        $pdf->Cell(30, $line_height, $this->check_number, 0, 0, 'R');
        $pdf->SetXY($check_left_x + $right_col1, $check_top_y+(1.5*$line_height));
        $pdf->Cell(30, $line_height, 'Date:', 0, 0, 'R');
        $pdf->SetX($check_left_x + $right_col2);
        $pdf->Cell(30, $line_height, $this->check_date, 0, 0, 'R');
        $pdf->SetXY($check_left_x + $right_col1, $check_top_y+(3*$line_height));
        $pdf->Cell(30, $line_height, 'Amount:', 0, 0, 'R');
        $pdf->SetX($check_left_x + $right_col2);
        $pdf->Cell(30, $line_height, str_pad(number_format($this->amount, 2), 25, '*', STR_PAD_LEFT), 0, 0, 'R');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY($check_left_x, $check_top_y + (6*$line_height));
        $pdf->Cell(0, $line_height, 'This check void after 90 days', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, $line_height, $this->amount_as_words.'   ', 0, 1, 'R');

        $pdf->SetXY($check_left_x + $envelope_window_tab, $check_top_y + (8*$line_height));
        foreach($this->their_address as $line) {
            $pdf->SetX($check_left_x + $envelope_window_tab);
            $pdf->Cell(0, $line_height, $line, 0, 1);
        }

        $pdf->Image('img/sig.png', $check_right_x - 63.5, $check_top_y + (9*$line_height), 63.5);
        $pdf->SetXY($check_right_x - 63.5, $pdf->GetY()+$line_height);
        $pdf->Cell(63.5, $line_height, 'Authorized By Signature', 'T');

        $pdf->SetXY($check_left_x + 36, $check_bottom_y + $line_height - 1);
        if (!isset($pdf->fonts['gnumicr'])) {
            $pdf->AddFont('GnuMICR', '', 'GnuMICR.php');
        }
        $pdf->SetFont('GnuMICR', '', 12);
        // In the MICR font:
        // A is the symbol for routing/transit
        // B is the symbol for amount
        // C is the symbol for account ("on-us")
        // D is the symbol for dash
        $bottom_line = 'C' . $this->check_number . 'C';
        $bottom_line .= ' ';
        $bottom_line .= 'A' . $this->routing_no . 'A';
        $bottom_line .= ' ';
        $bottom_line .= $this->checking_no . 'A';
        $pdf->Cell(0, $line_height, $bottom_line);

        return true;
    }
}

