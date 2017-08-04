<?php

namespace COREPOS\Fannie\API\auth;

class Notifications
{
    private $dbc;
    private $config;
    public function __construct($dbc, $config)
    {
        $this->dbc = $dbc;
        $this->config = $config;
    }

    public function setMessage($uid, $msgID, $msg, $url)
    {
        $msg = new UserMessagesModel($this->dbc);
        $msg->messageKey($msgID);
        $msg->message($msg);
        $msg->url($url);

        return $msg->save() ? true : false;
    }

    public function clearMessage($uid, $msgID)
    {
        $prep = $this->dbc->prepare("DELETE FROM UserMessages WHERE userID=? AND messageKey=?");
        $res = $this->dbc->execute($prep, array($uid, $msgID));

        return $ret ? true : false;
    }

    public function getMessages($uid)
    {
        $prefix = $this->config->get('OP_DB') . $this->dbc->sep();
        $prep = $this->dbc->prepare("SELECT message, url FROM {$prefix}UserMessages WHERE userID=?");
        $res = $this->dbc->execute($prep, array($uid));
        $ret = array();
        while ($row = $this->dbc->fetchRow($res)) {
            $ret[] = $row;
        }

        return $ret;
    }
}

