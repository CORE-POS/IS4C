<?php

if (class_exists('Mailchimp')) {

class McListEx extends Mailchimp_Lists
{
    /**
     * Export all members for a list
     * @param string $id
     * @status string The subscription status for this email addresses, either , subscribed, unsubscribed, or cleaned
     *   default value is subscribed
     * @return array of member records. The first record lists the order of fields. Each
     *  subsequent record describes a member of the list
     */
    public function export($id, $status='subscribed') 
    {
        $_params = array("id" => $id, "status" => $status);
        $this->master->export_mode = true;
        $ret = $this->master->call('list/', $_params);
        $this->master->export_mode = false;

        return $ret;
    }
}

} else {

class McListEx {}

}
