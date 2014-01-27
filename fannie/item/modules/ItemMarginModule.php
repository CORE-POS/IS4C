<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

include_once(dirname(__FILE__).'/../../config.php');
include_once(dirname(__FILE__).'/../../classlib2.0/item/ItemModule.php');
include_once(dirname(__FILE__).'/../../classlib2.0/lib/FormLib.php');
include_once(dirname(__FILE__).'/../../src/JsonLib.php');

class ItemMarginModule extends ItemModule {
	
	function ShowEditForm($upc){
		$db = $this->db();
		$p = $db->prepare_statement('SELECT normal_price,cost,department FROM products WHERE upc=?');
		$r = $db->exec_statement($p,array($upc));
		$vals = array(0,0,0);
		if ($db->num_rows($r) > 0)
			$vals = $db->fetch_row($r);
		$ret = '<fieldset id="ItemMarginFieldset">';
		$ret .= '<legend>Margin</legend>';
		$ret .= '<div id="ItemMarginContents">';
		$ret .= $this->calculateMargin($vals[0],$vals[1],$vals[2]);
		$ret .= '</div>';
		$ret .= '</fieldset>';
		$ret .= $this->js();
		return $ret;
	}

	private function getSRP($cost,$margin){
		$srp = sprintf("%.2f",$cost/(1-$margin));
		while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
		       substr($srp,strlen($srp)-1,strlen($srp)) != "9")
			$srp += 0.01;
		return $srp;
	}

	private function calculateMargin($price,$cost,$deptID)
    {
		$dbc = $this->db();

        $dm = 'Unknown';
        $dept = new DepartmentsModel($dbc);
        $dept->dept_no($deptID);
        if ($dept->load()) {
            $dm = $dept->margin() * 100;
        }

        if ((empty($dm) || $dm == 'Unknown') && $dbc->tableExists('deptMargin')) {
            $dmP = $dbc->prepare_statement("SELECT margin FROM deptMargin WHERE dept_ID=?");
            $dmR = $dbc->exec_statement($dmP,array($deptID));
            if ($dbc->num_rows($dmR) > 0){
                $row = $dbc->fetch_row($dmR);
                $dm = sprintf('%.2f',$dmR['margin']*100);
            }
        }

		$ret = "Desired margin on this department is ".$dm."%";
		$ret .= "<br />";
		
		$actual = 0;
		if ($price != 0)
			$actual = (($price-$cost)/$price)*100;
		if ($actual > $dm && is_numeric($dm)){
			$ret .= sprintf("<span style=\"color:green;\">Current margin on this item is %.2f%%<br />",
				$actual);
		}
		elseif (!is_numeric($price)){
			$ret .= "<span style=\"color:green;\">No price has been saved for this item<br />";
		}
		else {
			$ret .= sprintf("<span style=\"color:red;\">Current margin on this item is %.2f%%</span><br />",
				$actual);
			$srp = $this->getSRP($cost,$dm/100.0);
			$ret .= sprintf("Suggested price: \$%.2f ",$srp);
			$ret .= sprintf("(<a href=\"\" onclick=\"\$('#price').val(%.2f); updateMarginMod(); return false;\">Use this price</a>)",$srp);
		}

		return $ret;
	}

	private function js(){
		global $FANNIE_URL;
		ob_start();
		?>
		<script type="text/javascript">
		function updateMarginMod(){
			$.ajax({
				url: '<?php echo $FANNIE_URL; ?>item/modules/ItemMarginModule.php',
				data: 'p='+$('#price').val()+'&d='+$('#department').val()+'&c='+$('#cost').val(),
				cache: false,
				success: function(data){
					$('#ItemMarginContents').html(data);
				}
			});
		}
		$('#price').change(updateMarginMod);
		$('#cost').change(updateMarginMod);
		</script>
		<?php
		return ob_get_clean();
	}

	function AjaxCallback(){
		$p = FormLib::get_form_value('p',0);
		$d = FormLib::get_form_value('d',0);
		$c = FormLib::get_form_value('c',0);
		echo $this->CalculateMargin($p,$c,$d);
	}
}

/**
  This form does some fancy tricks via AJAX calls. This block
  ensures the AJAX functionality only runs when the script
  is accessed via the browser and not when it's included in
  another PHP script.
*/
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)){
	$obj = new ItemMarginModule();
	$obj->AjaxCallback();	
}
