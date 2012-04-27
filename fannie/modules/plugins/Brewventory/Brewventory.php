<?php
/*******************************************************************************

    Copyright 2012 Andy Theuninck

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

/**
  @class Brewventory
*/
class Brewventory extends FannieInventory {

	public $required = False;

	public $description = "
	Module for managing homebrew ingredients
	";

	protected $mode = 'menu';

	function get_header(){
		global $FANNIE_URL;
		$this->add_script($FANNIE_URL.'src/jquery/js/jquery.js');
		$this->add_script($FANNIE_URL.'src/jquery/js/jquery-ui-1.8.1.custom.min.js');

		ob_start();
		?>
		<html>
		<head>
		<link rel="STYLESHEET" type="text/css"
			href="<?php echo $FANNIE_URL; ?>src/jquery/css/smoothness/jquery-ui-1.8.1.custom.css" >
		<title>Brewventory</title>
		</head>
		<body>
		<?php
		return ob_get_clean();
	}

	function get_footer(){
		return "</body></html>";	
	}

	function import(){
		ob_start();
		echo str_replace('form ', 'form enctype="multipart/form-data" ',$this->form_tag());
		?>
		<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
		BeerXML file: <input type="file" name="import_xml_file" />
		<input type="submit" name="import_submit" value="Import Data" />
		</form>
		<?php
		return ob_get_clean();
	}

	function receive(){
		ob_start();
		echo $this->form_tag();
		?>
		<p>
		<b>Ingredient</b>: <input type="text" id="upc" name="upc" />
		</p>
		<p>
		<b>lbs</b>: <input type="text" name="lbs" size="3" value="0" />
		<b>ozs</b>: <input type="text" name="ozs" size="3" value="0" />
		</p>
		<input type="submit" name="add_submit" value="Add to Stock" />
		</form>
		<?php
		$this->add_onload_command(
			sprintf("\$('#upc').autocomplete({source:'%s&LookUp=1'});",
				$this->module_url())
		);
		return ob_get_clean();
	}

