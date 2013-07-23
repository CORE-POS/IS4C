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
  @class FannieRESTfulPage

  A variation on the standard FanniePage using
  REST principles.

  Four methods are available for processing user input
  and are automatically called based on HTTP request type:
  - get_handler
  - post_handler
  - put_handler
  - delete_handler
  These methods behave like FanniePage::preprocess; there
  are just four options automatically

  Eight methods are available for displaying the page
  and are also automatically called based on request type:
  - get_view
  - get_id_view
  - post_view
  - post_id_view
  - put_view
  - put_id_view
  - delete_view
  - delete_id_view
  These methods behave like FanniePage::body_content; again
  there are just more options.

  A class does not need to implement handlers or views
  for all request types. Use as few or as many as needed.

  PUT and DELETE are simulated by setting a form 
  field named "_method".
*/
class FannieRESTfulPage extends FanniePage {

	protected $__method = '';

	protected $__models = array();

	/**
	  Define available routes
	  Syntax is request method followed by
	  parameter names in angle brackets

	  method<one><two> should provide a controller
	  function named method_one_two_handler(). It
	  may optionally provide a view function
	  named method_one_two_view().

	  controller functions behave like FanniePage::preprocess
	  and should return True or False.

	  view functions behave like FanniePage::body_content
	  and should return an HTML string
	*/
	protected $__routes = array(
		'get',
		'get<id>',
		'post',
		'post<id>',
		'put',
		'put<id>',
		'delete',
		'delete<id>'
	);

	protected $__route_stem = 'unknown_request';

	/**
	  Extract paramaters from route definition
	  @param $route string route definition
	  @return array of parameter names
	*/
	private function route_params($route){
		$matches = array();
		$try = preg_match_all('/<(.+?)>/',$route,$matches);
		if ($try > 0) return $matches[1];
		else return False;
	}

	/**
	  Parse request info and determine which route to use
	*/
	public function read_routes(){
		// routes begin with method
		$this->__method = FormLib::get_form_value('_method');
		if ($this->__method === ''){
			$this->__method = $_SERVER['REQUEST_METHOD'];
		}
		$this->__method = strtolower($this->__method);

		// find all matching routes
		$try_routes = array();
		foreach($this->__routes as $route){
			// correct request type
			if(substr($route,0,strlen($this->__method)) == $this->__method){
				$params = $this->route_params($route);	
				if ($params === False || count($params) === 0){
					// route with no params
					if (!isset($try_routes[0])) $try_routes[0] = array();
					$try_routes[0][] = $route;
				}
				else {
					// make sure all params provided
					$all = True;
					foreach($params as $p){
						if (FormLib::get_form_value($p,False) === False){
							$all = False;
							break;
						}
					}
					if ($all){
						if (!isset($try_routes[count($params)]))
							$try_routes[count($params)] = array();
						$try_routes[count($params)][] = $route;
					}
				}
			}
		}
		
		// use the route with the most parameters
		// set class variables to parameters
		$num_params = array_keys($try_routes);
		rsort($num_params);
		$this->__route_stem = 'unknown_request';
		if (count($num_params) > 0){
			$longest = $num_params[0];
			$best_route = array_pop($try_routes[$longest]);
			$this->__route_stem = $this->__method;
			if ($longest > 0){
				foreach($this->route_params($best_route) as $param){
					$this->$param = FormLib::get_form_value($param);
					$this->__route_stem .= '_'.$param;
				}
			}
		}
	}

	public function preprocess(){
		$this->read_routes();
		$handler = $this->__route_stem.'_handler';
		$view = $this->__route_stem.'_view';	
		if (method_exists($this, $handler))
			return $this->$handler();
		elseif (method_exists($this, $view))
			return True;
		else
			return $this->unknown_request_handler();
	}

	/**
	  Process unknown HTTP method request
	  @return boolean
	  Returning True draws the page
	  Returning False does not
	*/
	protected function unknown_request_handler(){
		echo '<html><head><title>HTTP 400 - Bad Request</title>
			<body><h1>HTTP 400 - Bad Request</body></html>';
		return False;
	}

	public function body_content(){
		$func = $this->__route_stem.'_view';
		if (!method_exists($this, $func))
			return $this->unknown_request_view();
		else
			return $this->$func();
	}

	/**
	  Draw default page for unknown HTTP method
	  @return HTML string
	*/
	protected function unknown_request_view(){
		return 'HTTP 400 - Bad Request';
	}

	/**
	  Load model(s)
	  @param $database_connection SQLManager object
	  @param $class string name of model class
	  @param $params array of column names and values
	  @param $find [optional] string sort column or False
	  @return model object or array or model objects
	
	  If called without $find or $find=False returns a 
	  single model object. Provided $params must be sufficient
	  to uniquely identify a single record

	  If called with $find then returns an array of model
	  objects for all records that match $params and
	  sorted by $find.
	*/
	protected function get_model($database_connection, $class, $params, $find=False){
		$obj = new $class($database_connection);
		foreach($params as $name => $value){
			if (method_exists($obj, $name))
				$obj->$name($value);
		}
		if ($find)
			return $obj->find($find);
		else{
			$obj->load();
			return $obj;
		}	
	}

}
