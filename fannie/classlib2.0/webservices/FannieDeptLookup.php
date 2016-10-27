<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

namespace COREPOS\Fannie\API\webservices;
use \FannieDB;
use \FannieConfig;

class FannieDeptLookup extends FannieWebService
{
    
    public $type = 'json'; // json/plain by default

    /**
      Do whatever the service is supposed to do.
      Should override this.
      @param $args array of data
      @return an array of data
    */
    public function run($args=array())
    {
        $ret = array();
        if (!property_exists($args, 'type')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters',
            );
            return $ret;
        }

        // validate additional arguments
        switch (strtolower($args->type)) {
            case 'settings':
                if (!property_exists($args, 'dept_no')) {
                    // missing required arguments
                    $ret['error'] = array(
                        'code' => -32602,
                        'message' => 'Invalid parameters',
                    );
                    return $ret;
                }
                break;
            case 'children':
                if (!property_exists($args, 'superID') && !property_exists($args, 'dept_no')) {
                    // missing required arguments
                    $ret['error'] = array(
                        'code' => -32602,
                        'message' => 'Invalid parameters',
                    );
                    return $ret;
                }
                if (property_exists($args, 'superID') && is_array($args->superID) && count($args->superID) != 2) {
                    // range must specify exactly two superIDs
                    $ret['error'] = array(
                        'code' => -32602,
                        'message' => 'Invalid parameters',
                    );
                    return $ret;
                }
                if (property_exists($args, 'dept_no') && is_array($args->dept_no) && count($args->dept_no) != 2) {
                    // range must specify exactly two dept_nos 
                    $et['error'] = array(
                        'code' => -32602,
                        'message' => 'Invalid parameters',
                    );
                    return $ret;
                }
                break;
            default:
                // unknown type argument
                $ret['error'] = array(
                    'code' => -32602,
                    'message' => 'Invalid parameters',
                );
                return $ret;
        }

        // lookup results
        $dbc = FannieDB::getReadOnly(FannieConfig::factory()->get('OP_DB'));
        switch (strtolower($args->type)) {
            case 'settings':
                $model = new \DepartmentsModel($dbc);
                $model->dept_no($args->dept_no);
                $model->load();
                $ret['tax'] = $model->dept_tax();
                $ret['fs'] = $model->dept_fs();
                $ret['discount'] = $model->dept_discount();
                $ret['seeID'] = $model->dept_see_id();
                $ret['margin'] = $model->margin();

                return $ret;

            case 'children':
                $query = '';
                $params = array();
                if (property_exists($args, 'dept_no')) {
                    $query = '
                        SELECT s.subdept_no AS id,
                            s.subdept_name AS name
                        FROM departments AS d
                            INNER JOIN subdepts AS s ON d.dept_no=s.dept_ID ';
                        if (property_exists($args, 'superID') && is_numeric($args->superID)) {
                            $query .= ' INNER JOIN superdepts AS a ON d.dept_no=a.dept_ID ';
                        }
                        if (is_array($args->dept_no)) {
                            $query .= ' WHERE d.dept_no BETWEEN ? AND ? ';
                            $params[] = $args->dept_no[0];
                            $params[] = $args->dept_no[1];
                        } else {
                            $query .= ' WHERE d.dept_no = ? ';
                            $params[] = $args->dept_no;
                        }
                        if (property_exists($args, 'superID') && is_numeric($args->superID)) {
                            $query .= ' AND a.superID = ? ';
                            $params[] = $args->superID;
                        }
                        $query .= ' ORDER BY s.subdept_no';
                } else {
                    $query = '
                        SELECT d.dept_no AS id,
                            d.dept_name AS name
                        FROM superdepts AS s
                            INNER JOIN departments AS d ON d.dept_no=s.dept_ID ';
                    if (is_array($args->superID)) {
                        $query .= ' WHERE s.superID BETWEEN ? AND ? ';
                        $params[] = $args->superID[0];
                        $params[] = $args->superID[1];
                    } else {
                        $query .= ' WHERE s.superID = ? ';
                        $params[] = $args->superID;
                    }
                    $query .= ' ORDER BY d.dept_no';
                    // support meta-options for all departments
                    if (!is_array($args->superID) && $args->superID < 0) {
                        if ($args->superID == -1) {
                            $query = '
                                SELECT d.dept_no AS id,
                                    d.dept_name AS name 
                                FROM departments AS d
                                ORDER BY d.dept_no';
                            $params = array();
                        } elseif ($args->superID == -2) {
                            $query = '
                                SELECT d.dept_no AS id,
                                    d.dept_name AS name 
                                FROM departments AS d
                                    INNER JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID
                                WHERE m.superID <> 0
                                ORDER BY d.dept_no';
                            $params = array();
                        }
                    }
                }
                $prep = $dbc->prepare($query);
                $res = $dbc->execute($prep, $params);
                while ($w = $dbc->fetch_row($res)) {
                    $ret[] = array(
                        'id' => $w['id'],
                        'name' => $w['name'],
                    );
                }

                return $ret;
        }
    }

}

