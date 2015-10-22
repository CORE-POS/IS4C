<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op.
    Copyright 2015 West End Food Co-op, Toronto, Canada

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
  @class ProductNameFormatter
  Base class for formatting products.formatted_name
*/
class ProductNameFormatter {

    /**
      Compose the name.
      @param $params array of parameters, to be interpreted by the module.
      @return string, the formatted name

    */
    public function compose($params = array())
    {
        return "";
    }
}

