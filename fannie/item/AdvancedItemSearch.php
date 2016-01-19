<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class AdvancedItemSearch extends FannieRESTfulPage
{
    protected $header = 'Advanced Search';
    protected $title = 'Advanced Search';

    public $description = '[Advanced Search] is a tool to look up items with lots of search options.';
    public $has_unit_tests = true;

    /**
      List of search methods. Search methods
      are used to compose a query's FROM
      and WHERE clauses as well as parameter
      list.
    */
    private $search_methods = array(
        'searchUPC',
        'searchUPCs',
        'searchDescription',
        'searchBrand',
        'searchSuperDepartment',
        'searchDepartments',
        'searchServiceScale',
        'searchModifiedDate',
        'searchVendor',
        'searchPrice',
        'searchCost',
        'searchPriceRule',
        'searchTax',
        'searchLocal',
        'searchFoodstamp',
        'searchInUse',
        'searchDiscountable',
        'searchLocation',
        'searchSignage',
        'searchOrigin',
        'searchLikeCode',
    );

    /**
      List of filter methods. Filter methods
      accept an array of items and add or remove
      items based on the filter's conditions.
    */
    private $filter_methods = array(
        'filterSales',
        'filterMovement',
        'filterSavedItems',
    );

    public function preprocess()
    {
        $this->__routes[] = 'get<search>';
        $this->__routes[] = 'post<search>';
        $this->__routes[] = 'post<upc>';
        $this->__routes[] = 'get<init>';
        return parent::preprocess();
    }

    // failover on ajax call
    // if javascript breaks somewhere and the form
    // winds up submitted, at least display the results
    private $post_results = '';
    protected function post_upc_handler()
    {
        ob_start();
        $this->get_search_handler();
        $this->post_results = ob_get_clean();

        return true;
    }

    // failover on ajax call
    protected function post_upc_view()
    {
        return $this->get_view() . $this->post_results;
    }

    protected function post_search_handler()
    {
        return $this->get_search_handler();
    }

    /**
      Search based on a UPC. Asterisk is treated as a wildcard. 
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchUPC($search, $form)
    {
        try {
            if ($form->upc !== '') {
                if (strstr($form->upc, '*')) {
                    $upc = str_replace('*', '%', $form->upc);
                    $search->where .= ' AND p.upc LIKE ? ';
                    $search->args[] = $upc;
                } elseif (substr(BarcodeLib::padUPC($form->upc), 0, 8) == '00499999') {
                    $couponID = (int)substr(BarcodeLib::padUPC($form->upc), 8);
                    $search->from .= ' LEFT JOIN houseCouponItems AS h ON p.upc=h.upc ';
                    $search->where .= ' AND h.coupID=? ';
                    $search->args[] = $couponID;
                } else {
                    $upc = str_pad($form->upc, 13, '0', STR_PAD_LEFT);
                    $search->where .= ' AND p.upc = ? ';
                    $search->args[] = $upc;
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    private function searchUPCs($search, $form)
    {
        try {
            if ($form->upcs !== '') {
                $upcs = explode("\n", $form->upcs);
                $upcs = array_map(function($i){ return BarcodeLib::padUPC(trim($i)); }, $upcs);
                $search->args = array_merge($search->args, $upcs);
                $search->where .= ' AND p.upc IN (' . str_repeat('?,', count($upcs));
                $search->where = substr($search->where, 0, strlen($search->where)-1) . ')';
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search based on item description. 
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchDescription($search, $form)
    {
        try {
            if ($form->description !== '') {
                if (isset($form->serviceScale)) {
                    $search->where .= ' AND (p.description LIKE ? OR h.itemdesc LIKE ?) ';
                    $search->args[] = '%' . $form->description . '%';
                    $search->args[] = '%' . $form->description . '%';
                } else {
                    $search->where .= ' AND p.description LIKE ? ';
                    $search->args[] = '%' . $form->description . '%';
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search based on item brand. Numeric values are treated as
      UPC prefixes where as non-numeric values are matched against
      brand names.
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchBrand($search, $form)
    {
        try {
            if ($form->brand !== '') {
                if (is_numeric($form->brand)) {
                    $search->where .= ' AND p.upc LIKE ? ';
                    $search->args[] = '%' . $form->brand . '%';
                } else {
                    $search->where .= ' AND (p.brand LIKE ? OR x.manufacturer LIKE ? OR v.brand LIKE ?) ';
                    $search->args[] = '%' . $form->brand . '%';
                    $search->args[] = '%' . $form->brand . '%';
                    $search->args[] = '%' . $form->brand . '%';
                    if (!strstr($search->from, 'prodExtra')) {
                        $search->from .= ' LEFT JOIN prodExtra AS x ON p.upc=x.upc ';
                    }
                    if (!strstr($search->from, 'vendorItems')) {
                        $search->from .= ' LEFT JOIN vendorItems AS v ON p.upc=v.upc ';
                    }
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search based on super department.
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchSuperDepartment($search, $form)
    {
        try {
            if ($form->superID !== '') {
                /**
                  Unroll superdepartment into a list of department
                  numbers so products.department index can be utilizied
                */
                $superP = $this->connection->prepare('
                    SELECT dept_ID
                    FROM superdepts
                    WHERE superID=?'
                );
                $superR = $this->connection->execute($superP, array($form->superID));
                if ($superR && $this->connection->numRows($superR) > 0) {
                    $search->where .= ' AND p.department IN (';
                    while ($superW = $this->connection->fetch_row($superR)) {
                        $search->where .= '?,';
                        $search->args[] = $superW['dept_ID'];
                    }
                    $search->where = substr($search->where, 0, strlen($search->where)-1) . ') ';
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search based on a department range. If only one value is
      specified it matches that single department.
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchDepartments($search, $form)
    {
        try {
            $dept1 = $form->deptStart;
            $dept2 = $form->deptEnd;
            if ($dept1 !== '' || $dept2 !== '') {
                // work with just one department field set
                if ($dept1 === '') {
                    $dept1 = $dept2;
                } else if ($dept2 === '') {
                    $dept2 = $dept1;
                }
                // swap order if needed
                if ($dept2 < $dept1) {
                    $tmp = $dept1;
                    $dept1 = $dept2;
                    $dept2 = $tmp;
                }
                $search->where .= ' AND p.department BETWEEN ? AND ? ';
                $search->args[] = $dept1;
                $search->args[] = $dept2;
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Checks where items exist in the scaleItems table
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchServiceScale($search, $form)
    {
        try {
            if ($form->serviceScale !== '') {
                $search->from .= ' INNER JOIN scaleItems AS h ON h.plu=p.upc ';
                $search->where = str_replace('p.modified', 'h.modified', $search->where);
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Checks whether the item's modified date is before, after,
      or exactly on the specified date. If service scale has been
      specified it checks both products.modified and scaleItems.modified
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchModifiedDate($search, $form)
    {
        try {
            if ($form->modDate !== '') {
                switch ($form->modOp) {
                case 'Modified Before':
                    $search->where .= ' AND p.modified < ? ';
                    $search->args[] = $form->modDate . ' 00:00:00';
                    if (isset($form->serviceScale)) {
                        $search->where = str_replace('p.modified', '(p.modified', $search->where)
                            . ' OR h.modified < ?) ';
                        $search->args[] = $form->modDate . ' 00:00:00';
                    }
                    break;
                case 'Modified After':
                    $search->where .= ' AND p.modified > ? ';
                    $search->args[] = $form->modDate . ' 23:59:59';
                    if (isset($form->serviceScale)) {
                        $search->where = str_replace('p.modified', '(p.modified', $search->where)
                            . ' OR h.modified > ?) ';
                        $search->args[] = $form->modDate . ' 23:59:59';
                    }
                    break;
                case 'Modified On':
                default:
                    $search->where .= ' AND p.modified BETWEEN ? AND ? ';
                    $search->args[] = $form->modDate . ' 00:00:00';
                    $search->args[] = $form->modDate . ' 23:59:59';
                    if (isset($form->serviceScale)) {
                        $search->where = str_replace('p.modified', '(p.modified', $search->where)
                            . ' OR h.modified BETWEEN ? AND ?) ';
                        $search->args[] = $form->modDate . ' 00:00:00';
                        $search->args[] = $form->modDate . ' 23:59:59';
                    }
                    break;
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search vendor by ID (as opposed to by name). 
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchVendor($search, $form)
    {
        try {
            if ($form->vendor === '0') {
                $search->where .= ' AND p.default_vendor_id=0 ';
            } elseif ($form->vendor !== '') {
                $search->where .= ' AND (v.vendorID=? or p.default_vendor_id=?)';
                $search->args[] = $form->vendor;
                $search->args[] = $form->vendor;
                if (!strstr($search->from, 'vendorItems')) {
                    $search->from .= ' LEFT JOIN vendorItems AS v ON p.upc=v.upc AND v.vendorID = p.default_vendor_id ';
                    /* May at some point want to support this less restrictive selection.
                     * $from .= ' LEFT JOIN vendorItems AS v ON p.upc=v.upc ';
                     */
                }

                if (isset($form->vendorSale)) {
                    $search->where .= ' AND v.saleCost <> 0 ';
                    $search->where .= ' AND p.default_vendor_id=? ';
                    $search->where .= ' AND p.default_vendor_id=v.vendorID ';
                    $search->args[] = $form->vendor;
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items with a price greater than, less than, or exactly
      equal to the specified value
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchPrice($search, $form)
    {
        try {
            if ($form->price !== '') {
                switch ($form->price_op) {
                    case '=':
                        $search->where .= ' AND p.normal_price = ? ';
                        $search->args[] = $form->price;
                        break;
                    case '<':
                        $search->where .= ' AND p.normal_price < ? ';
                        $search->args[] = $form->price;
                        break;
                    case '>':
                        $search->where .= ' AND p.normal_price > ? ';
                        $search->args[] = $form->price;
                        break;
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items with a cost greater than, less than, or exactly
      equal to the specified value
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchCost($search, $form)
    {
        try {
            if ($form->cost !== '') {
                switch ($form->cost_op) {
                    case '=':
                        $search->where .= ' AND p.cost = ? ';
                        $search->args[] = $form->cost;
                        break;
                    case '<':
                        $search->where .= ' AND p.cost < ? ';
                        $search->args[] = $form->cost;
                        break;
                    case '>':
                        $search->where .= ' AND p.cost > ? ';
                        $search->args[] = $form->cost;
                        break;
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that do or do not have a custom pricing rule
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchPriceRule($search, $form)
    {
        try {
            if ($form->price_rule !== '') {
                if ($form->price_rule == 1) {
                    $search->where .= ' AND p.price_rule_id <> 0 ';
                } elseif ($form->price_rule == 0) {
                    $search->where .= ' AND p.price_rule_id = 0 ';
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that have a given tax rate
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchTax($search, $form)
    {
        try {
            if ($form->tax !== '') {
                $search->where .= ' AND p.tax=? ';
                $search->args[] = $form->tax;
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that have a given local setting
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchLocal($search, $form)
    {
        try {
            if ($form->local !== '') {
                $search->where .= ' AND p.local=? ';
                $search->args[] = $form->local;
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that are or are not foodstamp eligible
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchFoodstamp($search, $form)
    {
        try {
            if ($form->fs !== '') {
                $search->where .= ' AND p.foodstamp=? ';
                $search->args[] = $form->fs;
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Restrict search to products.inUse=1
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchInUse($search, $form)
    {
        try {
            if ($form->in_use !== '') {
                $search->where .= ' AND p.inUse=? ';
                $search->args[] = $form->in_use;
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that are or are not discount eligible
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchDiscountable($search, $form)
    {
        try {
            if ($form->discountable !== '') {
                $search->where .= ' AND p.discount=? ';
                if ($form->discountable == 1 || $form->discountable == 2) {
                    $search->args[] = 1;
                } else {
                    $search->args[] = 0;
                }
                $search->where .= ' AND p.line_item_discountable=? ';
                if ($form->discountable == 1 || $form->discountable == 3) {
                    $search->args[] = 1;
                } else {
                    $search->args[] = 0;
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that do or do not have a basic physical location
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchLocation($search, $form)
    {
        try {
            if ($form->location !== '') {
                if ($form->location == '1') {
                    $search->from .= ' INNER JOIN prodPhysicalLocation AS y ON p.upc=y.upc ';
                } else {
                    $search->where .= ' AND p.upc NOT IN (SELECT upc FROM prodPhysicalLocation) ';
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that do or do not have signage fields populated
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchSignage($search, $form)
    {
        try {
            if ($form->signinfo !== '') {
                if (!strstr($search->from, 'productUser')) {
                    $search->from .= ' LEFT JOIN productUser AS s ON p.upc=s.upc ';
                }
                if ($form->signinfo == '1') {
                    $search->where .= " AND s.brand IS NOT NULL 
                        AND s.description IS NOT NULL
                        AND s.brand <> ''
                        AND s.description <> '' ";
                } else {
                    $search->where .= " AND (s.brand IS NULL 
                        OR s.description IS NULL
                        OR s.brand = ''
                        OR s.description = '') ";
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items that have a given origin ID
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchOrigin($search, $form)
    {
        try {
            if ($form->origin != 0) {
                $search->from .= ' INNER JOIN ProductOriginsMap AS g ON p.upc=g.upc ';
                $search->where .= ' AND (p.current_origin_id=? OR g.originID=?) ';
                $search->args[] = $form->origin;
                $search->args[] = $form->origin;
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Search items in a given like code. Special values ANY and NONE
      will search items that belong to any like code or do not belong
      to a like code, respectively.
      @param $search [Search Object] see runSearchMethods()
      @param $form [ValueContainer] representing submitted form values
      @return [Search Object] see runSearchMethods()
    */
    private function searchLikeCode($search, $form)
    {
        try {
            if ($form->likeCode !== '') {
                if (!strstr($search->from, 'upcLike')) {
                    $search->from .= ' LEFT JOIN upcLike AS u ON p.upc=u.upc ';
                }
                if ($form->likeCode == 'ANY') {
                    $search->where .= ' AND u.upc IS NOT NULL ';
                } else if ($form->likeCode == 'NONE') {
                    $search->where .= ' AND u.upc IS NULL ';
                } else {
                    $search->where .= ' AND u.likeCode=? ';
                    $search->args[] = $form->likeCode;
                }
            }
        } catch (Exception $ex) {}

        return $search;
    }

    /**
      Filters item list based on whether or not they
      are in a sale batch
      @param $items [array] of items keyed by UPC
      @param $form [ValueContainer] representing submitted form values
      @return [array] of items keyed by UPC
    */
    private function filterSales($items, $form)
    {
        try {
            if ($form->onsale !== '') {

                $where = '1=1';
                $args = array();
                $dbc = $this->connection;

                if ($form->saletype !== '') {
                    $where .= ' AND b.batchType = ? ';
                    $args[] = $form->saletype;
                }

                $all = isset($form->sale_all) ? 1 : 0;
                $prev = isset($form->sale_past) ? 1 : 0;
                $now = isset($form->sale_current) ? 1 : 0;
                $next = isset($form->sale_upcoming) ? 1 : 0;
                // all=1 or all three times = 1 means no date filter
                if ($all == 0 && ($prev == 0 || $now == 0 || $next == 0)) {
                    // all permutations where one of the times is zero
                    if ($prev == 1 && $now == 1) {
                        $where .= ' AND b.endDate <= ' . $dbc->curdate();
                    } else if ($prev == 1 && $next == 1) {
                        $where .= ' AND (b.endDate < ' . $dbc->curdate() . ' OR b.startDate > ' . $dbc->curdate() . ') ';
                    } else if ($prev == 1) {
                        $where .= ' AND b.endDate < ' . $dbc->curdate();
                    } else if ($now == 1 && $next == 1) {
                        $where .= ' AND b.endDate >= ' . $dbc->curdate();
                    } else if ($now == 1) {
                        $where .= ' AND b.endDate >= ' . $dbc->curdate() . ' AND b.startDate <= ' . $dbc->curdate();
                    } else if ($next == 1) {
                        $where .= ' AND b.startDate > ' .$dbc->curdate();
                    }
                }

                $query = 'SELECT l.upc FROM batchList AS l INNER JOIN batches AS b
                            ON b.batchID=l.batchID WHERE ' . $where . ' 
                            GROUP BY l.upc';
                $prep = $this->connection->prepare($query);
                $result = $this->connection->execute($prep, $args);
                $saleUPCs = array();
                while ($row = $this->connection->fetchRow($result)) {
                    $saleUPCs[] = $row['upc'];
                }

                if ($form->onsale == 0) {
                    // only items that are not selected sales
                    foreach($saleUPCs as $s_upc) {
                        if (isset($items[$s_upc])) {
                            unset($items[$s_upc]);
                        }
                    }
                } else {
                    // only items that are in selected sales
                    // collect items in both sets
                    $valid = array();
                    foreach($saleUPCs as $s_upc) {
                        if (isset($items[$s_upc])) {
                            $valid[$s_upc] = $items[$s_upc];
                        }
                    }
                    $items = $valid;
                }
            }
        } catch (Exception $ex) {}

        return $items;
    }

    /**
      Filters item list based on whether they
      sold within a given number of days
      @param $items [array] of items keyed by UPC
      @param $form [ValueContainer] representing submitted form values
      @return [array] of items keyed by UPC
    */
    private function filterMovement($items, $form)
    {
        try {
            if ($form->soldOp !== '') {
                $movementStart = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j')-$form->soldOp-1, date('Y')));
                $movementEnd = date('Y-m-d', strtotime('yesterday'));
                $dlog = DTransactionsModel::selectDlog($movementStart, $movementEnd);

                $args = array($movementStart.' 00:00:00', $movementEnd.' 23:59:59');
                $args = array_merge($args, array_keys($items));
                $upc_in = str_repeat('?,', count(array_keys($items)));
                $upc_in = substr($upc_in, 0, strlen($upc_in)-1);

                $query = "SELECT t.upc
                          FROM $dlog AS t
                          WHERE tdate BETWEEN ? AND ?
                            AND t.upc IN ($upc_in)
                          GROUP BY t.upc";
                $prep = $this->connection->prepare($query);
                $result = $this->connection->execute($prep, $args);
                $valid = array();
                while ($row = $this->connection->fetchRow($result)) {
                    if (isset($items[$row['upc']])) {
                        $valid[$row['upc']] = $items[$row['upc']];
                    }
                }
                $items = $valid;
            }
        } catch (Exception $ex) {}

        return $items;
    }

    /**
      This is really a reversed filter. It appends items that the user
      has checked in previous results onto the list of items. But since
      filters take and return item lists there was no need to create
      a separate construct.
      @param $items [array] of items keyed by UPC
      @param $form [ValueContainer] representing submitted form values
      @return [array] of items keyed by UPC
    */
    private function filterSavedItems($items, $form)
    {
        try {
            $savedItems = $form->u;
            if (is_array($savedItems) && count($savedItems) > 0) {
                $savedQ = '
                    SELECT p.upc, 
                        p.brand,
                        p.description, 
                        m.super_name, 
                        p.department, 
                        d.dept_name,
                        p.normal_price, p.special_price,
                        CASE WHEN p.discounttype > 0 THEN \'X\' ELSE \'-\' END as onSale,
                        1 as selected
                   FROM products AS p 
                       LEFT JOIN departments AS d ON p.department=d.dept_no
                       LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                   WHERE p.upc IN (';
                foreach ($savedItems as $item) {
                    $savedQ .= '?,';
                }
                $savedQ = substr($savedQ, 0, strlen($savedQ)-1);
                $savedQ .= ')';

                $savedP = $this->connection->prepare($savedQ);
                $savedR = $this->connection->execute($savedP, $savedItems);
                while ($savedW = $this->connection->fetchRow($savedR)) {
                    if (isset($items[$savedW['upc']])) {
                        $items[$savedW['upc']]['selected'] = 1;
                    } else {
                        $items[$savedW['upc']] = $savedW;
                    }
                }
            }
        } catch (Exception $ex) {}

        return $items;
    }

    /**
      Run all search methods to compose the inital SQL query
      Then execute the query and return the results
      @param $form [ValueContainer] representing submitted form values
      @return [array] of items keyed by UPC

      All search methods take a simple object as an argument
      and return an object with the same structure. The search 
      objects has these properties:
      * from - search methods may modify this by adding joins
      * where - search methods may modify this by adding conditional statements
      * args - joins and/or conditional statements that incoporate
               user-submitted form data should use prepared statement 
               placeholders and append the form values to the
               $search->args array.
    */
    private function runSearchMethods($form)
    {
        $search = new stdClass();
        $search->from = 'products AS p 
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID';
        $search->where = '1=1';
        $search->args = array();
        foreach ($this->search_methods as $method) {
            $search = $this->$method($search, $form);
        }

        if ($search->where == '1=1') {
            throw new Exception('Too many results');
        }

        $this->connection->selectDB($this->config->get('OP_DB'));

        $query = '
            SELECT p.upc, 
                p.brand,
                p.description, 
                m.super_name, 
                p.department, 
                d.dept_name,
                p.normal_price, 
                p.special_price,
                CASE WHEN p.discounttype > 0 THEN \'X\' ELSE \'-\' END as onSale,
                0 as selected
            FROM ' . $search->from . '
            WHERE ' . $search->where;
        $prep = $this->connection->prepare($query);
        $result = $this->connection->execute($prep, $search->args);

        $items = array();
        while ($row = $this->connection->fetchRow($result)) {
            $items[$row['upc']] = $row;
        }

        return $items;
    }

    /**
      Run all filter methods and return the filtered
      list of items
      @param $items [array] of items keyed by UPC
      @param $form [ValueContainer] representing submitted form values
      @return [array] of items keyed by UPC
    */
    private function runFilterMethods($items, $form)
    {
        foreach ($this->filter_methods as $method) {
            $items = $this->$method($items, $form);
        }
        
        return $items;
    }

    protected function get_search_handler()
    {
        try {
            $items = $this->runSearchMethods($this->form);
        } catch (Exception $ex) {
            echo $ex->getMessage();
            return false;
        }

        $items = $this->runFilterMethods($items, $this->form);

        if (count($items) > 5000) {
            echo 'Too many results';
            return false;
        }

        $dataStr = http_build_query($_GET);
        echo 'Found ' . count($items) . ' items';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<a href="AdvancedItemSearch.php?init=' . base64_encode($dataStr) . '">Permalink for this Search</a>';
        echo $this->streamOutput($items);

        return false;
    }

    protected function get_init_handler()
    {
        $vars = base64_decode($this->init);
        parse_str($vars, $data);
        foreach ($data as $field_name => $field_val) {
            $this->add_onload_command('$(\'#searchform :input[name="' . $field_name . '"]\').val(\'' . $field_val . '\');' . "\n");
            if ($field_val) {
                $this->add_onload_command('$(\'#searchform :input[name="' . $field_name . '"][type="checkbox"]\').prop(\'checked\', true);' . "\n");
            }
        }
        $this->add_onload_command('getResults();' . "\n");

        return true;
    }

    private function streamOutput($data) 
    {
        $ret = '';
        $ret .= '<table class="table search-table">';
        $ret .= '<thead><tr>
                <th><input type="checkbox" onchange="toggleAll(this, \'.upcCheckBox\');" /></th>
                <th>UPC</th><th>Brand</th><th>Desc</th><th>Super</th><th>Dept</th>
                <th>Retail</th><th>On Sale</th><th>Sale</th>
                </tr></thead><tbody>';
        foreach ($data as $upc => $record) {
            $ret .= sprintf('<tr>
                            <td><input type="checkbox" name="u[]" class="upcCheckBox" value="%s" %s 
                                onchange="checkedCount(\'#selection-counter\', \'.upcCheckBox\');" /></td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%d %s</td>
                            <td>$%.2f</td>
                            <td>%s</td>
                            <td>$%.2f</td>
                            </tr>', 
                            $upc, ($record['selected'] == 1 ? 'checked' : ''),
                            \COREPOS\Fannie\API\lib\FannieUI::itemEditorLink($upc),
                            $record['brand'],
                            $record['description'],
                            $record['super_name'],
                            $record['department'], $record['dept_name'],
                            $record['normal_price'],
                            $record['onSale'],
                            $record['special_price']
            );
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    protected function get_init_view()
    {
        return $this->get_view();
    }

    public function css_content()
    {
        return '
                .search-table thead th {
                    cursor: hand;
                    cursor: pointer;
                }
            ';
    }

    protected function get_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->addScript('search.js');

        $ret = '<div class="col-sm-10">';

        $ret .= '<form method="post" id="searchform" onsubmit="getResults(); return false;" onreset="formReset();">';

        $ret .= '<table class="table table-bordered">';
        $ret .= '<tr>';

        $ret .= '<td class="text-right">
            <label class="small control-label">
                <a href="" class="btn btn-default btn-xs"
                onclick="$(\'.upc-in\').toggle(); return false;">+</a>
                UPC
            </label>
            </td>
            <td>
                <textarea class="upc-in form-control input-sm collapse" name="upcs"></textarea>
                <input type="text" name="upc" class="upc-in form-control input-sm" 
                    placeholder="UPC or PLU" />
            </td>';

        $ret .= '<td class="text-right">
            <label class="control-label small">Descript.</label>
            </td>
            <td>
            <input type="text" name="description" class="form-control input-sm" 
                placeholder="Item Description" />
            </td>';

        $ret .= '<td class="text-right">
            <label class="control-label small">Brand</label>
            </td>
            <td>
            <input type="text" name="brand" class="form-control input-sm" 
                placeholder="Brand Name" id="brand-field" />
            </td>';

        $ret .= '</tr><tr>';

        $ret .= '<td class="text-right">
            <label class="control-label small">Super Dept</label>
            </td>
            <td>
            <select name="superID" class="form-control input-sm" onchange="chainSuper(this.value);" >
                <option value="">Select Super...</option>';
        $supers = $dbc->query('SELECT superID, super_name FROM superDeptNames order by superID');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['superID'], $row['super_name']);
        }
        $ret .= '</select></td>';

        $ret .= '<td class="text-right">
            <label class="control-label small">Dept Start</label>
            </td>
            <td>
            <select name="deptStart" id="dept-start" class="form-control input-sm">
                <option value="">Select Start...</option>';
        $supers = $dbc->query('SELECT dept_no, dept_name FROM departments order by dept_no');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['dept_no'], $row['dept_no'], $row['dept_name']);
        }
        $ret .= '</select></td>';

        $ret .= '<td class="text-right">
            <label class="control-label small">Dept End</label>
            </td>
            <td>
            <select name="deptEnd" id="dept-end" class="form-control input-sm">
                <option value="">Select End...</option>';
        $supers = $dbc->query('SELECT dept_no, dept_name FROM departments order by dept_no');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['dept_no'], $row['dept_no'], $row['dept_name']);
        }
        $ret .= '</select></td>';

        $ret .= '</tr><tr>'; // end row

        $ret .= '<td>
            <select class="form-control input-sm" name="modOp">
                <option>Modified On</option>
                <option>Modified Before</option>
                <option>Modified After</option>
            </select>
            </td>
            <td>
            <input type="text" name="modDate" id="modDate" class="form-control input-sm date-field" 
                    placeholder="Modified date" />
           </td>';

        $ret .= '<td class="text-right">
                <label class="control-label small">Movement</label>
                </td>
                <td>
                <select name="soldOp" class="form-control input-sm"><option value="">n/a</option><option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option><option value="90">Last 90 days</option></select>
                </td>';

        $ret .= '<td class="text-right">
                    <label class="control-label small">Vendor</label>
                 </td>
                 <td>
                    <select name="vendor" class="form-control input-sm"
                    onchange="if(this.value===\'\' || this.value===\'0\') $(\'#vendorSale\').attr(\'disabled\',\'disabled\'); else $(\'#vendorSale\').removeAttr(\'disabled\');" >
                    <option value="">Any</option>
                    <option value="0">Not Assigned</option>';
        $vendors = $dbc->query('SELECT vendorID, vendorName FROM vendors ORDER BY vendorName');
        while ($row = $dbc->fetch_row($vendors)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['vendorID'], $row['vendorName']);
        }
        $ret .= '</select></td>';

        $ret .= '</tr><tr>'; // end row

        $ret .= '
            <td class="text-right">
                <label class="control-label small">Price</label>
            </td>
            <td class="form-inline">
                <select name="price_op" class="form-control input-sm">
                    <option>=</option>
                    <option>&lt;</option>
                    <option>&gt;</option>
                </select>
                <input type="text" class="form-control input-sm price-field"
                    name="price" placeholder="$0.00" />
            </td>
            <td class="text-right">
                <label class="control-label small">Cost</label>
            </td>
            <td class="form-inline">
                <select name="cost_op" class="form-control input-sm">
                    <option>=</option>
                    <option>&lt;</option>
                    <option>&gt;</option>
                </select>
                <input type="text" class="form-control input-sm price-field"
                    name="cost" placeholder="$0.00" />
            </td>
            <td class="form-inline" colspan="2">
                <label class="control-label small">Pricing Rule</label>
                <select name="price_rule" class="form-control input-sm">
                    <option value="">Any</option>
                    <option value="0">Standard</option>
                    <option value="1">Variable</option>
                </select>
            </td>
            </td>';

        $ret .= '</tr><tr>'; // end row

        $ret .= '<td class="text-right">
            <label class="control-label small">Origin</label>
            </td>';
        $ret .= '<td>
            <select name="originID" class="form-control input-sm"><option value="0">Any Origin</option>';
        $origins = $dbc->query('SELECT originID, shortName FROM origins WHERE local=0 ORDER BY shortName');
        while($row = $dbc->fetch_row($origins)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['originID'], $row['shortName']);
        }
        $ret .= '</select></td>';

        $ret .= '<td class="text-right">
                <label class="control-label small">Likecode</label> 
                </td>';
        $ret .= '<td>
            <select name="likeCode" class="form-control input-sm"><option value="">Choose Like Code</option>
                <option value="ANY">In Any Likecode</option>
                <option value="NONE">Not in a Likecode</option>';
        $lcs = $dbc->query('SELECT likeCode, likeCodeDesc FROM likeCodes ORDER BY likeCode');
        while($row = $dbc->fetch_row($lcs)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['likeCode'], $row['likeCode'], $row['likeCodeDesc']);
        }
        $ret .= '</select></td>';

        $ret .= '<td colspan="2">
            <label class="small" for="vendorSale">
            On Vendor Sale
            <input type="checkbox" id="vendorSale" name="vendorSale" class="checkbox-inline" disabled />
            </label>';
        $ret .= ' | 
                <label class="small" for="in_use">
                InUse
                <input type="checkbox" name="in_use" id="in_use" value="1" checked class="checkbox-inline" />
                </label>
                </td>'; 

        $ret .= '</tr><tr>';

        $ret .= '<td colspan="2" class="form-inline">
            <div class="form-group">
            <label class="control-label small">Tax</label>
            <select name="tax" class="form-control input-sm"><option value="">Any</option><option value="0">NoTax</option>';
        $taxes = $dbc->query('SELECT id, description FROM taxrates');
        while($row = $dbc->fetch_row($taxes)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['id'], $row['description']);
        }
        $ret .= '</select></div>';

        $ret .= '&nbsp;&nbsp;
            <div class="form-group">
            <label class="control-label small">FS</label>
            <select name="fs" class="form-control input-sm">
            <option value="">Any</option><option value="1">Yes</option><option value="0">No</option></select>
            </div>
            </td>';

        $ret .= '<td colspan="2" class="form-inline">
            <div class="form-group">
            <label class="control-label small">Local</label>
            <select name="local" class="form-control input-sm"><option value="">Any</option><option value="0">No</option>';
        $origins = new OriginsModel($dbc);
        foreach ($origins->getLocalOrigins() as $originID => $shortName) {
            $ret .= sprintf('<option value="%d">%s</option>', $originID, $shortName);
        }
        $ret .= '</select></div> ';

        $ret .= '&nbsp;&nbsp;
            <div class="form-group">
            <label class="control-label small">%Disc</label>
            <select name="discountable" class="form-control input-sm">
                <option value="">Any</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
                <option value="2">Trans Only</option>
                <option value="3">Line Only</option>
            </select>
            </div>
            <label class="small" for="serviceScale">
            Service Scale
            <input type="checkbox" id="serviceScale" name="serviceScale" class="checkbox-inline" />
            </label>
            </td>';

        $ret .= '<td colspan="2" class="form-inline">
            <div class="form-group">
            <label class="control-label small">Location</label>
            <select name="location" class="form-control input-sm">
            <option value="">Any</option><option value="1">Yes</option><option value="0">No</option>
            </select>
            </div>';

        $ret .= '&nbsp;&nbsp;
            <div class="form-group">
            <label class="control-label small">Sign Info</label>
            <select name="signinfo" class="form-control input-sm">
            <option value="">Any</option><option value="1">Yes</option><option value="0">No</option>
            </select>
            </div>';

        $ret .= '</td>'; // end row

        $ret .= '</tr><tr>';

        $ret .= '<td colspan="6" class="form-inline">';

        $ret .= '
                <label class="control-label small">In Sale Batch</label>
                <select name="onsale" class="form-control input-sm"
                    onchange="if(this.value===\'\') $(\'.saleField\').attr(\'disabled\',\'disabled\'); else $(\'.saleField\').removeAttr(\'disabled\');" >
                    <option value="">Any</option>';
        $ret .= '<option value="1">Yes</option><option value="0">No</option>';
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;
            <label class="control-label small">Sale Type</label>
            <select disabled class="saleField form-control input-sm" name="saletype">
            <option value="">Any Sale Type</option>';
        $vendors = $dbc->query('SELECT batchTypeID, typeDesc FROM batchType WHERE discType <> 0');
        while($row = $dbc->fetch_row($vendors)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['batchTypeID'], $row['typeDesc']);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;
                <label class="small">
                All Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_all" id="sale_all" value="1" /> 
                </label> | ';
        $ret .= '<label class="small">
                Past Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_past" id="sale_past" value="1" /> 
                </label> | ';
        $ret .= '<label class="small">
                Current Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_current" id="sale_current" value="1" /> 
                </label> | ';
        $ret .= '<label class="small">
                Upcoming Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_upcoming" id="sale_upcoming" value="1" /> 
                </label>';
        $ret .= '</td>'; 

        $ret .= '</tr></table>';
        
        $ret .= '<button type="submit" class="btn btn-default btn-core">Find Items</button>';
        $ret .= '<button type="reset" class="btn btn-default btn-reset">Clear Settings</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<span id="selection-counter"></span>';
        $ret .= '</form>';

        $ret .= '<hr />';
        $ret .= '
            <div class="progress collapse">
                <div class="progress-bar progress-bar-striped active"  role="progressbar" 
                    aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                    <span class="sr-only">Searching</span>
                </div>
            </div>';
        $ret .= '<div id="resultArea"></div>';

        $ret .= '</div>'; // end col-sm-10

        $ret .= '<div class="col-sm-2">';
        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Selected Items</div>
            <div class="panel-body">';
        $ret .= '<p><button type="submit" class="btn btn-default btn-xs" 
            onclick="goToBatch();">Price or Sale Batch</button></p>';
        $ret .= '<p><button type="submit" class="btn btn-default btn-xs" 
            onclick="goToEdit();">Group Edit Items</button></p>';
        $ret .= '<p><button type="submit" class="btn btn-default btn-xs" 
            onclick="goToList();">Product List Tool</button></p>';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToSigns();">Tags/Signs</button></p>';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToMargins();">Margins</button></p>';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToCoupons();">Store Coupons</button></p>';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToSync();">Scale Sync</button></p>';
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Report on Items</div>
            <div class="panel-body">';
        $ret .= '<select id="reportURL" class="form-control input-sm">';
        $ret .= sprintf('<option value="%sreports/DepartmentMovement/SmartMovementReport.php?date1=%s&date2=%s&lookup-type=u">
                        Movement</option>', $FANNIE_URL, date('Y-m-d'), date('Y-m-d'));
        $ret .= sprintf('<option value="%sreports/from-search/PercentageOfSales/PercentageOfSalesReport.php">
                        %% of Sales</option>', $FANNIE_URL);
        $ret .= '</select> ';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToReport();">Get Report</button></p>';
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '<form method="post" id="actionForm" target="__advs_act"></form>';
        $ret .= '</div>';

        $this->add_script('autocomplete.js');
        $this->add_onload_command("bindAutoComplete('#brand-field', '../ws/', 'brand');\n");
        $this->addScript('../src/javascript/tablesorter/jquery.tablesorter.js');
        
        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Specify one or more search conditions to find a set
            of products in POS. Select one or more products in
            the list of results and click one of the right hand
            buttons to feed the select product(s) into different
            tools and reports. Star (*) is permitted as a wild
            card in text fields.
            </p>
            <p>
            Selected items are retained across searches and can
            be used to build larger sets of products. For example,
            you could run a search for items in department one
            with a description including "CAN", select a few products
            in the result set, then run a second search for items
            in department two with a description including
            "FROZEN". The second search results will include 
            products that match the second search <strong>and</strong>
            products that were selected in the first search.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $get = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($get));

        // search crafted to use as many methods as possible
        // and return a single result
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->upc = '*2';
        $form->description = 'CHER';
        $form->brand = '170';
        $form->superID = 2;
        $form->deptStart = 29;
        $form->deptEnd = 20;
        $form->vendor = '0'; // string type matters
        $form->price = 2;
        $form->price_op = '>';
        $form->cost = 20;
        $form->cost_op = '<';
        $form->price_rule = 0;
        $form->tax = 0;
        $form->fs = 1;
        $form->in_use = 1;
        $form->discountable = 1;
        $form->local = 0;
        $form->location = 0;
        $form->signinfo = 0;
        $form->likeCode = 'NONE';

        $items = $this->runSearchMethods($form);
        $phpunit->assertInternalType('array', $items);
        $phpunit->assertEquals(1, count($items));
        $phpunit->assertArrayHasKey('0001707710532', $items);

        // easiest filter to trigger is the saved items
        // sales or movement would require substantially more
        // sample data
        $form->u = array('0001707710532', '0001707710332', '0001707712132');
        $items = $this->runFilterMethods($items, $form);
        $phpunit->assertInternalType('array', $items);
        $phpunit->assertEquals(3, count($items));
    }

}

FannieDispatch::conditionalExec();

