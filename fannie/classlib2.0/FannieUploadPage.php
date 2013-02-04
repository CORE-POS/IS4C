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

include_once(dirname(__FILE__).'/FanniePage.php');
include_once(dirname(__FILE__).'/lib/FormLib.php');
include_once(dirname(__FILE__).'/../src/tmp_dir.php');
include_once(dirname(__FILE__).'/../src/csv_parser.php');
include_once(dirname(__FILE__).'/../src/Excel/reader.php');

/**
  @class FanniePage
  Class for drawing screens
*/
class FannieUploadPage extends FanniePage {

	public $required = True;

	public $description = "
	Base class for handling file uploads
	";

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
	protected $preview = True;
	/**
	  Define user-selectable options
	*/
	protected $preview_opts = array(
		'upc' => array(
			'name' => 'upc',
			'display_name' => 'UPC',
			'default' => 7,
			'required' => True
		),
		'price' => array(
			'name' => 'price',
			'display_name' => 'Sale Price',
			'default' => 24,
			'required' => True
		),
		'abt' => array(
			'name' => 'abt',
			'display_name' => 'A/B/TPR',
			'default' => 5,
			'required' => True
		),
		'sku' => array(
			'name' => 'sku',
			'display_name' => 'SKU',
			'default' => 8,
			'required' => False
		),
		'sub' => array(
			'name' => 'sub',
			'display_name' => 'Sub',
			'default' => 6,
			'required' => False
		)
	);

	protected $preview_selections = array();

	protected $upload_field_name = 'FannieUploadFile';
	protected $upload_file_name = '';
	protected $allowed_extensions = array('csv','xls');

	protected $error_details = 'n/a';

	/**
	  Handle pre-display tasks such as input processing
	  @return
	   - True if the page should be displayed
	   - False to stop here
	*/
	function preprocess(){
		global $FANNIE_URL;

		$col_select = FormLib::get_form_value('col_select','');

		if (isset($_FILES[$this->upload_field_name])){
			/* file upload submitted */
			$try = $this->process_upload();
			if ($try){
				$this->content_function = 'basic_preview';
				$this->window_dressing = False;
				$this->add_script($FANNIE_URL.'src/jquery/jquery.js');
			}
			else
				$this->content_function = 'upload_error';
		}
		else if (is_array($col_select)){
			$this->upload_file_name = FormLib::get_form_value('upload_file_name','');
			
			/* column selections submitted */
			for($i=0;$i<count($col_select);$i++){
				if ($col_select[$i] != '(ignore)')
					$this->preview_selections[$col_select[$i]] = $i;
			}
			$chk_required = True;
			$this->error_details = '';
			foreach($this->preview_opts as $opt){
				if ($opt['required'] == True && !isset($this->preview_selections[$opt['name']])){
					$this->error_details .= '<li><b>'.$opt['display_name'].'</b> is required</li>';
					$chk_required = False;
				}
			}

			if ($chk_required == True){
				$try = $this->process_file($this->file_to_array());
				if ($try){
					unlink($this->upload_file_name);
					$this->content_function = 'results_content';
				}
				else
					$this->content_function = 'processing_error';
			}
			else {
				$this->content_function = 'basic_preview';
				$this->window_dressing = False;
				$this->add_script($FANNIE_URL.'src/jquery/jquery.js');
			}
		}

		return True;
	}

	/**
	  Store uploaded file
	  @return True on success, False on error
	*/
	function process_upload(){
		/* use a dedicated temp directory */
		$tpath = sys_get_temp_dir().'/fannie/';
		if (!is_dir($tpath)){
			if (!mkdir($tpath)){
				$this->error_details = 'Directory error';
				return False;
			}
		}

		$tmpfile = $_FILES[$this->upload_field_name]['tmp_name'];
		$path_parts = pathinfo($_FILES[$this->upload_field_name]['name']);
		$extension = strtolower($path_parts['extension']);
		$zip = False;

		/* validate file by extension */
		if ($extension == 'zip'){
			$zip = True;
			/* if it's a zip file, try to unzip it */
			if(!class_exists('ZipArchive')){
				unlink($tmpfile);
				$this->error_details = 'No ZIP support';
				return False;
			}
			$za = new ZipArchive();
			if ($za->open($tmpfile) !== True){
				unlink($tmpfile);
				$this->error_details = 'Bad ZIP file';
				return False;
			}
			$found = False;
			/*
			  Go through all the files in the zip archive
			  If one has a valid extension, extract it to
			  the temp directory, remove the zip file,
			  and update $tmpfile so it points as the extracted
			  file
			*/
			for($i=0;$i<$za->numFiles;$i++){
				$entry = $za->getNameIndex($i);
				$ext = strtolower(substr($entry,-4));
				if ($ext[0] == '.' && in_array(substr($ext,1),$this->allowed_extensions)){
					$found = True;
					$za->extractTo($tpath, $entry);
					$za->close();
					unlink($tmpfile);
					$tmpfile = realpath($tpath.'/'.$entry);
					$extension = substr($ext,1);
					break;
				}
			}
			if (!$found){
				$za->close();
				unlink($tmpfile);
				$this->error_details = 'Bad ZIP contents';
				return False;
			}
		}
		else if (!in_array($extension,$this->allowed_extensions)){
			$this->error_details = 'Bad file';
			unlink($tmpfile);
			return False;
		}

		/* get a unique temp file name */
		$this->upload_file_name = tempnam($tpath,$extension);
		if ($this->upload_file_name === False){
			$this->upload_file_name = '';
			unlink($tmpfile);
			$this->error_details = 'No name found';
			return False;
		}

		$func = 'move_uploaded_file';
		/* PHP doesn't recognize the extracted file as "uploaded" */
		if ($zip) $func = 'rename';

		/* rename the uploaded file */
		if ($func($tmpfile, $this->upload_file_name) === False){
			$this->upload_file_name = '';
			unlink($tmpfile);
			unlink($this->upload_file_name);
			$this->error_details = 'Could not rename';
			return False;
		}

		/* if we got here, nothing went wrong */
		return True;
	}

