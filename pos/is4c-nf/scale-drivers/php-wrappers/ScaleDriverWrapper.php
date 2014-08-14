<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
*/

/** @class ScaleDriverWrapper
    PHP Module for talking to hardware

    This class deals with reading and writing
    to hardware devices that PHP can't talk to
    directly. Normally this means "scanner scale".
    All drivers should provide a ScaleDriverWrapper
    subclass.

    Modules that extend this class must at least define
    ReadFromScale and WriteToScale.
*/

class ScaleDriverWrapper 
{

    /**
      Javascript used to interact with
      scale driver. Default is poll-scale.js.

      Javascript file must provide a function
      named pollScale.
    */
    public function javascriptFile()
    {
        return 'poll-scale.js';
    }

	/**
	  This method updates the driver code
	  or configuration with the specified port name.
	  @param $portName a serial port device name
	   e.g., /dev/ttyS0 or COM1
	  <b>Not used much</b>
	  In practice it's kind of a mess. The driver code
	  has to be browser-writable for this to work
	  and in many cases the driver also needs to be
	  recompiled. Browser-based driver configuration
	  just doesn't work very well, so if your
	  implementation skips this method that's probably
	  fine.
	*/
	public function SavePortConfiguration($portName){}
	
	/** 
	   This method updates the driver code or configuration
	   with the specified path
	   @param $absPath is the top of IT CORE, should have
	   trailing slash
	   <b>not used much</b>
	   See the SavePortConfiguration() method for details.
	*/
	public function SaveDirectoryConfiguration($absPath){}

	/** 
	   Reads available scale and scanner input
	   Function should print a JSON object with two fields:
		'scale' is an HTML string to display current scale weight/status
		'scans' is a string representing a UPC
	   Use scaledisplaymsg() to generate scale HTML. This ensures
	   appropriate weight-related session variables are
	   updated.
	*/
	public function ReadFromScale(){}

	/** 
	   Sends output to the scale. 
	   @param $str the output
	   Currently supported messages (not case sensitive):
		1. goodBeep
		2. errorBeep
		3. twoPairs
		4. rePoll
		5. wakeup
	*/
	public function WriteToScale($str){}

	/** Clear all pending input 
	    
	    If the driver has been running in the backgounrd
	    and the browser hasn't, there could be a lot of 
	    accumulated weight data. POS uses this method
	    to discard everything on startup. 
	 */
	public function ReadReset(){}

}

