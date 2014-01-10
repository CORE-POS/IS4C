<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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

class ReportModel extends BasicModel 
{

    protected $name = '';

    protected $tables = array();

    protected $where = '';
    protected $group = '';
    protected $having = '';
    protected $order = '';

    // not editable
    public function create(){}
    public function save(){}
    public function delete(){}
    public function pushToLanes(){}
    public function deleteFromLanes(){}

    public function find($sort='')
    {
        $sql = 'SELECT ';
        foreach($this->columns as $name => $definition) {
            $sql .= $definition.' as '.$this->connection->identifier_escape($name).',';
        }
        $sql = substr($sql,0,strlen($sql)-1);
        
        $sql .= ' FROM ';
        foreach($this->tables as $name => $join) {
            if (!empty($join)) {
                $sql .= ' '.$join['type'].' ';
            }
            $sql .= $name;
            if (!empty($join)) {
                $sql .= ' '.$join['on'].' ';
            }
        }
        if (!empty($this->where)) {
            $sql .= ' WHERE '.$this->where;
        }
        if (!empty($this->group_by)) {
            $sql .= ' GROUP BY '.$this->group_by;
        }
        if (!empty($this->having)) {
            $sql .= ' HAVING '.$this->having;
        }
        if (!empty($this->order_by)) {
            $sql .= ' ORDER BY '.$this->order_by;
        }
    }
}

