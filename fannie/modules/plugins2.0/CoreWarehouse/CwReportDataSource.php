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

namespace COREPOS\Fannie\Plugin\CoreWarehouse {

/**
  @class CwReportDataSource
  Base class for extracting transaction data
  from Core Warehouse Plugin tables and feeding
  that data back into default reports
*/
class CwReportDataSource
{
    protected $valid_reports = array();

    /**
      Find a data source class for the existing name
      @param [string] $report_class_name name of report
      @return [CwReportDataSource] data source object 
        or [boolean] false if none exists
    */
    public static function getDataSource($report_class_name)
    {
        $sources = \FannieAPI::listModules('\COREPOS\Fannie\Plugin\CoreWarehouse\CwReportDataSource');
        foreach ($sources as $source_class) {
            $obj = new $source_class();
            if ($obj->sourceForReport($report_class_name)) {
                return $obj;
            }
        }

        return false;
    }

    /**
      Fetch data for the specified report
      @param [string] $report_class_name name of report
      @param [FannieConfig] $config current configuration
      @param [SQLManager] $connection database connection
      @return [array] report records
    */
    public function fetchReportData($report_class_name, \FannieConfig $config, \SQLManager $connection)
    {
        return array();
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
        return array();
    }

    /**
      Can this source be used with the given report
      @param [string] $report_class_name name of report
      @return [boolean]
    */
    protected function sourceForReport($report_class_name)
    {
        return in_array($report_class_name, $this->valid_reports);
    }

    /**
      Convert a date string to a warehouse format date ID
      Ex: 2000-10-31 => 20001031
      @param $date [string] date representation
      @return [integer] equivalent date ID
    */
    protected function dateToID($date)
    {
        if (strtotime($date)) {
            return (int)(date('Ymd', strtotime($date)));
        } else {
            return false;
        }
    }
}

}

