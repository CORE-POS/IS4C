<?php

use COREPOS\pos\lib\MiscLib;

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
    function __construct($t,$o,$i=""){
        $this->title = $t;
        $this->output_text = $o;
        $this->img = $i;
    }

    function display($id="",$tagType="submit",$onclick=""){
        $ret = "";
        $baseURL = MiscLib::baseURL();
        if ($this->img == ""){
            $ret .= sprintf('
                <button type="%s" onclick="%s"
                    name="quickkey_submit" id="%s"
                    value="%s"
                    class="quick_button_max pos-button coloredBorder">
                    %s
                </button>
                <input type="hidden" name="%s"
                    value="%s" />',
                $tagType, $onclick,
                $id,
                $this->title,
                $this->title,
                md5($this->title),
                $this->output_text);
        } else {
            $imgURL = $baseURL . 'plugins/QuickKeys/quickkeys/' 
                . (is_numeric($this->img) ? 'noauto/img.php?imgID=' . $this->img : 'imgs/' . $this->img);
            $fontSize = strlen($this->title) > 10 ? 75 : 100;
            $ret .= sprintf("<button type=\"%s\" onclick=\"%s\"
                name=\"quickkey_submit\" id=\"%s\" value=\"%s\"
                class=\"quick_button_max pos-button coloredBorder quickButtonImage\">
                <span style=\"font-size: %d%%; line-height: 0.2em;\">%s</span><br />
                <img src=\"%s\" />
                </button>
                <input type=\"hidden\" name=\"%s\"
                value=\"%s\" />",
                $tagType, $onclick,
                $id,$this->title,
                $fontSize, $this->title,
                $imgURL,
                md5($this->title),
                $this->output_text);
        }

        return $ret;
    }
}

