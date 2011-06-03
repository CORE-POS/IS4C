<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

class quickkey {
	var $title;
	var $img;
	var $output_text;

	function quickkey($t,$o,$i=""){
		$this->title = $t;
		$this->output_text = $o;
		$this->img = $i;
	}

	function display($id=""){
		global $CORE_PATH;
		$ret = sprintf("<form action=\"%s\" method=\"post\"
			style=\"display:inline;\">",
			$_SERVER["PHP_SELF"]);
		$ret = "";
		if ($this->img == ""){
			$ret .= sprintf("<input type=\"submit\"
				name=\"quickkey_submit\" id=\"%s\"
				value=\"%s\" class=\"quick_button\" />
				<input type=\"hidden\" name=\"%s\"
				value=\"%s\" />",$id,$this->title,
				md5($this->title),
				$this->output_text);
		}
		else {
			$ret .= sprintf("<input type=\"submit\"
				name=\"quickkey_submit\" id=\"%s\"
				value=\"%s\" class=\"quick_button\" 
				src=\"%s\" />
				<input type=\"hidden\" name=\"%s\"
				value=\"%s\" />",$id,$this->title,
				$CORE_PATH.
				"quickkeys/imgs/".$this->img,
				md5($this->title),
				$this->output_text);
		}
		//$ret .= "</form>";
		return $ret;
	}
}

?>
