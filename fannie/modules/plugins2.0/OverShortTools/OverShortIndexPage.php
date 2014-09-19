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
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class OverShortIndexPage extends FanniePage {

    protected $header = 'Over Short Tools';
    protected $title = 'Over Short Tools';
    protected $auth_classes = array('overshorts');
    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Menu] lists over/short pages.';

    function body_content(){
        ob_start();
        ?>
        <ul>
        <li><a href="OverShortDayPage.php" target="_WholeByCashier">Whole Day O/S</a>
            <?php
            echo FannieHelp::ToolTip('<ul><li>View POS tender totals for all cashiers
                        on a given day and enter actual counted totals.
                        <li>The tool will calculate how much an individual
                        cashier is over or short for each tender
                        <br />as well as how much the overall store is over
                        or short.
                        <li>Work can be saved and resumed later.
                        <li>The day can be marked "resolved".</ul>');
            ?>
        </li> 
        <li><a href="OverShortCashierPage.php" target="_SingleCashier">Single Cashier O/S</a>
            <?php
            echo FannieHelp::ToolTip('<ul><li>View POS tender totals for cashiers
                        and enter actual counted totals.
                        <li>The difference
                        between this and the "Whole Day O/S" tool is this
                        accepts individual check amounts. If you do not need
                        all the individual checks (typically used for generating
                        bank deposit paperwork) then the "Whole Day O/S" tool
                        is likely more effective. The two tools work with the
                        same data so you can mix and match if needed.</ul>');
            ?>
        </li>
        <li><a href="OverShortSafecountPage.php" target="_SafeCount">Safe Count</a>
            <?php
            echo FannieHelp::ToolTip('<br />Enter information about cash on hand to calculate
                        <ul><li>what should be sent to the bank,
                        <li>how much change to order
                        in various denominations, and
                        <li>what should remain afterwards.
                        </ul>
                        <b>Very WFC specific</b>');
            ?>
        </li> 
        <li><a href="OverShortDepositSlips.php" target="_DepositSlips">Deposit Slips</a>
            <?php
            echo FannieHelp::ToolTip('<br />Uses information from the Safe Count tool to generate
                        paperwork that goes with the bank deposit.
                        <br /><b>Very WFC specific</b>');
            ?>
        </li> 
        <!--
        <li><a href="OverShortDayTillPage.php" target="_WholeByTill">Whole Day O/S by Till</a>
            <?php
            echo FannieHelp::ToolTip('<ul><li>View POS tender totals for all tills
                        on a given day and enter actual counted totals.
                        <li>The tool will calculate how much an individual
                        tills is over or short for each tender
                        <br />as well as how much the overall store is over
                        or short.
                        <li>Work can be saved and resumed later.
                        <li>The day can be marked "resolved".</ul>');
            ?>
        </li> 
        -->
        </ul>

        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

