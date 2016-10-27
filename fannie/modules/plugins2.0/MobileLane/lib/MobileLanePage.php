<?php

class MobileLanePage extends FannieRESTfulPage
{
    public function getHeader()
    {
        $this->addJQuery();
        $this->addBootstrap();
        $url = $this->config->get('URL');
        $this->addScript($url . 'src/javascript/linea/cordova-2.2.0.js');
        $this->addScript($url . 'src/javascript/linea/ScannerLib-Linea-2.0.0.js');
        $bcss = array();
        if (file_exists(__DIR__ . '/../../../../' . 'src/javascript/composer-components/bootstrap/js/bootstrap.min.js')) {
            $bcss = array(
                $url . 'src/javascript/composer-components/bootstrap/css/bootstrap.min.css',
                $url . 'src/javascript/composer-components/bootstrap-default/css/bootstrap.min.css',
                $url . 'src/javascript/composer-components/bootstrap-default/css/bootstrap-theme.min.css',
            );
        } elseif (file_exists(__DIR__ . '/../../../../' . 'src/javascript/bootstrap/js/bootstrap.min.js')) {
            $bcss = array(
                $url . 'src/javascript/bootstrap/css/bootstrap.min.css',
                $url . 'src/javascript/bootstrap-default/css/bootstrap.min.css',
                $url . 'src/javascript/bootstrap-default/css/bootstrap-theme.min.css',
            );
        }

        $css = array_reduce($bcss, function($c, $i) {
            return sprintf('%s<link rel="stylesheet" type="text/css" href="%s">', $c, $i);
        });

        return <<<HTML
<!doctype html>
<html>
<head>
    <title>MobileLane</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {$css}
</head>
<body>
<div class="container">
HTML;
    }

    public function getFooter()
    {
        return <<<HTML
</div>
HTML;
    }
}

