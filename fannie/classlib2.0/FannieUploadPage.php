<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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

if (!class_exists('FanniePage')) {
    include_once(dirname(__FILE__).'/FanniePage.php');
}
if (!class_exists('FormLib')) {
    include_once(dirname(__FILE__).'/lib/FormLib.php');
}
if (!class_exists('Spreadsheet_Excel_Reader')) {
    include_once(dirname(__FILE__).'/../src/Excel/xls_read/reader.php');
}
if (!class_exists('PHPExcel_IOFactory')) {
    include_once(dirname(__FILE__).'/../src/Excel/xlsx_read/Classes/PHPExcel.php');
}

/**
  @class FanniePage
  Class for drawing screens
*/
class FannieUploadPage extends FanniePage 
{

    public $required = true;

    public $description = "
    Base class for handling file uploads
    ";

    public $page_set = 'Import Tools';

    /**
      Function for drawing page content.
      form_content, preview_content, and
      results content provided by default.
    */
    protected $content_function = "form_content";

    /**
      Show a preview where the user can choose
      columns that contain data
    */
    protected $preview = true;
    /**
      Define user-selectable options
    */
    protected $preview_opts = array(
        'example' => array(
            'name' => 'upc',
            'display_name' => 'UPC',
            'default' => 7,
            'required' => true,
        ),
    );

    protected $preview_selections = array();

    protected $upload_field_name = 'FannieUploadFile';
    protected $upload_file_name = '';
    protected $allowed_extensions = array('csv','xls','xlsx');

    protected $error_details = 'n/a';

    /**
      Split uploaded file into multiple smaller files
      process_file() will be called separately for
      each smaller file. split_start() and split_end()
      are called at the beginning and end of the whole
      process. Only works with CSV files in *nix environments.
    */
    protected $use_splits = false;

    /**
      Make repeated AJAX calls to process part of the file
      and provide progress feedback. Similar to splitting
      in that process_file is called repeatedly and split_start()
      and split_end() called once each at the very beginning
      and end. Works with all supported file types BUT must
      be able to load the entire file within PHP's memory_limit.
      Memory allocated to load the file can be substantially
      higher than the raw file size.
    */
    protected $use_js = false;

