<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class ECheckVerifyPage extends NoInputCorePage 
{

    function preprocess()
    {
        $amount = $_REQUEST['amount'];
        if (isset($_REQUEST['selectlist'])) {
            $opt = $_REQUEST['selectlist'];
            if ($opt == '' || strtoupper($opt) == 'CL') {
                CoreLocal::set('lastRepeat', '');
                $this->change_page($this->page_url."gui-modules/pos2.php");

                return false;
            } else {
                $inp = ($amount*100) . $opt;
                $this->change_page(
                    $this->page_url
                        ."gui-modules/pos2.php?reginput="
                        . urlencode($inp)
                        . '&repeat=1'
                );

                return false;
            }
        }

        return true;
    }
    
    function head_content()
    {
        ?>
        <script type="text/javascript" src="../../js/singleSubmit.js"></script>
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
                    $('#selectlist :selected').val('');
                }
                $('#selectform').submit();
            }
            prevPrevKey = prevKey;
            prevKey = jsKey;
        }
        </script> 
        <?php
    } // END head() FUNCTION

    function body_content() 
    {
        $paper = CoreLocal::get('EcpPaperTender');
        if ($paper === '') {
            $paper = 'CK';
        }
        $echeck = CoreLocal::get('EcpElectronicTender');
        if ($echeck === '') {
            $echeck = 'TK';
        }
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored">
        <span class="larger">Check Type ($<?php echo sprintf('%.2f', $_REQUEST['amount']); ?>)</span>
        <form id="selectform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <select size="3" name="selectlist"
                id="selectlist" onblur="$('#selectlist').focus();">
            <option selected value="<?php echo $echeck; ?>">Electronic</option>
            <option value="<?php echo $paper; ?>">Paper</option>
            <option value="TC">Gift Certificate</option>
            </select>
            <input type="hidden" name="amount" value="<?php echo $_REQUEST['amount']; ?>" />
        </form>
        <p>
        <span class="smaller">[clear] to cancel</span>
        </p>
        </div>
        </div>
        <?php
        $this->add_onload_command("\$('#selectlist').focus();\n");
        $this->add_onload_command("singleSubmit.restrict('#selectform');\n");
        $this->add_onload_command("\$('#selectlist').keypress(processkeypress);\n");
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

