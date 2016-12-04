<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class AdminIndexPage extends FanniePage
{
    protected $header = 'Fannie :: Admin Tools';
    protected $title = 'Fannie :: Admin Tools';
    public $description = '[Admin Tools] is a landing page listing various administrative tools.';
    public $has_unit_tests = true;

    public function body_content()
    {
        return $this->twig->loadTemplate('admin.twig.html')->render(array());
        return <<<HTML
<a href="../mem/index.php"><font size=4>Membership Management</font></a></br>
Utilities for managing membership database
</br></br>

<a href="Cashiers/"><font size=4>Cashier Management</font></a></br>
Utilities for managing cashier database and cashier reports
</br></br>

<a href="Tenders/"><font size=4>Tenders</font></a></br>
 Maintain the tenders (types of payment) list
</br></br>

<a href="../ordering/"><font size=4>Special Orders</font></a></br>
Manage special orders
</br></br>

<a href="labels"><font size=4>Generate Shelftags</font></a><br>
    Create and print shelftag batches
</br></br>

<a href="LookupReceipt"><font size=4>Transaction Look-up</font></a></br>
    Search transaction history and reprint receipts
HTML;
    }

    public function helpContent()
    {
        return '<p>' . _('
            This landing page provides a handful of links to administrative tools.
            Each is already described.
            ') . '</p>';
    }

    /**
      Simple page. Just call body method for the sake of code coverage.
      phpunit will blow up if a notice or warning occurs
    */
    public function unitTest($phpunit)
    {
        $body = $this->bodyContent();
        $phpunit->assertNotEquals(0, strlen($body), 'Page is empty');
    }
}

FannieDispatch::conditionalExec();

