<?php

namespace COREPOS\Fannie\API\item;

/**
  @class TagDataSource
  This class exists solely as a parent
  class that other modules can implement.
*/
class TagDataSource
{
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
        return array(
            'upc' => '',
            'description' => '',
            'brand' => '',
            'normal_price' => '',
            'sku' => '',
            'size' => '',
            'units' => '',
            'vendor' => '',
            'pricePerUnit' => '',
        );
    }
}

