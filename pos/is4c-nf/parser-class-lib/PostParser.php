<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class PostParser
  The base module for parsing modifying Parser results

  Enabled PostParser modules look at the output
  produced by input Parsers and may modify the result
*/
class PostParser 
{

	/**
      Re-write the output value
	  @param [keyed array] Parser output value
	  @return [keyed array] Parser output value

	  The output array has the following keys:
	   - main_frame If set, change page to this URL
	   - output HTML output to be displayed
	   - target Javascript selector string describing which
	     element should contain the output
	   - redraw_footer True or False. Set to True if
	     totals have changed.
	   - receipt False or string type. Print a receipt with
	     the given type.
       - trans_num string current transaction identifier
	   - scale Update the scale display and session variables
	   - udpmsg False or string. Send a message to hardware
	     device(s)
	   - retry False or string. Try the input again shortly.
	*/
	public function parse($json)
    {
        return $json;
	}

	/**
	  Gather postparse modules
	  @return array of PostParser class names

	  Scan the parse directory for module files.
	  Return an array of available modules.
	*/
	static public function getPostParseChain()
    {
		$set = AutoLoader::ListModules('PostParser');
		$set = array_reverse($set);

		return $set;
	}

}

