<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CommentDashboard extends FannieRESTfulPage
{
    protected $window_dressing = false;
    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $period = date('Y-m-d');

        $query = "SELECT t.name,
                count(*) AS ttl
            FROM Comments AS c
                INNER JOIN Categories AS t ON c.categoryID=t.categoryID
            WHERE c.categoryID > 0
                AND c.commentID NOT IN (
                    SELECT commentID FROM Responses GROUP BY commentID
                )
            GROUP BY t.name
            ORDER BY t.name";
        $res = $this->connection->query($query);
        $open = array('labels'=> array(), 'bars'=>array());
        while ($row = $this->connection->fetchRow($res)) {
            $open['labels'][] = $row['name'];
            $open['bars'][] = $row['ttl'];
        }
        $barData = json_encode($open);

        $this->addScript($this->config->get('URL') . 'src/javascript/jquery.js');
        $this->addScript($this->config->get('URL') . 'src/javascript/Chart.min.js');
        $this->addScript('dashboard.js');
        $this->addOnloadCommand("dashboard.openGraph({$barData});");
        //$this->addOnloadCommand("setTimeout('location.reload()', 30000);");

        return <<<HTML
<html>
    <head>
        <title>Comments Dashboard</title>
    </head>
    <body>
        <div style="width:90%">
            <canvas id="openBar"></canvas>
        </div>
    </body>
</html>
HTML;
    }
}

FannieDispatch::conditionalExec();

