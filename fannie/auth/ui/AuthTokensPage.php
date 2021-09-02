<?php

use Ramsey\Uuid\Uuid;
use COREPOS\Fannie\API\lib\FannieUI;

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class AuthTokensPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $title = 'Fannie : Auth : Tokens';
    protected $header = 'Fannie : Auth : Tokens';

    public $description = "Generate tokens for alternate user authentication";

    protected function delete_id_handler()
    {
        $model = new UserTokensModel($this->connection);
        $model->token($this->id);
        $model->revoked(1);
        $model->save();

        return 'AuthTokensPage.php';
    }

    protected function put_handler()
    {
        $model = new UserTokensModel($this->connection);
        $model->username($this->current_user);
        $model->token(Uuid::uuid4());
        $model->save();

        return 'AuthTokensPage.php';
    }

    protected function get_view()
    {
        $model = new UserTokensModel($this->connection);
        $model->username($this->current_user);
        $model->revoked(0);
        $table = '';
        foreach ($model->find() as $obj) {
            $table .= sprintf('<tr><td>%s</td><td><a href="AuthTokensPage.php?_method=delete&id=%s">%s</td></tr>',
                $obj->token(), $obj->token(), FannieUI::deleteIcon());
        }

        return <<<HTML
<table class="table table-bordered table-striped">
<tr><th>Token</th><th>Revoke</th></tr>
    {$table}
</table>
<p>
    <a href="AuthTokensPage.php?_method=put" class="btn btn-default">Create Token</a>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

