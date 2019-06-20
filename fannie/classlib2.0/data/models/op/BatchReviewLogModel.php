<?php

/*******************************************************************************

    Copyright 2019 Whole Foods Co-op

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
  @class BatchReviewLogModel
*/
class BatchReviewLogModel extends BasicModel
{
    protected $name = "batchReviewLog";
    protected $preferred_db = 'op';

    protected $columns = array(
    'bid' => array('type'=>'INT', 'primary_key'=>true),
    'vid' => array('type'=>'INT'),
    'printed' => array('type'=>'TINYINT', 'default'=>0),
    'user' => array('type'=>'VARCHAR(30)'),
    'created' => array('type'=>'DATETIME'),
    'forced' => array('type'=>'DATETIME'),
    'comments' => array('type'=>'TEXT', 'replaces'=>'notes'),
    );

    public function doc()
    {
        return '
Tracks information about price change batches. This assumes price
update cycles are happening on a per-vendor basis.

* bid is the batchID
* vid is te vendorID
* printed indicates whether signage has been prepared
* user lists the last user to update the record
* created is the date the batch was *added* to the review log
* forced is when the batch was forced
* notes is a spot for random comments
';
    }

}

