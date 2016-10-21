<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DepartmentIndexPage extends FanniePage 
{
    protected $title = "Fannie : Manage Departments";
    protected $header = "Manage Departments";

    protected $must_authenticate = true;
    protected $auth_classes = array('departments', 'admin');

    public $description = '[Department Menu] lists pages related to departments.';
    public $themed = true;
    
    function body_content(){
        ob_start();
        ?>
        <ul>
        <li> <a href="SuperDeptEditor.php">Super Departments</a></li>
        <li> <a href="DepartmentEditor.php">Departments</a></li>
        <li> <a href="SubDeptEditor.php">Sub Departments</a></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            CORE organizes POS products into a hierarchy.
            The top category is <strong>Super Departments</strong>.
            Each super department contains one or more
            <strong>Departments</strong>. A department may in
            turn contain one or more <strong>Sub Departments</strong>.
            </p>
            <p>
            Departments are the only tier that is strictly required,
            but many tools in CORE are designed to work best with
            a higher-level set of super departments.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

