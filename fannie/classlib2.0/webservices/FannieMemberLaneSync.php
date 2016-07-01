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

namespace COREPOS\Fannie\API\webservices;

class FannieMemberLaneSync extends \COREPOS\Fannie\API\webservices\FannieWebService
{
    
    public $type = 'json'; // json/plain by default

    /**
      Do whatever the service is supposed to do.
      Should override this.
      @param $args array of data
      @return an array of data
    */
    public function run($args=array())
    {
        $ret = array();
        if (!property_exists($args, 'id')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters needs id',
            );
            return $ret;
        }

        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $custdata = new \CustdataModel($dbc);
        $custdata->CardNo($args->id);
        foreach ($custdata->find() as $c) {
            $c->pushToLanes();
        }

        $cards = new \MemberCardsModel($dbc);
        $cards->card_no($args->id);
        $cards->load();
        $cards->pushToLanes();

        return array('done'=>true);
    }
}

