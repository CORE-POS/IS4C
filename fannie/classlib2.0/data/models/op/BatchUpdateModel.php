<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../../FannieDB.php');
}
if (!class_exists('BarcodeLib')) {
    include(dirname(__FILE__).'/../../../lib/BarcodeLib.php');
}

class BatchUpdateModel extends BasicModel
{
    protected $name = 'batchUpdate';

    protected $preferred_db = 'op';

    protected $columns = array(
    'batchUpdateID' => array('type'=>'BIGINT UNSIGNED', 'primary_key'=>true, 'increment'=>true),
    'updateType' => array('type'=>'VARCHAR(20)'),
    'upc' => array('type'=>'VARCHAR(13)'),
    'specialPrice' => array('type'=>'MONEY'),
    'batchID' => array('type'=>'INT'),
    'batchType' => array('type'=>'SMALLINT'),
    'modified' => array('type'=>'DATETIME'),
    'user' => array('type'=>'VARCHAR(20)'),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    'owner'=> array('type'=>'VARCHAR(20)'),
    'batchName'=> array('type'=>'VARCHAR(30)'),
    'quantity'=> array('type'=>'SMALLINT(6)'),
    );

    const UPDATE_CREATE = 'BATCH CREATED';
    const UPDATE_DELETE = 'BATCH DELETED';
    const UPDATE_FORCED = 'BATCH STARTED';
    const UPDATE_STOPPED = 'BATCH STOPPED';
    const UPDATE_EDIT = 'BATCH EDITED';
    const UPDATE_PRICE_EDIT = 'ITEM EDITED';
    const UPDATE_REMOVED = 'ITEM REMOVED';
    const UPDATE_ADDED = 'ITEM ADDED';

    /**
        Requires tables batches & batchUpdate exist.
    */

    public function logUpdate($type='UNKNOWN', $user=false)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (!$user) {
            $user = FannieAuth::getUID(FannieAuth::checkLogin());
        }

        /**
            if a UPC was passed to obj, use batchList and batches,
                if only BATCHID was passed, use only batches.
        */
        if ($this->upc()) {
            $batchList = new BatchListModel($dbc);
            $batchList->upc($this->upc());
            $batchList->batchID($this->batchID());
            $exists = $batchList->load();
            $logType = 'ITEM';
        } else {
            $batch = new BatchesModel($dbc);
            $batch->batchID($this->batchID());
            $exists = $batch->load();
            $logType = 'BATCH';

        }
        if (!$exists) {
            return false;
        }

        /**
            2 diff. kind of entries (item,batch)
        */
        if ($logType == 'BATCH') {
            $this->updateType($type);
            $this->user($user);
            $this->startDate($batch->startDate());
            $this->endDate($batch->endDate());
            $this->batchType($batch->batchType());
            $this->batchName($batch->batchName());
            $this->owner($batch->owner());
            $this->modified(date('Y-m-d H:i:s'));
            $saved = $this->save();
        } else {
            $this->updateType($type);
            $this->user($user);
            $this->upc($this->upc());
            $this->specialPrice($batchList->salePrice());
            $this->quantity($batchList->quantity());
            $this->batchID($batchList->batchID());
            $this->modified(date('Y-m-d H:i:s'));
            $saved = $this->save();
        }
        if ($saved === false) {
            $json['error'] = 1;
            $json['msg'] = 'Error logging batch history ' . $this->batchID();
        }

        return true;
    }

    public function doc()
    {
        return '
This table keeps a record of all changes
made to sales batches.
        ';
    }
}

