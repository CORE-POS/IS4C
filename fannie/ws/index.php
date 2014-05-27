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

  Input data should be a JSON object.
  The object must have a property named "service"
  specifying which webservice is being called.
  Other input data will be passed to the underlying
  service.
*/

$input = false;
if (FormLib::get('json', '') !== '') {
    $input = FormLib::get('json');
} else {
    $input = file_get_contents('php://input');
}

header('Content-type: application/json');
if ($input === false) {
    echo json_encode(array('error' => 'No input'));

    return false;
}

$input = json_decode($input);
if ($input === null) {
    echo json_encode(array('error' => 'Malformed input'));

    return false;
}

if (!property_exists($input, 'service')) {
    echo json_encode(array('error' => 'No service specified'));

    return false;
}

if (!class_exists($input->service)) {
    echo json_encode(array('error' => 'Invalid service: ' . $input->service));

    return false;
}

$service_class = $input->service;
$service_obj = new $service_class();
if (!$service_obj instanceof FannieWebService) {
    echo json_encode(array('error' => 'Invalid service: ' . $input->service));

    return false;
}

$output = $service_obj->run($input);
echo json_encode($output);

