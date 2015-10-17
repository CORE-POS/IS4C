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
  @class BatchMergeTableModel
*/
class BatchMergeTableModel extends BasicModel
{

    protected $name = "batchMergeTable";
    protected $preferred_db = 'op';

    protected $columns = array(
        'startDate' => array('type'=>'DATETIME'),
        'endDate' => array('type'=>'DATETIME'),
        'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
        'description' => array('type'=>'VARCHAR(30)'),
        'batchID' => array('type'=>'INT', 'primary_key'=>true)
    );

    public function doc()
    {
        return '
Use:
This is a speedup table for reports. It\'s
populated (daily) by a scheduled task.
It unrolls likecoded batchList
entries back into upcs which simplifies subsequent
queries. At WFC batchList is also a bit large
and slow to join against directly.
        ';
    }
}

