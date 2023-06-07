<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

if (!class_exists('FDFBulkFormatter')) {
    include(__DIR__ . '/../../../../../Scannie/content/Testing/FDFBulkFormatter.php');
}

class ScannieBulkWrapper extends FannieRESTfulPage
{

    protected $header = 'Bulk Labels';
    protected $title = 'Bulk Labels';

    protected function post_id_view()
    {
        $upcs = explode("\n", $this->id);
        $upcs = array_map('trim', $upcs);
        $upcs = array_filter($upcs, function ($i) { return strlen($i) > 0; });
        $bids = explode("\n", FormLib::get('bid'));
        $bids = array_map('trim', $bids);
        $bids = array_filter($bids, function ($i) { return strlen($i) > 0; });
        $organic = FormLib::get('organic', false) ? true : false;
        $local = FormLib::get('local', false) ? true : false;

        chdir('../../../../../Scannie/content/Testing');
        exec('rm fdfs/*.fdf');
        exec('rm fdfs/finishedPdfs/*.fin.pdf');
        $fdf = new FDFBulkFormatter();
        $fdf->setUPCs($upcs);
        $fdf->setOrganic($organic);
        $fdf->setLocal($local);
        $fdf->setBatchIDs($bids);
        $barcodeOrder = $fdf->run();

        $template = 'conv.pdf';
        if ($organic && $local) {
            $template = 'localog.pdf';
        } elseif ($organic && !$local) {
            $template = 'og.pdf';
        } elseif (!$organic && $local) {
            $template = 'localconv.pdf';
        }
        exec("sh finishFDFs.sh $template");

        $this->get_barcodes($barcodeOrder);

        $ret = '';
        $dh = opendir('fdfs/finishedPdfs/');
        while (($file = readdir($dh)) !== false) {
            if (substr($file, -8) == ".fin.pdf") {
                chmod("fdfs/finishedPdfs/$file", 0666);
                $newfilename = substr($file, 0, 15) . '.fin.pdf';
                exec("pdftk fdfs/finishedPdfs/$file stamp tmp_barcodes.pdf output fdfs/finishedPdfs/$newfilename");
                chmod("fdfs/finishedPdfs/$newfilename", 0666);
                exec("rm fdfs/finishedPdfs/$file");
            }
        }
        $dh = opendir('fdfs/finishedPdfs/');
        while (($file = readdir($dh)) !== false) {
            if (substr($file, -8) == ".fin.pdf") {
                $ret .= sprintf('<li><a href="/Scannie/content/Testing/fdfs/finishedPdfs/%s">%s</a></li>', $file, $file);
            }
        }

        return <<<HTML
Sign file(s):
<ul>
    {$ret}
</ul>
HTML;
    }

    private function get_barcodes($order)
    {
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10,10,10);
        $pdf->SetFont('Arial', '', 7);
        $dbc = $this->connection;

        $upcs = explode(".", $order);
        $upcs = array_map('trim', $upcs);
        $upcs = array_filter($upcs, function ($i) { return strlen($i) > 0; });

        $items = $upcs;
        $myitems = array();
        foreach ($items as $k => $upc) {
            $upc = BarcodeLib::padUPC($upc);
            $items[$k] = $upc;
            $myitems[] = $upc;
        }

        $prep = $dbc->prepare("SELECT i.sku, p.description, i.size, i.units, v.vendorName, p.brand, p.upc
            FROM products AS p 
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN vendorItems AS i ON p.default_vendor_id=i.vendorID AND p.upc=i.upc
            WHERE p.upc = ?
        ");

        $myskus = array();
        foreach ($myitems as $upc) {
            if ($upc < 9999) {
                $res = $dbc->execute($prep, $upc);
                $row = $dbc->fetchRow($res);
                // don't print barcodes that are too wide for slotted space
                $row['sku'] = (strlen($row['sku']) > 8) ? 1 : $row['sku'];
                $myskus[] = $row['sku'];
            }
        }

        $posX = 60;
        $posY = 105;

        foreach (array(0,1,2,3) as $k => $v) {
            // create SKU barcode
            $img = Image_Barcode2::draw(isset($myskus[$k]) ? $myskus[$k] : '', 'code128', 'png', false, 7, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);

            // create UPC barcode
            $img2 = Image_Barcode2::draw(isset($upcs[$k]) ? $upcs[$k] : '', 'code128', 'png', false, 7, 1, false);
            $file2 = tempnam(sys_get_temp_dir(), 'imgo') . '.png';
            imagepng($img2, $file2);

            if ($v == 0) {
                $pdf->Image($file, $posX-3, $posY-5);
                $pdf->Image($file2, $posX-3, $posY+1);
            } elseif ($v == 1) {
                $pdf->Image($file, $posX+94, $posY-5);
                $pdf->Image($file2, $posX+94, $posY+1);
            } elseif ($v == 2) {
                $pdf->Image($file, $posX-3, $posY+97);
                $pdf->Image($file2, $posX-3, $posY+103);
            } elseif ($v == 3) {
                $pdf->Image($file, $posX+94, $posY+97);
                $pdf->Image($file2, $posX+94, $posY+103);
            }
            unlink($file);
        }

        $filename = "/var/www/html/Scannie/content/Testing/tmp_barcodes.pdf";
        $pdf->Output($filename, 'F');
        chmod($filename, 0666);

        return false;
    }

    protected function get_view()
    {
        return <<<HTML

<div class="row">
    <div class="col-lg-12">
    <div>
        <ul>
            <li><a href= "NutriFactEntry.php">Enter Nutrition Facts</a></li>
            <li><a href= "IngredientSOPFormatter.php">Ingredients SOP Formatter</a></li>
        </ul>
    </div>
    <form method="post" action="ScannieBulkWrapper.php">
        <div class="row">
            <div class="col-lg-6">
                <div class="form-group">
                    <label>PLU(s)</label>
                    <textarea name="id" class="form-control" rows="8"></textarea>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="form-group">
                    <label>Sales Batch ID(s)</label> <i>Optional - use batch price instead of current price</i>
                    <textarea name="bid" class="form-control" rows="8"></textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" value="1" name="organic" checked />
                Organic
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" value="1" name="local" />
                Local
            </label>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default">Make Signs</button>
        </div>
    </form>
    </div>
</div>
HTML;
    }
}

FannieDispatch::conditionalExec();

