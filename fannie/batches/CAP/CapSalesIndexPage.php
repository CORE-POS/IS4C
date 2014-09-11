<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CapSalesIndexPage extends FanniePage {
    protected $title = "Fannie - CAP sales";
    protected $header = "CAP Sales";

    public $description = '[Co+op Deals Menu] lists options for importing and creating
    Co+op Deals batches.';

    function body_content(){
        ob_start();
        ?>
        <ul>
        <li><a href="CoopDealsUploadPage.php">Upload Price File</a></li>
        <li><a href="CoopDealsReviewPage.php">Review data &amp; create sales batches</a></li>
        </ul>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
