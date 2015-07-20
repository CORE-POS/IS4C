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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI.php')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ShelfTagQueuesPage extends \COREPOS\Fannie\API\FannieCRUDPage 
{
    protected $model_name = 'ShelfTagQueuesModel';
    protected $header = 'Shelf Tag Queues';
    protected $title = 'Shelf Tag Queues';
    public $description = '[Shelf Tag Queues] are named slots where new tags
    can be queued before printing.';

    public function helpContent()
    {
        return '<p>
            Add, delete, or rename shelf tag queues. Use the red trash
            icon to delete. The Add button creates a new entry named "NEW".
            Edit existing entries and click save to rename queues.
            </p>';
    }
}

FannieDispatch::conditionalExec();
