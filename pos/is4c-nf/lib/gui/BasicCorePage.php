<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

    This file is part of IT CORE.

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

namespace COREPOS\pos\lib\gui;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\lib\DriverWrappers\ScaleDriverWrapper;

use COREPOS\common\ui\CorePage;

/**

 @class BasicCorePage

   This is the base class for all display scripts

   Display scripts are not required to use this
   base class but it does provide a lot of common
   functionality for building HTML pages with standard
   headers, footers, and styling.

 */

class BasicCorePage extends CorePage
{
    /**
      Relative URL for POS root directory
      Pages often need this.
    */
    protected $page_url;
    protected $body_class='mainBGimage';
    protected $title = "COREPOS";
    protected $hardware_polling = true;

    /**
      A LocalStorage instance representing session data
    */
    protected $session;

    /**
      A COREPOS\common\mvc\ValueContainer
    */
    protected $form;

    /**
      Constructor

      The constructor automatically runs
      the preprocess and print_page methods
      (if applicable). Creating a new instance
      will output the entire page contents
    */
    public function __construct($session, $form)
    {
        $this->session = $session;
        $this->form = $form;
        $this->page_url = MiscLib::baseURL();
        if (file_exists(dirname(__FILE__) . '/../../graphics/is4c.gif')) {
            $this->body_class = 'mainBGimage';
        } elseif (file_exists(dirname(__FILE__) . '/../../graphics/your_logo_here.gif')) {
            $this->body_class = 'placeholderBGimage';
        }
        ob_start();
        $this->drawPage();
        ob_end_flush();
    }

    /**
      Add output in the <head> section
      @return None

      This function should print anything that
      belongs inside the HTML head tags
    */
    public function head_content()
    {
    }

    public function getHeader()
    {
        $my_url = $this->page_url;
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <?php
        echo "<head>";
        echo "<title>".$this->title."</title>";
        $charset = $this->session->get('CoreCharSet') === '' ? 'utf-8' : $this->session->get('CoreCharSet');
        // 18Aug12 EL Add content/charset.
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\" />\n";
        echo "<link rel=\"stylesheet\" type=\"text/css\"
            href=\"{$my_url}/css/pos.css\">";
        // include store css file if it exists
        if (file_exists(dirname(__FILE__).'/../../css/store.css')){
            echo "<link rel=\"stylesheet\" type=\"text/css\"
                href=\"{$my_url}/css/store.css\">";
        }
        $jquery = MiscLib::jqueryFile();
        echo "<script type=\"text/javascript\"
            src=\"{$my_url}/js/{$jquery}\"></script>";
        echo '<script type="text/javascript" src="' . $my_url . '/js/errorLog.js"></script>';
        $this->head_content();
        echo "</head>";
        echo '<body class="'.$this->body_class.'">';
        echo "<div id=\"boundingBox\">";

        return ob_get_clean();
    }

    public function getFooter()
    {
        ob_start();
        echo "</div>";
        $this->scale_box();
        $this->scanner_scale_polling($this->hardware_polling);

        return ob_get_clean();
    }

    protected $mask_input = false;
    public function hide_input($bool)
    {
        $this->mask_input = $bool;
    }

    /**
      Display the standard header with input box
      @param $action What the form does
      @return None

      The default action is for a page to POST
      back to itself. Any specified action will
      be included in the form tag exactly as is.
      You can pass "action=..." or "onsubmit=..."
      (or both) but $action should have one or the
      other attributes
    */
    protected function input_header($action='')
    {
        if (empty($action)) {
            $action = "action=\"". filter_input(INPUT_SERVER, 'PHP_SELF') ."\"";
        }
        $inputType = "text";
        if ($this->mask_input) {
            $inputType = "password";
        }
        $form = '
        <div class="inputform ' . ($this->session->get("training")==1?'training':'') . '">
            <form name="form" id="formlocal" method="post" autocomplete="off"
                ' . $action . '>
            <input name="reginput" value="" onblur="$(\'#reginput\').focus();"
                type="' . $inputType . '" id="reginput"  />
            </form>
        </div>';

        echo str_replace('{{FORM}}', $form, $this->commonHeader());
    }

