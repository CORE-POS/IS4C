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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class WfcHtMenuPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('view_all_hours');

    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[Menu] for plugin pages.';

    protected $header = 'Menu';
    protected $title = 'Menu';

    public function body_content()
    {
        ob_start();
        ?>
<ul>
<li><a href=WfcHtListPage.php>View Employees</a></li>
<li><a href=WfcHtPayPeriodsPage.php>View Pay Periods</a></li>
<li><a href=reports/WfcHtReport.php>Hours worked report</a></li>
<br />
<li><a href=WfcHtUploadPage.php>Upload ADP Data</a></li>
<li><a href=WfcHtSalaryUploadPage.php>Update Salary PTO</a></li>
<br />
<li><a href=WfcHtSyncPage.php>Import New Employees</a></li>
</ul>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();
