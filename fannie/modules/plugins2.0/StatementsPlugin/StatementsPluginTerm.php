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

include_once(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FPDF')) {
    include(dirname(__FILE__) . '/../../../src/fpdf/fpdf.php');
}

class StatementsPluginTerm extends FannieRESTfulPage
{
    public $page_set = 'Plugin :: StatementsPlugin';
    public $description = '[Termination PDF] generates membership termination letters.';
    public $themed = true;

    public function post_id_handler()
    {
        $numbers = array("zero","one","two","three","four","five","six","seven",
                "eight","nine","ten","eleven","twelve","thirteen","fourteen",
                "fifteen","sixteen","seventeen","eighteen","nineteen","twenty");

        if (!is_array($this->id)) {
            $this->id = array($this->id);
        }

        $today = date("F j, Y");

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Medium.php');

        //Meat of the statement
        foreach ($this->id as $card_no) {
            $pdf->AddPage();
            $pdf->Image('new_letterhead_horizontal.png',5,10, 200);
            $pdf->SetFont('Gill','','12');
            $pdf->Ln(45);

            $account = \COREPOS\Fannie\API\member\MemberREST::get($card_no);

            $pdf->Cell(10,10,$today,0);
            $pdf->Ln(15);

            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $firstname = ucwords(strtolower($c['firstName']));
                    $lastname = ucwords(strtolower($c['lastName']));
                    $fullname = $firstname." ".$lastname;
                    break;
                }
            }

            //Member address
            $pdf->Cell(10,10,trim($fullname),0);
            $pdf->Ln(5);

            $pdf->Cell(80,10,$account['addressFirstLine'],0);
            if ($account['addressSecondLine']) {
                $pdf->Cell(80,10,$account['addressSecondLine'],0);
            }
            $pdf->Cell(90,10,$account['city'] . ', ' . $account['state'] . '   ' . $account['zip'],0);
            $pdf->Ln(15);

            $pdf->MultiCell(0,5,"Dear ".$firstname.",");
            $pdf->Ln(5);

            $txt = "We have received your Application to Terminate your membership at WFC. The
Board reviews termination requests annually in ";
            $pdf->SetFont("Gill","","12");
            $pdf->Write(5,str_replace("\n"," ",$txt));
            $pdf->SetFont("Gill","B","12");
            $pdf->Write(5,"February");
            $pdf->SetFont("Gill","","12");
            $txt = ". Refunds, less any indebtedness owed to WFC, are authorized for payment in
the order received subject to the financial health of WFC and receipt of additional stock
from new members. Your stock will be refunded as soon as possible based on these criteria.";
            $pdf->Write(5,str_replace("\n"," ",$txt)."\n");
            $pdf->Ln(5);

            $txt = "Submission of an Application to Terminate immediately inactivates your owner
benefits and discounts and your right to participate in governance of WFC. Please keep us
advised of any changes in your mailing address.";
            $pdf->MultiCell(0,5,str_replace("\n", ' ', $txt));
            $pdf->Ln(5);

            $txt = "If you have any questions, please do not hesitate to ask. I can be reached at the
number above or at mms@wholefoods.coop. Thank you.";
            $pdf->MultiCell(0,5,str_replace("\n", ' ', $txt));
            $pdf->Ln(5);

            $pdf->MultiCell(0,5,"Thank you for your support of WFC");
            $pdf->Ln(10);

            $pdf->MultiCell(0,5,"Sincerely yours,");
            $pdf->MultiCell(0,5,"WHOLE FOODS COMMUNITY CO-OP, INC.");
            $pdf->Ln(10);

            $pdf->MultiCell(0,5,"Amanda Borgren");
            $pdf->MultiCell(0,5,"Owner Services");

        }

        $pdf->Output('member term letters.pdf','D');

        return false;
    }
}

FannieDispatch::conditionalExec();

