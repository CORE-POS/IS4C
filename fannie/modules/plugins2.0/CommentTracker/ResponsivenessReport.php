<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class ResponsivenessReport extends FannieReportPage
{
    protected $header = 'Comment Responsiveness';
    protected $title = 'Comment Responsiveness';
    protected $must_authenticate = true;
    protected $new_tablesorter = true;

    protected $report_headers = array('Category', '# of Comments', '# of Responses', '% Responded', 'Avg Response Time (Hours)');

    public function report_description_content()
    {
        if (FormLib::get('all', false) === false) {
            return array(
                'Viewing Since ' . date('F j, Y', strtotime('30 days ago')) . '<br />',
                '<a href="ResponsivenessReport.php?all=1">Switch to All Time</a>',
            );
        }
        return array(
            'Viewing All Time <br />',
            '<a href="ResponsivenessReport.php">Switch to Last 30 Days</a>',
        );
    }

    public function fetch_report_data()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['CommentDB'] . $this->connection->sep();

        $args = array();
        $where = '';
        if (FormLib::get('all', false) === false) {
            $args = date('Y-m-d', strtotime('30 days ago'));
            $where = 'WHERE c.tdate >= ?';
        }

        $query = "SELECT g.name,
                COUNT(*) AS totalComments,
                SUM(CASE WHEN r.commentID IS NOT NULL THEN 1 ELSE 0 END) as totalResponses,
                AVG(" . $this->connection->seconddiff('c.tdate', 'r.tdate') . ") AS avgSeconds
            FROM {$prefix}Comments AS c
                INNER JOIN {$prefix}Categories AS g ON c.categoryID=g.categoryID
                LEFT JOIN {$prefix}FirstResponses AS r ON c.commentID=r.commentID
            {$where}
            GROUP BY g.name";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $record = array(
                $row['name'],
                $row['totalComments'],
                $row['totalResponses'],
                sprintf('%.2f', $row['totalResponses'] == 0 ? 0 : $row['totalResponses'] / $row['totalComments'] * 100),
                sprintf('%.2f', $row['avgSeconds'] / 3600),
            );
            $data[] = $record;
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

