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
        $this->__routes[] = 'post<extern>';
        $this->__routes[] = 'post<upc>';
        $this->__routes[] = 'get<init>';
        return parent::preprocess();
    }

    protected function post_upc_handler()
    {
        return $this->get_search_handler();
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

        return $search;
    }

    private function searchUPCs($search, $form)
    {
        if ($form->upcs !== '') {
            $upcs = explode("\n", $form->upcs);
            $upcs = array_map(function($i) {
                $i = str_replace(' ', '-', $i);
                if (preg_match('/\d-\d+-\d+-\d/', $i)) {
                    $ret = trim(str_replace('-', '', $i));
                    return substr($ret, 0, strlen($ret)-1);
                } else {
                    $i = str_replace('-', '', $i);
                }
                return $i;
            }, $upcs);
            $upcs = array_map(function($i){ return BarcodeLib::padUPC(trim($i)); }, $upcs);
            $search->args = array_merge($search->args, $upcs);
            $search->where .= ' AND p.upc IN (' . str_repeat('?,', count($upcs));
            $search->where = substr($search->where, 0, strlen($search->where)-1) . ')';
        }

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
        $dept1 = $form->deptStart;
        $dept2 = $form->deptEnd;
        if ($dept1 !== '' || $dept2 !== '') {
            // work with just one department field set
            if ($dept1 === '') {
                $dept1 = $dept2;
            } elseif ($dept2 === '') {
                $dept2 = $dept1;
            }
            $search->where .= ' AND p.department BETWEEN ? AND ? ';
            // add dept lower then higher
            $search->args[] = $dept1 < $dept2 ? $dept1 : $dept2;
            $search->args[] = $dept2 > $dept1 ? $dept2 : $dept1;
        }

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
        if ($form->serviceScale !== '') {
            $search->from .= ' INNER JOIN scaleItems AS h ON h.plu=p.upc ';
            $search->where = str_replace('p.modified', 'h.modified', $search->where);
        }

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
        if ($form->modDate !== '') {
            switch ($form->modOp) {
                case 'Modified Before':
                    $search = $this->modifiedBeforeAfter($search, $form, '<');
                    break;
                case 'Modified After':
                    $search = $this->modifiedBeforeAfter($search, $form, '>');
                    break;
                case 'Modified On':
                default:
                    $search = $this->modifiedOn($search, $form);
                    break;
            }
        }

        return $search;
    }

    private function modifiedOn($search, $form)
    {
        $search->where .= ' AND p.modified BETWEEN ? AND ? ';
        $search->args[] = $form->modDate . ' 00:00:00';
        $search->args[] = $form->modDate . ' 23:59:59';
        if (isset($form->serviceScale)) {
            $search->where = str_replace('p.modified', '(p.modified', $search->where)
                . ' OR h.modified BETWEEN ? AND ?) ';
            $search->args[] = $form->modDate . ' 00:00:00';
            $search->args[] = $form->modDate . ' 23:59:59';
        }

        return $search;
    }

    private function modifiedBeforeAfter($search, $form, $op)
    {
        $time = $op === '<' ? '00:00:00' : '23:59:59';
        $search->where .= ' AND p.modified ' . $op . ' ? ';
        $search->args[] = $form->modDate . ' ' . $time;
        if (isset($form->serviceScale)) {
            $search->where = str_replace('p.modified', '(p.modified', $search->where)
                . ' OR h.modified ' . $op . ' ?) ';
            $search->args[] = $form->modDate . ' ' . $time;
        }

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
        if ($form->price !== '') {
            $search = $this->numericComparison($search, $form->price_op, 'p.normal_price', $form->price);
        }

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
        if ($form->cost !== '') {
            $search = $this->numericComparison($search, $form->cost_op, 'p.cost', $form->cost);
        }

        return $search;
    }

    private function numericComparison($search, $op, $field, $value)
    {
        switch ($op) {
            case '=':
                $search->where .= ' AND ' . $field. ' = ? ';
                $search->args[] = $value;
                break;
            case '<':
                $search->where .= ' AND ' . $field. ' < ? ';
                $search->args[] = $value;
                break;
            case '>':
                $search->where .= ' AND ' . $field. ' > ? ';
                $search->args[] = $value;
                break;
        }

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
        if ($form->price_rule !== '') {
            if ($form->price_rule == -1) {
                $search->where .= ' AND p.price_rule_id <> 0 ';
            } elseif ($form->price_rule == 0) {
                $search->where .= ' AND p.price_rule_id = 0 ';
            } else {
                $search->from .= ' INNER JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID ';
                $search->where .= ' AND r.priceRuleTypeID=? ';
                $search->args[] = $form->price_rule;
            }
        }

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
        if ($form->tax !== '') {
            $search->where .= ' AND p.tax=? ';
            $search->args[] = $form->tax;
        }

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
        if ($form->local !== '') {
            $search->where .= ' AND p.local=? ';
            $search->args[] = $form->local;
        }

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
        if ($form->fs !== '') {
            $search->where .= ' AND p.foodstamp=? ';
            $search->args[] = $form->fs;
        }

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
        if ($form->in_use !== '') {
            $search->where .= ' AND p.inUse=? ';
            $search->args[] = $form->in_use;
        }

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
        if ($form->location !== '') {
            if ($form->location == '1') {
                $search->from .= ' INNER JOIN prodPhysicalLocation AS y ON p.upc=y.upc ';
            } else {
                $search->where .= ' AND p.upc NOT IN (SELECT upc FROM prodPhysicalLocation) ';
            }
        }

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
        if ($form->origin != 0) {
            $search->from .= ' INNER JOIN ProductOriginsMap AS g ON p.upc=g.upc ';
            $search->where .= ' AND (p.current_origin_id=? OR g.originID=?) ';
            $search->args[] = $form->origin;
            $search->args[] = $form->origin;
        }

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
                } elseif ($prev == 1 && $next == 1) {
                    $where .= ' AND (b.endDate < ' . $dbc->curdate() . ' OR b.startDate > ' . $dbc->curdate() . ') ';
                } elseif ($prev == 1) {
                    $where .= ' AND b.endDate < ' . $dbc->curdate();
                } elseif ($now == 1 && $next == 1) {
                    $where .= ' AND b.endDate >= ' . $dbc->curdate();
                } elseif ($now == 1) {
                    $where .= ' AND b.endDate >= ' . $dbc->curdate() . ' AND b.startDate <= ' . $dbc->curdate();
                } elseif ($next == 1) {
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
        if ($form->soldOp !== '') {
            $movementStart = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j')-$form->soldOp-1, date('Y')));
            $movementEnd = date('Y-m-d', strtotime('yesterday'));
            $dlog = DTransactionsModel::selectDlog($movementStart, $movementEnd);

            $args = array($movementStart.' 00:00:00', $movementEnd.' 23:59:59');
            list($upc_in, $args) = $this->connection->safeInClause(array_keys($items), $args);

            $query = "SELECT t.upc
                      FROM $dlog AS t
                      WHERE tdate BETWEEN ? AND ?
                        AND t.upc IN ($upc_in)
                        AND t.charflag <> 'SO'
                      GROUP BY t.upc
                      HAVING SUM(total) <> 0";
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
            try {
                $search = $this->$method($search, $form);
            } catch (Exception $ex) {}
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
                p.cost,
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
            try {
                $items = $this->$method($items, $form);
            } catch (Exception $ex) {}
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
        $ret .= '<table class="table search-table table-striped">';
        $ret .= '<thead><tr>
                <th><input type="checkbox" onchange="toggleAll(this, \'.upcCheckBox\');" /></th>
                <th>UPC</th><th>Brand</th><th>Desc</th><th>Super</th><th>Dept</th>
                <th>cost</th><th>Retail</th><th>On Sale</th><th>Sale</th>
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
                            $record['cost'],
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
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $url = $this->config->get('URL');
        $today = date('Y-m-d');

        $this->addScript('search.js');
        $this->addScript('autocomplete.js');
        $this->addOnloadCommand("bindAutoComplete('#brand-field', '../ws/', 'brand');\n");
        $this->addScript('../src/javascript/tablesorter/jquery.tablesorter.js');

        $model = new SuperDeptNamesModel($dbc);
        $superOpts = $model->toOptions(-1);

        $depts = $dbc->query('SELECT dept_no, dept_name FROM departments order by dept_no');
        $deptOpts = '';
        while ($row = $dbc->fetchRow($depts)) {
            $deptOpts .= sprintf('<option value="%d">%d %s</option>', $row['dept_no'], $row['dept_no'], $row['dept_name']);
        }

        $model = new VendorsModel($dbc);
        $vendorOpts = $model->toOptions(-999);

        $rule = new PriceRuleTypesModel($dbc);
        $ruleOpts = $rule->toOptions();

        $origins = $dbc->query('SELECT originID, shortName, local FROM origins ORDER BY shortName');
        $originOpts = $localOpts = '';
        while ($row = $dbc->fetchRow($origins)) {
            if ($row['local']) {
                $localOpts .= sprintf('<option value="%d">%s</option>', $row['originID'], $row['shortName']);
            } else {
                $originOpts .= sprintf('<option value="%d">%s</option>', $row['originID'], $row['shortName']);
            }
        }
        if ($localOpts === '') {
            $localOpts = '<option value="1">Yes</option>';
        }

        $model = new LikeCodesModel($dbc);
        $lcOpts = $model->toOptions();

        $model = new TaxRatesModel($dbc);
        $taxOpts = $model->toOptions();

        $model = new BatchTypeModel($dbc);
        $model->discType(0, '<>');
        $btOpts = $model->toOptions();

        return include(__DIR__ . '/search.template.html');
    }

    protected function post_extern_view()
    {
        $body = $this->get_view();
        ob_start();
        $this->get_search_handler();
        $results = ob_get_clean();
        $body .= '<div class="collapse" id="externResults">' . $results . '</div>';
        $this->addOnloadCommand("\$('#resultArea').html(\$('#externResults').html());\n");

        return $body;
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

