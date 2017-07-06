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

namespace COREPOS\Fannie\Plugin\AWS;
use COREPOS\Fannie\API\FanniePlugin;

/**
*/
class AWS extends FanniePlugin 
{
    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'AwsApiKey' => array('default'=>'','label'=>'API Key',
            'description'=>'Credentials to connect to AWS'),
    'AwsApiSecret' => array('default'=>'','label'=>'API Secret',
            'description'=>'Credentials to connect to AWS'),
    'AwsRegion' => array('default'=>'us-east-1','label'=>'Default Region',
            'description'=>'Valid AWS availability zone'),
    );

    public $plugin_description = 'Integrate Amazon Web Services technologies';
}

