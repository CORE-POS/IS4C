<?php

use OTPHP\TOTP;
use Endroid\QrCode\QrCode;

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class AuthFactorPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $title = 'Fannie : Auth : Multi Factor';
    protected $header = 'Fannie : Auth : Multi Factor';

    public $description = "Manage multi-factor authentication options";

    public function preprocess()
    {
        $this->addRoute('get<start>', 'get<qr>');
        return parent::preprocess();
    }

    protected function post_handler()
    {
        $uri = FormLib::get('uri');
        $prep = $this->connection->prepare('
            UPDATE Users
            SET totpURL=?
            WHERE name=?');
        $this->connection->execute($prep, array($uri, $this->current_user));

        return 'AuthFactorPage.php';
    }

    protected function delete_handler()
    {
        $prep = $this->connection->prepare('
            UPDATE Users
            SET totpURL=NULL
            WHERE name=?');
        $this->connection->execute($prep, array($this->current_user));

        return 'AuthFactorPage.php';
    }

    protected function get_qr_handler()
    {
        header('Content-type: image/png');
        $tmp = explode('secret=', $this->qr);
        $qrCode = new QrCode();
        $qrCode
            ->setText($this->qr)
            ->setSize(400)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel($tmp[1])
            ->setLabelFontSize(10)
            ->render();
    }

    protected function get_start_view()
    {
        if (!class_exists('OTPHP\\TOTP')) {
            return '<div class="alert alert-danger">Sorry, TOTP support is not installed. It\'s provided via Composer.</div>';
        }
        $label = $this->getLabel();
        $otp = new TOTP($label);
        $url = $otp->getProvisioningUri();
        $encodeURL = urlencode($otp->getProvisioningUri());

        return <<<HTML
<form method="post" action="AuthFactorPage.php">
<p>
Scan this to add the account to your app / device.
</p>
<img src="AuthFactorPage.php?qr={$encodeURL}" />
<input type="hidden" name="uri" value="{$url}" />
<p>
After you've successfully added the account to your app / device,
click Enable to turn on two-factor authentication.
</p>
<p>
<button type="submit" class="btn btn-default btn-core">Enable</button>
</p>
</form>
HTML;
    }

    private function getLabel()
    {
        $label = 'CORE-POS';
        if ($this->config->get('COOP_ID')) {
            $label .= '/' . $this->config->get('COOP_ID');
        }

        return $label;
    }

    protected function get_view()
    {
        $userP = $this->connection->prepare('SELECT * FROM Users WHERE name=?');
        $user = $this->connection->getRow($userP, array($this->current_user));
        if (isset($user['totpURL']) && $user['totpURL']) {
            return $this->enabledMenu($user['totpURL']);
        }

        return $this->notEnabledMenu();
    }

    private function notEnabledMenu()
    {
        return <<<HTML
<p>
Two factor authentication is not currently enabled for your account.
To use this feature you'll need Google Authenticator or another TOTP-compatible
app or device.
</p>
<p>
<a href="AuthFactorPage.php?start=1" class="btn btn-default">Enable Two Factor Authentication</a>
</p>
HTML;
    }

    private function enabledMenu($uri)
    {
        $encoded = urlencode($uri);
        return <<<HTML
<p>
Two factor authentication is enabled for your account.
</p>
<p>
<a href="AuthFactorPage.php?qr={$encoded}" class="btn btn-default">View Your QR Code</a>
</p>
<p>
<a href="AuthFactorPage.php?_method=delete" class="btn btn-default btn-danger">Turn Off Two Factor Authentication</a>
</p>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->enabledMenu('http://example.com'));
        $phpunit->assertInternalType('string', $this->notEnabledMenu());
        $phpunit->assertInternalType('string', $this->get_view());
        $phpunit->assertInternalType('string', $this->getLabel());
        $this->start = 1;
        $phpunit->assertInternalType('string', $this->get_start_view());
        $this->current_user = 'test';
        $phpunit->assertEquals('AuthFactorPage.php', $this->post_handler());
        $phpunit->assertEquals('AuthFactorPage.php', $this->delete_handler());
    }
}

FannieDispatch::conditionalExec();