    /**
      Handle pre-display tasks such as input processing
      @return
       - True if the page should be displayed
       - False to stop here
    */
    public function preprocess()
    {
        global $FANNIE_URL, $FANNIE_OP_DB;

        $col_select = FormLib::get_form_value('cs','');

        if (isset($_FILES[$this->upload_field_name])) {
            /* file upload submitted */
            $try = $this->processUpload();
            if ($try) {
                $this->content_function = 'basicPreview';
                $this->window_dressing = False;
                $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
            } else {
                $this->content_function = 'uploadError';
            }
        } else if (is_array($col_select)) {
            $this->upload_file_name = FormLib::get_form_value('upload_file_name','');
            
            /* column selections submitted */
            for($i=0;$i<count($col_select);$i++) {
                if ($col_select[$i] !== '') {
                    $this->preview_selections[$col_select[$i]] = $i;
                }
            }
            $chk_required = true;
            $this->error_details = '';
            foreach($this->preview_opts as $opt) {
                if ($opt['required'] == true && !isset($this->preview_selections[$opt['name']])) {
                    $this->error_details .= '<li><b>'.$opt['display_name'].'</b> is required</li>';
                    $chk_required = false;
                }
            }

            if ($chk_required == true) {

                $try = false;
                if ($this->use_js) {
                    /**
                      Create temporary database table
                      and load all records into the table
                    */
                    if (FormLib::get('ajaxOp', '') == 'upload') {
                        $ret = array('error'=>0);
                        $fileData = $this->fileToArray();
                        $offset = FormLib::get('offset', 0);
                        $chunk_size = 200;

                        if (count($fileData) == 0) {
                            $ret['error'] = 'File is empty';
                            unlink($this->upload_file_name);
                            echo json_encode($ret);
                            return false;
                        }

                        $num_columns = count($fileData[0]);

                        /** first pass; create table **/
                        if ($offset == 0) {
                            $this->split_start();
                        }

                        // Extract lines & process
                        $lines = array();
                        for ($i=$offset; $i<count($fileData); $i++) {
                            if (count($fileData[$i]) != $num_columns) {
                                continue;
                            }
                            $lines[] = $fileData[$i];
                            if (count($lines) > $chunk_size) {
                                break;
                            }
                        }
                        $try = $this->process_file($lines);

                        $done = ($offset + $chunk_size) > count($fileData) ? true : false;

                        if (count($lines) == 0 && !$done) {
                            $ret['error'] = 'Upload into database failed';
                            unlink($this->upload_file_name);
                            echo json_encode($ret);
                            return false;
                        } elseif (!$done) {
                            $ret['num_lines'] = count($fileData);
                            $ret['cur_record'] = $offset + $chunk_size;
                            $ret['done'] = $done;
                            echo json_encode($ret);
                            return false;
                        } else {
                            $ret['cur_record'] = 0;
                            $ret['done'] = $done;
                            $this->split_end();
                            unlink($this->upload_file_name);
                            echo json_encode($ret);
                            return false;
                        }
                    /**
                      Render page that includes ajax javascript
                    */
                    } else {
                        $this->content_function = 'ajaxContent';

                        return true;
                    }
                } elseif ($this->use_splits) {
                    /* break file into pieces */
                    $files = FormLib::get_form_value('f');
                    if ($files === '') {
                        $tempdir = dirname($this->upload_file_name);
                        if (!is_dir($tempdir.'/splits')) {
                            mkdir($tempdir.'/splits');
                        }
                        $orig = escapeshellarg($this->upload_file_name);
                        $new = escapeshellarg($tempdir.'/splits/csvUNFISPLIT');
                        system("split -l 2500 $orig $new");
                        $dir = opendir($tempdir.'/splits');
                        while ($current = readdir($dir)) {
                            if (!strstr($current,"UNFISPLIT")) {
                                continue;
                            }
                            $files[$i++] = $current;
                        }
                        closedir($dir);
                        unlink($this->upload_file_name);
                        $this->split_start();
                    }

                    if (!is_array($files)) {
                        $this->error_detail = 'Split problem';
                        $this->content_function = 'results_content';
                    } else {
                        /* process one file */
                        $this->upload_file_name = sys_get_temp_dir().'/fannie/splits/'.array_pop($files);                                    
                        $try = $this->process_file($this->fileToArray());
                        unlink($this->upload_file_name);
                        if ($try && count($files) > 0) {
                            /* if more remain, redirect back to self */
                            $url = $_SERVER['PHP_SELF'].'?';
                            foreach($files as $f) {
                                $url .= 'f[]='.$f.'&';
                            }
                            foreach($col_select as $c) {
                                $url .= 'cs[]='.$c.'&';
                            }
                            $url = rtrim($url,'&');
                            header('Location: '.$url);

                            return false;
                        } else if ($try && count($files) == 0) {
                            /* finished; call cleanup function */
                            $this->split_end();
                        }
                    }
                } else { // not using splits
                    $try = $this->process_file($this->fileToArray());
                }

                if ($try) {
                    $this->content_function = 'results_content';
                } else {
                    $this->content_function = 'processingError';
                }

                if (file_exists($this->upload_file_name)) {
                    unlink($this->upload_file_name);
                }
            } else { // selected columns were invalid; redisplay preview screen
                $this->content_function = 'basicPreview';
                $this->window_dressing = False;
                $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
            }
        }

        return true;
    }

