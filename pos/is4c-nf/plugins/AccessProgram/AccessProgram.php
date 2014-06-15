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

class AccessProgram extends Plugin {

	public $plugin_settings = array(
        'AccessQuickMenu' => array('default'=>'', 'label'=>'Quick Menu #',
            'description' => 'Save list of applicable programs in a
            quick menu using the QuickMenus plugin'),
        'ServerOpDB' => array('default'=>'core_op', 'label'=>'Contact Info DB',
            'description' => 'Name of server-side DB with contact information'),
	);

	public $plugin_description = 'WFC plugin for tracking member with an access
(low income) discount';

}
