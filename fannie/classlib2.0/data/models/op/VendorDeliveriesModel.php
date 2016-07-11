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
  @class VendorDeliveriesModel
*/
class VendorDeliveriesModel extends BasicModel
{

    protected $name = "vendorDeliveries";
    protected $preferred_db = 'op';

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'frequency' => array('type'=>'VARCHAR(10)'),
    'regular' => array('type'=>'TINYINT', 'default'=>1),
    'nextDelivery' => array('type'=>'DATETIME'),
    'nextNextDelivery' => array('type'=>'DATETIME'),
    'sunday' => array('type'=>'TINYINT', 'default'=>0),
    'monday' => array('type'=>'TINYINT', 'default'=>0),
    'tuesday' => array('type'=>'TINYINT', 'default'=>0),
    'wednesday' => array('type'=>'TINYINT', 'default'=>0),
    'thursday' => array('type'=>'TINYINT', 'default'=>0),
    'friday' => array('type'=>'TINYINT', 'default'=>0),
    'saturday' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function doc()
    {
        return '
Use:
Schedule of vendor deliveries
        ';
    }

    /**
      Calculate next delivery dates
    */
    public function autoNext()
    {
        $now = mktime();
        switch (strtolower($this->frequency())) {
            case 'weekly':
                $next = $now;
                $found = false;
                for ($i=0; $i<7; $i++) {
                    $next = mktime(0, 0, 0, date('n',$next), date('j',$next)+1, date('Y',$next)); 
                    $func = strtolower(date('l', $next));
                    if ($this->$func()) {
                        $this->nextDelivery(date('Y-m-d', $next));
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    for ($i=0; $i<7; $i++) {
                        $next = mktime(0, 0, 0, date('n',$next), date('j',$next)+1, date('Y',$next)); 
                        $func = strtolower(date('l', $next));
                        if ($this->$func()) {
                            $this->nextNextDelivery(date('Y-m-d', $next));
                            break;
                        }
                    }
                }
                break;
        }
    }
}

