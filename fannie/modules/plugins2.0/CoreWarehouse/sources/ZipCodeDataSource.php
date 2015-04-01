<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

namespace COREPOS\Fannie\Plugin\CoreWarehouse\sources {

use COREPOS\Fannie\Plugin\CoreWarehouse;

/**
  @class CwReportDataSource
  Base class for extracting transaction data
  from Core Warehouse Plugin tables and feeding
  that data back into default reports
*/
class ZipCodeDataSource extends CoreWarehouse\CwReportDataSource
{
    protected $valid_reports = array('ZipCodeReport');

    /**
      Fetch data for the specified report
      @param [string] $report_class_name name of report
      @param [FannieConfig] $config current configuration
      @param [SQLManager] $connection database connection
      @return [array] report records or [boolean] false
        if this source cannot handle the request
    */
    public function fetchReportData($report_class_name, \FannieConfig $config, \SQLManager $connection)
    {
        $date1 = \FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = \FormLib::get_form_value('date2',date('Y-m-d'));
        $type = \FormLib::get_form_value('report-basis','Purchases');
        $exclude = \FormLib::get_form_value('excludes','');
        if ($type == 'Join Date') {
            return false;
        }

        $ex = preg_split('/\D+/',$exclude, 0, PREG_SPLIT_NO_EMPTY);
        $exCondition = '';
        $exArgs = array();
        foreach ($ex as $num) {
            $exCondition .= '?,';
            $exArgs[] = $num;
        }
        $exCondition = substr($exCondition, 0, strlen($exCondition)-1);

        $originalDB = $connection->defaultDatabase();
        $plugin_settings = $config->get('PLUGIN_SETTINGS');
        $connection->selectDB($plugin_settings['WarehouseDatabase']);
        $query = "
            SELECT 
                CASE WHEN m.zip='' THEN 'none' ELSE m.zip END as zipcode,
                COUNT(*) as num_trans, 
                SUM(total) as spending,
                COUNT(DISTINCT s.card_no) as uniques
            FROM sumMemSalesByDay AS s 
                INNER JOIN " . $config->get('OP_DB') . $connection->sep()."meminfo AS m ON s.card_no=m.card_no 
            WHERE ";
        if (!empty($exArgs)) {
            $query .= "s.card_no NOT IN ($exCondition) AND ";
        }
        $query .= "s.date_id BETWEEN ? AND ?
            GROUP BY zipcode
            ORDER BY SUM(total) DESC";
        $exArgs[] = $this->dateToID($date1);
        $exArgs[] = $this->dateToID($date2);
        $prep = $connection->prepare($query);
        $result = $connection->execute($prep, $exArgs);
        while ($row = $connection->fetchRow($result)) {
            $record = array($row['zipcode'],$row['num_trans'],$row['uniques'],$row['spending']);
            $data[] = $record;
        }

        $connection->setDefaultDB($originalDB);

        return $data;
    }

    /**
      Get list of additional fields, if any,
      that can be used with this data source and
      the specified report
      @param [string] $report_class_name name of report
      @return [array] of CwReportField objects
    */
    public function additionalFields($report_class_name)
    {
        $field = new CoreWarehouse\CwReportField();
        $field->name = 'report-basis';
        $field->label = 'Based on';
        $field->type = CoreWarehouse\CwReportField::FIELD_TYPE_SELECT;
        $field->options = array('Join Date', 'Purchases');

        return array($field);
    }
}

}

