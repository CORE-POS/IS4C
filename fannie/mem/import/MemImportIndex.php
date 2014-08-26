<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemImportIndex extends FanniePage {
    protected $title = "Fannie :: Member Tools";
    protected $header = "Import Member Information";

    public $description = '[Member Import Menu] lists tools for importing member information.';
    
    function body_content(){
        ob_start();
        ?>
        <ul>
        <li><a href="MemNameNumImportPage.php">Names &amp; Numbers</a></li>
        <li><a href="MemContactImportPage.php">Contact Information</a></li>
        <li><a href="EquityHistoryImportPage.php">Existing Equity</a></li>
        </ul>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>

