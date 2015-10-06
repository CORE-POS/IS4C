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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PDFtoCSVPage extends FannieRESTfulPage 
{

    public $themed = true;
    protected $header = 'PDF to CSV';
    protected $title = 'PDF to CSV';

    public $description = '[PDF to CSV] attempts to convert PDF text to tabular data.';

    private $file_content = '';

    public function preprocess()
    {
        $this->__routes[] = 'post<file>';
        $this->__routes[] = 'post<download>';

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

    public function post_download_handler()
    {
        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment; filename="PDFasCSV.csv"');
        echo FormLib::get('download');

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
        unlink($_FILES['file-upload']['tmp_name']);

        return true;
    }

    public function post_file_view()
    {
        $ret = '<form method="post">';
        $ret .= '<textarea name="download" rows="20" cols="150">';
        foreach (preg_split('/[\r\n]+/', $this->file_content, -1, PREG_SPLIT_NO_EMPTY) as $line) {
            foreach (preg_split('/(  +)|(\t+)/', $line, -1, PREG_SPLIT_NO_EMPTY) as $piece) {
                $ret .= '"' . trim($piece) . '",';
            }
            $ret .= "\r\n";
        }
        $ret .= '</textarea>';
        $ret .= '<p><button type="submit" class="btn btn-default">Download CSV</button></p>';
        $ret .= '</form>';

        return $ret;
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
            Upload a PDF, extract text, and format data.
            </p>';
    }

}

FannieDispatch::conditionalExec();

