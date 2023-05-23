<?php

class MailChimpTask30 extends FannieTask
{
    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig(array(
            'apiKey' => $settings['MailChimpApiKey'],
            'server' => $settings['MailChimpPrefix'],
        ));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $res = $dbc->query("SELECT email_1 FROM meminfo AS m INNER JOIN custdata AS c
            ON m.card_no=c.CardNo AND c.personNum=1
            WHERE email_1 LIKE '%@%'");
        $accounts = array();
        while ($row = $dbc->fetchRow($res)) {
            $accounts[] = array(
                'email_address' => $row['email_1'],
                'status' => 'subscribed',
            );
            if (count($accounts) >= 450) {
                $resp = $client->lists->batchListMembers($settings['MailChimpListID'], array(
                    'members' => $accounts,
                    'sync_tags' => false,
                    'update_existing' => false,
                ));
                $accounts = array();
            }
        }
        if (count($accounts) > 0) {
            $resp = $client->lists->batchListMembers($settings['MailChimpListID'], array(
                'members' => $accounts,
                'sync_tags' => false,
                'update_existing' => false,
            ));
        }
    }
}
