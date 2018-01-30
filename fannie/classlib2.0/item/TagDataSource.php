<?php

namespace COREPOS\Fannie\API\item;
use COREPOS\Fannie\API\lib\PriceLib;

/**
  @class TagDataSource
  This class exists solely as a parent
  class that other modules can implement.
*/
class TagDataSource
{

    private static $lookupP = false;
    private static function getQuery($dbc)
    {
        if (self::$lookupP !== false) {
            return self::$lookupP;
        }

        $query = '
            SELECT p.upc,
                p.description,
                p.normal_price,
                p.brand,
                v.vendorName AS vendor,
                p.size AS p_size,
                p.unitofmeasure,
                i.sku,
                i.units,  
                i.size AS vi_size
            FROM products AS p
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN vendorItems AS i ON p.upc=i.upc AND v.vendorID=i.vendorID
            WHERE p.upc=?';
        self::$lookupP = $dbc->prepare($query);

        return self::$lookupP;
    }

    /** 
      Get shelf tag fields for a given item
      @param $dbc [SQLManager] database connection object
      @param $upc [string] Item UPC
      @param $price [optional, default false] use a specified price
        rather than the product's current price
      @return [keyed array] of tag data with the following keys:
        - upc
        - description
        - brand
        - normal_price
        - sku
        - size
        - units
        - vendor
        - pricePerUnit
    */
    public function getTagData($dbc, $upc, $price=false)
    {
        $query = self::getQuery($dbc);
        $ret = $dbc->getRow($query, array($upc));

        if ($price !== false) {
            $ret['normal_price'] = $price;
        }

        $ret = $this->fixupRow($ret);

        return $ret;
    }

    /** 
      Get shelf tag fields for several items
      @param $dbc [SQLManager] database connection object
      @param $upcs [array] Item UPC
      @return [array keyed by UPC] of tag data. Each record
        has the following keys:
        - upc
        - description
        - brand
        - normal_price
        - sku
        - size
        - units
        - vendor
        - pricePerUnit
    */
    public function getMany($dbc, $upcs)
    {
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $query = '
            SELECT p.upc,
                p.description,
                p.normal_price,
                p.brand,
                v.vendorName AS vendor,
                p.size AS p_size,
                p.unitofmeasure,
                i.sku,
                i.units,  
                i.size AS vi_size
            FROM products AS p
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN vendorItems AS i ON p.upc=i.upc AND v.vendorID=i.vendorID
            WHERE p.upc IN (' . $inStr . ')
            ORDER BY p.upc';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $ret = array();
        $prevUPC = false;
        while ($row = $dbc->fetchRow($res)) {
            if ($prevUPC != $row['upc']) {
                $row = $this->fixupRow($row);
                $ret[$row['upc']] = $row;
            }
            $prevUPC = $row['upc'];
        }

        return $upc;
    }

    private function fixupRow($ret)
    {
        $ret['size'] = '';
        if (is_numeric($ret['p_size']) && !empty($ret['p_size']) && !empty($ret['unitofmeasure'])) {
            $ret['size'] = $ret['p_size'] . ' ' . $ret['unitofmeasure'];
        } elseif (!empty($ret['p_size'])) {
            $ret['size'] = $ret['p_size'];
        } elseif (!empty($ret['vi_size'])) {
            $ret['size'] = $ret['vi_size'];
        }

        $ret['pricePerUnit'] = PriceLib::pricePerUnit($ret['normal_price'], $ret['size']);

        return $ret;
    }
}