    protected function dateJS()
    {
        return <<<JAVASCRIPT
        <script type="text/javascript">
        function betterDate() {
            var myNow = new Date();
            var ampm = 'AM';
            var hour = myNow.getHours();
            var minute = myNow.getMinutes();
            if (hour >= 12){
                ampm = 'PM';
                hour = hour - 12;
            }
            if (hour == 0) hour = 12;

            var year = myNow.getYear() % 100;
            var month = myNow.getMonth()+1;
            var day = myNow.getDate();
            if (year < 10) year = '0'+year;
            if (month < 10) month = '0'+month;
            if (day < 10) day ='0'+day;
            if (minute < 10) minute = '0'+minute;

            var timeStr = month+'/'+day+'/'+year+' ';
            timeStr += hour+':'+minute+' '+ampm;
            $('#timeSpan').html(timeStr);
            setTimeout(betterDate,20000);
        }
        </script>
JAVASCRIPT;
    }

    protected function commonHeader()
    {
        $myUrl = $this->page_url;
        $this->add_onload_command("betterDate();\n\$('#reginput').focus();");

        // this needs to be configurable; just fixing
        // a giant PHP warning for the moment
        $time = strftime("%m/%d/%y %I:%M %p", time());

        $this->session->set("repeatable",0);
        ob_start();
        echo $this->dateJS();
        ?>
        <div id="inputArea">
            {{FORM}}
            <div class="notices coloredText <?php echo ($this->session->get("training")==1?'training':''); ?>">
            <?php
            if ($this->session->get("training") == 1) {
                echo "<span class=\"text\">"._("training")." </span>"
                     ."<img alt=\"training\" src='{$myUrl}graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
            } elseif ($this->session->get("standalone") == 0) {
                echo "<img alt=\"online\" src='{$myUrl}graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
            } else {
                echo "<span class=\"text\">stand alone</span>"
                     ."<img alt=\"standalone\" src='{$myUrl}graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
            }
            if ($this->session->get("receiptToggle")==1){
                echo "<img id=\"receipticon\" alt=\"receipt\" src='{$myUrl}graphics/receipt.gif'>&nbsp;&nbsp;&nbsp;";
            } else {
                echo "<img id=\"receipticon\" alt=\"no receipt\" src='{$myUrl}graphics/noreceipt.gif'>&nbsp;&nbsp;&nbsp;";
            }
            if ($this->session->get("CCintegrate") == 1 && $this->session->get("training") == 0) {
               if ($this->session->get("CachePanEncBlock")=="")
                   echo "<img alt=\"cc mode\" src='{$myUrl}graphics/ccIn.gif'>&nbsp;";
               else
                   echo "<img alt=\"cc available\" src='{$myUrl}graphics/ccInLit.gif'>&nbsp;";
            } elseif ($this->session->get("CCintegrate") == 1 && $this->session->get("training") == 1) {
               if ($this->session->get("CachePanEncBlock")=="")
                   echo "<img alt=\"cc test mode\" src='{$myUrl}graphics/ccTest.gif'>&nbsp;";
               else
                   echo "<img alt=\"cc available (test)\" src='{$myUrl}graphics/ccTestLit.gif'>&nbsp;";
            }

            echo "<span id=\"timeSpan\" class=\"time\">".$time."</span>\n";
            if ($this->session->get("prefix") != ""){
                $this->add_onload_command("\$('#reginput').val('"
                    .$this->session->get("prefix")."');\n");
                $this->session->set("prefix","");
            }
            ?>

            </div>
        </div>
        <div id="inputAreaEnd"></div>
        <?php

        return ob_get_clean();
    }

    /**
      Display the standard header without input box
      @return None
    */
    protected function noinput_header()
    {
        $form = '
            <div class="inputform">
            &nbsp;
            </div>';
        echo str_replace('{{FORM}}', $form, $this->commonHeader());
    }

