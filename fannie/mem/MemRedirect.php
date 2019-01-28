<?php

include(__DIR__ . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

/**
 * Redirects requests to the configured member editing tool
 */
class MemRedirect extends FannieRESTfulPage
{
    protected function get_id_handler()
    {
        return $this->get_handler();
    }

    protected function get_handler()
    {
        $url = $this->config->get('MEMBER_URL');
        if ($url == '') {
            $url = 'mem/MemberEditor.php';
        }
        $param = $this->config->get('MEMBER_PARAM');
        if ($param == '') {
            $param = 'memNum';
        }

        if (strpos($url, '://') === false && $url[0] != '/') {
            $url = $this->config->get('URL') . $url;
        }

        $memID = FormLib::get($param);
        if ($memID) {
            $url .= '?' . $param . '=' . $memID;
        } elseif (isset($this->id)) {
            $url .= '?' . $param . '=' . $this->id;
        }

        return $url;
    }
}

FannieDispatch::conditionalExec();