	function menu(){
		$ret = '<ul>';
		$ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
				$this->module_url(),'view','View Current Inventory');
		$ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
				$this->module_url(),'receive','Add Purchases');
		$ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
				$this->module_url(),'sale','Use Ingredients');
		$ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
				$this->module_url(),'adjust','Enter Adjustments');
		$ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
				$this->module_url(),'import','Import Definitions');
		$ret .= '</ul>';
		return $ret;
	}

	function preprocess(){
		$this->mode = get_form_value('mode','menu');

		/**
		  Begin form callbacks
		*/

		/**
		  Callback for import() display function
		*/
		if (isset($_REQUEST['import_submit'])){
			$tmpfile = $_FILES['import_xml_file']['tmp_name'];
			$filename = tempnam(sys_get_temp_dir(),'brewvenImport');
			move_uploaded_file($tmpfile, $filename);

			$bxml = new BeerXMLParser($filename);
			$data = $bxml->get_data();
		
			$dbc = op_connect();
			foreach($data['Hops'] as $h)
				echo $this->add_hops($dbc, $h)."<br />";

			foreach($data['Fermentables'] as $f)
				echo $this->add_malt($dbc, $f)."<br />";

			foreach($data['Yeast'] as $y)
				echo $this->add_yeast($dbc, $y)."<br />";

			foreach($data['Misc'] as $m)
				echo $this->add_misc($dbc, $m)."<br />";

			echo '<p />';
			printf('<a href="%s&mode=menu">Home</a>',$this->module_url());
			return False;
		}

		/**
		  jQuery autocomplete callback
		*/
		if (isset($_REQUEST['LookUp']) && isset($_REQUEST['term'])){
			$dbc = op_connect();
			$query = sprintf("SELECT p.upc,u.description,u.brand,u.sizing,
					x.distributor FROM products AS p
					INNER JOIN productUser as u ON p.upc=u.upc
					INNER JOIN prodExtra AS x ON p.upc=x.upc
					WHERE p.mixmatchcode IN ('hops','malts','yeasts','brewmisc')
					AND u.description LIKE %s",
					$dbc->escape("%".$_REQUEST['term']."%")
			);

			$json = "[";
			$result = $dbc->query($query);
			while($row = $dbc->fetch_row($result)){
				$json .= "{ \"label\": \"".$row['description'];

				if (!empty($row['brand']) || !empty($row['sizing']) || !empty($row['distributor']))
					$json .= " ("; 
				if (!empty($row['brand']))
					$json .= $row['brand'].", ";
				if (!empty($row['distributor']))
					$json .= $row['distributor'].", ";
				if (!empty($row['sizing']))
					$json .= $row['sizing'].", ";
				$json = rtrim($json,", ");
				if (!empty($row['brand']) || !empty($row['sizing']) || !empty($row['distributor']))
					$json .= ")"; 

				$json .= "\", \"value\": \"".$row['upc']."\"},";
			}
			$json = rtrim($json,",");
			$json .= "]";

			header("Content-type: application/json");
			echo $json;
			return False;
		}

		/**
		  End form callbacks
		*/
	

		return True;
	}

	/**
	  Add malts to product database
	  @param $dbc SQLManager object
	  @param $malt_info array of BeerXML fields
	  @return string describing result
	*/
	private function add_malt($dbc, $malt_info){
		$good_desc = $malt_info['name'];
		$short_desc = substr($malt_info['name'],0,30);
		$hash = $good_desc;
		$hash .= (isset($malt_info['supplier'])?$malt_info['supplier']:'');
		$hash .= (isset($malt_info['origin'])?$malt_info['origin']:'');
		$upc = substr(md5($hash),0,13);

		$q = "SELECT upc FROM products WHERE upc=".$dbc->escape($upc);
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0)
			return "<i>Omitting malt: $good_desc (already exists)</i>";

		$userQ = sprintf("INSERT INTO productUser
			(upc, description, brand, sizing, photo,
			long_text, enableOnline) VALUES
			(%s, %s, %s, %s, '', %s, 0)",
			$dbc->escape($upc),
			$dbc->escape($good_desc),
			$dbc->escape(isset($malt_info['supplier'])?$malt_info['supplier']:''),
			"''",
			$dbc->escape(isset($malt_info['notes'])?$malt_info['notes']:'')
		);

		$xtraQ = sprintf("INSERT INTO prodExtra (upc, distributor, 
			manufacturer, cost, margin, variable_pricing, location,
			case_quantity, case_cost, case_info) VALUES
			(%s, %s, '', %.2f, %.2f, 0, '', '', %.2f, '')",
			$dbc->escape($upc),
			$dbc->escape(isset($malt_info['origin'])?$malt_info['origin']:''),
			(isset($malt_info['color'])?$malt_info['color']:0),
			0,0
		);

		$prodQ = sprintf("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
				(%s, %s, %s, 'malts')",
				$dbc->escape($upc),
				$dbc->escape($short_desc),
				$dbc->now()
		);

		$dbc->query("DELETE FROM products WHERE upc=".$dbc->escape($upc));
		$dbc->query($prodQ);

		$dbc->query("DELETE FROM prodExtra WHERE upc=".$dbc->escape($upc));
		$dbc->query($xtraQ);

		$dbc->query("DELETE FROM productUser WHERE upc=".$dbc->escape($upc));
		$dbc->query($userQ);

		return "Imported malt: $good_desc";
	}

	/**
	  Add misc ingredients to product database
	  @param $dbc SQLManager object
	  @param $misc_info array of BeerXML fields
	  @return string describing result
	*/
	private function add_misc($dbc, $misc_info){
		$good_desc = $misc_info['name'];
		$short_desc = substr($misc_info['name'],0,30);
		$hash = $good_desc;
		$hash .= (isset($misc_info['supplier'])?$misc_info['supplier']:'');
		$hash .= (isset($misc_info['type'])?$misc_info['type']:'');
		$upc = substr(md5($hash),0,13);

		$q = "SELECT upc FROM products WHERE upc=".$dbc->escape($upc);
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0)
			return "<i>Omitting misc: $good_desc (already exists)</i>";

		$userQ = sprintf("INSERT INTO productUser
			(upc, description, brand, sizing, photo,
			long_text, enableOnline) VALUES
			(%s, %s, %s, %s, '', %s, 0)",
			$dbc->escape($upc),
			$dbc->escape($good_desc),
			$dbc->escape(isset($misc_info['supplier'])?$misc_info['supplier']:''),
			$dbc->escape(isset($misc_info['type'])?$misc_info['type']:''),
			$dbc->escape(isset($misc_info['notes'])?$misc_info['notes']:'')
		);

		$xtraQ = sprintf("INSERT INTO prodExtra (upc, distributor, 
			manufacturer, cost, margin, variable_pricing, location,
			case_quantity, case_cost, case_info) VALUES
			(%s, %s, '', %.2f, %.2f, 0, '', '', %.2f, %s)",
			$dbc->escape($upc),
			"''",
			0,0,0,
			"''"
		);

		$prodQ = sprintf("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
				(%s, %s, %s, 'brewmisc')",
				$dbc->escape($upc),
				$dbc->escape($short_desc),
				$dbc->now()
		);

		$dbc->query("DELETE FROM products WHERE upc=".$dbc->escape($upc));
		$dbc->query($prodQ);

		$dbc->query("DELETE FROM prodExtra WHERE upc=".$dbc->escape($upc));
		$dbc->query($xtraQ);

		$dbc->query("DELETE FROM productUser WHERE upc=".$dbc->escape($upc));
		$dbc->query($userQ);

		return "Imported misc: $good_desc";
	}

	/**
	  Add yeast to product database
	  @param $dbc SQLManager object
	  @param $yeast_info array of BeerXML fields
	  @return string describing result
	*/
	private function add_yeast($dbc, $yeast_info){
		$good_desc = $yeast_info['name'];
		$short_desc = substr($yeast_info['name'],0,30);
		$hash = $good_desc;
		$hash .= (isset($yeast_info['laboratory'])?$yeast_info['laboratory']:'');
		$hash .= (isset($yeast_info['product_id'])?$yeast_info['product_id']:'');
		$upc = substr(md5($hash),0,13);

		$q = "SELECT upc FROM products WHERE upc=".$dbc->escape($upc);
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0)
			return "<i>Omitting yeast: $good_desc (already exists)</i>";

		$userQ = sprintf("INSERT INTO productUser
			(upc, description, brand, sizing, photo,
			long_text, enableOnline) VALUES
			(%s, %s, %s, %s, '', %s, 0)",
			$dbc->escape($upc),
			$dbc->escape($good_desc),
			$dbc->escape(isset($yeast_info['laboratory'])?$yeast_info['laboratory']:''),
			$dbc->escape(isset($yeast_info['form'])?$yeast_info['form']:''),
			$dbc->escape(isset($yeast_info['notes'])?$yeast_info['notes']:'')
		);

		$xtraQ = sprintf("INSERT INTO prodExtra (upc, distributor, 
			manufacturer, cost, margin, variable_pricing, location,
			case_quantity, case_cost, case_info) VALUES
			(%s, %s, '', %.2f, %.2f, 0, '', '', %.2f, %s)",
			$dbc->escape($upc),
			$dbc->escape(isset($yeast_info['type'])?$yeast_info['type']:''),
			0,0,0,
			$dbc->escape(isset($yeast_info['product_id'])?$yeast_info['product_id']:'')
		);

		$prodQ = sprintf("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
				(%s, %s, %s, 'yeasts')",
				$dbc->escape($upc),
				$dbc->escape($short_desc),
				$dbc->now()
		);

		$dbc->query("DELETE FROM products WHERE upc=".$dbc->escape($upc));
		$dbc->query($prodQ);

		$dbc->query("DELETE FROM prodExtra WHERE upc=".$dbc->escape($upc));
		$dbc->query($xtraQ);

		$dbc->query("DELETE FROM productUser WHERE upc=".$dbc->escape($upc));
		$dbc->query($userQ);

		return "Imported yeast: $good_desc";
	}

	/**
	  Add hops to product database
	  @param $dbc SQLManager object
	  @param $hop_info array of BeerXML fields
	  @return string describing result
	*/
	private function add_hops($dbc, $hop_info){
		$good_desc = $hop_info['name'];
		$short_desc = substr($hop_info['name'],0,30);
		$hash = $good_desc;
		$hash .= (isset($hop_info['origin'])?$hop_info['origin']:'');
		$hash .= (isset($hop_info['form'])?$hop_info['form']:'');
		$upc = substr(md5($good_desc),0,13);

		$q = "SELECT upc FROM products WHERE upc=".$dbc->escape($upc);
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0)
			return "<i>Omitting hops: $good_desc (already exists)</i>";
		
		$userQ = sprintf("INSERT INTO productUser
			(upc, description, brand, sizing, photo,
			long_text, enableOnline) VALUES
			(%s, %s, %s, %s, '', %s, 0)",
			$dbc->escape($upc),
			$dbc->escape($good_desc),
			$dbc->escape(isset($hop_info['supplier'])?$hop_info['supplier']:''),
			$dbc->escape(isset($hop_info['form'])?$hop_info['form']:''),
			$dbc->escape(isset($hop_info['notes'])?$hop_info['notes']:'')
		);

		$xtraQ = sprintf("INSERT INTO prodExtra (upc, distributor, 
			manufacturer, cost, margin, variable_pricing, location,
			case_quantity, case_cost, case_info) VALUES
			(%s, %s, '', %.2f, %.2f, 0, '', '', %.2f, '')",
			$dbc->escape($upc),
			$dbc->escape(isset($hop_info['origin'])?$hop_info['origin']:''),
			(isset($hop_info['alpha'])?$hop_info['alpha']:0),
			(isset($hop_info['beta'])?$hop_info['beta']:0),
			(isset($hop_info['hsi'])?$hop_info['hsi']:0)
		);

		$prodQ = sprintf("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
				(%s, %s, %s, 'hops')",
				$dbc->escape($upc),
				$dbc->escape($short_desc),
				$dbc->now()
		);

		$dbc->query("DELETE FROM products WHERE upc=".$dbc->escape($upc));
		$dbc->query($prodQ);

		$dbc->query("DELETE FROM prodExtra WHERE upc=".$dbc->escape($upc));
		$dbc->query($xtraQ);

		$dbc->query("DELETE FROM productUser WHERE upc=".$dbc->escape($upc));
		$dbc->query($userQ);

		return "Imported hops: $good_desc";
	}
}

