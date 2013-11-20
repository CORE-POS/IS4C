<?php

class WfcEquityMessage extends CustomerReceiptMessage {

	function message($str){
		global $CORE_LOCAL;
		$ret = "";
		if (strstr($str," == ") ){
			$lines = explode(" == ",$str);
			if ($CORE_LOCAL->get("equityNoticeAmt") > 0){
				if (isset($lines[0]) && is_numeric(substr($lines[0],13))){
					$newamt = substr($lines[0],13) - $CORE_LOCAL->get("equityNoticeAmt");
					$lines[0] = sprintf('EQUITY BALANCE DUE $%.2f',$newamt);
					if ($newamt <= 0 && isset($lines[1]))
						$lines[1] = "PAID IN FULL";
				}
			}
			foreach($lines as $line)
				$ret .= ReceiptLib::centerString($line)."\n";
		}
		else
			$ret .= $str;
		return $ret;
	}

}

?>
