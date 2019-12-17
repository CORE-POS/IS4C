<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

/**
  @class HouseCouponEditor
*/
class DuplicateCoupon extends FannieRESTfulPage 
{

    public $discoverable = false;

    protected $must_authenticate = true;
    protected $auth_classes = array('tenders');

    protected $title = "Fannie :: House Coupons";
    protected $header = "Duplicate Coupon";

    protected function post_id_handler()
    {
        $name = FormLib::get('name');
        $start = FormLib::get('start');
        $end = FormLib::get('end');

        $nextP = $this->connection->prepare("SELECT MAX(coupID) FROM houseCoupons");
        $nextID = $this->connection->getValue($nextP) + 1;

        $model = new HouseCouponsModel($this->connection);
        $model->coupID($this->id);
        $model->load();
        $model->coupID($nextID);
        $model->description($name);
        $model->startDate($start);
        $model->endDate($end);
        $model->save();

        $itemP = $this->connection->prepare("
            INSERT INTO houseCouponItems (coupID, upc, type)
            SELECT {$nextID}, upc, type
            FROM houseCouponItems
            WHERE coupID=?");
        $this->connection->execute($itemP, array($this->id));

        return 'HouseCouponEditor.php?edit_id=' . $nextID;
    }

    protected function get_id_view()
    {
        $model = new HouseCouponsModel($this->connection);
        $model->coupID($this->id);
        $model->load();
        $obj = $model->toStdClass();

        return <<<HTML
<form method="post" action="DuplicateCoupon.php">
    <input type="hidden" name="id" value="{$this->id}" />
    <strong>Original Receipt Label</strong> {$obj->description}<br /> 
    <strong>Original Internal Label</strong> {$obj->label}<br /> 
    <strong>Original Summary</strong> {$obj->summary}<br /> 
    <br />
    <div class="form-group">
        <label>New Receipt Label</label>
        <input type="text" name="name" value="{$obj->description}" class="form-control" />
    </div>
    <div class="form-group">
        <label>New Start Date</label>
        <input type="text" name="start" class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>New End Date</label>
        <input type="text" name="end" class="form-control date-field" required />
    </div>
    <div class="form-group">
        <button class="btn btn-default" type="submit">Duplicate</button>
    </div>
</form>
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
Create a new copy of an existing coupon. The new coupon's receipt label,
start date, and end date will be the values specified here. All other settings
will be copied over from the original coupon.
HTML;
    }
}

FannieDispatch::conditionalExec();

