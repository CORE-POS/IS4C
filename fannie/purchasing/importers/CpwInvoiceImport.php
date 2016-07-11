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

class CpwInvoiceImport extends FannieRESTfulPage 
{

    public $themed = true;
    protected $header = 'Import CPW Invoice';
    protected $title = 'Import CPW Invoice';

    public $description = '[CPW Invoice Import] is a specialized tool for importing CPW invoices';
    public $page_set = 'Purchasing';

    private $filedata;

    public function preprocess()
    {
        $this->__routes[] = 'post<file>';
        $this->__routes[] = 'post<vendorID><invoice_num><po_num><invoice_date>';
        $this->__routes[] = 'get<complete>';

        return parent::preprocess();
    }

    public function post_vendorID_invoice_num_po_num_invoice_date_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $skus = FormLib::get('sku', array());
        $upcs = FormLib::get('upc', array());
        $descriptions = FormLib::get('desc', array());
        $cases = FormLib::get('qty', array());
        $units = FormLib::get('units', array());
        $sizes = FormLib::get('size', array());
        $costs = FormLib::get('unitCost', array());
        $totals = FormLib::get('totalCost', array());

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
                AND v.upc <> \'0000000000000\'
                AND v.upc <> \'\'
                AND v.upc IS NOT NULL
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

        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?complete=' . $orderID);

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
        if (!isset($path_parts['extension']) || (strtolower($path_parts['extension']) != 'xls' && strtolower($path_parts['extension'] != 'csv'))) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Uploaded file is not a .csv or .xls');");
            $this->__route_stem = 'get';

