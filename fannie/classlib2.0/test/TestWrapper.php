<?php

namespace COREPOS\Fannie\API\test;

class TestWrapper
{
    public function __construct($con, $conf, $log)
    {
        $this->connection = $con;
        $this->config = $conf;
        $this->logger = $log;
    }

    protected function runRESTfulPage($page, $form)
    {
        $page->setForm($form);
        $pre = $page->preprocess();
        if ($pre === true) {
            return $page->bodyContent();
        } else {
            return $pre;
        }
    }
}

