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

if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/classlib2.0/FannieAPI.php');
}

/**
*/
class CpwPriceImport extends \COREPOS\Fannie\API\FanniePlugin 
{
    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'CpwPriceURL' => array('default'=>'https://resources.cpw.coop/pricelist/Pricelist-c.csv','label'=>'URL',
            'description'=>'URL to download pricing file'),
    'CpwCostUpdates' => array('default'=>'0','label'=>'Update Costs',
            'options' => array('Product & Catalog Costs' => 1, 'Catalog Costs' => 0),
            'description'=>'Which costs to update'),
    );

    public $plugin_description = 'Plugin for managing data warehouse. No end-user facing
        functionality here. The plugin is just a set of tools for creating summary
        tables and loading historical transaction data into said tables. Reports may
        utilize the warehouse when available. In some cases it may just mean simpler
        queries; in others there may be a performance benefit to querying
        pre-aggregated data.';
}

