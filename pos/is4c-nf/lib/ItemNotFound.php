<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op.

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

/**
  @class ItemNotFound
  Deal with a UPC entry that doesn't
  exist in products or match a SpecialUPC
  handler
*/
class ItemNotFound extends LibraryClass 
{
    /**
      React to missing item
      @param $upc [string] UPC value
      @param $json [keyed array] formatted return value
      @return [keyed array] formatted return value
      
      The $json parameter and return value have the
      same format as Parser since this module interacts
      with input parsing. 
    */
    public function handle($upc, $json)
    {
        global $CORE_LOCAL;
        $opts = array('upc'=>$upc,'description'=>'BADSCAN');
        TransRecord::add_log_record($opts);
        $CORE_LOCAL->set("boxMsg", $upc . ' ' . _("not a valid item"));
        $json['main_frame'] = MiscLib::baseURL() . "gui-modules/boxMsg2.php";

        return $json;
    }
}

/**
  @example 
  
  Log item not found with a different description
  (contrived, obviously)

class LogNotFound extends ItemNotFound 
{
	public function handle($upc, $json)
    {
        global $CORE_LOCAL;
        $opts = array('upc'=>$upc,'description'=>'NOTFOUND');
        TransRecord::add_log_record($opts);
        $CORE_LOCAL->set("boxMsg", $upc . ' ' . _("not a valid item"));
        $json['main_frame'] = $my_url . "gui-modules/boxMsg2.php";

        return $json;
	}
}
*/

