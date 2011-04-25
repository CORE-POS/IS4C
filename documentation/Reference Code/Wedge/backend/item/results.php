<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');

	function results($backoffice) {
		if (isset($backoffice['multiple_results'])) {
				$html='
			<div id="results_similar_products_wrap">
				<table id="results_similar_products">
					<thead>
						<tr>
							<th>UPC</th>
							<th>Description</th>
							<th>Price</th>
						</tr>
					</thead>
					<tfoot/>
					<tbody>';
				
				foreach ($backoffice['multiple_results'] as $row) {
					$html.='
						<tr>
							<td><a href="/item/?a=search&q='.$row['upc'].'&t=upc"/>'.$row['upc'].'</td>
							<td>'.$row['description'].'</td>
							<td class="textAlignRight">'.money_format("%!.2n", $row['normal_price']).'</td>
						</tr>';
				}
				
				$html.='
					</tbody>
				</table>
			</div>';
		} else if (isset($backoffice['product_detail'])) {
			similarproducts(&$backoffice);
			if (count($backoffice['similar_products']>0)) {
				$html='
			<div id="results_similar_products_wrap">
				<table id="results_similar_products">
					<thead>
						<tr>
							<th>UPC</th>
							<th>Description</th>
							<th>Price</th>
						</tr>
					</thead>
					<tfoot/>
					<tbody>';
				
				foreach ($backoffice['similar_products'] as $row) {
					$html.='
						<tr>
							<td><a href="/item/?a=search&q='.$row['upc'].'&t=upc"/>'.$row['upc'].'</td>
							<td>'.$row['description'].'</td>
							<td class="textAlignRight">'.money_format("%!.2n", $row['normal_price']).'</td>
						</tr>';
				}
				
				$html.='
					</tbody>
				</table>
			</div>';
			} else {
				$html='';
			}
		} else {
			$html='';
		}
		return $html;
	}