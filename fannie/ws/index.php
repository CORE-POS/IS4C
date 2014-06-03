<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include('../config.php');
if (!class_exists('FannieAPI')) {
    include('../classlib2.0/FannieAPI.php');
}

/**
  General purpose webservice endpoint
  Input data can be passed in a GET or POST parameter
  named "json" or POSTed directly to this URL.

  Request and response format follows JSON-RPC 2.0.
  Request objects MUST contain a "method" field and
  MAY contain "id" and "params" fields.

  The response object WILL contain fields "jsonrpc"
  and "id". The response WILL also contain EITHER
  a "result" field or an "error" field.
*/

$response = array(
    'jsonrpc' => '2.0',
    'id' => null,
);

$input = false;
if (FormLib::get('json', '') !== '') {
    $input = FormLib::get('json');
} else {
    $input = file_get_contents('php://input');
}

header('Content-type: application/json');
if ($input === false) {
    $response['error'] = array(
        'code' => -32600,
        'message' => 'Invalid Request',
    );
    echo json_encode($response);

    return false;
}

$input = json_decode($input);
if ($input === null) {
    $response['error'] = array(
        'code' => -32700,
        'message' => 'JSON Parse Error',
    );
    echo json_encode($response);

    return false;
}

$send_reply = false;
if (property_exists($input, 'id') && $input->id != null) {
    $response['id'] = $input->id;
    $send_reply = true;
}

if (!property_exists($input, 'method')) {
    $response['error'] = array(
        'code' => -32600,
        'message' => 'Invalid request: method not specified',
    );
    if ($send_reply) {
        echo json_encode($response);
    }

    return false;
}

if (!class_exists($input->method)) {
    $response['error'] = array(
        'code' => -32601,
        'message' => 'Method not found',
    );
    if ($send_reply) {
        echo json_encode($response);
    }

    return false;
}

$service_class = $input->method;
$service_obj = new $service_class();
if (!$service_obj instanceof FannieWebService) {
    $response['error'] = array(
        'code' => -32601,
        'message' => 'Method not found',
    );
    if ($send_reply) {
        echo json_encode($response);
    }

    return false;
}

$params = array();
if (property_exists($input, 'params')) {
    $params = $input->params;
}

$output = $service_obj->run($params);
if (is_array($output) && isset($output['error'])) {
    $response['error'] = $output['error'];
} else {
    $response['result'] = $output;
}
if ($send_reply) {
    echo json_encode($response);
}

