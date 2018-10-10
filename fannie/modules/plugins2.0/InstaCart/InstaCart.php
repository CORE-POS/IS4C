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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

/**
*/
class InstaCart extends \COREPOS\Fannie\API\FanniePlugin 
{
    public $plugin_settings = array(
    'InstaCartFtpUser' => array('default'=>'', 'label'=>'FTP Username',
            'description'=>'InstaCart FTP credentials'), 
    'InstaCartFtpPw' => array('default'=>'', 'label'=>'FTP Password',
            'description'=>'InstaCart credentials'), 
    'InstaCartDB' => array('default'=>'InstaCart', 'label'=>'InstaCart Database',
            'description'=>'Database for InstaCart-specific information'), 
    'InstaCartMode' => array('default'=>1, 'label'=>'InstaCart Mode',
            'description'=>'Configuration mechanism for sending items',
            'options'=>array('Include'=>1, 'Exclude'=>0)),
    'InstaSalePrices' => array('default'=>1, 'label'=>'Sale Prices',
            'description'=>'Whether or not to pass along promo sale pricing',
            'options'=>array('Yes'=>1, 'No'=>0)),
    'InstaCartSalesCutoff' => array('default'=>0, 'label'=>'Recent Sales Cutoff',
            'description'=>'Exclude items that have not sold in past X days'), 
    'InstaCartNewCutoff' => array('default'=>0, 'label'=>'New Item Cutoff',
            'description'=>'Include items with no sales that were created in the last X days'),
    'InstaCartCompUser' => array('default'=>'', 'label'=>'Comp Username',
            'description'=>'InstaCart Comparison credentials'), 
    'InstaCartCompPw' => array('default'=>'', 'label'=>'Comp Password',
            'description'=>'InstaCart Comparison credentials'), 
    );

    public $plugin_description = 'Plugin for submitting InstaCart data. You may need
        to install flysystem/sftp via composer to actually transmit data.';
}

