<?php

function ArrayToXls($array){
	global $FANNIE_ROOT;

	include_once($FANNIE_ROOT.'src/Excel/xls_write/Spreadsheet_Excel_Writer/Writer.php');

	$fn = tempnam(sys_get_temp_dir(),"xlstemp");
	$workbook = new Spreadsheet_Excel_Writer($fn);
	$worksheet =& $workbook->addWorksheet();

	$format_bold =& $workbook->addFormat();
	$format_bold->setBold();

	for($i=0;$i<count($array);$i++){
		for($j=0;$j<count($array[$i]);$j++){
			// 5Apr14 EL Added the isset test for StoreSummaryReport.php with multiple header sets.
			//            Why should it be needed?
			if (isset($array[$i][$j])) {
				if ( ($pos = strpos($array[$i][$j],chr(0))) !== False){
					$val = substr($array[$i][$j],0,$pos);
					$worksheet->write($i,$j,$val,$format_bold);
				} else  {
					$worksheet->write($i,$j,$array[$i][$j]);
				}
			}
		}
	}

	$workbook->close();

	$ret = file_get_contents($fn);
	unlink($fn);
	return $ret;
}

function ArrayToXls2($array){
	$ret = xlsBOF();
	$rownum = 1;
	foreach($array as $row){
		$colnum = 0;
		foreach($row as $col){
			if (is_numeric($col))
				$ret .= xlsWriteNumber($rownum,$colnum,$col);
			elseif(!empty($col))
				$ret .= xlsWriteLabel($rownum,$colnum,$col);
			$colnum++;
		}
		$rownum++;
	}
	$ret .= xlsEOF();

	return $ret;
}

/* additional functions from example @
   http://www.appservnetwork.com/modules.php?name=News&file=article&sid=8
*/
function xlsBOF() {
    return pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);  
} 

function xlsEOF() {
    return pack("ss", 0x0A, 0x00);
}

function xlsWriteNumber($Row, $Col, $Value) {
    return  pack("sssss", 0x203, 14, $Row, $Col, 0x0)
    	. pack("d", $Value);
} 

function xlsWriteLabel($Row, $Col, $Value ) {
    $L = strlen($Value);
    return pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x2bc, $L)
    	. $Value;
}

?>
