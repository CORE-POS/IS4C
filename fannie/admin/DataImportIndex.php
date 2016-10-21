<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class DataImportIndex extends FannieRESTfulPage
{
    protected $header = 'Fannie :: Data Import Tools';
    protected $title = 'Fannie :: Data Import Tools';
    public $description = '[Data Import Tools] is a landing page listing all available import options.';
    public $page_set = 'Import Tools';
    public $themed = true;

    public function get_view()
    {
        return '<ul>
            <li>' . _('Item Related') . '
                <ul>
                    <li><a href="../item/import/ProductImportPage.php">' . _('Products') . '</a></li>
                    <li><a href="../item/import/DepartmentImportPage.php">' . _('Departments') . '</a></li>
                    <li><a href="../item/import/SubDeptImportPage.php">' . _('Sub Departments') . '</a></li>
                </ul>
            </li>
            <li>' . _('Member Related') . '
                <ul>
                    <li><a href="../mem/import/MemNameNumImportPage.php">' . _('Names & Numbers')  . '</a></li>
                    <li><a href="../mem/import/MemContactImportPage.php">' . _('Contact Information') . '</a></li>
                    <li><a href="../mem/import/EquityHistoryImportPage.php">' . _('Existing Equity') . '</a></li>
                </ul>
            </li>
        </ul>';
    }

    public function helpContent()
    {
        return _('<p>
            These data import tools can load different kinds
            of data from spreadsheets (generally CSVs). The tools
            are intended for initializing the system as opposed to
            for ongoing maintenance.
            </p>');
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }

}

FannieDispatch::conditionalExec();

