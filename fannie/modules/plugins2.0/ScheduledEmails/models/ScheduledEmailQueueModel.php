<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

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

/**
  @class ScheduledEmailQueueModel
*/
class ScheduledEmailQueueModel extends BasicModel
{
    protected $name = "ScheduledEmailQueue";
    protected $preferred_db = 'plugin:ScheduledEmailDB';

    protected $columns = array(
    'scheduledEmailQueueID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'scheduledEmailTemplateID' => array('type'=>'INT'),
    'cardNo' => array('type'=>'INT'),
    'sendDate' => array('type'=>'DATETIME'),
    'templateData' => array('type'=>'TEXT'),
    'sent' => array('type'=>'TINYINT', 'default'=>0),
    'sentDate' => array('type'=>'DATETIME'),
    'sentToEmail' => array('type'=>'VARCHAR(100)'),
    );
}

