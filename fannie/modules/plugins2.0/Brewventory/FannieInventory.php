<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
  @class FannieInventory
  Class for inventory Management
*/
class FannieInventory extends FanniePage {

    public $required = True;

    public $description = "
    Class for managing inventory
    ";

    protected $mode = 'view';

    /**
      Display screen content
      Default modes are
      - view
      - receive
      - sale
      - adjust

      You can define your own. Just make
      a method with the same name.
    */
    function body_content(){
        switch ($this->mode){
        case 'view':
            return $this->view();
        case 'receive':
            return $this->receive();
        case 'sale':
            return $this->sale();
        case 'adjust':
            return $this->adjust();
        default:
            $func = $this->mode;
            if (method_exists($this, $func))
                return $this->$func();
            else
                return "";
        }
    }

    /**
      Display receiving entry form
      @return HTML string
    */
    function receive(){
        return "";
    }

    /**
      Display sales entry form
      @return HTML string
    */
    function sale(){
        return "";
    }

    /**
      Display adjustment entry form
      @return HTML string
    */
    function adjustt(){
        return "";
    }

    /**
      Display current inventory
      @return HTML string
    */
    function view(){
        return "";
    }
}

?>
