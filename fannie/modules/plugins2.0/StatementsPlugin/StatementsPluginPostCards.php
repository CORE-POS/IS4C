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

class StatementsPluginPostCards extends FannieRESTfulPage
{
    public $page_set = 'Plugin :: StatementsPlugin';
    public $description = '[Postcard PDF] generates 4"x6" pages with address info';
    public $themed = true;

    public function post_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $cards = "(";
        $args = array();
        if (!is_array($this->id)) {
            $this->id = array($this->id);
        }
        foreach ($this->id as $c){
            $cards .= "?,";
            $args[] = $c;
        }
        $cards = rtrim($cards,",");
        $cards .= ")";

        $memberP = $dbc->prepare("
            SELECT m.card_no,
                c.FirstName,
                c.LastName,
                m.street,
                m.city,
                m.state,
                m.zip
            FROM meminfo AS m 
                INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
            WHERE cardno IN $cards
            ORDER BY m.card_no,
                c.personNum");
        $memberR = $dbc->execute($memberP, $args);

        $today = date("F j, Y");

        $pdf = new FPDF('L','in',array(3.5,5.0));
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');

        $primary = "";
        $pdf->SetAutoPageBreak(true,0);
        $pdf->SetFont("Gill","",10);
        //Meat of the statement
        while ($memberW = $dbc->fetch_row($memberR)) {
	        $pdf->AddPage();

	        $fullname = $memberW['FirstName'] . ' ' . $memberW['LastName'];
            $pdf->SetXY(2.75,1.45);
            $pdf->Cell(2,0.25,$fullname,"",1,"L");

            $pdf->SetX(2.75);
            $address = str_replace("\n"," ",$memberW['street']);
            $pdf->Cell(2,0.25,$address,"",1,"L");
	
            $pdf->SetX(2.75);
            $str = $memberW['city'].", ".$memberW['state']." ".$memberW['zip'];
            $pdf->Cell(2,0.25,$str,"",1,"L");
        }

        $pdf->Output('member postcards.pdf','D');

        return false;
    }
}

FannieDispatch::conditionalExec();