/**
  @class BeerXMLParser
  Class to read BeerXML files
*/
class BeerXMLParser {
	
	private $data = array('Hops'=>array(),
			'Fermentables'=>array(),
			'Yeast'=>array(),
			'Misc'=>array()
	);

	private $hop;
	private $ferm;
	private $yeast;
	private $misc;
	private $outer_element = "";
	private $current_element = array();

	public function BeerXMLParser($filename){
		$file = file_get_contents($filename);
		if (!$file) $this->data = False;
		else {
			$xml_parser = xml_parser_create();
			xml_set_object($xml_parser,$this);
			xml_set_element_handler($xml_parser, "startElement", "endElement");
			xml_set_character_data_handler($xml_parser, "charData");
			xml_parse($xml_parser, $file, True);
			xml_parser_free($xml_parser);
		}
	}

	public function get_data(){
		return $this->data;
	}

	private function startElement($parser,$name,$attrs){
		switch(strtolower($name)){
		case 'hop':
			$this->outer_element = "hop";
			$this->hop = array();
			break;
		case 'fermentable':
			$this->outer_element = "fermentable";
			$this->ferm = array();
			break;
		case 'yeast':
			$this->outer_element = "yeast";
			$this->yeast = array();
			break;
		case 'misc':
			$this->outer_element = "misc";
			$this->misc = array();
			break;
		}
		array_unshift($this->current_element,strtolower($name));
	}

