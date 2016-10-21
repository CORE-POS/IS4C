<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	function make_synchronization_query() {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='SELECT `synchronizationLog`.`datetime` FROM `is4c_log`.`synchronizationLog` WHERE `synchronizationLog`.`name`=\'products\' AND `synchronizationLog`.`status`=1 ORDER BY `synchronizationLog`.`datetime` DESC LIMIT 1';
			$result=mysql_query($query, $link);

			// TODO, if unable to find last sync datetime, sync whole table?
			if ($result && mysql_num_rows($result)==1) {
				$row=mysql_fetch_array($result);
		
				// Grab products from server to synchronize
				$query='SELECT `products`.`upc`, `products`.`description`, `products`.`normal_price`, `products`.`pricemethod`, `products`.`groupprice`, `products`.`quantity`, `products`.`special_price`, `products`.`specialpricemethod`, `products`.`specialgroupprice`, `products`.`specialquantity`, `products`.`start_date`, `products`.`end_date`, `products`.`department`, `products`.`size`, `products`.`tax`, `products`.`foodstamp`, `products`.`scale`, `products`.`mixmatchcode`, `products`.`modified`, `products`.`advertised`, `products`.`tareweight`, `products`.`discount`, `products`.`discounttype`, `products`.`unitofmeasure`, `products`.`wicable`, `products`.`qttyEnforced`, `products`.`inUse`, `products`.`subdept`, `products`.`deposit`, `products`.`id` FROM `is4c_op`.`products` WHERE `products`.`modified`>=\''.$row['datetime'].'\' AND `products`.`inUse`=1';
				$result=mysql_query($query, $link);
				if ($result) {
					if (mysql_num_rows($result)>0) {
						$query='INSERT INTO `opdata`.`products` (`upc`, `description`, `normal_price`, `pricemethod`, `groupprice`, `quantity`, `special_price`, `specialpricemethod`, `specialgroupprice`, `specialquantity`, `start_date`, `end_date`, `department`, `size`, `tax`, `foodstamp`, `scale`, `mixmatchcode`, `modified`, `advertised`, `tareweight`, `discount`, `discounttype`, `unitofmeasure`, `wicable`, `qttyEnforced`, `inUse`, `subdept`, `deposit`, `id`) VALUES ';
						while ($row=mysql_fetch_array($result)) {
							$query.=' ( '.$row['upc'].', \''.$row['description'].'\', '.$row['normal_price'].', '.$row['pricemethod'].', '.$row['groupprice'].', '.$row['quantity'].', '.$row['special_price'].', '.$row['specialpricemethod'].', '.$row['specialgroupprice'].', '.$row['specialquantity'].', \''.$row['start_date'].'\', \''.$row['end_date'].'\', '.$row['department'].', \''.$row['size'].'\', '.$row['tax'].', '.$row['foodstamp'].', '.$row['scale'].', \''.$row['mixmatchcode'].'\', \''.$row['modified'].'\', '.$row['advertised'].', '.$row['tareweight'].', '.$row['discount'].', '.$row['discounttype'].', \''.$row['unitofmeasure'].'\', '.$row['wicable'].', '.$row['qttyEnforced'].', '.$row['inUse'].', '.$row['subdept'].', '.$row['deposit'].', '.$row['id'].' ),';
						}
		
						// yeah, that's a dirty way to remove the trailing comma
						$query=substr($query, 0, -1);
						$query.=' ON DUPLICATE KEY UPDATE `upc`=VALUES(`upc`), `description`=VALUES(`description`), `normal_price`=VALUES(`normal_price`), `pricemethod`=VALUES(`pricemethod`), `groupprice`=VALUES(`groupprice`), `quantity`=VALUES(`quantity`), `special_price`=VALUES(`special_price`), `specialpricemethod`=VALUES(`specialpricemethod`), `specialgroupprice`=VALUES(`specialgroupprice`), `specialquantity`=VALUES(`specialquantity`), `start_date`=VALUES(`start_date`), `end_date`=VALUES(`end_date`), `department`=VALUES(`department`), `size`=VALUES(`size`), `tax`=VALUES(`tax`), `foodstamp`=VALUES(`foodstamp`), `scale`=VALUES(`scale`), `mixmatchcode`=VALUES(`mixmatchcode`), `modified`=VALUES(`modified`), `advertised`=VALUES(`advertised`), `tareweight`=VALUES(`tareweight`), `discount`=VALUES(`discount`), `discounttype`=VALUES(`discounttype`), `unitofmeasure`=VALUES(`unitofmeasure`), `wicable`=VALUES(`wicable`), `qttyEnforced`=VALUES(`qttyEnforced`), `inUse`=VALUES(`inUse`), `subdept`=VALUES(`subdept`), `deposit`=VALUES(`deposit`), `id`=VALUES(`id`)';
						return $query;
					} else {
//						return mysql_num_rows($result);
						return false;
					}
				} else {
//					return mysql_error($link);
					return false;
				}
			} else {
//				return mysql_error($link);
				return false;
			}
		} else {
			return false;
		}
	}
?>