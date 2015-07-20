<?php
include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class DevelopersList extends FanniePage
{

    public $description = '[Developer Targets] is a bare list of member numbers
    in the developer target promo group.';

    public function body_content()
    {
        $dbc = $this->connection;
        $settings = $this->config->PLUGIN_SETTINGS;
        $dbc->selectDB($settings['TargetedPromosDB']);
        $model = new DeveloperTargetsModel($dbc);
        $model->addedDate('2015-07-20', '>');
        echo '<textarea>';
        foreach ($model->find() as $obj) {
            echo $obj->card_no() . "\n";
        }
        echo '</textarea>';
    }

    public function helpContent()
    {
        return '
            <p>
            Copy/paste this list into coupon
            mailing creation tools.
            </p>';
    }
}

FannieDispatch::conditionalExec();
