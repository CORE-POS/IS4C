<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
  @class PrintShelftags
  Module for printing shelf tags
*/
class PrintShelftags extends FanniePage {

	public $required = False;

	public $description = "
	Module for printing shelftags
	";

	protected $header = "Fannie :: Print Shelf Tags";
	protected $title = "Print Shelf Tags";

	private $display_function;

	function preprocess(){
		$this->display_function = 'regular_tags_display';

		/**
		  Pick a display function
		*/
		if (isset($_REQUEST['batch'])){
			$this->display_function = 'batch_tags_display';	
			return True;
		}
		else if (isset($_REQUEST['edit'])){
			$this->window_dressing = False;
			$this->display_function = 'edit_tags_display';
			return True;
		}
		else if (isset($_REQUEST['delete'])){
			$this->display_function = 'delete_tags_display';
			return True;
		}


		/**
		  Process delete request submission
		*/
		if (isset($_REQUEST['dodelete'])){
			$id = get_form_value('deleteID',-1);
			$dbc = op_connect();
			$q = sprintf("DELETE FROM shelftags WHERE
				id=%d",$id);
			$r = $dbc->query($q);
			$dbc->close();
			header("Location: ".$this->module_url());
			return False;
		}

		/**
		  Process edit request submission
		*/
		if (isset($_REQUEST['doedit'])){
			$upcs = get_form_value('upc',array());
			$desc = get_form_value('desc',array());
			$price = get_form_value('price',array());
			$brand = get_form_value('brand',array());
			$sku = get_form_value('sku',array());
			$size = get_form_value('size',array());
			$units = get_form_value('units',array());
			$vendor = get_form_value('vendor',array());
			$ppo = get_form_value('ppo',array());
			$id = get_form_value('id',-1);

			$dbc = op_connect();
			for($i=0;$i<count($upcs);$i++){
				$upQ = sprintf("UPDATE shelftags SET
					normal_price=%.2f,
					brand=%s,
					sku=%s,
					size=%s,
					units=%s,
					vendor=%s,
					pricePerUnit=%s
					WHERE upc=%s AND id=%d",
					(isset($price[$i])?$price[$i]:0),
					(isset($brand[$i])?$dbc->escape($brand[$i]):"''"),
					(isset($sku[$i])?$dbc->escape($sku[$i]):"''"),
					(isset($size[$i])?$dbc->escape($size[$i]):"''"),
					(isset($units[$i])?$dbc->escape($units[$i]):"''"),
					(isset($vendor[$i])?$dbc->escape($vendor[$i]):"''"),
					(isset($ppo[$i])?$dbc->escape($ppo[$i]):"''"),
					$dbc->escape($upcs[$i]), $id
				);
				$dbc->query($upQ);
			}
			$dbc->close();
			header("Location: ".$this->module_url());
			return False;
		}

		/**
		  Output a PDF
		*/
		if (isset($_REQUEST['layout'])){
			$mod = get_form_value('layout');
			$offset = get_form_value('offset',0);
			
			$records = array();
			$id = get_form_value('regid',-1);
			$batchIDs = get_form_value('batchID',array());
			if (is_numeric($id) && $id != -1)
				$records = $this->get_regular_records($id);
			else
				$records = $this->get_batch_records($batchIDs);

			if (empty($records)){
				echo '<b>Error</b>: no tags found';
				return False;
			}
			else if (!class_exists($mod)){
				echo '<b>Error</b>: layout '.$mod.' not found';
				return False;
			}

			$obj = new $mod();
			$obj->set_data($records);
			$obj->set_offset($offset);
			$obj->run_module();
			return False;
		}

		return True;
	}

	function body_content(){
		$func = $this->display_function;
		return $this->$func();
	}

	function css_content(){
		return "
		.one {
			background: #ffffff;
		}
		.two {
			background: #ffffcc;
		}
		";
	}

	function regular_tags_display(){
		global $FANNIE_URL;
		ob_start();

		$modules = $this->layout_modules();
		?>
		Regular shelf tags
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<a href="<?php echo $this->module_url(); ?>&batch=1">Batch shelf tags</a>
		<p />
		<?php echo $this->form_tag('get'); ?>
		<table cellspacing=0 cellpadding=4 border=1>
		<tr><td>
		Offset: <input type=text size=2 name=offset value=0 />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<select id=layoutselector name=layout>
		<?php
		foreach($modules as $l => $filename){
			if ($l == $FANNIE_DEFAULT_PDF)
				echo "<option selected>".$l."</option>";
			else
				echo "<option>".$l."</option>";
		}
		?>
		</select>
		</td></tr>
		</table>
		<p />
		<table cellspacing=0 cellpadding=4 border=1>
		<input type="hidden" id="regid" name="regid" value="-1" />
		<?php
		$dbc = op_connect();
		$query = "SELECT superID,super_name FROM superDeptNames
			GROUP BY superID,super_name
			ORDER BY superID";
		$result = $dbc->query($query);
		while($row = $dbc->fetch_row($result)){
			printf("<tr><td>%s barcodes</td><td><a href=\"\" onclick=\"\$('#regid').val(%d);\$('form').submit();return false;\">
				Print</a></td><td><a href=\"%s&delete=%d\">Clear</a></td>
				<td><a href=\"%s&edit=%d\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\"
				alt=\"Edit\" border=0 /></td></tr>",$row[1],$row[0],
				$this->module_url(),$row[0],$this->module_url(),$row[0]);
		}
		echo "</table>";
		echo "</form>";
		$dbc->close();

		return ob_get_clean();
	}

	function batch_tags_display(){
		ob_start();

		$modules = $this->layout_modules();
		?>
		<a href="<?php echo $this->module_url(); ?>">Regular shelf tags</a>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		Batch shelf tags
		<p />
		<?php
		echo $this->form_tag('get');
		echo "<b>Select batch(es*) to be printed</b>:<br />";
		$dbc = op_connect();
		$fetchQ = "select b.batchID,b.batchName
			  from batches as b left join
			  batchBarcodes as c on b.batchID = c.batchID
			  where c.upc is not null
				  group by b.batchID,b.batchName
				  order by b.batchID desc";
		$fetchR = $dbc->query($fetchQ);
		echo "<select name=batchID[] multiple style=\"{width:300px;}\" size=15>";
		while($fetchW = $dbc->fetch_array($fetchR))
			echo "<option value=$fetchW[0]>$fetchW[1]</option>";
		echo "</select><p />";
		echo "<fieldset>";
		echo "Offset: <input size=3 type=text name=offset value=0 />";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<select name=layout>";
		foreach($modules as $l => $filename){
			if ($l == $FANNIE_DEFAULT_PDF)
				echo "<option selected>".$l."</option>";
			else
				echo "<option>".$l."</option>";
		}	
		echo "</select>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input type=submit value=Print />";
		echo "</fieldset>";
		echo "</form>";

		$dbc->close();
		return ob_get_clean();
	}

	function delete_tags_display(){
		$id = get_form_value('delete',-1);

		$ret = "Are you sure?";
		$ret .= "<p />";
		$ret .= $this->form_tag('get');
		$ret .= sprintf('<input type="hidden" name="deleteID" value="%d" />',$id);
		$ret .= '<input type="submit" name="dodelete"
				value="Yes, Delete shelf tags" />';
		$ret .= '<p />';
		$ret .= '<input type="submit" name="nodelete"
				value="Do not delete shelf tags" />';
		$ret .= '</form>';

		return $ret;
	}

	function edit_tags_display(){
		$id = get_form_value('edit',-1);	
		$ret = $this->form_tag('post');
		$ret .= "<table cellspacing=0 cellpadding=4 border=1>";
		$ret .= "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
		$ret .= "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th></tr>";

		$class = array("one","two");
		$c = 1;

		$dbc = op_connect();
		$query = sprintf("select upc,description,normal_price,brand,sku,size,units,vendor,pricePerUnit from shelftags
			where id=%d order by upc",$id);
		$result = $dbc->query($query);
		while ($row = $dbc->fetch_row($result)){
			$ret .= "<tr class=$class[$c]>";
			$ret .= "<td>$row[0]</td><input type=hidden name=upc[] value=\"$row[0]\" />";
			$ret .= "<td><input type=text name=desc[] value=\"$row[1]\" size=25 /></td>";
			$ret .= "<td><input type=text name=price[] value=\"$row[2]\" size=5 /></td>";
			$ret .= "<td><input type=text name=brand[] value=\"$row[3]\" size=13 /></td>";
			$ret .= "<td><input type=text name=sku[] value=\"$row[4]\" size=6 /></td>";
			$ret .= "<td><input type=text name=size[] value=\"$row[5]\" size=6 /></td>";
			$ret .= "<td><input type=text name=units[] value=\"$row[6]\" size=4 /></td>";
			$ret .= "<td><input type=text name=vendor[] value=\"$row[7]\" size=7 /></td>";
			$ret .= "<td><input type=text name=ppo[] value=\"$row[8]\" size=10 /></td>";
			$ret .= "</tr>";
			$c = ($c+1)%2;
		}
		$ret .= "</table>";
		$ret .= sprintf("<input type=hidden name=id value=\"%d\" />",$id);
		$ret .= "<input type=submit name=doedit value=\"Update Shelftags\" />";
		$ret .= "</form>";

		$dbc->close();
		return $ret;
	}

	function layout_modules(){
		global $FANNIE_ROOT;
		$modules = array();
		get_available_modules($FANNIE_ROOT.'class-lib',$modules,'LabelPDF');
		get_available_modules($FANNIE_ROOT.'class-lib',$modules,'LabelPDF');
		return $modules;
	}

	function get_regular_records($id){
		$dbc = op_connect();
		$data = array();
		$query = sprintf("SELECT s.*,p.scale FROM shelftags AS s
			INNER JOIN products AS p ON s.upc=p.upc
			WHERE s.id=%d ORDER BY
			p.department,s.upc",$id);
		$result = $dbc->query($query);
		while($row = $dbc->fetch_row($result)){
			$myrow = array(
			'normal_price' => $row['normal_price'],
			'description' => $row['description'],
			'brand' => $row['brand'],
			'units' => $row['units'],
			'size' => $row['size'],
			'sku' => $row['sku'],
			'pricePerUnit' => $row['pricePerUnit'],
			'upc' => $row['upc'],
			'vendor' => $row['vendor'],
			'scale' => $row['scale']
			);			
			$data[] = $myrow;
		}
		$dbc->close();
		return $data;
	}

	function get_batch_records($ids){
		$dbc = op_connect();
		$batchIDList = '';
		foreach($ids as $x)
			$batchIDList .= ((int)$x).',';
		$batchIDList = substr($batchIDList,0,strlen($batchIDList)-1);
		$testQ = "select b.*,p.scale
			FROM batchBarcodes as b INNER JOIN products AS p
			ON b.upc=p.upc WHERE batchID in ($batchIDList) and b.description <> ''
			ORDER BY batchID";
		$result = $dbc->query($testQ);
		while($row = $dbc->fetch_row($result)){
			$myrow = array(
			'normal_price' => $row['normal_price'],
			'description' => $row['description'],
			'brand' => $row['brand'],
			'units' => $row['units'],
			'size' => $row['size'],
			'sku' => $row['sku'],
			'pricePerUnit' => '',
			'upc' => $row['upc'],
			'vendor' => $row['vendor'],
			'scale' => $row['scale']
			);			
			$data[] = $myrow;
		}
		$dbc->close();
		return $data;
	}
}
