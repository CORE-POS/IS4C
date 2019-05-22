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

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\Scanning\SpecialUPCs\HouseCoupon;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class EmailPage extends BasicCorePage 
{
    private $bmpPath;

    function head_content()
    {
        ?>
        <script type="text/javascript">
        function emailSubmit() {
            var inp = $('#reginput').val().toUpperCase();
            if (inp === 'CL') {
                window.location = 'pos2.php';
            } else if (inp == 'CP' || inp == 'IC') {
                window.location="EmailPage.php?email=override";
            } else {
                window.location = 'EmailPage.php';
            }
            return false;
        }
        function getEmailFromPad() {
            $.ajax({
                url: 'http://localhost:8999',
                type: 'POST',
                data: '<msg>termEmail</msg>',
                dataType: 'text'
            }).done(function (resp) {
                var email = encodeURIComponent(resp);
                if (resp.includes("@")) {
                    location = 'EmailPage.php?email=' + email;
                } else {
                    var curText = $('.textArea').html();
                    var newText = '<b>Error</b>: invalid email<br />' + curText;
                    $('.textArea').html(newText);
                }
            }).fail(function () {
                var curText = $('.textArea').html();
                var newText = '<b>Error</b>: could not get email<br />' + curText;
                $('.textArea').html(newText);
            });
        }
        </script>
        <?php
    }

    function preprocess()
    {
        $email = FormLib::get('email', false);
        if ($email !== false) {
            $email = trim($email);
            TransRecord::addcomment("EMAIL ENTERED");
            TransRecord::addLogRecord(array('upc'=>'EMAIL', 'description'=>$email));
            // todo: add coupon
            $hcoup = new HouseCoupon(new WrappedStorage());
            $add = $hcoup->getValue(321);
            TransRecord::addhousecoupon('0049999900321', $add['department'], -1 * $add['value'], $add['description'], $add['discountable']);
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return false;
        }

        $this->addOnloadCommand("getEmailFromPad();");
        return true;
    }

    function body_content()
    {
        $this->input_header("onsubmit=\"return emailSubmit();\" action=\"".AutoLoader::ownURL()."\"");
        echo DisplayLib::printheaderb();
        ?>
        <div class="baseHeight">
        <?php
        echo "<div id=\"boxMsg\" class=\"centeredDisplay\">";

        echo "<div class=\"boxMsgAlert coloredArea\">";
        echo _("Waiting for email address");
        echo "</div>";

        echo "<div class=\"\">";

        echo "<div id=\"imgArea\"></div>";
        echo '<div class="textArea">';
        echo '<span id="emInstructions" style="font-size:90%;">';
        echo _('[subtotal] to get re-request email, [clear] to cancel');
        echo '</span>';
        echo "</div>";

        echo "</div>"; // empty class
        echo "</div>"; // #boxMsg
        echo "</div>"; // .baseHeight
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";

        $this->session->set("boxMsg",'');
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

