<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class GumSettingsModel

  Catch-all key-value storage for settings.
  This plugin may wind up with a lot of settings
  and keeping them separate from Fannie's
  general settings may be a bit easier.
*/
class GumSettingsModel extends BasicModel
{

    protected $name = "GumSettings";
    protected $preferred_db = 'plugin:GiveUsMoneyDB';

    protected $columns = array(
    'gumSettingID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'key' => array('type'=>'VARCHAR(50)', 'index'=>true),
    'value' => array('type'=>'VARCHAR(50)'),
    );

    protected $unique = array('key');
}

