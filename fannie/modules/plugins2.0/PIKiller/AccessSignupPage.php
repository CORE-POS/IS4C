<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('AccessDiscountsModel')) {
    include(__DIR__ . '/models/AccessDiscountsModel.php');
}

class AccessSignupPage extends FannieRESTfulPage
{
    protected $header = 'Access Signup';
    protected $title = 'Access Signup';
    public $disoverable = false;
    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers', 'editmembers_csc');

    public function preprocess()
    {
        $this->addRoute('get<complete>');
        return parent::preprocess();
    }

    protected function get_id_handler()
    {
        $prep = $this->connection->prepare("SELECT memType, FirstName, LastName
            FROM custdata
            WHERE CardNo=? AND personNum=1");
        $row = $this->connection->getRow($prep, array($this->id));
        if (!is_array($row)) {
            echo json_encode(array(
                'error' => 1,
                'msg' => '<div class="alert alert-danger">Account not found</div>',
            ));
            return false;
        }
        if ($row['memType'] == 3 || $row['memType'] == 9) {
            echo json_encode(array(
                'error' => 1,
                'msg' => '<div class="alert alert-danger">Staff account not eligible</div>',
            ));
            return false;
        } elseif ($row['memType'] == 2) {
            echo json_encode(array(
                'error' => 1,
                'msg' => '<div class="alert alert-danger">Business account not eligible</div>',
            ));
            return false;
        } elseif ($row['memType'] == 4) {
            echo json_encode(array(
                'error' => 1,
                'msg' => '<div class="alert alert-danger">Nabs account not eligible</div>',
            ));
            return false;
        } elseif ($row['memType'] == 0) {
            echo json_encode(array(
                'error' => 1,
                'msg' => '<div class="alert alert-danger">Non-owner account not eligible</div>',
            ));
            return false;
        }

        echo json_encode(array(
            'error' => 0,
            'msg' => '<div class="alert alert-success">' . $row['FirstName'] . ' ' . $row['LastName'] . '</div>',
        ));
        return false;
    }

    protected function post_id_handler()
    {
        $numflag = FormLib::get('program');
        $tNo = DTrans::getTransNo($this->connection);
        DTrans::addItem($this->connection, $tNo, array(
            'card_no' => $this->id,
            'numflag' => $numflag,
            'upc' => 'ACCESS',
            'description' => 'ACCESS SIGNUP',
            'quantity' => 1,
            'ItemQtty' => 1,
            'mixMatch' => substr($this->current_user, 0, 13),
        ));

        $account = \COREPOS\Fannie\API\member\MemberREST::get($this->id);
        $account['customerTypeID'] = 5;
        for ($i=0; $i<count($account['customers']); $i++) {
            $account['customers'][$i]['discount'] = 10;
        }
        $resp = \COREPOS\Fannie\API\member\MemberREST::post($this->id, $account);

        $callbacks = FannieConfig::config('MEMBER_CALLBACKS');
        foreach ($callbacks as $cb) {
            $obj = new $cb();
            $obj->run($this->id);
        }

        $model = new AccessDiscountsModel($this->connection);
        $model->cardNo($this->id);
        $model->lastRenewal(date('Y-m-d H:i:s'));
        $model->expires(date('Y-m-d', mktime(0,0,0,date('n')+2,date('j'),date('Y')+1)));
        $model->userID(FannieAuth::getUID($this->current_user));
        $model->programID($numflag);
        $model->renewerName(FormLib::get('name'));
        $model->notes(FormLib::get('notes'));
        $model->save();

        return 'AccessSignupPage.php?complete=' . $this->id;
    }

    protected function get_complete_view()
    {
        return '<div class="alert alert-success">Owner #' . $this->complete . ' signed up for Access Discount</div>'
            . $this->get_view();
    }

    protected function get_view()
    {

        return <<<HTML
<form method="post" action="AccessSignupPage.php">
<div class="form-group">
    <label>Owner #</label>
    <input type="text" name="id" id="id" class="form-control" required 
        onchange="lookupMember(this.value);" />
</div>
<div id="msgs"></div>
<div class="form-group">
    <label>Program</label>
    <select name="program" class="form-control" required>
        <option value="">Select one</option>
        <option value="1">Emergency Assistance Program</option>
        <option value="2">Energy Assistance Program</option>
        <option value="3">Medicaid</option>
        <option value="4">Section 8</option>
        <option value="5">School Meal Program</option>
        <option value="6">SNAP</option>
        <option value="7">SSDI or RSDI</option>
        <option value="8">WIC</option>
    </select>
</div>
<div class="form-group">
    <label>Your Name</label>
    <input type="text" name="name" class="form-control" />
</div>
<div class="form-group">
    <textarea rows="4" class="form-control" name="notes"></textarea>
</div>
<div class="form-group collapse" id="submitBtn">
    <button type="submit" class="btn btn-default">Sign Up</button>
</div>
<script>
function lookupMember(id) {
    $.ajax({
        method: 'get',
        data: 'id=' + id,
        dataType: 'json'
    }).done(function (resp) {
        $('#msgs').html(resp.msg);
        if (resp.error) {
            $('#submitBtn').hide();
        } else {
            $('#submitBtn').show();
        }
    }).fail(function () {
        $('#msgs').html('<div class="alert alert-danger">Cannot lookup account</div>');
    });
}
</script>
HTML;
    }
}

FannieDispatch::conditionalExec();

