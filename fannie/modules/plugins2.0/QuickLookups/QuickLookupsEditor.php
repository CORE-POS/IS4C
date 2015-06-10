<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class QuickLookupsEditor extends \COREPOS\Fannie\API\FannieCRUDPage 
{
    protected $model_name = 'QuickLookupsModel';
    protected $header = 'Quick Key/Menu Editor';
    protected $title = 'Quick Key/Menu Editor';
    public $description = '[Quick Key/Menu Editor] manages dynamic menus for lane QuickMenu and QuickKey plugins.';
    protected $display_sorting = array('lookupSet', 'sequence');

    protected $column_name_map = array(
    'lookupSet' => 'Menu Number', 
    'sequence' => 'Position in Menu',
    );

    public function helpContent()
    {
        return '<p>
            Manage entries in QuickMenus or QuickKeys. These are plugins for
            putting a list of commands in a menu or series of touchable/clickable
            buttons on the lane.
            </p>
            <p>
            The Menu Number controls which entries are grouped together. For example,
            when the cashier enters "QM1" or "QK1" all the entries in Menu Number one
            are displayed. Position in Menu controls the order of entries within the
            menu. Lowered numbered positions are displayed first and higher numbered
            positions are displayed last.
            </p>
            <p>
            The Label is the text displayed on the cashier\'s screen; the Action is the
            POS command triggered when the cashier chooses that particular entry.
            </p>
            ';
    }
}

FannieDispatch::conditionalExec();

