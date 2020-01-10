<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpGreensPreps extends FannieRESTfulPage
{
    protected $header = 'Greens & Preps';
    protected $title = 'Greens & Preps';

    protected function post_handler()
    {
        $greenP = $this->connection->prepare("INSERT INTO RpGreens VALUES (?)");
        $this->connection->query('TRUNCATE TABLE RpGreens');
        $this->connection->startTransaction();
        foreach (explode("\n", FormLib::get('greens')) as $lc) {
            $this->connection->execute($greenP, array(trim($lc)));
        }
        $this->connection->commitTransaction();

        $prepP = $this->connection->prepare("INSERT INTO RpPreps VALUES (?)");
        $this->connection->query('TRUNCATE TABLE RpPreps');
        $this->connection->startTransaction();
        foreach (explode("\n", FormLib::get('preps')) as $lc) {
            $this->connection->execute($prepP, array(trim($lc)));
        }
        $this->connection->commitTransaction();

        return 'RpDailyPage.php';
    }

    protected function get_view()
    {
        $gP = $this->connection->prepare("SELECT likeCode FROM RpGreens");
        $greens = $this->connection->getAllValues($gP, array());
        $greens = implode("\n", $greens);
        $pP = $this->connection->prepare("SELECT likeCode FROM RpPreps");
        $preps = $this->connection->getAllValues($pP, array());
        $preps = implode("\n", $preps);
        return <<<HTML
<form method="post" action="RpGreensPreps.php">
    <div class="row">
        <div class="col-sm-5">
            <h3>Greens LikeCodes</h3>
            <textarea rows="10" class="form-control" name="greens">{$greens}</textarea>
        </div>
        <div class="col-sm-5">
            <h3>Preps LikeCodes</h3>
            <textarea rows="10" class="form-control" name="preps">{$preps}</textarea>
        </div>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Save</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