	/**
	  Do something with the uploaded data
	  @param $linedata an array of arrays
	   (each inner area is one line of data)
	  @return True on success, False on error
	*/
	function process_file($linedata){
		return True;
	}

	/**
	  Display if there is an upload error
	  @return An HTML string
	*/
	function upload_error(){
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
	function processing_error(){
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
	function body_content(){
		if (!isset($this->content_function))
			$this->content_function = 'form_content';
		if (!method_exists($this,$this->content_function))
			$this->content_function = 'form_content';
		$func = $this->content_function;
		$ret = $this->$func();
		switch($this->content_function){
		case 'form_content':
			$ret .= $this->basic_form();
			break;
		}
		return $ret;
	}
	
	/**
	  Any extra content before the form itself.
	  @return An HTML string
	*/
	function form_content(){
		return "";
	}

	/**
	  Default form automatically included with form_content()
	  @return An HTML string
	*/
	protected function basic_form(){
		return sprintf('
		<form enctype="multipart/form-data" action="%s" method="post">
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
	function preview_content(){
		return "";
	}

	/**
	  Default preview of uploaded data
	  @return An HTML string
	*/
	protected function basic_preview(){
		$ret = '<h3>Select columns</h3>';
		/* show any errors */
		if ($this->error_details != 'n/a' && $this->error_details != ''){
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
		$linedata = $this->file_to_array(5);
		foreach ($linedata as $data){
			$j=0;
			foreach($data as $d){
				$table .='<td>'.$d.'</td>';
				$j++;
			}
			if ($j > $width) $width = $j;
			$table .= '</tr>';
		}
		fclose($fp);

		/* draw select boxes for each column */
		$ret .= '<tr>';
		for ($i=0;$i<$width;$i++){
			$ret .= '<td><select class="columnSelector" name="col_select[]">';
			$ret .= '<option>(ignore)</option>';
			foreach($this->preview_opts as $key => $info){
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
	function javascript_content(){
		ob_start();
		?>
		$(document).ready(function(){
			$('.columnSelector').change(function(){
				var myElem = this;
				$('.columnSelector').each(function(i){
					if (this != myElem && $(this).val() == $('*:focus').val())
						$(this).val('(ignore)');
				});
			});
		});
		<?php
		return ob_get_clean();
	}

	/**
	  What to display when the upload is done 
	  @return An HTML string
	*/
	function results_content(){
		return "";
	}

	/**
	  Get the numerical index that the user selected for
	  a given column
	  @param $name the name (as defined in $this->preview_opts)
	  @return Integer index if available otherwise False
	*/
	protected function get_column_index($name){
		if (isset($this->preview_selections[$name]))
			return $this->preview_selections[$name];
		else
			return False;
	}

	/**
	  Get two-dimensional array of file data
	  @param $limit if specified only return $limit records
	  @return An array of arrays. Each inner array
	    represents one line of data
	*/
	protected function file_to_array($limit=0){
		if (substr(basename($this->upload_file_name),0,3) == 'csv')
			return $this->csv_to_array($limit);
		elseif (substr(basename($this->upload_file_name),0,3) == 'xls'){
			return $this->xls_to_array($limit);
		}
		else	
			return array();
	}

	/**
	  Helper for csv files. See file_to_array()
	*/
	protected function csv_to_array($limit=0){
		$fp = fopen($this->upload_file_name,'r');
		if (!$fp) return array();
		$ret = array();
		while(!feof($fp)){
			$line = fgets($fp);
			$ret[] = csv_parser($line);
			if ($limit != 0 && count($ret) >= $limit) break;
		}
		fclose($fp);
		return $ret;
	}

	/**
	  Helper for xls files. See file_to_array()
	*/
	protected function xls_to_array($limit){
		if (!class_exists('Spreadsheet_Excel_Reader')){
			return array();
		}

		$data = new Spreadsheet_Excel_Reader();
		$data->read($this->upload_file_name);

		$sheet = $data->sheets[0];
		$rows = $sheet['numRows'];
		$cols = $sheet['numCols'];
		$ret = array();
		for($i=1; $i<=$rows; $i++){
			$line = array();
			for ($j=1; $j<=$cols; $j++){
				if (isset($sheet['cells'][$i]) && isset($sheet['cells'][$i][$j]))
					$line[] = $sheet['cells'][$i][$j];
				else
					$line[] = '';
			}
			$ret[] = $line;
			if ($limit != 0 && count($ret) >= $limit) break;
		}
		return $ret;
	}
}

?>
