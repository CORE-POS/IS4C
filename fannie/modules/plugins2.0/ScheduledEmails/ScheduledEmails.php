<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

/**
*/
class ScheduledEmails extends \COREPOS\Fannie\API\FanniePlugin 
{

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'ScheduledEmailDB' => array('default'=>'core_schedule_email','label'=>'Database Name',
            'description'=>'Database to store email templates and schedules'),
    'ScheduledEmailFrom' => array('default'=>'no-reply@localhost','label'=>'Sender Address',
            'description'=>'The "From" address for scheduled emails.'),
    'ScheduledEmailFromName' => array('default'=>'CORE POS','label'=>'Sender Name',
            'description'=>'A name to associate with the "From" address'),
    'ScheduledEmailReplyTo' => array('default'=>'no-reply@localhost','label'=>'Reply-To Address',
            'description'=>'The "Reply" address for scheduled emails.'),
    );

    public $plugin_description = 'Plugin for templating and sending emails to
            members on a scheduled basis.';
}

