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
  @class LikeCodesModel
*/
class LikeCodesModel extends BasicModel
{

    protected $name = "likeCodes";
    protected $preferred_db = 'op';

    protected $columns = array(
    'likeCode' => array('type'=>'INT', 'primary_key'=>true),
    'likeCodeDesc' => array('type'=>'VARCHAR(50)'),
    );

    public function toOptions($selected=0, $id_as_label=false)
    {
        $res = $this->connection->query('SELECT likeCode, likeCodeDesc FROM likeCodes ORDER BY likeCode');
        $ret = '';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<option %s value="%d">%d %s</option>',
                    ($selected === $row['likeCode'] ? 'selected' : ''),
                    $row['likeCode'], $row['likeCode'], $row['likeCodeDesc']);
        }

        return $ret;
    }

    public function doc()
    {
        return '
Use:
Like Codes group sets of items that will always
have the same price. It\'s mostly used for produce,
but could be applied to product lines, too
(e.g., all Clif bars)

The actual likeCode => upc mapping is in upcLike
        ';
    }
}

