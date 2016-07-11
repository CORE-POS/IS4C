<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

use COREPOS\common\mvc;

/**
  @class FannieRESTfulPage

  A variation on the standard FanniePage using
  REST principles.

  Four methods are available for processing user input
  and are automatically called based on HTTP request type:
  - getHandler
  - postHandler
  - putHandler
  - deleteHandler
  These methods behave like FanniePage::preprocess; there
  are just four options automatically

  Eight methods are available for displaying the page
  and are also automatically called based on request type:
  - getView
  - get_idView
  - postView
  - post_idView
  - putView
  - put_idView
  - deleteView
  - delete_idView
  These methods behave like FanniePage::body_content; again
  there are just more options.

  A class does not need to implement handlers or views
  for all request types. Use as few or as many as needed.

  PUT and DELETE are simulated by setting a form 
  field named "_method".
*/
class FannieRESTfulPage extends FanniePage 
{
    protected $__method = '';

    protected $__models = array();

    /**
      Define available routes
      Syntax is request method followed by
      parameter names in angle brackets

      method<one><two> should provide a controller
      function named method_one_twoHandler(). It
      may optionally provide a view function
      named method_one_twoView().

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

    protected $__route_stem = 'unknownRequest';

    protected $form;

    protected $routing_trait;

    public function __construct()
    {
        $this->routing_trait = new \COREPOS\common\ui\CoreRESTfulRouter();
        $this->form = new COREPOS\common\mvc\FormValueContainer();
        parent::__construct();
    }

    /**
      Extract paramaters from route definition
      @param $route string route definition
      @return array of parameter names
    */
    private function routeParams($route)
    {
        $matches = array();
        $try = preg_match_all('/<(.+?)>/', $route, $matches);
        if ($try > 0) {
            return $matches[1];
        } else {
            return False;
        }
    }

    /**
      Parse request info and determine which route to use
    */
    public function readRoutes()
    {
        // routes begin with method
        try {
            $this->__method = $this->form->_method;
        } catch (Exception $ex) {
            $this->__method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
            if ($this->__method === null) {
                $this->__method = 'get';
            }
        }
        $this->__method = strtolower($this->__method);

        // find all matching routes
        $try_routes = array();
        foreach($this->__routes as $route) {
            // correct request type
            if(substr($route,0,strlen($this->__method)) == $this->__method) {
                $try_routes = $this->checkRoute($route, $try_routes);
            }
        }
        
        $this->__route_stem = $this->bestRoute($try_routes);
    }

    public function addRoute()
    {
        foreach (func_get_args() as $r) {
            if (!in_array($r, $this->__routes)) {
                $this->__routes[] = $r;
            }
        }
    }

    protected function checkRoute($route, $try_routes)
    {
        $params = $this->routeParams($route);    
        if ($params === false || count($params) === 0) {
            // route with no params
            if (!isset($try_routes[0])) {
                $try_routes[0] = array();
            }
            $try_routes[0][] = $route;
        } else {
            // make sure all params provided
            $all = true;
            foreach($params as $p) {
                // just checking whether field exists
                // exception means it doesn't
                try {
                    $this->form->$p;
                } catch (Exception $e) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                if (!isset($try_routes[count($params)])) {
                    $try_routes[count($params)] = array();
                }
                $try_routes[count($params)][] = $route;
            }
        }

        return $try_routes;
    }

    protected function bestRoute($try_routes)
    {
        // use the route with the most parameters
        // set class variables to parameters
        $num_params = array_keys($try_routes);
        rsort($num_params);
        $ret = 'unknownRequest';
        if (count($num_params) > 0) {
            $longest = $num_params[0];
            $best_route = array_pop($try_routes[$longest]);
            $ret = $this->__method;
            if ($longest > 0) {
                foreach($this->routeParams($best_route) as $param) {
                    $this->$param = $this->form->$param;
                    $ret .= '_'.$param;
                }
            }
        }

        return $ret;
    }

