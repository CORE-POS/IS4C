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

load_class('FanniePage');

/**
  @class FannieReport
  Class for creating reports
*/
class FannieReport extends FanniePage {

	private $downloadable = False;
	private $headers = array();

	/**
	  Send headers and remove extra HTML for download
	  @param $filename the file name
	  @param $type the file type. Currently allowed:
	   - excel
	*/
	function download($filename, $type){
		switch(strtolower($type)){
		case 'excel':
			$this->headers[] = "Content-Type: application/ms-excel";
			$this->headers[] = "Content-Disposition: attachment; filename=\"$filename\"";
		}
		$this->downloadable = True;
		$this->window_dressing = False;

		foreach($this->headers as $h)
			header($h);
	}
}

?>
