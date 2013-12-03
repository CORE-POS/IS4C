<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include('../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include_once($FANNIE_ROOT.'src/JsonLib.php');

class ViewPurchaseOrders extends FannieRESTfulPage {

	protected $header = 'Purchase Orders';
	protected $title = 'Purchase Orders';

	protected $must_authenticate = false;

	private $show_all = true;

	function preprocess(){
		$this->__routes[] = 'get<pending>';
		$this->__routes[] = 'get<placed>';
		$this->__routes[] = 'post<id><setPlaced>';
		$this->__routes[] = 'get<id><export>';
		if (FormLib::get_form_value('all') === '0')
			$this->show_all = false;
		return parent::preprocess();
	}

	function get_id_export_handler(){
		if (!file_exists('exporters/'.$this->export.'.php'))
			return $this->unknown_request_handler();
		include_once('exporters/'.$this->export.'.php');	
		if (!class_exists($this->export))
			return $this->unknown_request_handler();

		$exportObj = new $this->export();
		$exportObj->send_headers();
		$exportObj->export_order($this->id);
		return False;
	}

	function post_id_setPlaced_handler(){
		global $FANNIE_OP_DB;
		$model = new PurchaseOrderModel(FannieDB::get($FANNIE_OP_DB));
		$model->orderID($this->id);
		$model->placed($this->setPlaced);
		if ($this->setPlaced == 1)
			$model->placedDate(date('Y-m-d H:m:s'));
		else
			$model->placedDate(null);
		$model->save();
		echo ($this->setPlaced == 1) ? $model->placedDate() : 'n/a';
		return False;
	}

	function get_pending_handler(){
		echo $this->get_orders(0);
		return False;
	}

	function get_placed_handler(){
		echo $this->get_orders(1);
		return False;
	}

	function get_orders($placed){
		global $FANNIE_OP_DB;	
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$query = 'SELECT p.orderID, p.vendorID, MIN(creationDate) as creationDate,
			MIN(placedDate) as placedDate, COUNT(i.orderID) as records,
			SUM(i.unitCost*i.caseSize*i.quantity) as estimatedCost,
			SUM(i.receivedTotalCost) as receivedCost, v.vendorName,
			MAX(i.receivedDate) as receivedDate
			FROM PurchaseOrder as p
			LEFT JOIN PurchaseOrderItems AS i ON p.orderID = i.orderID
			LEFT JOIN vendors AS v ON p.vendorID=v.vendorID
			WHERE placed=? ';
		if (!$this->show_all) $query .= 'AND userID=? ';
		$query .= 'GROUP BY p.orderID, p.vendorID, v.vendorName
			ORDER BY MIN(creationDate)';
		$args = array($placed);
		if (!$this->show_all) $args[] = FannieAuth::getUID($this->current_user);

		$prep = $dbc->prepare_statement($query);
		$result = $dbc->exec_statement($prep, $args);

		$ret = '<table cellspacing="0" cellpadding="4" border="1">';
		$ret .= '<tr><th>Created</th><th>Vendor</th><th># Items</th><th>Est. Cost</th>
			<th>Placed</th><th>Received</th><th>Rec. Cost</th></tr>';
		while($w = $dbc->fetch_row($result)){
			$ret .= sprintf('<tr><td><a href="ViewPurchaseOrders.php?id=%d">%s</a></td>
					<td>%s</td><td>%d</td><td>%.2f</td>
					<td>%s</td><td>%s</td><td>%.2f</td></tr>',
					$w['orderID'],
					$w['creationDate'], $w['vendorName'], $w['records'],
					$w['estimatedCost'],
					($placed == 1 ? $w['placedDate'] : '&nbsp;'),
					(!empty($w['receivedDate']) ? $w['receivedDate'] : '&nbsp;'),
					(!empty($w['receivedCost']) ? $w['receivedCost'] : 0.00)
			);
		}
		$ret .= '</table>';
	
		return $ret;
	}

	function get_id_view(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$order = new PurchaseOrderModel($dbc);
		$order->orderID($this->id);
		$order->load();

		$vendor = new VendorsModel($dbc);
		$vendor->vendorID($order->vendorID());
		$vendor->load();

		$ret = '<b>Vendor</b>: '.$vendor->vendorName();
		$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		$ret .= '<b>Created</b>: '.$order->creationDate();
		$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		$ret .= '<b>Placed</b>: <span id="orderPlacedSpan">'.($order->placed() ? $order->placedDate() : 'n/a').'</span>';
		$ret .= '<input type="checkbox" '.($order->placed() ? 'checked' : '').' id="placedCheckbox"
				onclick="togglePlaced('.$this->id.');" />';

		$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

		$ret .= 'Export as: <select id="exporterSelect">';
		$dh = opendir('exporters');
		while( ($file=readdir($dh)) !== False){
			if (substr($file,-4) != '.php')
				continue;
			include('exporters/'.$file);
			$class = substr($file,0,strlen($file)-4);
			if (!class_exists($class)) continue;
			$obj = new $class();
			if (!isset($obj->nice_name)) continue;
			$ret .= '<option value="'.$class.'">'.$obj->nice_name.'</option>';
		}
		$ret .= '</select>';
		$ret .= '<input type="submit" value="Export" onclick="doExport('.$this->id.');return false;" />';

        $codingQ = 'SELECT d.salesCode, SUM(o.receivedTotalCost) as rtc
                    FROM PurchaseOrderItems AS o
                    LEFT JOIN products AS p ON o.internalUPC=p.upc
                    LEFT JOIN deptSalesCodes AS d ON p.department=d.dept_ID
                    WHERE o.orderID=?
                    GROUP BY d.salesCode';
        $codingP = $dbc->prepare($codingQ);
        $codingR = $dbc->execute($codingP, array($this->id));
        $ret .= '<br />';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1"><tr><th colspan="2">Coding(s)</th>';
        $ret .= '<td><b>PO#</b>: '.$order->vendorOrderID().'</td>';
        $ret .= '<td><b>Invoice#</b>: '.$order->vendorInvoiceID().'</td>';
        $ret .= '</tr>';
        while($codingW = $dbc->fetch_row($codingR)) {
            if ($codingW['rtc'] == 0 && empty($codingW['salesCode'])) {
                continue;
            } else if (empty($codingW['salesCode'])) {
                $codingW['salesCode'] = 'n/a';
            }
            $ret .= sprintf('<tr><td>%s</td><td>%.2f</td><td colspan="2"></td></tr>',
                        $codingW['salesCode'], $codingW['rtc']); 
        }
        $ret .= '</table><br />';

		$model = new PurchaseOrderItemsModel($dbc);
		$model->orderID($this->id);

		$ret .= '<table cellspacing="0" cellpadding="4" border="1">';
		$ret .= '<tr><th>SKU</th><th>Brand</th><th>Description</th>
			<th>Unit Size</th><th>Units/Case</th><th>Cases</th>
			<th>Est. Cost</th><th>&nbsp;</th><th>Received</th>
			<th>Rec. Qty</th><th>Rec. Cost</th></tr>';
		foreach($model->find() as $obj){
			$ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td>
					<td>%s</td><td>%s</td><td>%d</td><td>%.2f</td>
					<td>&nbsp;</td><td>%s</td><td>%d</td><td>%.2f</td>
					</tr>',
					$obj->sku(),
					$obj->brand(),
					$obj->description(),
					$obj->unitSize(), $obj->caseSize(),
					$obj->quantity(),
					($obj->quantity() * $obj->caseSize() * $obj->unitCost()),
					$obj->receivedDate(),
					$obj->receivedQty(),
					$obj->receivedTotalCost()
			);
		}
		$ret .= '</table>';

		$this->add_script('js/view.js');

		return $ret;
	}

	function get_view(){
		$ret = '<b>Status</b><select id="orderStatus" onchange="fetchOrders();">';
        $ret .= '<option value="pending">Pending</option><option selected value="placed">Placed</option>';
		$ret .= '</select>';

		$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

		$ret .= '<b>Showing</b><select id="orderShow" onchange="fetchOrders();">';
		if ($this->show_all)
			$ret .= '<option value="0">My Orders</option><option selected value="1">All Orders</option>';
		else
			$ret .= '<option selected value="0">My Orders</option><option value="1">All Orders</option>';
		$ret .= '</select>';

		$ret .= '<hr />';
		
		$ret .= '<div id="ordersDiv"></div>';	

		$this->add_script('js/view.js');
		$this->add_onload_command("fetchOrders();\n");

		return $ret;
	}
}

FannieDispatch::go();

?>
