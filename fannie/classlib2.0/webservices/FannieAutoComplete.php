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

namespace COREPOS\Fannie\API\webservices 
{

class FannieAutoComplete extends FannieWebService 
{
    
    public $type = 'json'; // json/plain by default

    /**
      Do whatever the service is supposed to do.
      Should override this.
      @param $args array of data
      @return an array of data
    */
    public function run($args)
    {
        $ret = array();
        if (!property_exists($args, 'field') || !property_exists($args, 'search')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters',
            );
            return $ret;
        } else if (strlen($args->search) < 1) {
            // search term is too short
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters',
            );
            return $ret;
        }

        $dbc = \FannieDB::getReadOnly(\FannieConfig::factory()->get('OP_DB'));
        switch (strtolower($args->field)) {
            case 'item':
                $res = false;
                if (!is_numeric($args->search)) {
                    $prep = $dbc->prepare('SELECT p.upc,
                                            p.description
                                           FROM products AS p
                                            LEFT JOIN productUser AS u ON u.upc=p.upc
                                           WHERE p.description LIKE ?
                                            OR p.brand LIKE ?
                                            OR u.description LIKE ?
                                            OR u.brand LIKE ?
                                           GROUP BY p.upc,
                                            p.description
                                           ORDER BY p.description');
                    $term = '%' . $args->search . '%';
                    $res = $dbc->execute($prep, array($term, $term, $term, $term));
                } elseif (ltrim($args->search, '0') != '') {
                    $prep = $dbc->prepare('
                        SELECT p.upc,
                            p.upc AS description
                        FROM products AS p
                        WHERE p.upc LIKE ?
                        GROUP BY p.upc');
                    $res = $dbc->execute($prep, array('%'.$args->search . '%'));
                }
                while ($res && $row = $dbc->fetch_row($res)) {
                    $ret[] = array(
                        'label' => $row['description'],
                        'value' => $row['upc'],
                    );
                }

            case 'brand':
                $prep = $dbc->prepare('SELECT brand
                                       FROM products
                                       WHERE brand LIKE ?
                                       GROUP BY brand
                                       ORDER BY brand');
                $res = $dbc->execute($prep, array($args->search . '%'));
                while ($row = $dbc->fetch_row($res)) {
                    $ret[] = $row['brand'];
                }

                return $ret;

            case 'long_brand':
                $prep = $dbc->prepare('SELECT u.brand
                                       FROM productUser AS u
                                        INNER JOIN products AS p ON u.upc=p.upc
                                       WHERE u.brand LIKE ?
                                       GROUP BY u.brand
                                       ORDER BY u.brand');
                $res = $dbc->execute($prep, array($args->search . '%'));
                while ($row = $dbc->fetch_row($res)) {
                    $ret[] = $row['brand'];
                }

                return $ret;

            case 'vendor':
                $prep = $dbc->prepare('SELECT vendorID,
                                        vendorName
                                       FROM vendors
                                       WHERE vendorName LIKE ?
                                       ORDER BY vendorName');
                $res = $dbc->execute($prep, array($args->search . '%'));
                while ($row = $dbc->fetch_row($res)) {
                    $ret[] = $row['vendorName'];
                }
                if ($dbc->tableExists('prodExtra')) {
                    $prep = $dbc->prepare('SELECT distributor
                                           FROM prodExtra
                                           WHERE distributor LIKE ?
                                           GROUP BY distributor
                                           ORDER BY distributor');
                    $res = $dbc->execute($prep, array($args->search . '%'));
                    while ($row = $dbc->fetch_row($res)) {
                        if (!in_array($row['distributor'], $ret)) {
                            $ret[] = $row['distributor'];
                        }
                    }
                }

                return $ret;

            case 'mfirstname':
            case 'mlastname':
            case 'maddress':
            case 'mcity':
            case 'memail':
                return \COREPOS\Fannie\API\member\MemberREST::autoComplete($args->field, $args->search);

            case 'sku':
                $query = 'SELECT sku
                          FROM vendorItems
                          WHERE sku LIKE ? ';
                $param = array($args->search . '%');
                if (property_exists($args, 'vendor_id')) {
                    $query .= ' AND vendorID=? ';
                    $param[] = $args->vendor_id;
                }
                $query .= 'GROUP BY sku
                          ORDER BY sku';
                $prep = $dbc->prepare($query);
                $res = $dbc->execute($prep, $param);
                while ($row = $dbc->fetch_row($res)) {
                    $ret[] = $row['sku'];
                    if (count($ret) > 50) {
                        break;
                    }
                }
            
                return $ret;

            case 'unit':
                $query = '
                    SELECT unitofmeasure
                    FROM products
                    WHERE unitofmeasure LIKE ?
                    GROUP BY unitofmeasure
                    ORDER BY unitofmeasure';
                $param = array($args->search . '%');
                $prep = $dbc->prepare($query);
                $res = $dbc->execute($prep, $param);
                while ($row = $dbc->fetchRow($res)) {
                    $ret[] = $row['unitofmeasure'];
                    if (count($ret) > 50) {
                        break;
                    }
                }

                return $ret;

            default:
                return $ret;
        }
    }
}

}

namespace 
{
    // global namespace wrapper class
    class FannieAutoComplete extends \COREPOS\Fannie\API\webservices\FannieAutoComplete {}
}

