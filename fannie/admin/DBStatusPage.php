<?php

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class DBStatusPage extends FannieRESTfulPage
{
    public $description = '[DB Status] show the administrator what\'s executing on the database';
    protected $header = 'DB Status';
    protected $title = 'DB Status';
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    protected function delete_id_view()
    {
        $adapter = $this->connection->getAdapter($this->config->get('SERVER_DBMS'));
        try {
            $query = $adapter->kill($this->id);
            $res = $this->connection->query($query);
        } catch (Exception $ex) {
        }

        return 'DBStatusPage.php';
    }

    protected function get_view()
    {
        $adapter = $this->connection->getAdapter($this->config->get('SERVER_DBMS'));
        $query = $adapter->getProcessList();
        $res = $this->connection->query($query);

        $ret .= '<table class="table table-bordered table-striped">';
        $ret .= '<tr><th>ID</th><th>User</th><th>Host</th><th>Seconds</th><th>Status</th><th>Query</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%d</td><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>',
                $row['ID'], $row['USER'], $row['HOST'], $row['TIME'], $row['STATE'], $row['INFO']);
        }
        $ret .= '</table>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals('', $this->get_view());
    }
}

FannieDispatch::conditionalExec();

