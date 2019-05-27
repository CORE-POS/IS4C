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

class DefaultPdfPoExport {

    public $nice_name = 'PDF (Default)';
    public $extension = 'pdf';
    public $mime_type = 'application/pdf';

    function send_headers(){
    }

    public function exportString($id)
    {
        $pdf = $this->buildPDF($id);
        return $pdf->Output('string', 'S');
    }

    function export_order($id){
        $pdf = $this->buildPDF($id);
        $pdf->Output('order_export.pdf', 'D');
    }

    protected function buildPDF($id){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($id);
        $order->load();

        $items = new PurchaseOrderItemsModel($dbc);
        $items->orderID($id);

        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($order->vendorID());
        $vendor->load();

        $contact = new VendorContactModel($dbc);
        $contact->vendorID($order->vendorID());
        $contact->load();

        if (!class_exists('FPDF')) include_once(__DIR__ . '/../../src/fpdf/fpdf.php');
        $pdf = new FPDF('P','mm','Letter');
        $pdf->AddPage();
    
        $pdf->SetFont('Arial','','12');
        $pdf->Cell(100, 5, 'Vendor: '.$vendor->vendorName(), 0, 0);
        $pdf->Cell(100, 5, 'Date: '.date('Y-m-d'), 0, 0);
        $pdf->Ln();
        $pdf->Cell(100, 5, 'Phone: '.$contact->phone(), 0, 0);
        $pdf->Cell(100, 5, 'Fax: '.$contact->fax(), 0, 0);
        $pdf->Ln();
        $pdf->Cell(100, 5, 'Email: '.$contact->email(), 0, 0);
        $pdf->Cell(100, 5, 'Website: '.$contact->website(), 0, 0);
        $pdf->Ln();
        $pdf->MultiCell(0, 5, "Ordering Info:\n".$contact->notes(), 'B');
        $pdf->Ln();

        $cur_page = 0;
        $pdf->SetFontSize(10);
        foreach($items->find() as $obj){
            if ($cur_page != $pdf->PageNo()){
                $cur_page = $pdf->PageNo();
                $pdf->Cell(25, 5, 'SKU', 0, 0);
                $pdf->Cell(20, 5, 'Order Qty', 0, 0);
                $pdf->Cell(20, 5, 'Case Size', 0, 0);
                $pdf->Cell(20, 5, 'Total Units', 0, 0);
                $pdf->Cell(20, 5, 'Unit Size', 0, 0);
                $pdf->Cell(30, 5, 'Brand', 0, 0);
                $pdf->Cell(65, 5, 'Description', 0, 0);
                $pdf->Ln();
            }

            $pdf->Cell(25, 5, $obj->sku(), 0, 0);
            $pdf->Cell(20, 5, $obj->quantity(), 0, 0, 'C');
            $pdf->Cell(20, 5, $obj->caseSize(), 0, 0, 'C');
            $pdf->Cell(20, 5, $obj->caseSize() * $obj->quantity(), 0, 0, 'C');
            $pdf->Cell(20, 5, $obj->unitSize(), 0, 0, 'C');
            $pdf->Cell(30, 5, substr($obj->brand(), 0, 10), 0, 0);
            $pdf->Cell(65, 5, $obj->description(), 0, 0);
            $pdf->Ln();
        }

        return $pdf;
    }
}

