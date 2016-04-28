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

class GumTaxFormTemplate 
{
    private $my_address = array();
    private $their_address = array();

    private $tax_id;
    private $tax_year;
    private $fields;
    private $account_number;

    private $my_federal_id = 'xx-xxxxxxx';
    private $my_state_id = 'xxxxxx';

    public function __construct($custdata, $meminfo, $tax_id, $tax_year, $fields, $account_number='')
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $settings = new GumSettingsModel($dbc);

        $this->tax_id = $tax_id;
        $this->tax_year = $tax_year;
        $this->fields = $fields;
        $this->account_number = $account_number;

        $this->their_address[] = $custdata->FirstName() . ' ' . $custdata->LastName();
        $this->their_address[] = $meminfo->street();
        $this->their_address[] = $meminfo->city() . ', ' . $meminfo->state() . ' ' . $meminfo->zip();

        $settings->key('storeFederalID');
        if ($settings->load()) {
            $this->my_federal_id = $settings->value();
        }

        $settings->key('storeStateID');
        if ($settings->load()) {
            $this->my_state_id = $settings->value();
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
        $this->my_address[3] = '555-867-5309';
        $settings->key('storePhone');
        if ($settings->load()) {
            $this->my_address[3] = $settings->value();
        }
    }
    
    public function renderAsHTML()
    {
        $ret = '<table style="border: 1px solid black; border-collapse: collapse;">';

        $ret .= '<tr>';

        $ret .= '<td style="border: 1px solid black; width:40%;" rowspan="3" colspan="2">';
        $ret .= 'PAYER\'S name, street address, city, state, ZIP code, and telephone no.';
        $ret .= '<div style="margin-left: 10px; font-weight: bold;">';
        foreach($this->my_address as $line) {
            $ret .= $line .'<br />';
        }
        $ret .= '</div>';
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black; width:20%;">Payer\'s RTIN (optional)<br />$</td>';

        $ret .= '<td style="border: 1px solid black; text-align: center; width:20%;" rowspan="2">';
        $ret .= 'OMB No. 1545-0115';
        $ret .= '<div style="font-size:180%; font-weight:bold;">' . $this->tax_year . '</div>';
        $ret .= 'Form <b>1099-INT</b>';
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black; text-align: center; font-size:150%; font-weight: bold; width:20%;" rowspan="2">';
        $ret .= 'Interest Income';
        $ret .= '</td>';

        $ret .= '</tr><tr>';

        $ret .= '<td style="border: 1px solid black;">1. Interest Income<br />$ ';
        if (isset($this->fields[1])) {
            $ret .= number_format($this->fields[1], 2);
        }
        $ret .= '</td>';

        $ret .= '</tr><tr>';

        $ret .= '<td style="border: 1px solid black;" colspan="2">2. Early withdrawl penalty<br />$ ';
        if (isset($this->fields[2])) {
            $ret .= number_format($this->fields[2], 2);
        }
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;" rowspan="5">';
        $ret .= '<div style="text-align:right;"><b>Copy B</b><br />For Recipient</div>';
        $ret .= '<i>This is important tax information related to interest income earned during ';
        $ret .= $this->tax_year . ' that is being furnished to the Internal Revenue Service. If
                you are required to file a return, a negligence penalty or other sanction may be
                imposed on you if this income is taxable and the IRS determines that it has not
                been reported.</i>';
        $ret .= '</td>';

        $ret .= '</tr><tr>';

        $ret .= '<td style="border: 1px solid black;">PAYER\'s federal identification number<br />';
        $ret .= $this->my_federal_id . '</td>';

        $ret .= '<td style="border: 1px solid black;">RECIPIENT\'s federal identification number<br />';
        $ret .= $this->tax_id . '</td>';

        $ret .= '<td style="border: 1px solid black;" colspan="2">3. Interest on U.S. Savings Bonds and Treas. Obligations<br />$ ';
        if (isset($this->fields[3])) {
            $ret .= number_format($this->fields[3], 2);
        }
        $ret .= '</td>';

        $ret .= '</tr><tr>';
        
        $ret .= '<td style="border: 1px solid black;" rowspan="3" colspan="2">';
        $ret .= 'RECIPIENT\'S NAME<br />';
        $ret .= $this->their_address[0] . '<br />';
        $ret .= 'Street address (including apt. no.)<br />';
        $ret .= $this->their_address[1] . '<br />';
        $ret .= 'City, state, and zip code<br />';
        $ret .= $this->their_address[2] . '<br />';
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;">4. Federal Tax Withheld<br />$ ';
        if (isset($this->fields[4])) {
            $ret .= number_format($this->fields[4], 2);
        }
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;">5. Investment expenses<br />$ ';
        if (isset($this->fields[5])) {
            $ret .= number_format($this->fields[5], 2);
        }
        $ret .= '</td>';
        
        $ret .= '</tr><tr>';

        $ret .= '<td style="border: 1px solid black;">6. Foreign tax paid<br />$ ';
        if (isset($this->fields[6])) {
            $ret .= number_format($this->fields[6], 2);
        }
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;">7. Foreign or U.S. Possessions<br />$ ';
        if (isset($this->fields[7])) {
            $ret .= number_format($this->fields[7], 2);
        }
        $ret .= '</td>';

        $ret .= '</tr><tr>';

        $ret .= '<td style="border: 1px solid black;">8. Tax exempt interest<br />$ ';
        if (isset($this->fields[8])) {
            $ret .= number_format($this->fields[8], 2);
        }
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;">9. Specified private bond int.<br />$ ';
        if (isset($this->fields[9])) {
            $ret .= number_format($this->fields[9], 2);
        }
        $ret .= '</td>';

        $ret .= '</tr><tr>';

        $ret .= '<td style="border: 1px solid black;">Account number (optional)<br />';
        $ret .= $this->account_number . '</td>';

        $ret .= '<td style="border: 1px solid black;">10. Tax exmpt CUSIP no.<br />';
        if (isset($this->fields[10])) {
            $ret .= $this->fields[10];
        }
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;">11. State<br />';
        if (isset($this->fields[11])) {
            $ret .= $this->fields[11];
        }
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;">12. State/Payer\'s state no.<br />';
        if (isset($this->fields[12])) {
            $ret .= $this->fields[12];
        }
        $ret .= '</td>';

        $ret .= '<td style="border: 1px solid black;">13. State income withheld<br />$ ';
        if (isset($this->fields[13])) {
            $ret .= number_format($this->fields[13], 2);
        }
        $ret .= '</td>';

        $ret .= '</tr>';
        $ret .= '</table>';

        return $ret;
    }

