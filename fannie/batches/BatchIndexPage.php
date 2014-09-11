<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
include(dirname(__FILE__). '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchIndexPage extends FanniePage {
    protected $title = 'Fannie - Batch Module';
    protected $header = 'Sales Batches';

    public $description = '[Batch Menu] lists various batch related pages.';

    function body_content(){
        ob_start();
        ?>
        <ul>
            <li><a href="newbatch/">Sales Batches</a> is a tool to create
                batches manually one item at a time.</li>
            <li><a href="xlsbatch/">Upload Batch</a> is a tool to create
                a batch from a spreadsheet.</li>
            <li><a href="BatchTypeEditor.php">Manage Batch Types</a> adds, removes, or
                adjusts batch types</li>
            <li><a href="CAP/">Co+op Deals</a> imports the Co+op Deals pricing
                spreadsheet, determines where sale items exist in POS,
                and creates appropriate sales batches.</li>
            <li><a href="UNFI/">Vendor Pricing</a> imports cost information
                from vendor spreadsheets, calculates SRPs based on desired
                margins, and creates price change batches to apply new
                SRPs.</li>
        </ul>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