            return true;
        }

        $name_with_extension = tempnam(sys_get_temp_dir(), 'cpw') . '.' . $path_parts['extension'];
        $tmpfile = $_FILES['file-upload']['tmp_name'];
        move_uploaded_file($tmpfile, $name_with_extension);

        $this->filedata = \COREPOS\Fannie\API\data\FileData::fileToArray($name_with_extension);
        unlink($name_with_extension);

        return true;
    }

    public function post_file_view()
    {
        $inv_no = '';
        $inv_date = '';
        $po_no = '';
        $line = 0;
        for ($line; $line<count($this->filedata); $line++) {
            $data = $this->filedata[$line];
            if (isset($data[4]) && $data[4] == 'Invoice No:') {
                $inv_no = $data[6];    
            } elseif (isset($data[4]) && $data[4] == 'PO #:') {
                $po_no = $data[6];
            } elseif (isset($data[4]) && $data[4] == 'Order Date:') {
                $inv_date = \COREPOS\Fannie\API\data\FileData::excelFloatToDate($data[6]);
            } elseif (in_array('Ordered', $data) && in_array('Price', $data)) {
                break; // item data begins
            }
        }
        $ret = '<form method="post">';
        $ret .= '<div class="form-group">
            <label>Invoice #</label>
            <input type="text" name="invoice_num" class="form-control" value="' . $inv_no . '" />
            </div>';
        $ret .= '<div class="form-group">
            <label>PO #</label>
            <input type="text" name="po_num" class="form-control" value="' . $po_no . '" />
            </div>';
        $ret .= '<div class="form-group">
            <label>Invoice Date</label>
            <input type="text" name="invoice_date" class="form-control date-field" value="' . $inv_date . '" />
            </div>';
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $vendors = new VendorsModel($dbc);
        $ret .= '<div class="form-group">
            <label>Vendor</label>
            <select class="form-control" name="vendorID">';
        $vendorID = 0;
        foreach ($vendors->find('vendorName') as $obj) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                (preg_match('/cpw/i', $obj->vendorName()) ? 'selected' : ''),
                $obj->vendorID(), $obj->vendorName());
            if (preg_match('/cpw/i', $obj->vendorName())) {
                $vendorID = $obj->vendorID();
            }
        }
        $ret .= '</select></div>';
        $ret .= '<table class="table">
            <tr>
                <th>SKU</th>
                <th>UPC</th>
                <th>Item</th>
                <th>Unit Size</th>
                <th>Case Size</th>
                <th># of Cases</th>
                <th>Unit Cost</th>
                <th>Total Cost</th>
            </tr>';
        $upcP = $dbc->prepare('
            SELECT upc
            FROM vendorItems
            WHERE vendorID=?
                AND sku=?');
        for ($line; $line<count($this->filedata); $line++) {
            $data = $this->filedata[$line];
            if (!is_numeric($data[0]) || !is_numeric($data[5]) || !is_numeric($data[7])) {
                // not an item line
                continue;
            }
            $numCases = $data[1];
            $description = $data[2];
            $sku = $data[5];
            $caseCost = $data[6];
            $totalCost = $data[7];
            list($caseSize, $unitSize) = $this->caseAndUnit($description);

            $unitCost = $caseCost / $caseSize;
            $upc = '';
            $upcR = $dbc->execute($upcP, array($vendorID, $sku));
            if ($upcR && $dbc->numRows($upcR)) {
                $upcW = $dbc->fetchRow($upcR);
                $upc = $upcW['upc'];
            }

            $ret .= sprintf('<tr>
                <td><input type="text" name="sku[]" size="8" value="%s" class="form-control input-sm" /></td>
                <td><input type="text" name="upc[]" size="13" value="%s" class="form-control upc-field input-sm" /></td>
                <td><input type="text" name="desc[]" value="%s" class="form-control input-sm" /></td>
                <td><input type="text" name="size[]" size="3" value="%s" class="form-control input-sm" /></td>
                <td><input type="text" name="units[]" size="4" value="%s" class="form-control input-sm" /></td>
                <td><input type="text" name="qty[]" size="3" value="%d" class="form-control input-sm" /></td>
                <td>
                    <div class="input-group">
                        <div class="input-group-addon">$</div>
                        <input type="text" name="unitCost[]" size="5" value="%.2f" class="form-control input-sm" />
                    </div>
                </td>
                <td>
                    <div class="input-group">
                        <div class="input-group-addon">$</div>
                        <input type="text" name="totalCost[]" size="5" value="%.2f" class="form-control input-sm" />
                    </div>
                </td>
                </tr>',
                $sku,
                $upc,
                $description,
                $unitSize,
                $caseSize,
                $numCases,
                $unitCost,
                $totalCost);
        }
        $ret .= '</table>
            <p>
                <button type="submit" class="btn btn-default">Import Invoice</button>
            </p>
            </form>';

        $this->addScript('../../item/autocomplete.js');
        $this->addOnloadCommand("bindAutoComplete('.upc-field', '../../ws/', 'item');\n");

        return $ret;
    }

    private function caseAndUnit($description)
    {
        $caseSize = 1;
        $unitSize = '';
        if (preg_match('/(\d+) *\/(\d+) ?LB/', $description, $matches)) {
            $caseSize = $matches[1] * $matches[2];
            $unitSize = 'LB';
        } elseif (preg_match('/(\d+) *\/(\d+) ?#/', $description, $matches)) {
            $caseSize = $matches[1] * $matches[2];
            $unitSize = 'LB';
        } elseif (preg_match('/(\d+) *- *(\d+) ?LB/', $description, $matches)) {
            $caseSize = ($matches[1] + $matches[2]) / 2.0;
            $unitSize = 'LB';
        } elseif (preg_match('/(\d+) *- *(\d+) ?#/', $description, $matches)) {
            $caseSize = ($matches[1] + $matches[2]) / 2.0;
            $unitSize = 'LB';
        } elseif (preg_match('/(\d+) *LB/', $description, $matches)) {
            $caseSize = $matches[1];
            $unitSize = 'LB';
        } elseif (preg_match('/(\d+) *#/', $description, $matches)) {
            $caseSize = $matches[1];
            $unitSize = 'LB';
        } elseif (preg_match('/(\d+) *- *(\d+) ?CT/', $description, $matches)) {
            $caseSize = ($matches[1] + $matches[2]) / 2.0;
            $unitSize = 'CT';
        } elseif (preg_match('/(\d+) *CT/', $description, $matches)) {
            $caseSize = $matches[1];
            $unitSize = 'CT';
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
        return '<form method="post"
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
            Upload a CPW invoice file. Review the information
            extracted from the file and make any necessary
            adjustments. Finally, import the amended information
            as a new invoice.
            </p>';
    }

}

FannieDispatch::conditionalExec();

