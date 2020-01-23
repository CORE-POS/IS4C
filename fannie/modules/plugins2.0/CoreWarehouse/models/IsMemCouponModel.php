<?php

class IsMemCouponModel extends ViewModel
{
    protected $name = 'IsMemCoupon';
    protected $preferred_db = 'plugin:WarehouseDatabase';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)'),
    'memberOnly' => array('type'=>'INT'),
    );

    public function definition()
    {
        return "SELECT CONCAT('00499999', LPAD(coupID, 5, '0')) AS upc,
                memberOnly
            FROM " . FannieDB::fqn('houseCoupons', 'op');
    }
}

