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
  @class ShelfTagQueuesModel
*/
class ShelfTagQueuesModel extends BasicModel
{

    protected $name = "ShelfTagQueues";
    protected $preferred_db = 'op';

    protected $columns = array(
    'shelfTagQueueID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    );

    public function initQueues()
    {
        $supers = $this->connection->query('
            SELECT superID,
                super_name
            FROM MasterSuperDepts AS m
            WHERE superID > 0
            GROUP BY superID,
                super_name
        ');
        while ($w = $this->connection->fetchRow($supers)) {
            $this->shelfTagQueueID($w['superID']);
            $this->description($w['super_name']);
            $this->save();
        }
        $this->reset();
    }

    public function toOptions($selected=0, $id_as_label=false)
    {
        $queues = $this->find('shelfTagQueueID');
        if (count($queues) == 0) {
            $this->initQueues();
            $queues = $this->find('shelfTagQueueID');
        }
        $ret = '<option value="0">Default Queue</option>';
        foreach ($queues as $q) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($selected == $q->shelfTagQueueID() ? 'selected' : ''),
                $q->shelfTagQueueID(), $q->description());
        }

        return $ret;
    }

}

