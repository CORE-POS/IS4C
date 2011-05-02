<?php

function HtmlToArray($str){

	$dom = new DOMDocument();
	@$dom->loadHTML($str); // ignore warning on [my] poorly formed html

	$tables = $dom->getElementsByTagName("table");
	$rows = $tables->item(0)->getElementsByTagName('tr');

	/* convert tables to 2-d array */
	$ret = array();
	$i = 0;
	foreach($rows as $row){
		$ret[$i] = array();
		foreach($row->childNodes as $node){
			if (!property_exists($node,'tagName')) continue;
			$val = trim($node->nodeValue,chr(160).chr(194));
			if ($node->tagName=="th") $val .= chr(0).'bold';

			if ($node->tagName=="th" || $node->tagName=="td")
				$ret[$i][] = $val;
		}
		$i++;
	}

	/* prepend any other lines to the array */
	$str = preg_replace("/<table.*?>.*<\/table>/s","",$str);
	$str = preg_replace("/<head.*?>.*<\/head>/s","",$str);
	$str = preg_replace("/<body.*?>/s","",$str);
	$str = str_replace("</body>","",$str);
	$str = str_replace("<html>","",$str);
	$str = str_replace("</html>","",$str);

	$extra = preg_split("/<br.*?>/s",$str);
	foreach(array_reverse($extra) as $e){
		if (!empty($e))
			array_unshift($ret,array($e));
	}

	return $ret;
}

?>