	private function endElement($parser,$name){
		switch(strtolower($name)){
		case 'hop':
			$this->data['Hops'][] = $this->hop;
			break;
		case 'fermentable':
			$this->data['Fermentables'][] = $this->ferm;
			break;
		case 'yeast':
			$this->data['Yeast'][] = $this->yeast;
			break;
		case 'misc':
			$this->data['Misc'][] = $this->misc;
			break;
		}
		array_shift($this->current_element);
	}

	private function charData($parser,$data){
		switch($this->outer_element){
		case 'hop':
			if (!isset($this->hop[$this->current_element[0]]))
				$this->hop[$this->current_element[0]] = "";
			$this->hop[$this->current_element[0]] .= $data;
			break;
		case 'fermentable':
			if (!isset($this->ferm[$this->current_element[0]]))
				$this->ferm[$this->current_element[0]] = "";
			$this->ferm[$this->current_element[0]] .= $data;
			break;
		case 'yeast':
			if (!isset($this->yeast[$this->current_element[0]]))
				$this->yeast[$this->current_element[0]] = "";
			$this->yeast[$this->current_element[0]] .= $data;
			break;
		case 'misc':
			if (!isset($this->misc[$this->current_element[0]]))
				$this->misc[$this->current_element[0]] = "";
			$this->misc[$this->current_element[0]] .= $data;
			break;
		}
	}
}
?>
