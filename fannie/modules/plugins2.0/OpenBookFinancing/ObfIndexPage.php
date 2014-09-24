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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class ObfIndexPage extends FannieRESTfulPage 
{
    protected $title = 'OBF: Menu';
    protected $header = 'OBF: Menu';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Menu] lists all the OBF pages.';

    public function get_view()
    {
        return '<ul>
            <li><a href="ObfWeeklyReport.php">View Weekly Reports</a></li>
            <li><a href="ObfQuarterEntryPage.php">Enter Quarterly Sales and Labor Plan</a></li>
            <li><a href="ObfWeekEntryPage.php">Enter Weekly Labor and Forecast Data</a></li>
            <li><a href="ObfCategoriesPage.php">Manage OBF Categories</a></li>
            <li><a href="ObfMappingPage.php">Map OBF Categories to POS</a></li>
            </ul>';
    }
}

FannieDispatch::conditionalExec();

