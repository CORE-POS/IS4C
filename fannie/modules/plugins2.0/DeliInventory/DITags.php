<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class DITags extends FannieRESTfulPage
{
    protected $header = 'Deli Order Tags';
    protected $title = 'Deli Order Tags';

    protected function get_id_handler()
    {
        $store = $this->id;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10,10,10);
        $pdf->SetFont('Arial', '', 7);
        $dbc = $this->connection;
        $args = array($store);
        $prep = $dbc->prepare('
            SELECT v.description, v.sku, n.vendorName, v.brand, v.units, v.size
            FROM deliInventoryCat AS d
                INNER JOIN vendorItems AS v ON v.sku=d.orderno AND v.vendorID=d.vendorID
                INNER JOIN vendors AS n ON d.vendorID=n.vendorID
            WHERE d.vendorID=1 
                AND d.storeID=?');
        $res = $dbc->execute($prep, $args);
        $posX = 5;
        $posY = 20;
        while ($row = $dbc->fetchRow($res)) {
            //$prepB = $dbc->prepare('SELECT units, size FROM vendorItems WHERE sku = ?');
            $prepB = $dbc->prepare('SELECT max(receivedDate), caseSize, unitSize, brand FROM PurchaseOrderItems WHERE sku = ?');
            $resB = $dbc->execute($prepB, $row['sku']);
            $pdf->SetXY($posX+3, $posY);
            $pdf->Cell(0, 5, substr($row['description'], 0, 25));
            $pdf->Ln(3);
            $pdf->SetX($posX+3);
            $pdf->Cell(0, 5, $row['vendorName'] . ' - ' . $row['sku']);
            $img = Image_Barcode2::draw($row['sku'], 'code128', 'png', false, 20, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);
            $pdf->Image($file, $posX, $posY+7);
            unlink($file);
            $pdf->SetXY($posX+3, $posY+16);
            $pdf->Cell(0, 5, $row['size'] . ' / ' . $row['units'] . ' - ' . $row['brand']);
            $pdf->SetXY($posX+35, $posY+17.5);
            $posX += 52;
            if ($posX > 170) {
                $posX = 5;
                $posY += 31;
                if ($posY > 250) {
                    $posY = 20;
                    $pdf->AddPage();
                }
            }
        }
        $pdf->Output('skus.pdf', 'I');

        return false;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker('id');
        return <<<HTML
<form method="get" action="DITags.php">
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Tags</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

