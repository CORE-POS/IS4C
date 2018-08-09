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

use COREPOS\pos\plugins\Plugin;

class Inventory extends Plugin
{
    public $plugin_settings = array(
        'InventoryOpDB' => array(
            'label' => 'Server Operational DB',
            'description' => 'Name of the database w/ inventory data',
            'default'=> 'core_op',
        ),
        'InventoryIncludeSuspended' => array(
            'label' => 'Include Suspended Transactions',
            'description' => 'Include suspended transactions in inventory data',
            'default' => 0,
            'options' => array(
                'No' => 0,
                'Yes' => 1,
            ),
        ),
    );

    public $plugin_description = 'Plugin for inventory integration';
}

