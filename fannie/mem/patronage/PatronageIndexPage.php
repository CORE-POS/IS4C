<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

class PatronageIndexPage extends FannieRESTfulPage
{
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Fannie :: Patronage Tools";
    public $themed = true;

    public function helpContent()
    {
        return '<p><strong>Overview</strong>: this collection of tools
            compiles patronage data for a year.</p>
            <p>The first four steps are in order. Start by gathering up
            transaction data from the year with <em>Create working table</em>.
            Then <em>Calculate gross purchases</em> (and discounts), if necessary
            <em>Calculate rewards</em>, and then <em>Update net purchases</em>.
            Gross purchases is member spending during the year. That amount may
            be reduced by benefits received during the year to get net purchases.
            Member discounts are one such benefit. Rewards are any other benefits
            that should reduce gross purchases.</p>
            <p>At this point, there is a draft copy of member spending for the
            year. The <em>Report of loaded info</em> lists this information
            and can also export it as a spreadsheet.</p>
            <p>To save this draft copy permanently, either use the <em>Allocate Patronage</em>
            tool to set distribution amounts for each member <strong>or</strong>
            use <em>Upload rebates</em> to import a spreadsheet that has distribution
            amounts. This should probably be the data exported from <em>Report of loaded info</em>
            with additional columns for distributions.</p>
            <p>Add Omitted Member creates a patronage record for a member who was not
            included in the original patronage allocation.</p>
            <p>Print Checks does exactly what it says.</p>
            <p>Upload Check Numbers takes a spreadsheet of check numbers provided by the bank
            and marks those patronage checks as cashed.</p>';
    }

    public function get_view()
    {
        return '
            <ul>
            <li><a href="CreatePatronageSnapshot.php">Create working table</a></li>
            <li><a href="PatronageGrossPurchases.php">Calculate gross purchases</a></li>
            <li><a href="PatronageCalcRewards.php">Calculate rewards</a></li>
            <li><a href="PatronageCalcNet.php">Update net purchases</a></li>
            <li><a href="DraftPatronageReport.php">Report of loaded info</a></li>
            <li><a href="AllocatePatronagePage.php">Allocate Patronage</a></li>
            <li><a href="AddPatronageEntryPage.php">Add Omitted Member</a></li>
            <li><a href="PatronageUploadPage.php">Upload rebates</a></li>
            <li><a href="PatronageChecks.php">Print Checks</a></li>
            <li><a href="PatronageCheckNumbersUploadPage.php">Upload Check Numbers</a></li>
            </ul>';
    }
}

FannieDispatch::conditionalExec();

