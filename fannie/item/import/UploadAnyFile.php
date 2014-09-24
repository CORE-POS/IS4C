<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 + Upload a file from workstation to $temp_dir/misc/
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 *  6Mar2013 Andy Theuninck wrapped in base class
 *  2Sep2012 Eric Lee Based on /IS4C/fannie/batches/UNFI/uploadPriceSheet.php
*/

/* configuration for your module - Important */
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class UploadAnyFile extends FanniePage {

    protected $title = "Fannie - Upload Any File";
    protected $header = "Upload Any File";

    public $description = '[Generic Upload] simply uploads a file to temporary storage
    on the server.';

    private $tpath;
    
    function preprocess(){
        $this->tpath = sys_get_temp_dir()."/misc/";
        $this->mode = 'form';
        /* Is this a request-to-upload or an initial display of the form? */
        if (FormLib::get_form_value('MAX_FILE_SIZE') != '' && FormLib::get_form_value('doUpload') != ''){
            $this->mode = 'process';
        }
        return True;
    }

    /**
      Process an upload
      @return HTML string explaining results
    */
    function process_file(){
        if (!is_dir($this->tpath)) mkdir($this->tpath);
        /* rm any files in $tpath - probably not a good idea
        $dh = opendir($tpath);
        while (($file = readdir($dh)) !== false) {
            if (!is_dir($tpath.$file)) unlink($tpath.$file);
        }
        closedir($dh);
        */

        if ($_FILES['upload']['error'] != UPLOAD_ERR_OK){
            $msg = "Error uploading file<br />";
            $msg .= "Error code is: ".$_FILES['upload']['error'].'<br />';
            $msg .= '<a href="http://www.php.net/manual/en/features.file-upload.errors.php">Details</a>';
        }


        $tmpfile = $_FILES['upload']['tmp_name'];
        $path_parts = pathinfo($_FILES['upload']['name']);
        /* Could base a routine for upzipping an uploaded .zip on this,
            which as it stands is specialized for a UNFI price update.
        if ($path_parts['extension'] == "zip"){
            move_uploaded_file($tmpfile,$tpath."unfi.zip");
            $output = system("unzip {$tpath}unfi.zip -d $tpath &> /dev/null");
            unlink($tpath."unfi.zip");
            $dh = opendir($tpath);
            while (($file = readdir($dh)) !== false) {
                if (!is_dir($tpath.$file)) rename($tpath.$file,$tpath."unfi.csv");
            }
            closedir($dh);
        }
        else { Not a .zip }
        */

        $out = move_uploaded_file($tmpfile, $this->tpath."{$path_parts['basename']}");

        return "Done. File is in ".$this->tpath.$path_parts['basename']."<br />
            This directory may be deleted on reboot.<br />";
    }

    /**
      Call appropriate method depending on whether the
      form has been submitted.
    */
    function body_content(){
        if ($this->mode == 'form')
            return $this->upload_form();
        elseif ($this->mode == 'process')
            return $this->process_file();
        else
            return 'An unknown error occurred.';
    }
    
    /**
      Draw upload form
      @return HTML string containing form
    */
    function upload_form(){
        ob_start();
        ?>
        <form enctype="multipart/form-data" action="UploadAnyFile.php" method="post">
        <input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
        <input type="hidden" name="doUpload" value="x" />
        Filename: <input type="file" id="file" name="upload" />
        <input type="submit" value="Upload File" />
        <br />Best if the file is &lt;2MB.
        <br />The file will be placed in <?php echo $this->tpath; ?>
        </form>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