    public function renderAsPDF($pdf, $start_y)
    {
        $top_left_x = 6.35;
        $top_left_y = $start_y;
        $line_height = 5;

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($top_left_x, $start_y, $top_left_x + 203.2, $start_y);
        $pdf->Line($top_left_x, $start_y, $top_left_x, $start_y + 76.2);
        $pdf->Line($top_left_x, $start_y + 76.2, $top_left_x + 203.2, $start_y + 76.2);
        $pdf->Line($top_left_x + 203.2, $start_y, $top_left_x + 203.2, $start_y + 76.2);

        $pdf->SetLineWidth(0.2);
        $pdf->Line($top_left_x + 88.9, $start_y, $top_left_x + 88.9, $start_y + 76.2);
        $pdf->Line($top_left_x + 165.1, $start_y, $top_left_x + 165.1, $start_y + 76.2);
        $pdf->Line($top_left_x, $start_y + 26.9875, $top_left_x + 165.1, $start_y + 26.9875);
        $pdf->Line($top_left_x, $start_y + 38.1, $top_left_x + 165.1, $start_y + 38.1);
        $pdf->Line($top_left_x, $start_y + 65.0875, $top_left_x + 203.2, $start_y + 65.0875);
        $pdf->Line($top_left_x + 44.45, $start_y + 26.9876, $top_left_x + 44.45, $start_y + 38.1);
        $pdf->Line($top_left_x + 44.45, $start_y + 65.0875, $top_left_x + 44.45, $start_y + 76.2);
        $pdf->Line($top_left_x + 127.0, $start_y + 38.1, $top_left_x + 127.0, $start_y + 76.2);
        $pdf->Line($top_left_x + 127.0, $start_y, $top_left_x + 127.0, $start_y + 19.05);
        $pdf->Line($top_left_x + 88.9, $start_y + 19.05, $top_left_x + 203.2, $start_y + 19.05);
        $pdf->Line($top_left_x + 88.9, $start_y + 9.525, $top_left_x + 127.0, $start_y + 9.525);
        $pdf->Line($top_left_x + 88.9, $start_y + 46.0375, $top_left_x + 165.1, $start_y + 46.0375);
        $pdf->Line($top_left_x + 88.9, $start_y + 55.5625, $top_left_x + 165.1, $start_y + 55.5625);

        $pdf->SetFont('Arial', '', 6);
        $small_height = 3;
        $med_height = 5;
        $pdf->SetXY($top_left_x, $start_y);
        $pdf->Cell(88.9, $small_height, 'PAYER\'S name, street address, city, state, ZIP code, and telephone no');
        $pdf->SetFont('Arial', '', 8);
        for($i=0; $i<count($this->my_address); $i++) {
            $pdf->SetXY($top_left_x + 3, $start_y + ($med_height * ($i+1)));
            $pdf->Cell(88, $med_height, $this->my_address[$i]);
        }
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 89, $start_y);
        $pdf->Cell((127.0-88.9), $small_height, 'Payer\'s RTIN (optional)');

