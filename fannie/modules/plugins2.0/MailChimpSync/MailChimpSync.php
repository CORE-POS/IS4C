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

global $FANNIE_ROOT;
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

/**
*/
class MailChimpSync extends \COREPOS\Fannie\API\FanniePlugin 
{

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'MailChimpApiKey' => array('default'=>'','label'=>'API Key',
            'description'=>'API key for access to MailChimp. Found
            under account settings.'),
    'MailChimpListID' => array('default'=>'','label'=>'List ID',
            'description'=>'List ID for the MailChimp list to sync
            with. Found under List Settings Name & Defaults.'),
    'MailChimpMergeVarField' => array('default' => 0, 'label'=>'Owner Number field exists',
            'options'=>array('Yes'=>1, 'No'=>0),
            'description'=>'Set this to Yes once the field has been created. This plugin
            will create the field automatically, but polling on every synchronization
            to check whether it exists is against MailChimp\'s best practices.'),
    );

    public $plugin_description = 'Plugin for posting reversal transaction. A reversal is
            essentially the original transaction times minus one. Used in correcting
            and cleaning up after mistakes.';
}

