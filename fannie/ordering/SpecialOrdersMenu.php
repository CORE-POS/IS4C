<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class SpecialOrdersMenu extends FanniePage
{
    protected $title = "Fannie :: Special Orders";
    protected $header = "Special Orders";
    protected $must_authenticate = true;
    public $description = '[Special Order Menu] links to other special order related pages';
    public $page_set = 'Special Orders';

    public function body_content()
    {
        $myID = Store::getIdByIp();
        $edit = FannieAuth::validateUserQuiet('ordering_edit');
        $view = ($myID == 2 || $edit || $this->config->get('SO_UI') === 'bootstrap') ? 'OrderViewPage.php' : 'view.php';
        $list = ($myID == 2 || $edit || $this->config->get('SO_UI') === 'bootstrap') ? 'NewSpecialOrdersPage.php' : 'clearinghouse.php';
        return <<<HTML
<ul>
<li><a href="{$view}">Create Order</a></li>
<li>Review Orders
    <ul>
    <li><a href="{$list}">Active Orders</a></li>
    <li><a href="OldSpecialOrdersPage.php">Old Orders</a></li>
    </ul>
</li>
<li><a href="SoReceivingReport.php">Receiving Report</a></li>
</ul>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

