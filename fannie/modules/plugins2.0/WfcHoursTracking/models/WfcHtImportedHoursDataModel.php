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

/**
  @class WfcHtImportedHoursDataModel
*/
class WfcHtImportedHoursDataModel extends BasicModel
{

    protected $name = "ImportedHoursData";

    protected $columns = array(
    'empID' => array('type'=>'INT', 'primary_key'=>true),
    'periodID' => array('type'=>'INT', 'primary_key'=>true),
    'year' => array('type'=>'SMALLINT', 'index'=>true),
    'hours' => array('type'=>'DOUBLE'),
    'OTHours' => array('type'=>'DOUBLE'),
    'PTOHours' => array('type'=>'DOUBLE'),
    'EmergencyHours' => array('type'=>'DOUBLE'),
    'SecondRateHours' => array('type'=>'DOUBLE'),
    'HolidayHours' => array('type'=>'DOUBLE'),
    'UTOHours' => array('type'=>'DOUBLE'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function empID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empID"])) {
                return $this->instance["empID"];
            } elseif(isset($this->columns["empID"]["default"])) {
                return $this->columns["empID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["empID"] = func_get_arg(0);
        }
    }

    public function periodID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["periodID"])) {
                return $this->instance["periodID"];
            } elseif(isset($this->columns["periodID"]["default"])) {
                return $this->columns["periodID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["periodID"] = func_get_arg(0);
        }
    }

    public function year()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["year"])) {
                return $this->instance["year"];
            } elseif(isset($this->columns["year"]["default"])) {
                return $this->columns["year"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["year"] = func_get_arg(0);
        }
    }

    public function hours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hours"])) {
                return $this->instance["hours"];
            } elseif(isset($this->columns["hours"]["default"])) {
                return $this->columns["hours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["hours"] = func_get_arg(0);
        }
    }

    public function OTHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["OTHours"])) {
                return $this->instance["OTHours"];
            } elseif(isset($this->columns["OTHours"]["default"])) {
                return $this->columns["OTHours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["OTHours"] = func_get_arg(0);
        }
    }

    public function PTOHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PTOHours"])) {
                return $this->instance["PTOHours"];
            } elseif(isset($this->columns["PTOHours"]["default"])) {
                return $this->columns["PTOHours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["PTOHours"] = func_get_arg(0);
        }
    }

    public function EmergencyHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["EmergencyHours"])) {
                return $this->instance["EmergencyHours"];
            } elseif(isset($this->columns["EmergencyHours"]["default"])) {
                return $this->columns["EmergencyHours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["EmergencyHours"] = func_get_arg(0);
        }
    }

    public function SecondRateHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["SecondRateHours"])) {
                return $this->instance["SecondRateHours"];
            } elseif(isset($this->columns["SecondRateHours"]["default"])) {
                return $this->columns["SecondRateHours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["SecondRateHours"] = func_get_arg(0);
        }
    }

    public function HolidayHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["HolidayHours"])) {
                return $this->instance["HolidayHours"];
            } elseif(isset($this->columns["HolidayHours"]["default"])) {
                return $this->columns["HolidayHours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["HolidayHours"] = func_get_arg(0);
        }
    }

    public function UTOHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["UTOHours"])) {
                return $this->instance["UTOHours"];
            } elseif(isset($this->columns["UTOHours"]["default"])) {
                return $this->columns["UTOHours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["UTOHours"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