    /**
      Output the standard scale display box
      @return None
    */
    protected function scale_box()
    {
        ?>
        <div id="scalebox">
            <div id="scaleTop" class="coloredArea">
            <?php echo _("weight"); ?>
            </div>
            <div id="scaleBottom">
            <?php echo DisplayLib::scaledisplaymsg(); ?>
            </div>
            <div id="scaleIconBox">
            <?php echo DisplayLib::drawNotifications(); ?>
            </div>
        </div>
        <?php
    }

    /**
      Read input from scale
      @return None

      Outputs the javascript used to poll for scale
      input and activates it on page load.
    */
    protected function scanner_scale_polling($include_scans=true)
    {
        if (!$include_scans) {
            return '';
        }
        $scaleObj = ScaleDriverWrapper::factory($this->session->get('scaleDriver'));
        ?>
        <script type="text/javascript"
            src="<?php echo $this->page_url; ?>js/<?php echo $scaleObj->javascriptFile(); ?>">
        </script>
        <script type="text/javascript"
            src="<?php echo $this->page_url; ?>js/sockjs.min.js">
        </script>
        <script type="text/javascript"
            src="<?php echo $this->page_url; ?>js/stomp.min.js">
        </script>
        <?php
        if ($this->session->get('MQ')) {
            UdpComm::udpSend('mq_up');
            $this->add_onload_command("subscribeToQueue('".$this->page_url."');\n");
        } else {
            UdpComm::udpSend('mq_down');
            $this->add_onload_command("pollScale('".$this->page_url."');\n");
        }
    }

    /**
      Print the standard footer
      @return None
    */
    public function footer()
    {
        echo '<div id="footer">';
        DisplayLib::printfooter();
        echo '</div>';
    }

    /**
      Go to a different page
      @param $url the new page URL

      Use this function instead of manual redirects
      to allow debug output.
    */
    protected function change_page($url)
    {
        if ($this->session->get("Debug_Redirects") == 1){
            $stack = debug_backtrace();
            printf('Follow redirect to <a href="%s">%s</a>',$url,$url);
            echo '<hr />Stack:';
            foreach($stack as $s){
                echo '<ul><li>';
                if(!empty($s['class'])) echo $s['class'].'::';
                echo $s['function'].'()';
                if (isset($s['line']))
                    echo '<li>Line '.$s['line'].', '.$s['file'];
            }
            foreach($stack as $s) echo '</ul>';
        }
        else
            header("Location: ".$url);
    }

    /**
      Callback for javascript scanner-scale polling
      This one sends scan input to a form field on the
      page and other inputs through the normal parser
    */
    protected function default_parsewrapper_js($input="reginput",$form="formlocal")
    {
    ?>
    <script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
    <script type="text/javascript">
    function parseWrapper(str) {
        if (/^\d+$/.test(str)) {
            $('#<?php echo $input; ?>').val(str);
            $('#<?php echo $form; ?>').submit();
        } else {
            runParser(str, '<?php echo $this->page_url; ?>');
        }
    }
    </script>
    <?php
    }

    /**
      Callback for javascript scanner-scale polling
      This one ignores scan input and runs anything
      else through the parser
    */
    protected function noscan_parsewrapper_js()
    {
    ?>
    <script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
    <script type="text/javascript">
    function parseWrapper(str) {
        if (/^\d+$/.test(str)) {
            // do nothing
        } else {
            runParser(str, '<?php echo $this->page_url; ?>');
        }
    }
    </script>
    <?php
    }
}

/**
   @example HelloWorld.php

  AutoLoader.php should be included in any top level
  scripts. If the URL in the browser address bar
  is your script, it's a top level script. No other
  includes are necessary. AutoLoader will include
  other classes as needed.

  body_content() draws the page. Methods from BasicCorePage
  provide the standard input box at the top and footer
  at the bottom. DisplayLib::boxMsg() is a utility function that
  puts the 'Hello World' message in a standard message
  box.

  preprocess() handles input. In this case any form
  input causes a redirect to the main display script.

  Note the very last line creating an object. That's
  necessary to actually display anything.

*/
