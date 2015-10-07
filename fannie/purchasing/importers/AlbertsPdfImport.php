<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class AlbertsPdfImport extends FannieRESTfulPage 
{

    public $themed = true;
    protected $header = 'Import Alberts Invoice PDF';
    protected $title = 'Import Alberts Invoice PDF';

    public $description = '[Import Alberts Invoice] specialized tool to import Alberts invoices from PDFs.';
    public $page_set = 'Purchasing';

    private $file_content = '';

    public function preprocess()
    {
        $this->__routes[] = 'post<file>';
        $this->__routes[] = 'post<vendorID><invoice_num><po_num><invoice_date>';
        $this->__routes[] = 'get<complete>';

        return parent::preprocess();
    }

    /**
      Make sure pdftotext and the PDF wrapper for that
      program are available.
    */
    public function readinessCheck()
    {
        $ready = \COREPOS\Fannie\API\lib\UploadLib::pdfUploadEnabled();
        if ($ready !== true) {
            $this->error_text = $ready;
            return false;
        } else {
            return true;
        }
    }

    public function post_vendorID_invoice_num_po_num_invoice_date_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $skus = FormLib::get('sku', array());
        $upcs = FormLib::get('upc', array());
        $descriptions = FormLib::get('description', array());
        $totals = FormLib::get('total', array());
        $cases = FormLib::get('cases', array());
        $units = FormLib::get('units', array());
        $sizes = FormLib::get('size', array());
        $costs = FormLib::get('cost', array());

        $order = new PurchaseOrderModel($dbc);
        $order->vendorID($this->vendorID);
        $order->creationDate($this->invoice_date);
        $order->placed(1);
        $order->placedDate($this->invoice_date);
        $order->vendorOrderID($this->po_num);
        $order->vendorInvoiceID($this->invoice_num);
        $order->userID(0);
        $orderID = $order->save();

        $checkP = $dbc->prepare('
            SELECT v.sku
            FROM vendorItems AS v
            WHERE v.vendorID=?
                AND v.sku=?');
        $vendorItem = new VendorItemsModel($dbc);

        $item = new PurchaseOrderItemsModel($dbc);

        for ($i=0; $i<count($skus); $i++) {
            $sku = $skus[$i];
            $upc = BarcodeLib::padUPC(isset($upcs[$i]) ? $upcs[$i] : '');
            $qty = isset($cases[$i]) ? $cases[$i] : 1;
            $caseSize = isset($units[$i]) ? $units[$i] : 1;
            $unitSize = isset($sizes[$i]) ? $sizes[$i] : '';
            $unitCost = isset($costs[$i]) ? $costs[$i] : 0;
            $totalCost = isset($totals[$i]) ? $totals[$i] : 0;
            $desc = isset($descriptions[$i]) ? substr($descriptions[$i], 0, 50) : '';

            $item->reset();
            $item->orderID($orderID);
            $item->sku($sku);
            $item->quantity($qty);
            $item->unitCost($unitCost);
            $item->caseSize($caseSize);
            $item->receivedDate($this->invoice_date);
            $item->receivedQty($qty);
            $item->receivedTotalCost($totalCost);
            $item->unitSize($unitSize);
            $item->brand('');
            $item->description($desc);
            $item->internalUPC($upc);
            $item->save();

            /**
              Add entries to vendor catalog if they don't exist
            */
            $checkR = $dbc->execute($checkP, array($this->vendorID, $sku));
            if ($checkR && $dbc->numRows($checkR) == 0) {
                $vendorItem->vendorID($this->vendorID);
                $vendorItem->sku($sku);
                $vendorItem->upc($upc);
                $vendorItem->description($desc);
                $vendorItem->brand('');
                $vendorItem->units($caseSize);
                $vendorItem->size($unitSize);
                $vendorItem->cost($unitCost);
                $vendorItem->vendorDept(0);
                $vendorItem->save();
            }
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?complete=' . $orderID);

        return false;
    }

    public function post_file_handler()
    {
        if (!isset($_FILES['file-upload'])) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'No file uploaded!');");
            $this->__route_stem = 'get';

            return true;
        } 

        if ($_FILES['file-upload']['error'] != UPLOAD_ERR_OK) {
            $msg = \COREPOS\Fannie\API\lib\UploadLib::errorToMessage($_FILES['file-upload']['error']);
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', '$msg');");
            $this->__route_stem = 'get';

            return true;
        }

        $path_parts = pathinfo($_FILES['file-upload']['name']);
        if (!isset($path_parts['extension']) || strtolower($path_parts['extension']) != 'pdf') {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Uploaded file is not a .pdf');");
            $this->__route_stem = 'get';

            return true;
        }

        try {
            $obj = XPDF\PdfToText::create();
            $obj->setOutputMode('layout');
            $this->file_content = $obj->getText($_FILES['file-upload']['tmp_name']);
        } catch (Exception $e) {
            $msg = str_replace("'", '', $e->getMessage());
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', '$msg');");
            $this->__route_stem = 'get';
        }

        return true;
    }

    public function post_file_view()
    {
        $invoice_num = false;
        $invoice_date = false;
        $po_num = false;
        $items = array();

        $lines = explode("\n", $this->file_content);
        $pattern = '/\d+\s+(\d+)\s+[^0-9]*(\d\d\d+)\s+(.*?)\s\s+(.*?)\s+([0-9-]+)\s+\d+\s+\$?([0-9\.]+)\s+\$?([0-9\.]+)\s+\$?([0-9\.]+)/';
        echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        echo '<table class="table">';
        echo '<tr><thead>
            <th>SKU</th>
            <th>UPC</th>
            <th>Description</th>
            <th>Total Cost</th>
            <th># Cases</th>
            <th>Units/Case</th>
            <th>Unit Size</th>
            <th>Unit Cost</th>
            </tr>
            </thead>';
        for ($i=0; $i<count($lines); $i++) {
            $line = trim($lines[$i]);
            $fields = preg_split('/\s{2,}/', $line);
            if (count($fields) == 0) {
                continue;
            }
            if ($i == 1 && count($fields) >= 6) {
                $invoice_num = $fields[2];
                $po_num = $fields[3];
                $invoice_date = $fields[5];
            } else {
                if (!preg_match($pattern, $line, $matches)) {
                    /**
                      Excessively wide fields can break the layout and split the single PDF line
                      into two text lines. Try joining consecutive lines and see if they
                      match the single-line pattern
                    */
                    if ($i+1 < count($lines) && preg_match($pattern, $line . ' ' . $lines[$i+1], $matches)) {
                        $i++;
                    } else {
                        continue;
                    }
                }
                $numCases = $matches[1];
                $sku = $matches[2];
                $description = $matches[3];
                list($caseSize, $unitSize) = $this->caseAndUnit($description);

                $upc = $matches[5];
                if (strlen($upc) == 5 && $upc[0] == '9') {
                    $upc = substr($upc, 1);
                } elseif (strstr($upc, '-')) {
                    $upc = substr($upc, 0, strlen($upc)-1);
                }
                $upc = str_replace('-', '', $upc);
                $upc = BarcodeLib::padUPC($upc);

                $receivedCost = $matches[7];
                printf('<tr>
                    <td><input type="number" class="form-control input-sm" name="sku[]" value="%s" /></td>
                    <td><input type="number" class="form-control input-sm" name="upc[]" value="%s" /></td>
                    <td><input type="text" class="form-control input-sm" name="description[]" value="%s" /></td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-addon">$</span>
                            <input type="text" class="form-control input-sm" name="total[]" value="%.2f" />
                        </div>
                    </td>
                    <td><input type="number" class="form-control input-sm" name="cases[]" value="%s" /></td>
                    <td><input type="text" class="form-control input-sm" name="units[]" value="%s" /></td>
                    <td><input type="text" class="form-control input-sm" name="size[]" value="%s" /></td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-addon">$</span>
                            <input type="text" class="form-control input-sm" name="cost[]" value="%.2f" />
                        </div>
                    </td>
                    </tr>',
                    $sku, $upc, $description,
                    $receivedCost,
                    $numCases,
                    $caseSize,
                    $unitSize,
                    $receivedCost / $numCases / $caseSize
                );
            }
        }
        echo '</table>';
        printf('<div class="form-group">
            <label>Invoice #</label>
            <input type="text" name="invoice_num" value="%s" class="form-control" />
            </div>', $invoice_num); 
        printf('<div class="form-group">
            <label>PO #</label>
            <input type="text" name="po_num" class="form-control" value="%s" />
            </div>', $po_num); 
        printf('<div class="form-group">
            <label>Invoice Date</label>
            <input type="text" class="form-control date-field" name="invoice_date" value="%s" />
            </div>', $invoice_date); 
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $vendors = new VendorsModel($dbc);
        echo '<div class="form-group">
            <label>Vendor</label>
            <select class="form-control" name="vendorID">';
        foreach ($vendors->find('vendorName') as $obj) {
            printf('<option %s value="%d">%s</option>',
                (preg_match('/albert/i', $obj->vendorName()) ? 'selected' : ''),
                $obj->vendorID(), $obj->vendorName());
        }
        echo '</select></div>';
        echo '<p>
            <button type="submit" class="btn btn-default">Import Invoice</button>
            </p>';
        echo '</form>';
    }

    private function caseAndUnit($description)
    {
        $caseSize = 1;
        $unitSize = 1;
        if (preg_match('/(\d+)x\d+/', $description, $m)) {
            $caseSize = $m[1];
            $tmp = preg_split('/\d+x/', $description, 2);
            if (count($tmp) >= 2) {
                $tmp = explode(' ', $tmp[1], 3);
                if (count($tmp) >= 2) {
                    $unitSize = trim($tmp[0] . $tmp[1], ',');
                }
            }
        } elseif (preg_match('/(\d+) lb/i', $description, $m)) {
            $caseSize = $m[1];
            $unitSize = 'lb';
        } elseif (preg_match('/(\d+) ct/i', $description, $m)) {
            $caseSize = $m[1];
            $unitSize = 'ea';
        } elseif (preg_match('/(\d+) pt/i', $description, $m)) {
            $caseSize = $m[1];
            $unitSize = 'pt';
        }

        return array($caseSize, $unitSize);
    }

    public function get_complete_view()
    {
        return '<div class="alert alert-success">Import complete</div>
                <p>
                    <a href="../ViewPurchaseOrders.php?id=' . $this->complete . '"
                        class="btn btn-default">View Order</a>
                </p>';
    }

    public function get_view()
    {
        return '<form action="' . $_SERVER['PHP_SELF'] . '" method="post"
            enctype="multipart/form-data">
            <div id="alert-area"></div>
            <p>
            <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
            <input type="hidden" name="file" value="1" />
            Filename: <input type="file" id="file-upload" name="file-upload" />
            <button type="submit" class="btn btn-default">Upload File</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            Upload a PDF invoice from Alberts. View the information
            that was loaded from file and make any necessary corrections.
            Finally, import the invoice as amended.
            </p>';
    }

}

FannieDispatch::conditionalExec();

