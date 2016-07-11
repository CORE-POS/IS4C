<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op, Duluth, MN

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

namespace COREPOS\Fannie\API\monitor;

/**
  @class Monitor
  API class for monitoring system status and
  building a dashboard.
*/
class Monitor
{
    protected $config;
    /**
      @param $config [FannieConfig] instance of configuration
    */
    public function __construct($config)
    {
        $this->config = $config;
    }
    /**
      Assess current status of system(s) being
      monitored by this class.
      @return [string] JSON-encoded string representing status
    */
    public function check()
    {
        return json_encode(array());
    }

    /**
      Determine whether system status is critical. Returning
      true tells the monitoring system to notify someone
      as soon as possible.
      @param $json [string] JSON-encoded string representing current 
        system status. This is the value returned by the most recent
        call to check().
      @return [boolean]
        true => critical
        false => not critical
    */
    public function escalate($json)
    {
        return false;
    }

    /**
      Convert system status to HTML so it can be displayed to
      a user. These return values are used to build web-based
      dashboards.
      @param $json [string] JSON-encoded string representing current 
        system status. This is the value returned by the most recent
        call to check().
      @return [string] HTML representation of status
    */
    public function display($json)
    {
        return '';
    }
}

