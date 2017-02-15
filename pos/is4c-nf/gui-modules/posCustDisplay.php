<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class posCustDisplay extends BasicCorePage
{
    protected $title = "COREPOS Customer Display";
    protected $hardware_polling = false;

    public function body_content()
    {
        echo $this->noinput_header();
        ?>
        <div class="baseHeight">
        <?php

        if ($this->session->get("plainmsg") && strlen($this->session->get("plainmsg")) > 0) {
            echo DisplayLib::printheaderb();
            echo "<div class=\"centerOffset\">";
            echo DisplayLib::plainmsg($this->session->get("plainmsg"));
            echo "</div>";
        } else {
            // No input and no messages, so
            // list the items
            echo ($this->session->get("End") == 1) ? DisplayLib::printReceiptfooter(true) : DisplayLib::lastpage(true);
        }
        echo "</div>"; // end base height

        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter(true);
        echo '</div>';

    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        ob_start();
        $this->session->set('plainmsg', 'foo');
        $this->body_content();
        $body = ob_get_clean();
        $phpunit->assertNotEquals(0, strlen($body));
        ob_start();
        $this->session->set('plainmsg', '');
        $this->session->set('End', 1);
        $this->body_content();
        $body = ob_get_clean();
        $phpunit->assertNotEquals(0, strlen($body));
        $this->session->set('End', 0);
    }
}

AutoLoader::dispatch();

