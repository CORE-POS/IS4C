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
  @class DLogBigModel
*/
class DLogBigModel extends DLogModel
{

    protected $name = "dlogBig";
    protected $preferred_db = 'arch';

    public function create()
    {
        ob_start();
        $this->normalizeLog($this->name, 'bigArchive', BasicModel::NORMALIZE_MODE_APPLY);
        ob_end_clean();

        if ($this->connection->tableExists($this->name)) {
            return true;
        } else {
            return false;
        }
    }

    public function doc()
    {
        return '
Use:
Same as other dlog views (see DLogModel) but over bigArchive.
            ';
    }
}

