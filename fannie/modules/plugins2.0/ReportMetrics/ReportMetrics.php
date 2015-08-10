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
class ReportMetrics extends \COREPOS\Fannie\API\FanniePlugin 
{
    public $plugin_settings = array(
    'ReportMetricsEmail' => array('default'=>'', 'label'=>'Email Address',
            'description'=>'Send status info to address(es)'), 
    'ReportMetricsSmtpHost' => array('default'=>'127.0.0.1', 'label'=>'SMTP Host',
            'description'=>''),
    'ReportMetricsSmtpPort' => array('default'=>'25', 'label'=>'SMTP Port',
            'description'=>''),
    'ReportMetricsSmtpEnc' => array('default'=>'', 'label'=>'SMTP Encryption',
            'description'=>'', 
            'options'=> array('none'=>'', 'SSL'=>'ssl', 'TLS'=>'tls')),
    'ReportMetricsSmtpUser' => array('default'=>'', 'label'=>'SMTP Username',
            'description'=>'(can be blank if authentication is not required)'),
    'ReportMetricsSmtpPass' => array('default'=>'', 'label'=>'SMTP Password',
            'description'=>'(can be blank if authentication is not required)'),
    );

    public $plugin_description = 'Plugin for submitting installation status.
    Requires PHPMailer if using remote and/or authenticated SMTP.';
}

