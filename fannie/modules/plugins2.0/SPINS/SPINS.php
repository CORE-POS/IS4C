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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class SPINS extends FanniePlugin 
{
    public $plugin_settings = array(
    'SpinsFtpUser' => array('default'=>'', 'label'=>'FTP Username',
            'description'=>'ftp.spins.com credentials'), 
    'SpinsFtpPw' => array('default'=>'', 'label'=>'FTP Password',
            'description'=>'ftp.spins.com credentials'), 
    'SpinsOffset' => array('default'=>0, 'label'=>'Week Offset',
            'description'=>'SPINS often uses non-standard week numbering. The offset
            should be the difference between an ISO week number and the SPINS week
            number for a given date.'),
    );

    public $plugin_description = 'Plugin for submitting SPINS data';
}