    /**
      Store uploaded file
      @return True on success, False on error
    */
    protected function processUpload() 
    {
        /* use a dedicated temp directory */
        $tpath = sys_get_temp_dir().'/fannie/';
        if (!is_dir($tpath)) {
            if (!mkdir($tpath)) {
                $this->error_details = 'Directory error';
                return false;
            }
        }

        $tmpfile = $_FILES[$this->upload_field_name]['tmp_name'];
        $path_parts = pathinfo($_FILES[$this->upload_field_name]['name']);
        $extension = isset($path_parts['extension']) ? strtolower($path_parts['extension']) : '';
        $zip = false;
        if ($_FILES[$this->upload_field_name]['error'] != UPLOAD_ERR_OK) {
            $msg = '';
            switch($_FILES[$this->upload_field_name]['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $msg = 'File is too big. Try zipping it.';
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
            if (file_exists($tmpfile)) {
                unlink($tmpfile);
            }
            $this->error_details = $msg;

            return false;
        }

        /* validate file by extension */
        if ($extension == 'zip') {
            $zip = true;
            /* if it's a zip file, try to unzip it */
            if(!class_exists('ZipArchive')) {
                unlink($tmpfile);
                $this->error_details = 'No ZIP support';
                return false;
            }
            $za = new ZipArchive();
            if ($za->open($tmpfile) !== true) {
                unlink($tmpfile);
                $this->error_details = 'Bad ZIP file';
                return false;
            }
            $found = false;
            /*
              Go through all the files in the zip archive
              If one has a valid extension, extract it to
              the temp directory, remove the zip file,
              and update $tmpfile so it points as the extracted
              file
            */
            for($i=0;$i<$za->numFiles;$i++) {
                $entry = $za->getNameIndex($i);
                $ext = strtolower(substr($entry,-4));
                if ($ext[0] == '.' && in_array(substr($ext,1),$this->allowed_extensions)) {
                    $found = true;
                    $za->extractTo($tpath, $entry);
                    $za->close();
                    unlink($tmpfile);
                    $tmpfile = realpath($tpath.'/'.$entry);
                    $extension = substr($ext,1);
                    break;
                }
            }
            if (!$found) {
                $za->close();
                unlink($tmpfile);
                $this->error_details = 'Bad ZIP contents';

                return false;
            }
        } else if (!in_array($extension,$this->allowed_extensions)) {
            $this->error_details = 'Bad file';
            unlink($tmpfile);

            return false;
        }

        /* get a unique temp file name */
        $this->upload_file_name = tempnam($tpath,substr($extension,-3));
        if ($this->upload_file_name === false) {
            $this->upload_file_name = '';
            unlink($tmpfile);
            $this->error_details = 'No name found';

            return false;
        }

        $func = 'move_uploaded_file';
        /* PHP doesn't recognize the extracted file as "uploaded" */
        if ($zip) {
            $func = 'rename';
        }

        /* rename the uploaded file */
        if ($func($tmpfile, $this->upload_file_name) === False) {
            $this->upload_file_name = '';
            unlink($tmpfile);
            unlink($this->upload_file_name);
            $this->error_details = 'Could not rename';

            return false;
        }

        /* if we got here, nothing went wrong */
        return true;
    }

    /**
      Do something with the uploaded data
      @param $linedata an array of arrays
       (each inner area is one line of data)
      @return True on success, False on error
    */
    public function process_file($linedata)
    {
        return true;
    }

    /**
      Called before processing split files
      process_file() will be called multiple times
      so anything that should only happen once
      goes here instead.
    */
    public function split_start()
    {

    }

    /**
      Called after processing all split files
      process_file() will be called multiple times
      so anything that should only happen once
      goes here instead.
    */
    public function split_end()
    {

    }

    /**
      Display if there is an upload error
      @return An HTML string
    */
    protected function uploadError()
    {
        return sprintf('Something went wrong uploading the file. 
            Details: <em>%s</em>. 
            <a href="%s">Try again</a>?',
            $this->error_details,
            $_SERVER['PHP_SELF']);
    }

    /**
      Display if there is an processing error
      @return An HTML string
    */
    protected function processingError()
    {
        return sprintf('Something went wrong processing the file. 
            Details: <em>%s</em>. 
            <a href="%s">Try again</a>?',
            $this->error_details,
            $_SERVER['PHP_SELF']);
    }

    /**
      Use the function $this->content_function to generate
      the page contents.
      @return An HTML string
    */
    public function bodyContent()
    {
        if (!isset($this->content_function))
            $this->content_function = 'form_content';
        if (!method_exists($this,$this->content_function))
            $this->content_function = 'form_content';
        $func = $this->content_function;
        $ret = $this->$func();
        switch($this->content_function){
        case 'form_content':
            $ret .= $this->basicForm();
            break;
        }

        return $ret;
    }
    
    /**
      Any extra content before the form itself.
      @return An HTML string
    */
    public function form_content()
    {
        return "";
    }

    /**
      Default form automatically included with form_content()
      @return An HTML string
    */
    protected function basicForm()
    {
        return sprintf('
        <form id="FannieUploadForm" enctype="multipart/form-data" 
            action="%s" method="post">
        <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
        Filename: <input type="file" id="%s" name="%s" />
        <input type="submit" value="Upload File" />
        </form>', $_SERVER['PHP_SELF'],
        $this->upload_field_name,
        $this->upload_field_name);
    }

    /**
      Any extra content to show with the preview
      @return An HTML string
    */
    public function preview_content()
    {
        return "";
    }

    /**
      Default preview of uploaded data
      @return An HTML string
    */
    protected function basicPreview()
    {
        $ret = '<h3>Select columns</h3>';
        /* show any errors */
        if ($this->error_details != 'n/a' && $this->error_details != '') {
            $ret .= '<ul style="border: solid 1px red;">';
            $ret .= $this->error_details;
            $ret .= '</ul>';
        }
        $ret .= sprintf('<form action="%s" method="post">',$_SERVER['PHP_SELF']);
        $ret .= $this->preview_content();
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';

        /* Read the first five rows from the file
           for a preview. Determine row width at
           the same time */
        $fp = fopen($this->upload_file_name,'r');
        $width = 0;
        $table = "";
        $linedata = $this->fileToArray(5);
        foreach ($linedata as $data) {
            $j=0;
            foreach($data as $d) {
                $table .='<td>'.$d.'</td>';
                $j++;
            }
            if ($j > $width) $width = $j;
            $table .= '</tr>';
        }
        fclose($fp);

        /* draw select boxes for each column */
        $ret .= '<tr>';
        for ($i=0;$i<$width;$i++) {
            $ret .= '<td><select class="columnSelector" name="cs[]">';
            $ret .= '<option value="">(ignore)</option>';
            foreach($this->preview_opts as $key => $info) {
                $ret .= sprintf('<option value="%s" %s>%s</option>',
                    $info['name'],
                    ($i==$info['default']?'selected':''),
                    $info['display_name']);
            }
            $ret .= '</td>';
        }
        $ret .= '</tr>';
        $ret .= $table . '</table>';
        $ret .= sprintf('<input type="hidden" name="upload_file_name" value="%s" />',
                $this->upload_file_name);
        $ret .= '<input type="submit" value="Continue" />';
        $ret .= '</form>';

        return $ret;
    }

    /**
      This function ensures column selections are unique.
    */
    public function javascript_content()
    {
        ob_start();
        ?>
        $(document).ready(function(){
            $('.columnSelector').change(function(){
                var myElem = this;
                $('.columnSelector').each(function(i){
                    if (this != myElem && $(this).val() == $('*:focus').val())
                        $(this).val('');
                });
            });
        });
        function doUpload(file_name, offset)
        {
            var data = 'ajaxOp=upload&upload_file_name=' + encodeURIComponent(file_name);
            data += '&' + $('#fieldInfo :input').serialize() + '&offset=' + offset;
            if (offset == 0) {
                $('#uploadingSpan').html('Uploading data');
            }
            $.ajax({
                type: 'post',
                dataType: 'json',
                data: data,
                success: function(resp) {
                    if (resp.error == 0) {
                        if (!resp.done) {
                            $('#numLines').html('/'+resp.num_lines+' lines');
                            $('#uploadingSpan').html('Uploading '+resp.cur_record);
                            doUpload(file_name, resp.cur_record);
                        } else {
                            $('#progressSpan').html('Processing 0');
                            $('#numRecords').html('/'+resp.num_records+' records');
                            $('#resultsSpan').html('Upload complete');
                        }
                    } else {
                        $('#uploadingSpan').html('Upload error: ' + resp.error);
                    }
                }
            });
        }
        <?php
        return ob_get_clean();
    }

    /**
      What to display when the upload is done 
      @return An HTML string
    */
    public function results_content()
    {
        return "";
    }

    public function ajaxContent()
    {
        $ret = '<div id="progressDiv">
            <span id="uploadingSpan"></span><span id="numLines"></span><br />
            <span id="progressSpan"></span><span id="numRecords"></span><br />
            <span id="resultsSpan"></span>
            </div>';
        $ret .= '<div id="fieldInfo" style="display:none;">';
        foreach (FormLib::get('cs', array()) as $column) {
            $ret .= sprintf('<input type="hidden" name="cs[]" value="%s" />', $column);
        }
        $ret .= '</div>';

        $this->add_onload_command("doUpload('" . $this->upload_file_name . "', 0);");

        return $ret;
    }

    /**
      Get the numerical index that the user selected for
      a given column
      @param $name the name (as defined in $this->preview_opts)
      @return Integer index if available otherwise False
    */
    protected function get_column_index($name)
    {
        if (isset($this->preview_selections[$name])) {
            return $this->preview_selections[$name];
        } else {
            return false;
        }
    }

    /**
      Get two-dimensional array of file data
      @param $limit if specified only return $limit records
      @return An array of arrays. Each inner array
        represents one line of data
    */
    protected function fileToArray($limit=0) 
    {
        if (substr(basename($this->upload_file_name),0,3) == 'csv') {
            return $this->csvToArray($limit);
        } elseif (substr(basename($this->upload_file_name),0,3) == 'xls') {
            return $this->xlsToArray($limit);
        } elseif (substr(basename($this->upload_file_name),0,3) == 'lsx') {
            // php tempfile nameing only allows a three character prefix
            return $this->xlsxToArray($limit);
        } else {
            return array();
        }
    }

    /**
      Helper for csv files. See fileToArray()
    */
    protected function csvToArray($limit=0)
    {
        $fp = fopen($this->upload_file_name,'r');
        if (!$fp) {
            return array();
        }
        $ret = array();
        while(!feof($fp)) {
            $ret[] = fgetcsv($fp);
            if ($limit != 0 && count($ret) >= $limit) {
                break;
            }
        }
        fclose($fp);

        return $ret;
    }

    /**
      Helper for xls files. See fileToArray()
    */
    protected function xlsToArray($limit)
    {
        if (!class_exists('Spreadsheet_Excel_Reader')) {
            return array();
        }

        $data = new Spreadsheet_Excel_Reader();
        $data->read($this->upload_file_name);

        $sheet = $data->sheets[0];
        $rows = $sheet['numRows'];
        $cols = $sheet['numCols'];
        $ret = array();
        for($i=1; $i<=$rows; $i++) {
            $line = array();
            for ($j=1; $j<=$cols; $j++) {
                if (isset($sheet['cells'][$i]) && isset($sheet['cells'][$i][$j])) {
                    $line[] = $sheet['cells'][$i][$j];
                } else {
                    $line[] = '';
                }
            }
            $ret[] = $line;
            if ($limit != 0 && count($ret) >= $limit) {
                break;
            }
        }

        return $ret;
    }

    protected function xlsxToArray($limit)
    {
        if (!class_exists('PHPExcel_IOFactory')) {
            return array();
        }

        $objPHPExcel = PHPExcel_IOFactory::load($this->upload_file_name);
        $sheet = $objPHPExcel->getActiveSheet();
        $rows = $sheet->getHighestRow();
        $cols = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        $ret = array();
        for ($i=1; $i<=$rows; $i++) {
            $new = array();
            for($j=0; $j<=$cols; $j++) {
                $new[] = $sheet->getCellByColumnAndRow($j,$i)->getValue();
            }
            $ret[] = $new;
            if ($limit != 0 && count($ret) >= $limit) {
                break;
            }
        }

        return $ret;
    }
}

