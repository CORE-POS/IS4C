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
  @class FannieModule
  Base class for Fannie.
*/
class FannieModule {

	public $required = True;

	public $description = "
	Base class for all Fannie Modules.
	";

	/**
	  Called when module is enabled
	  Automatically registers class
	*/
	function enable(){
		$info = new ReflectionClass($this);
		register_symbols($info->getFileName(),
			$this->provided_functions(),$info->name);
	}

	/**
	  Called when module is disabled
	  Automatically unregisters class
	*/
	function disable(){
		$info = new ReflectionClass($this);
		unregister_symbols($this->provided_functions(),$info->name);
	}

	/**
	  Called when the module is used
	*/
	function run_module(){

	}

	/**
	  Get list of non-class functions
	  this module provides
	  @return array of function names
	*/
	function provided_functions(){
		return array();
	}
}

/**
  @example SimpleHelloWorld.php
  The most basic module possible.

  The run_module() method is invoked to
  let the module do whatever it's supposed 
  to do. Most user-facing modules will use
  likely use FanniePage or one of its
  subclasses.
*/

/**
  @example FunctionLibrary.php
  A module that provides functions.

  The class is portion of the module
  is responsible for defining what functions
  the module provides.
*/
