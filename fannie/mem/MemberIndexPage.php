<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemberIndexPage extends FanniePage {

    protected $title = "Fannie :: Member Tools";
    protected $header = "Member Tools";

    public $description = '[Member Menu] lists member related pages.';
    public $themed = true;

    function body_content(){
        ob_start();
        ?>
        <ul>
        <li><a href="MemberSearchPage.php">View/Edit Members</a></li>
        <li><a href="MemberTypeEditor.php">Manage Member Types</a></li>
        <li><a href="NewMemberTool.php">Create New Members</a></li>
        <li><a href="numbers/index.php">Print Member Stickers</a></li>
        <li><a href="MemCorrectionIndex.php ">Equity, AR, &amp; Patronage Corrections</a></li>
        <li><a href="import/">Import Data</a></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            A collection of membership tools.
            </p>
            <p>
            View/edit is the primary tool for managing individual
            memberships. Members can be categorized into Member Types.
            Types can even include customers who are not members
            of the co-op. Creating new members is oriented towards
            pre-allocating sets of memberships so they are available
            for purchase and immediate use. Stickers is perhaps
            WFC-only. Corrections deal with adjusting activity on
            and between memberships. Import data is for loading initial
            data into CORE.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

