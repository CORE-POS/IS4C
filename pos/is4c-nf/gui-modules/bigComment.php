<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\TransRecord;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class bigComment extends NoInputCorePage 
{
    /**
      Input processing function
    */
    function preprocess()
    {
        // a selection was made
        if ($this->form->tryGet('comment') !== '') {

            if ($this->form->tryGet('cleared') === '1') {
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }
            
            $comment = str_replace("\r",'',$this->form->comment);
            // remove trailing newline from double enter
            $comment = substr($comment,0,strlen($comment)-1);
            $lines = explode("\n", $comment);
            foreach($lines as $line){
                $line = trim($line);
                if (strlen($line) == 0)
                    continue;
                elseif (strlen($line) <= 30)
                    TransRecord::addcomment($line);
                else {
                    $wrap = wordwrap($line, 30, "\n", True);
                    $shorter_lines = explode("\n", $wrap);
                    foreach($shorter_lines as $short_line)
                        TransRecord::addcomment($short_line);
                }
            }
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return False;
        }
        return True;
    } // END preprocess() FUNCTION

    /**
      Pretty standard javascript for
      catching CL typed in a select box
    */
    function head_content()
    {
        ?>
        <script type="text/javascript" >
        var prevKey = -1;
        var prevPrevKey = -1;
        function processkeypress(e) {
            var jsKey;
            if (e.keyCode) // IE
                jsKey = e.keyCode;
            else if(e.which) // Netscape/Firefox/Opera
                jsKey = e.which;
            if (jsKey==13) {
                if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
                (prevKey == 108 || prevKey == 76) ){ //CL<enter>
                    $('#cleared').val(1);
                    $('#selectform').submit();
                }
                else if ( (prevPrevKey == 116 || prevPrevKey == 84) &&
                (prevKey == 116 || prevKey == 84) ){ //TT<enter>
                    $('#cleared').val(1);
                    $('#selectform').submit();
                }
                else if (prevKey == 13){
                    $('#selectform').submit();
                }
            }
            prevPrevKey = prevKey;
            prevKey = jsKey;
        }
        </script> 
        <?php
    } // END head() FUNCTION

    /**
      Build a <select> form that submits
      back to this script
    */
    function body_content()
    {
        echo "<div class=\"baseHeight\">"
            ."<div class=\"listbox\">"
            ."<form name=\"selectform\" method=\"post\" action=\"" . filter_input(INPUT_SERVER, 'PHP_SELF') . "\""
            ." id=\"selectform\">"
            ."<textarea name=\"comment\" id=\"comment\" "
            ." cols=\"35\" rows=\"15\" onblur=\"\$('#comment').focus();\">";

        echo "</textarea>"
            .'<input type="hidden" name="cleared" id="cleared" value="0" />'
            ."</form>"
            ."</div>"
            ."<div class=\"listboxText coloredText centerOffset\">";
        echo _('press [enter] twice to save');
        echo '<br />';
        echo _("clear to cancel")."</div>"
            ."<div class=\"clear\"></div>";
        echo "</div>";

        $this->add_onload_command("\$('#comment').keypress(processkeypress);\n");
        $this->add_onload_command("\$('#comment').focus();\n");
    } // END body_content() FUNCTION

}

AutoLoader::dispatch();

