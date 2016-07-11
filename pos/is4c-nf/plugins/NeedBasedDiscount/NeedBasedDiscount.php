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

use COREPOS\pos\plugins\Plugin;

class NeedBasedDiscount extends Plugin {

    public $plugin_settings = array(
       'needBasedPercent' => array(
        'default' => '',
        'label' => 'Percentage Discount',
        'description' => 'Enter the percentage discount of your need-based discount program
                        e.g. for a 5% discount enter 0.05'
        ),
       'needBasedName' => array(
        'default' => '',
        'label' => 'Program Name',
        'description' => 'Enter the name of your own need-based discount program')
    );

    public $plugin_description = 'Apply a flat percentage discount to all Members enrolled<br>
                        in your Need-Based discount program.  Sometimes called Food For All or FLOWER.<br>
                        Trigger with "FF"';

    function plugin_transaction_reset()
    {
        CoreLocal::set('NeedDiscountFlag', 0);
    }
}

