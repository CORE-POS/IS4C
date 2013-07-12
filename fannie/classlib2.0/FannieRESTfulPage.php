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
  - get_show_view
  - get_id_view
  - post_show_view
  - post_id_view
  - put_show_view
  - put_id_view
  - delete_show_view
  - delete_id_view
  These methods behave like FanniePage::body_content; again
  there are just more options.

  A class does not need to implement handlers or views
  for all request types. Use as few or as many as needed.

  PUT and DELETE are simulated by setting a form 
  field named "_method".
*/
class FannieRESTfulPage extends FanniePage {

	protected $method = '';

	protected $id = False;
	protected $models = array();

	public function preprocess(){
		$this->method = FormLib::get_form_value('_method');
		if ($this->method === ''){
			$this->method = $_SERVER['REQUEST_METHOD'];
		}
		if (FormLib::get_form_value('id',False) !== False)
			$this->id = FormLib::get_form_value('id');
		switch(strtolower($this->method)){
		case 'get':
			return $this->get_handler();
			break;
		case 'post':
			return $this->post_handler();
			break;
		case 'put':
			return $this->put_handler();
			break;
		case 'delete':
			return $this->delete_handler();
			break;
		case '':
			return True;
			break;
		default:
			return $this->unknown_request_handler();
			break;
		}
	}

	/**
	  Process HTTP GET request
	  @return boolean
	  Returning True draws the page
	  Returning False does not
	*/
	protected function get_handler(){
		return $this->unknown_request_handler();;
	}

	/**
	  Process HTTP POST request
	  @return boolean
	  Returning True draws the page
	  Returning False does not
	*/
	protected function post_handler(){
		return $this->unknown_request_handler();;
	}

	/**
	  Process HTTP PUT request
	  @return boolean
	  Returning True draws the page
	  Returning False does not
	*/
	protected function put_handler(){
		return $this->unknown_request_handler();;
	}

	/**
	  Process HTTP DELETE request
	  @return boolean
	  Returning True draws the page
	  Returning False does not
	*/
	protected function delete_handler(){
		return $this->unknown_request_handler();;
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
		switch(strtolower($this->method)){
		case 'get':
			return ($this->id === False) ? $this->get_show_view() : $this->get_id_view();
			break;
		case 'post':
			return ($this->id === False) ? $this->post_show_view() : $this->post_id_view();
			break;
		case 'put':
			return ($this->id === False) ? $this->put_show_view() : $this->put_id_view();
			break;
		case 'delete':
			return ($this->id === False) ? $this->delete_show_view() : $this->delete_id_view();
			break;
		default:
			return $this->unknown_request_view();
			break;
		}
	}

	/**
	  Draw page for HTTP GET request
	  @return HTML string
	*/
	protected function get_show_view(){
		return $this->unknown_request_view();
	}

	/**
	  Draw page for HTTP GET request
	  @return HTML string
	*/
	protected function get_id_view(){
		return $this->unknown_request_view();
	}

	/**
	  Draw page for HTTP POST request
	  @return HTML string
	*/
	protected function post_show_view(){
		return $this->unknown_request_view();
	}

	/**
	  Draw page for HTTP POST request
	  @return HTML string
	*/
	protected function post_id_view(){
		return $this->unknown_request_view();
	}

	/**
	  Draw page for HTTP PUT request
	  @return HTML string
	*/
	protected function put_show_view(){
		return $this->unknown_request_view();
	}

	/**
	  Draw page for HTTP PUT request
	  @return HTML string
	*/
	protected function put_id_view(){
		return $this->unknown_request_view();
	}

	/**
	  Draw page for HTTP DELETE request
	  @return HTML string
	*/
	protected function delete_show_view(){
		return $this->unknown_request_view();
	}

	/**
	  Draw page for HTTP DELETE request
	  @return HTML string
	*/
	protected function delete_id_view(){
		return $this->unknown_request_view();
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
