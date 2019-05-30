<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

if (!class_exists('DefaultPdfPoExport')) {
    include(dirname(__FILE__) . '/DefaultPdfPoExport.php');
}

class WfcPdfExport extends DefaultPdfPoExport 
{
    public $nice_name = 'WFC (PDF)';
    public $extension = 'pdf';
    public $mime_type = 'application/pdf';

    public function exportString($id)
    {
        $pdf = $this->prepOrder($id);
        return $pdf->Output('string', 'S');
    }

    public function export_order($id)
    {
        $pdf = $this->prepOrder($id);

        $pdf->Output('order_export.pdf', 'D');
    }

    public function prepOrder($id)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($id);
        $order->load();

        $auto = new AutoOrderMapModel($dbc);
        $auto->vendorID($order->vendorID());
        $auto->storeID($order->storeID());
        $auto->load();

        $notes = new PurchaseOrderNotesModel($dbc);
        $notes->orderID($id);
        $notes->load();
        $noteContent = trim($notes->notes());

        $pdf = $this->buildPDF($id);
        $pdf->Ln(10);
        $pdf->Cell(0, 2, '', 'B', 1);
        $pdf->Ln(5);

        $pdf->Cell(50, 5, 'Whole Foods Co-op', 0, 1);
        if ($order->storeID() == 1) {
            $pdf->Cell(50, 5, '610 E 4th St', 0, 1);
            $pdf->Cell(50, 5, 'Duluth, MN 55805', 0, 1);
            $pdf->Cell(50, 5, '(218) 728-0884', 0, 1);
        } elseif ($order->storeID() == 2) {
            $pdf->Cell(50, 5, '4426 Grand Ave', 0, 1);
            $pdf->Cell(50, 5, 'Duluth, MN 55807', 0, 1);
            $pdf->Cell(50, 5, '(218) 336-0279', 0, 1);
        }
        if ($auto->accountID()) {
            $pdf->Cell(50, 5, 'Account #: ' . $auto->accountID(), 0, 1);
        }
        $pdf->Cell(50, 5, 'PO #: ' . $id, 0, 1);

        return $pdf;
    }
}

