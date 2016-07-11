<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ShrinkIndex extends FannieRESTfulPage
{
    protected $header = 'Shrink Tools';
    protected $title = 'Shrink Tools';
    public $themed = true;
    public $description = '[Shrink Menu] is a collection of shrink related tools.';

    public function get_view()
    {
        return '
            <ul>
                <li><a href="ShrinkTool.php">Enter Shrink</a></li>
                <li><a href="ShrinkReasonEditor.php">Edit Shrink Reasons</a></li>
                <li><a href="../../reports/DDD/">Report Shrink Items</a></li>
            </ul>';
    }

    public function helpContent()
    {
        return '<p>
            Shrink is for entering and reporting on loss. Each entry is
            tagged with a reason as well as a loss/contribute setting. In
            some jurisdictions, <em>contributing</em> items to a non-profit
            may result in a tax credit.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

