<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

    private $file_content = '';

    public function preprocess()
    {
        $this->__routes[] = 'post<file>';

        return parent::preprocess();
    }

    /**
      Make sure pdftotext and the PDF wrapper for that
      program are available.
    */
    public function readinessCheck()
    {
        if (!class_exists('XPDF\PdfToText')) {
            $this->error_text = 'Missing dependency php-xpdf/php-xpdf; install it using composer';

            return false;
        } else {
            try {
                $obj = XPDF\PdfToText::create();
            } catch (XPDF\Exception\BinaryNotFoundException $e) {
                $this->error_text = 'Cannot locate required binary "pdftotext". Package name
                    may be "poppler-utils" or "xpdf-utils" on Debian based distros or "xpdf"
                    on Red Hat based distros.';
                return false;
            } catch (Exception $e) {
                $this->error_text = 'Unexpected error initializing pdftotext wrapper. Details: '
                    . $e->getMessage();
                return false;
            }
        }

        return true;
    }

    public function post_file_handler()
    {
        if (!isset($_FILES['file-upload'])) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'No file uploaded!');");
            $this->__route_stem = 'get';

            return true;
        } 

        if ($_FILES['file-upload']['error'] != UPLOAD_ERR_OK) {
            $msg = '';
            switch($_FILES['file-upload']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $msg = 'File is too big.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $msg = 'Upload did not complete.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $msg = 'No file was uploaded.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $msg = 'No place to put the file.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $msg = 'Permission problem saving file.';
                    break;
                default:
                    $msg = 'Unknown problem uploading the file.';
                    break;
            }
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
        echo '<table class="table">';
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
            } elseif (count($fields) >= 10) {
                $numCases = $fields[1];
                $base = 2;
                if (!preg_match('/^\d{4,}$/', $fields[2]) && preg_match('/^\d{4,}$/', $fields[3])) {
                    $base = 3;
                } elseif (!preg_match('/^\d{4,}$/', $fields[2]) && !preg_match('/^\d{4,}$/', $fields[3])) {
                    // no sku found
                    continue;
                } 
                $sku = $fields[$base];
                $description = $fields[$base+1];
                $caseSize = 1;
                $unitSize = 1;
                if (preg_match('/(\d+)x\d+/', $description, $m)) {
                    $caseSize = $m[1];
                    $tmp = preg_split('/\d+x/', $description, 2);
                    if (count($tmp) >= 2) {
                        $tmp = explode(' ', $tmp[1], 3);
                        if (count($tmp) >= 2) {
                            $unitSize = $tmp[0] . $tmp[1];
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

                $upc = $fields[$base+3];
                if (strlen($upc) == 5 && $upc[0] == '9') {
                    $upc = substr($upc, 1);
                } elseif (strstr($upc, '-')) {
                    $upc = substr($upc, 0, strlen($upc)-1);
                }
                $upc = str_replace('-', '', $upc);
                $upc = BarcodeLib::padUPC($upc);

                $receivedCost = trim($fields[$base+6], '$');
                printf('<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%.2f</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%.2f</td>
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

}

FannieDispatch::conditionalExec();

