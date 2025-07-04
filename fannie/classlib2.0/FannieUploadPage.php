<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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

namespace COREPOS\Fannie\API;

if (!class_exists('\FannieAPI')) {
    include_once(dirname(__FILE__).'/FannieAPI.php');
}

/**
  @class FanniePage
  Class for drawing screens
*/
class FannieUploadPage extends \FanniePage 
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
            'default' => 0,
            'required' => false,
        ),
    );

    protected $preview_selections = array();

    protected $upload_field_name = 'FannieUploadFile';
    protected $upload_file_name = '';
    protected $upload_original_name = '';
    protected $allowed_extensions = array('csv','xls','xlsx', 'txt');

    protected $error_details = 'n/a';

    /**
        Some files contain a number of introductory lines
        at the begging of the file before the actual data beings.
        Showing them in the column preview isn't helpful and in
        some cases may interfere with detecting the number of
        data columns. This skipping only applies to the preview
        screen. The lines are still present when the whole
        file is processed.

        Using a negative value here enables "automatic line skipping".
        In this case all lines containing fewer than the number of 
        columns defined in $preview_opts are skipped. This can be useful
        if the information preceding the data varies in size.
     */
    protected $skip_first = 0;

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

    private function finishPreviewOpts()
    {
        foreach ($this->preview_opts as $k=>$v) {
            if (!isset($this->preview_opts[$k]['name'])) {
                $this->preview_opts[$k]['name'] = $k;
            }
            if (!isset($this->preview_opts[$k]['display_name'])) {
                $this->preview_opts[$k]['display_name'] = $this->preview_opts[$k]['name'];
            }
            if (!isset($this->preview_opts[$k]['required'])) {
                $this->preview_opts[$k]['required'] = false;
            }
            if (!isset($this->preview_opts[$k]['default'])) {
                $this->preview_opts[$k]['default'] = -1;
            }
        }
    }

    /**
      Handle pre-display tasks such as input processing
      @return
       - True if the page should be displayed
       - False to stop here
    */
    public function preprocess()
    {
        $col_select = \FormLib::get_form_value('cs','');
        $this->finishPreviewOpts();

        if (isset($_FILES[$this->upload_field_name])) {
            /* file upload submitted */
            $try = $this->processUpload();
            if ($try) {
                $this->content_function = 'basicPreview';
                if (!$this->themed) {
                    $this->window_dressing = false;
                }
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // windows has trouble with symlinks
                    $this->addScript($this->config->get('URL') . 'src/javascript/jquery-1.11.1/jquery-1.11.1.min.js');
                } else {
                    $this->addScript($this->config->get('URL') . 'src/javascript/jquery.js');
                }
            } else {
                $this->content_function = 'uploadError';
            }
        } else if (is_array($col_select)) {
            $this->upload_file_name = \FormLib::get_form_value('upload_file_name','');
            $this->original_file_name = \FormLib::get_form_value('original_file_name','');
            
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
                    if (\FormLib::get('ajaxOp', '') == 'upload') {
                        $ret = array('error'=>0);
                        $fileData = $this->fileToArray();
                        $offset = \FormLib::get('offset', 0);
                        $chunk_size = 200;

                        if (count($fileData) == 0) {
                            $ret['error'] = 'File is empty';
                            unlink($this->upload_file_name);
                            echo json_encode($ret);
                            return false;
                        }

                        $num_columns = count($fileData[0]);

                        /** first pass **/
                        if ($offset == 0) {
                            $this->split_start();
                        }

                        // Extract lines & process
                        $lines = array();
                        for ($i=$offset; $i<count($fileData); $i++) {
                            $lines[] = $fileData[$i];
                            if (count($lines) >= $chunk_size) {
                                break;
                            }
                        }
                        $try = $this->process_file($lines, $this->getIndexes());

                        $done = ($offset + $chunk_size) > count($fileData) ? true : false;

                        if (count($lines) == 0 && !$done) {
                            $ret['error'] .= 'Upload into database failed';
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
                    $files = \FormLib::get_form_value('f');
                    if ($files === '') {
                        $tempdir = dirname($this->upload_file_name);
                        if (!is_dir($tempdir.'/splits')) {
                            mkdir($tempdir.'/splits');
                        }
                        $orig = escapeshellarg($this->upload_file_name);
                        $new = escapeshellarg($tempdir.'/splits/csvUNFISPLIT');
                        system("split -l 2500 $orig $new");
                        $dir = opendir($tempdir.'/splits');
                        $i = 0;
                        $files = array();
                        while ($current = readdir($dir)) {
                            if (!strstr($current,"UNFISPLIT")) {
                                continue;
                            }
                            $files[$i] = $current;
                            $i++;
                        }
                        closedir($dir);
                        unlink($this->upload_file_name);
                        $this->split_start();
                    }

                    if (!is_array($files)) {
                        $this->error_details = 'Split problem';
                        $this->content_function = 'results_content';
                    } else {
                        /* process one file */
                        $this->upload_file_name = sys_get_temp_dir().'/fannie/splits/'.array_pop($files);                                    
                        $try = $this->process_file($this->fileToArray(), $this->getIndexes());
                        unlink($this->upload_file_name);
                        if ($try && count($files) > 0) {
                            /* if more remain, redirect back to self */
                            $url = filter_input(INPUT_SERVER, 'PHP_SELF').'?';
                            $url .= array_reduce($files, function($carry, $item){ return $carry . 'f[]=' . $item . '&'; }, '');
                            $url .= array_reduce($col_select, function($carry, $item){ return $carry . 'cs[]=' . $item . '&'; }, '');
                            $url = rtrim($url,'&');
                            header('Location: '.$url);

                            return false;
                        } else if ($try && count($files) == 0) {
                            /* finished; call cleanup function */
                            $this->split_end();
                        }
                    }
                } else { // not using splits
                    $try = $this->process_file($this->fileToArray(), $this->getIndexes());
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
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // windows has trouble with symlinks
                    $this->addScript($this->config->get('URL') . 'src/javascript/jquery-1.11.1/jquery-1.11.1.min.js');
                } else {
                    $this->addScript($this->config->get('URL') . 'src/javascript/jquery.js');
                }
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
        $this->original_file_name = $path_parts['filename'];
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
            $za = new \ZipArchive();
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
      @param $indexes an array of column names and indexes
      @return True on success, False on error
    */
    public function process_file($linedata, $indexes)
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
        return sprintf('<div class="alert alert-danger">
            Something went wrong uploading the file. 
            Details: <em>%s</em>. 
            <a href="%s">Try again</a>?</div>',
            $this->error_details,
            $_SERVER['PHP_SELF']);
    }

    /**
      Display if there is an processing error
      @return An HTML string
    */
    protected function processingError()
    {
        return sprintf('<div class="alert alert-danger">
            Something went wrong processing the file. 
            Details: <em>%s</em>. 
            <a href="%s">Try again</a>?</div>',
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
        <p>
        <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
        Filename: <input type="file" id="%s" name="%s" />
        <button type="submit" class="btn btn-default">Upload File</button>
        </p>
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
            $ret .= '<ul class="alert alert-danger">';
            $ret .= $this->error_details;
            $ret .= '</ul>';
        }
        $ret .= sprintf('<form action="%s" method="post">',$_SERVER['PHP_SELF']);
        $ret .= $this->preview_content();
        if ($this->themed) {
            $ret .= '<div class="table-responsive">';
            $ret .= '<table class="table">';
        } else {
            $ret .= '<div class="table-responsive">';
            $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        }

        /* Read the first five rows from the file
           for a preview. Determine row width at
           the same time */
        $fp = fopen($this->upload_file_name,'r');
        $width = 0;
        $table = "";
        $previewLength = 5 + $this->skip_first;
        if ($this->skip_first < 0) {
            $previewLength = 100;
        }
        $linedata = $this->fileToArray($previewLength);
        $row = -1;
        $shown = 0;
        foreach ($linedata as $data) {
            $j=0;
            $row++;
            if ($row < $this->skip_first && $this->skip_first > 0) continue;
            if ($this->skip_first < 0 && count($data) < count($this->preview_opts)) continue;
            if (is_array($data)) {
                foreach($data as $d) {
                    $table .='<td>'.$d.'</td>';
                    $j++;
                }
            }
            if ($j > $width) $width = $j;
            $table .= '</tr>';
            $shown++;
            if ($shown > 6) break;
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
        $ret .= '</div>';
        $ret .= sprintf('<input type="hidden" name="upload_file_name" value="%s" />',
                $this->upload_file_name);
        $ret .= sprintf('<input type="hidden" name="original_file_name" value="%s" />',
                $this->original_file_name);
        $ret .= '<p><button type="submit" class="btn btn-default">Continue</button></p>';
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
        $ret .= array_reduce(\FormLib::get('cs', array()),
            function ($carry, $column) {
                return $carry . sprintf('<input type="hidden" name="cs[]" value="%s" />', $column);
            }, '');
        foreach ($_POST as $key => $val) {
            if ($key != 'cs') {
                if (is_array($val)) {
                    $ret .= array_reduce($val, function($carry, $item) use ($key) {
                        return $carry . sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $item);
                    }, '');
                } else {
                    $ret .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $val);
                }
            }
        }
        $ret .= '</div>';

        $this->add_onload_command("doUpload('" . $this->upload_file_name . "', 0);");

        return $ret;
    }

    protected function getIndexes()
    {
        $ret = array();
        foreach ($this->preview_opts as $key => $info) {
            $name = isset($info['name']) ? $info['name'] : $key;
            $ret[$name] = $this->getColumnIndex($name);
        }

        return $ret;
    }

    /**
      Get the numerical index that the user selected for
      a given column
      @param $name the name (as defined in $this->preview_opts)
      @return Integer index if available otherwise False
    */
    protected function getColumnIndex($name)
    {
        if (isset($this->preview_selections[$name])) {
            return $this->preview_selections[$name];
        } else {
            return false;
        }
    }

    protected function get_column_index($name)
    {
        return $this->getColumnIndex($name);
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
        } elseif (substr(basename($this->upload_file_name),0,3) == 'txt') {
            return $this->txtToArray($limit);
        } else {
            return array();
        }
    }

    /**
      Helper for csv files. See fileToArray()
    */
    protected function csvToArray($limit=0)
    {
        return \COREPOS\Fannie\API\data\FileData::csvToArray($this->upload_file_name, $limit);
    }

    /**
      Helper for xls files. See fileToArray()
    */
    protected function xlsToArray($limit)
    {
        return \COREPOS\Fannie\API\data\FileData::xlsToArray($this->upload_file_name, $limit);
    }

    protected function xlsxToArray($limit)
    {
        return \COREPOS\Fannie\API\data\FileData::xlsxToArray($this->upload_file_name, $limit);
    }

    protected function txtToArray($limit=0)
    {
        return \COREPOS\Fannie\API\data\FileData::txtToArray($this->upload_file_name, $limit);
    }

    protected function simpleStats($stats, $key='imported')
    {
        $ret = '
            <p>Import Complete</p>
            <div class="alert alert-success">' . $stats[$key] . ' records imported</div>';
        if ($stats['errors']) {
            $ret .= '<div class="alert alert-error"><ul>';
            foreach ($stats['errors'] as $error) {
                $ret .= '<li>' . $error . '</li>';
            }
            $ret .= '</ul></div>';
        }

        return $ret;
    }

    protected function checkIndex($index, $line)
    {
        if ($index !== false && isset($line[$index])) {
            return true;
        } else {
            return false;
        }
    }

    public function helpContent()
    {
        return '
        <p><strong>General Import Tool Tips</strong>
            <ul>
                <li>CSV, XLS, and XLSX are all supported. However, CSV is most reliable.</li>
                <li>Maximum file size is usually 2MB. CSV files may be zipped to reduce
                    file size.</li>
                <li>The purpose of the preview screen is to specify the format of your
                    file. It shows the first five rows of data with dropdowns above each
                    column. Use the dropdowns to specify what (if any) data is present in 
                    each column. For example, if UPCs are in the 3rd column, set the dropdown
                    for the third column to UPC.</li>
                <li>Large files may take awhile to process. Give it 5 or 10 minutes before
                    deciding it didn\'t work.</li>
            </ul>
        </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->bodyContent());
        $phpunit->assertInternalType('string', $this->preview_content());
        $this->error_details = 'Test error';
        $phpunit->assertInternalType('string', $this->uploadError());
        $phpunit->assertInternalType('string', $this->processingError());
        $stats = array(
            'errors' => array('one', 'two'),
            'imported' => 0,
        );
        $phpunit->assertInternalType('string', $this->simpleStats($stats));
    }
}

