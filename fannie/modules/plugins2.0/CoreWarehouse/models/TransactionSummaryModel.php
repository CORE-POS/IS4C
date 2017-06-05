<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

if (!class_exists('CoreWarehouseModel')) {
    include_once(dirname(__FILE__).'/CoreWarehouseModel.php');
}

class TransactionSummaryModel extends CoreWarehouseModel {

    protected $name = 'transactionSummary';
    protected $preferred_db = 'plugin:WarehouseDatabase';
    
    protected $columns = array(
    'date_id' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'trans_num' => array('type'=>'VARCHAR(25)','primary_key'=>True,'default'=>''),
    'register_no' => array('type'=>'SMALLINT'),
    'emp_no' => array('type'=>'SMALLINT'),
    'tenderTotal' => array('type'=>'MONEY'),
    'taxTotal' => array('type'=>'MONEY'),
    'discountTotal' => array('type'=>'MONEY'),
    'percentDiscount' => array('type'=>'DOUBLE'),
    'retailTotal' => array('type'=>'MONEY'),
    'retailQty' => array('type'=>'DOUBLE'),
    'nonRetailTotal' => array('type'=>'MONEY'),
    'nonRetailQty' => array('type'=>'DOUBLE'),
    'ringCount' => array('type'=>'INT'),
    'start_time' => array('type'=>'DATETIME'),
    'end_time' => array('type'=>'DATETIME'),
    'duration' => array('type'=>'INT'),
    'card_no' => array('type'=>'INT','index'=>True),
    'memType' => array('type'=>'SMALLINT','index'=>True)
    );

    public function refresh_data($trans_db, $month, $year, $day=False){
        global $FANNIE_OP_DB;
        list($start_id, $start_date, $end_id, $end_date) = $this->dates($month, $year, $day);

        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $sql = FannieDB::get($settings['WarehouseDatabase']);

        $target_table = DTransactionsModel::selectDlog($start_date, $end_date);

        $this->clearDates($sql, $start_id, $end_id);

        // 5Jul2013 - percentDiscount not currently exposed via dlog
        $sql = "INSERT INTO ".$this->name." 
            SELECT DATE_FORMAT(tdate, '%Y%m%d') as date_id,
            trans_num,
            register_no,
            emp_no,
            SUM(CASE WHEN trans_type='T' THEN total ELSE 0 END) as tenderTotal,
            SUM(CASE WHEN upc='TAX' THEN total ELSE 0 END) as taxTotal,
            SUM(CASE WHEN upc='DISCOUNT' THEN total ELSE 0 END) as discountTotal,
            0 as percentDiscount,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID <> 0 THEN total else 0 END) as retailTotal,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID <> 0 AND trans_status='M' THEN itemQtty 
                WHEN trans_type IN ('I','D') AND m.superID <> 0 AND unitPrice=0.01 THEN 1 
                WHEN trans_type IN ('I','D') AND m.superID <> 0 AND trans_status<>'M'
                AND unitPrice<>0.01 THEN quantity ELSE 0 END) as retailQty,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID = 0 THEN total else 0 END) as retailTotal,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID = 0 AND trans_status='M' THEN itemQtty 
                WHEN trans_type IN ('I','D') AND m.superID = 0 AND unitPrice=0.01 THEN 1 
                WHEN trans_type IN ('I','D') AND m.superID = 0 AND trans_status<>'M'
                AND unitPrice<>0.01 THEN quantity ELSE 0 END) as retailQty,
            SUM(CASE WHEN trans_type in ('I','D') THEN 1 ELSE 0 END) as ringCount,
            MIN(tdate) as start_time,
            MAX(tdate) as end_time, "
            .$this->connection->seconddiff('MIN(tdate)','MAX(tdate)')." as duration,
            MAX(card_no) as card_no,
            MAX(memType) as memType
            FROM $target_table as t LEFT JOIN "
            .$FANNIE_OP_DB.$this->connection->sep()."MasterSuperDepts as m
            ON t.department=m.dept_ID
            WHERE tdate BETWEEN ? AND ? AND upc <> 'RRR'
            GROUP BY DATE_FORMAT(tdate,'%Y%m%d'), trans_num";
        $prep = $this->connection->prepare($sql);
        $result = $this->connection->execute($prep, array($start_date.' 00:00:00',$end_date.' 23:59:59'));
    }
}