        $pdf->SetXY($top_left_x + 89, $start_y + 9.6);
        $pdf->Cell(127.0-88.9, $small_height, '1. Interest Income');
        $pdf->SetXY($top_left_x + 89, $start_y + 9.6 + $small_height);
        $text = '$';
        if (isset($this->fields[1])) {
            $text .= number_format($this->fields[1], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((127.0-88.9), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 127.0, $start_y);
        $pdf->Cell((165.1-127), $small_height, 'OMB No. 1545-0115', 0, 0, 'C');
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetXY($top_left_x + 127.0, $start_y + $med_height);
        $pdf->Cell((165.1-127), $med_height, $this->tax_year, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($top_left_x + 127.0, $start_y + 19.05 - $small_height);
        $pdf->Cell((165.1-127), $small_height, 'Form 1099-INT', 0, 0, 'C');

        $pdf->SetFont('Arial', '', 14);
        $pdf->SetXY($top_left_x + 165.1, $start_y + $med_height);
        $pdf->Cell(203.2-165.1, $med_height, 'Interest Income', 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 89, $start_y + 19.05);
        $pdf->Cell((165.1-88.9), $small_height, '2. Early withdrawl penalty');
        $pdf->SetXY($top_left_x + 89, $start_y + 19.05 + $small_height);
        $text = '$';
        if (isset($this->fields[2])) {
            $text .= number_format($this->fields[2], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((165.0-88.9), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x, $start_y + 26.9875);
        $pdf->Cell(44.45-$top_left_x, $small_height, 'PAYER\'S federal identification number');
        $pdf->SetXY($top_left_x, $start_y + 26.9875 + $med_height);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(44.45-$top_left_x, $med_height, $this->my_federal_id, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 44.45, $start_y + 26.9875);
        $pdf->Cell(88.9-44.45, $small_height, 'RECIPIENT\'S identification number');
        $pdf->SetXY($top_left_x + 44.45, $start_y + 26.9875 + $med_height);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(88.9-44.45, $med_height, $this->tax_id, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 89, $start_y + 26.9875);
        $pdf->Cell(165.1-88.9, $small_height, '3. Interest on U.S. Savings Bonds and Treas. Obligations');
        $pdf->SetXY($top_left_x + 89, $start_y + 26.9875 + $small_height);
        $text = '$';
        if (isset($this->fields[3])) {
            $text .= number_format($this->fields[3], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((165.1-88.9), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 89, $start_y + 38.1);
        $pdf->Cell((127-88.9), $small_height, '4. Federal Tax Withheld');
        $pdf->SetXY($top_left_x + 89, $start_y + 38.1 + $small_height);
        $text = '$';
        if (isset($this->fields[4])) {
            $text .= number_format($this->fields[4], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((127-88.9), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 127, $start_y + 38.1);
        $pdf->Cell((127-88.9), $small_height, '5. Investment expenses');
        $pdf->SetXY($top_left_x + 127, $start_y + 38.1 + $small_height);
        $text = '$';
        if (isset($this->fields[5])) {
            $text .= number_format($this->fields[5], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((165.1-127), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 89, $start_y + 46.0375);
        $pdf->Cell((127-88.9), $small_height, '6. Foreign tax paid');
        $pdf->SetXY($top_left_x + 89, $start_y + 46.0375 + $small_height);
        $text = '$';
        if (isset($this->fields[6])) {
            $text .= number_format($this->fields[6], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((127-88.9), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);
        
        $pdf->SetXY($top_left_x + 127, $start_y + 46.0375);
        $pdf->Cell((127-88.9), $small_height, '7. Foreign or U.S. Posessions');
        $pdf->SetXY($top_left_x + 127, $start_y + 46.0375 + $small_height);
        $text = '$';
        if (isset($this->fields[7])) {
            $text .= number_format($this->fields[7], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((165.1-127), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 89, $start_y + 55.5625);
        $pdf->Cell((127-88.9), $small_height, '8. Tax exempt interest');
        $pdf->SetXY($top_left_x + 89, $start_y + 55.5625 + $small_height);
        $text = '$';
        if (isset($this->fields[8])) {
            $text .= number_format($this->fields[8], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((127-88.9), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 127, $start_y + 55.5625);
        $pdf->Cell((127-88.9), $small_height, '9. Specified private bont in.');
        $pdf->SetXY($top_left_x + 127, $start_y + 55.5625 + $small_height);
        $text = '$';
        if (isset($this->fields[9])) {
            $text .= number_format($this->fields[9], 2);
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((165.1-127), $med_height, $text);
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x, $start_y + 38.1);
        $pdf->Cell(88.9, $small_height, 'RECIPIENT\'S NAME');
        if (isset($this->their_address[0])) {
            $pdf->SetXY($top_left_x, $start_y + 38.1 + $small_height);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(88.9, $med_height, $this->their_address[0], 0, 0, 'C');
            $pdf->SetFont('Arial', '', 6);
        }
        $pdf->SetXY($top_left_x, $start_y + 38.1 + $small_height + $med_height);
        $pdf->Cell(88.9, $small_height, 'Street address (including apt. no.)');
        if (isset($this->their_address[1])) {
            $pdf->SetXY($top_left_x, $start_y + 38.1 + 2*$small_height + $med_height);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(88.9, $med_height, $this->their_address[1], 0, 0, 'C');
            $pdf->SetFont('Arial', '', 6);
        }
        $pdf->SetXY($top_left_x, $start_y + 38.1 + 2*$small_height + 2*$med_height);
        $pdf->Cell(88.9, $small_height, 'City, state, and zip code');
        if (isset($this->their_address[2])) {
            $pdf->SetXY($top_left_x, $start_y + 38.1 + 3*$small_height + 2*$med_height);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(88.9, $med_height, $this->their_address[2], 0, 0, 'C');
            $pdf->SetFont('Arial', '', 6);
        }

        $pdf->SetXY($top_left_x, $start_y + 65.0875);
        $pdf->Cell(44.45, $small_height, 'Account number (optional)');
        $pdf->SetXY($top_left_x, $start_y + 65.0875 + $small_height);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(44.45, $med_height, $this->account_number, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 44.45, $start_y + 65.0875);
        $pdf->Cell((127-88.9), $small_height, '10. Tax exmpt CUSIP no.');
        $pdf->SetXY($top_left_x + 44.45, $start_y + 65.0875 + $small_height);
        $text = '';
        if (isset($this->fields[10])) {
            $text .= $this->fields[10];
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(44.45, $med_height, $text, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 89, $start_y + 65.0875);
        $pdf->Cell((127-88.9), $small_height, '11. State');
        $pdf->SetXY($top_left_x + 89, $start_y + 65.0875 + $small_height);
        $text = '';
        if (isset($this->fields[11])) {
            $text .= $this->fields[11];
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((127-88.9), $med_height, $text, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 127, $start_y + 65.0875);
        $pdf->Cell((165.1-127), $small_height, '12. State/Payer\'s state no.');
        $pdf->SetXY($top_left_x + 127, $start_y + 65.0875 + $small_height);
        $text = '';
        if (isset($this->fields[12])) {
            $text .= $this->fields[12];
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((165.1-127), $med_height, $text, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 165.1, $start_y + 65.0875);
        $pdf->Cell(203.2-165.1, $small_height, '13. State income withheld');
        $pdf->SetXY($top_left_x + 165.1, $start_y + 65.0875 + $small_height);
        $text = '';
        if (isset($this->fields[13])) {
            $text .= $this->fields[13];
        }
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell((203.2-165.1), $med_height, $text, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 6);

        $pdf->SetXY($top_left_x + 165.1, $start_y + 19.05);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(203.2-165.1, $med_height, 'Copy B', 0, 0, 'R');
        $pdf->SetXY($top_left_x + 165.1, $start_y + 19.05 + $med_height);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(203.2-165.1, $med_height, 'For Recipient', 0, 0, 'R');
        $pdf->SetXY($top_left_x + 165.1, $start_y + 19.05 + 2*$med_height);
        $pdf->SetFont('Arial', '', 6);
        $box_height = 65.0875 - (19.05 + 2*$med_height);
        $text = 'This is important tax information related to interest income earned during ';
        $text .= $this->tax_year . ' that is being furnished to the Internal Revenue Service. If you are required to file a return, a negligence penalty or other sanction may be imposed on you if this income is taxable and the IRS determines that it has not been reported.';
        $pdf->MultiCell(203.2-165.1, $small_height, $text);

        return true;
    }
}

