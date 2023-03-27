<?php

/**
  Class to manage order related notifications
*/
class OrderNotifications
{
    public function __construct($dbc)
    {
        $this->dbc = $dbc;
    }

    /**
      Send email if item has been marked as arrived
    */
    public function itemArrivedEmail($orderID, $transID)
    {
        $order = $this->getOrder($orderID);
        $items = $this->getItems($orderID, $transID);
        $ret = false;
        if (isset($items[0]) && $order->sendEmails()) {
            $formatted = $this->formatItems($items);
            $formatted['store'] = $this->getStore($orderID);
            $addr = $this->getAddress($order);
            $ret = $this->sendArrivedEmail($addr, $formatted, $orderID);
        }

        return $ret;
    }

    /**
      Send email if order has been marked as arrived
    */
    public function orderArrivedEmail($orderID)
    {
        $order = $this->getOrder($orderID);
        $items = $this->getItems($orderID);
        $ret = false;
        if ($order->statusFlag() == 5 && $order->sendEmails()) {
            $formatted = $this->formatItems($items);
            $formatted['store'] = $this->getStore($orderID);
            $addr = $this->getAddress($order);
            $ret = $this->sendArrivedEmail($addr, $formatted, $orderID);
        }

        return $ret;
    }

    public function orderTestEmail($orderID)
    {
        $order = $this->getOrder($orderID);
        $ret = false;
        $formatted = array(
            'text' => 'This is just a test message to verify delivery',
            'html' => 'This is just a test message to verify delivery',
        );
        $formatted['store'] = $this->getStore($orderID);
        $addr = $this->getAddress($order);
        $ret = $this->sendArrivedEmail($addr, $formatted, $orderID);

        return $ret;
    }

    public function sendGenericMessage($orderID, $msg)
    {
        $order = $this->getOrder($orderID);
        $ret = false;
        $formatted = array(
            'text' => $msg,
            'html' => $msg,
        );
        $formatted['store'] = $this->getStore($orderID);
        $addr = $this->getAddress($order);
        $ret = $this->sendArrivedEmail($addr, $formatted, $orderID, true);

        return $ret;
    }

    private function getAddress($order)
    {
        switch ($order->sendEmails()) {
            case 1:
                return $order->email();
            case 2:
                return preg_replace('/[^0-9]/', '', $order->phone()) . '@mms.att.net';
            case 3:
                return preg_replace('/[^0-9]/', '', $order->phone()) . '@pm.sprint.com';
            case 4:
                return preg_replace('/[^0-9]/', '', $order->phone()) . '@tmomail.net';
            case 5:
                return preg_replace('/[^0-9]/', '', $order->phone()) . '@vzwpix.com';
            case 6:
                return preg_replace('/[^0-9]/', '', $order->phone()) . '@msg.fi.google.com';
            case 7:
                return preg_replace('/[^0-9]/', '', $order->phone());
            default:
                return false;
        }
    }

    /**
      Actually send the email. Requires Scheduled Emails plugin
    */
    private function sendArrivedEmail($addr, $items, $orderID, $altTemplate=false)
    {
        $ret = false;
        if (class_exists('ScheduledEmailSendTask')) {
            $config = FannieConfig::factory();
            $settings = $config->get('PLUGIN_SETTINGS');
            $dbc = $this->dbc;
            $dbc->selectDB($settings['ScheduledEmailDB']);
            $template = new ScheduledEmailTemplatesModel($dbc);
            $template->scheduledEmailTemplateID($config->get('SO_TEMPLATE'));
            if ($altTemplate) {
                $template->scheduledEmailTemplateID($config->get('SO_TEMPLATE') + 1);
            }
            $template->load();
            $msg = $template->textCopy();
            foreach ($items as $name => $value) {
                $msg = str_replace('{{' . $name . '}}', $value, $msg);
            }
            if (preg_match('/^[0-9]+$/', $addr)) { // send text message
                $sns = new COREPOS\Fannie\Plugin\AWS\SNS($config);
                $ret = $sns->sendSMS($addr, $msg);
            } else {
                $ret = ScheduledEmailSendTask::sendEmail($template, $addr, $items);
            }
            $dbc->selectDB($config->get('TRANS_DB'));
            $model = new SpecialOrderCommLogModel($dbc);
            $model->specialOrderID($orderID);
            $model->tdate(date('Y-m-d H:i:s'));
            $model->channel($addr);
            $model->message($msg);
            $model->save();
        }

        return $ret;
    }

    /**
      Convert item information array(s) to string
    */
    public function formatItems($items)
    {
        $ret = array('text'=>'', 'html'=>'');
        foreach ($items as $item) {
            $ret['text'] .= sprintf('%s: %d case%s of %.2f, $%.2f',
                $item['description'],
                $item['ItemQtty'],
                $item['ItemQtty'] > 1 ? 's' : '',
                $item['quantity'],
                $item['total']) . "\n";
        }
        $ret['html'] = nl2br($ret['text']);

        return $ret;
    }

    public function getStore($orderID)
    {
        $config = FannieConfig::factory();
        $query = '
            SELECT s.description
            FROM ' . FannieDB::fqn('SpecialOrders', 'trans') . ' AS o
                INNER JOIN ' . $config->get('OP_DB') . $this->dbc->sep() . 'Stores AS s
                    ON s.storeID=o.storeID
            WHERE o.specialOrderID=?';
        $prep = $this->dbc->prepare($query);
        return $this->dbc->getValue($prep, array($orderID));
    }

    /**
      Get item data from an order
    */
    private function getItems($orderID, $transID=false)
    {
        $query = '
            SELECT description,
                quantity,
                ItemQtty,
                total,
                staff
            FROM PendingSpecialOrder
            WHERE order_id=?
                AND trans_id > 0
                AND deleted=0';
        $args = array($orderID);
        if ($transID) {
            $query .= ' AND trans_id=?';
            $args[] = $transID;
        }
        $prep = $this->dbc->prepare($query);
        $res = $this->dbc->execute($prep, $args);
        $ret = array();
        while ($row = $this->dbc->fetchRow($res)) {
            $ret[] = $row;
        }

        return $ret;
    }

    /**
      Get an order model
    */
    private function getOrder($orderID)
    {
        $order = new SpecialOrdersModel($this->dbc);
        $order->specialOrderID($orderID);
        $order->load();

        return $order;
    }
}