    public function preprocess()
    {
        /*
        foreach ($this->__routes[] as $route) {
            $this->routing_trait->addRoute($route);
        }
        return $this->routing_trait->handler($this);
        */

        $this->readRoutes();
        $handler = $this->__route_stem.'Handler';
        $view = $this->__route_stem.'View';    
        $old_handler = $this->__route_stem.'_handler';
        $old_view = $this->__route_stem.'_view';    
        $ret = true;
        if (method_exists($this, $handler)) {
            $ret = $this->$handler();
        } elseif (method_exists($this, $old_handler)) {
            $ret = $this->$old_handler();
        } elseif (method_exists($this, $view)) {
            $ret = true;
        } elseif (method_exists($this, $old_view)) {
            $ret = true;
        } else {
            $ret = $this->unknownRequestHandler();
        }

        if ($ret === true) {
            return true;
        } elseif ($ret === false) {
            return false;
        } elseif (is_string($ret)) {
            if (!headers_sent()) {
                header('Location: ' . $ret);
            }
            return false;
        } else {
            // dev error/bug?
            return false;
        }
    }

    /**
      Process unknown HTTP method request
      @return boolean
      Returning True draws the page
      Returning False does not
    */
    protected function unknownRequestHandler()
    {
        echo '<html><head><title>HTTP 400 - Bad Request</title>
            <body><h1>HTTP 400 - Bad Request</body></html>';
        return False;
    }

    public function bodyContent()
    {
        //return $this->routing_trait->view($this);

        $func = $this->__route_stem.'View';
        $old_func = $this->__route_stem.'_view';
        if (method_exists($this, $func)) {
            return $this->$func();
        } elseif (method_exists($this, $old_func)) {
            return $this->$old_func();
        } else {
            return $this->unknownRequestView();
        }
    }

    /**
      Draw default page for unknown HTTP method
      @return HTML string
    */
    protected function unknownRequestView()
    {
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
    protected function getModel($database_connection, $class, $params, $find=False)
    {
        $obj = new $class($database_connection);
        foreach($params as $name => $value) {
            try {
                $obj->$name($value);
            } catch (Exception $ex) {
                $this->logger->debug($ex);
            }
        }
        if ($find) {
            return $obj->find($find);
        } else {
            $obj->load();
            return $obj;
        }
    }

    protected function get_model($database_connection, $class, $params, $find=False)
    {
        return $this->getModel($database_connection, $class, $params, $find);
    }

    public function unitTest($phpunit)
    {
        $this->__routes = array(
            'get',
            'get<id>',
            'post',
            'post<id>',
            'get<other>',
            'get<id><other>',
        );

        $values = new \COREPOS\common\mvc\ValueContainer();

        $values->_method = 'get';
        $this->setForm($values);
        $this->readRoutes(); 
        $phpunit->assertEquals('get', $this->__route_stem);

        $values->id = -99;
        $this->setForm($values);
        $this->readRoutes(); 
        $phpunit->assertEquals('get_id', $this->__route_stem);

        $values->other = -99;
        $this->setForm($values);
        $this->readRoutes(); 
        $phpunit->assertEquals('get_id_other', $this->__route_stem);

        unset($values->id);
        $this->setForm($values);
        $this->readRoutes(); 
        $phpunit->assertEquals('get_other', $this->__route_stem);

        $values->_method = 'post';
        $this->setForm($values);
        $this->readRoutes(); 
        $phpunit->assertEquals('post', $this->__route_stem);

        $values->id = -99;
        $this->setForm($values);
        $this->readRoutes(); 
        $phpunit->assertEquals('post_id', $this->__route_stem);
    }

    /**
      Set the form value container
      @param [ValueContainer] $f
      Accepts a generic ValueContainer instead of a FormValueContainer
      so that unit tests can inject preset values 
    */
    public function setForm(COREPOS\common\mvc\ValueContainer $f)
    {
        $this->form = $f;
    }
}
