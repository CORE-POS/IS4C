<?php

namespace COREPOS\Fannie\API\webservices; 
use \FannieConfig;
use \FannieDispatch;

/**
  FannieDispatch-compatible JSON request handler
  
  Override the get, post, put, and/or delete methods
  to handle requests. All overriden methods should
  return a json-encodable array. The put and post
  methods will received the json-decoded request body
  as an argument.
*/
abstract class JsonEndPoint
{
    private $dbc;
    private $config;
    private $logger;

    public function setLogger($l)
    {
        $this->logger = $l;
    }

    public function setConfig(FannieConfig $c)
    {
        $this->config = $c;
    }

    public function setConnection($db)
    {
        $this->dbc = $db;
    }

    protected function readInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    protected function sendResponse($msg)
    {
        header('Content-type: application/json');
        echo json_encode($msg);
    }

    protected function get()
    {
        header('HTTP/1.0 405 Method Not Allowed');
        return array('error' => 'GET Not implemented');
    }

    protected function post(array $json)
    {
        header('HTTP/1.0 405 Method Not Allowed');
        return array('error' => 'POST Not implemented');
    }

    protected function put(array $json)
    {
        header('HTTP/1.0 405 Method Not Allowed');
        return array('error' => 'PUT Not implemented');
    }

    protected function delete()
    {
        header('HTTP/1.0 405 Method Not Allowed');
        return array('error' => 'DELETE Not implemented');
    }

    public function draw_page()
    {
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
        switch (strtolower($method)) {
            case 'get':
                $this->sendResponse($this->get());
                break;
            case 'post':
                $this->sendResponse($this->post($this->readInput()));
                break;
            case 'put':
                $this->sendResponse($this->put($this->readInput()));
                break;
            case 'delete':
                $this->sendResponse($this->delete());
                break;
            default:
                header('HTTP/1.0 405 Method Not Allowed');
                echo "Unknown HTTP method";
                break;
        }
    }

    public static function dispatch($class)
    {
        $frames = debug_backtrace();
        if (count($frames) == 1) {
            FannieDispatch::runPage($class);
        }
    }
}

