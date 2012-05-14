<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

/**
  @class quickkey
  A class for building menus from buttons

  This class just displays a button that maps
  to an input string - essentially a virtual
  programmable key. If you define an array
  of these buttons, POS can build a menu
  automatically.

  These arrays should be defined in .php files
  in the quickkeys/keys/ directory. The files
  must have numeric names. 0.php will be triggered
  by QK0, 1.php will be triggered by QK1, etc.
  The array must be named $my_keys.
*/

/**
  @example 271828.php 
  The first two lines are a sanity checks. 
  $CORE_PATH <b>should</b> be defined 
  already but it doesn't hurt to make
  sure. These lines can be included as-is in every
  menu definition file.

  $my_keys defines the menu. Pretty basic, but a couple notes:
   - Some button description include newlines. The text
     doesn't wrap automatically and weird stuff can happen
     if you don't break long strings. Exact weirdness
     is browser dependent
   - The second argument can be any POS input including 
     the command for a different menu like "QK3" here. This
     is how you build nested menus.  
*/

class quickkey {
	/**
	  The button text. 
	*/
	var $title;
	/**
	  An image name.
	*/
	var $img;
	/**
	  What the button does
	*/
	var $output_text;

	/**
	  Constructor
	  @param $t is the button text. There's no
	   automatic wrapping to include newline(s)
	   if needed
	  @param $o is what the button does. When the
	   button is selected, this string is fed 
	   back into POS as input.
	  @param $i an image filename. Support for
	   images on buttons instead of text is
	   theoretical and not yet tested.
	*/
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
