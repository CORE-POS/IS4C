<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class QrTagPage extends FannieRESTfulPage
{
    protected function post_view()
    {
        $heading = FormLib::get('heading');
        $url = FormLib::get('url');
        return '<div style="border: solid 1px black; text-align: center; padding: 10px; width: 250px; background-color: #fff;">
            <h3>' . $heading . '</h3>
            <img src="noauto/qr.php?in=' . urlencode($url) . '" />
            </div>
            <br />';
    }

    protected function get_view()
    {
        return '<form method="post">
            <div class="form-group">
                <label>Heading</label>
                <input type="text" name="heading" class="form-control" />
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="text" name="url" class="form-control" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

