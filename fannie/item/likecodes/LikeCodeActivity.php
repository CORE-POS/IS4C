<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeActivity extends FannieRESTfulPage
{
    protected $title = 'Like Code Activity';
    protected $header = 'Like Code Activity';
    public $description = '[Like Code Activity] shows usage and availability for likecodes.';

    protected function post_id_handler()
    {
        $model = new LikeCodesModel($this->connection);
        $model->likeCode($this->id);
        try {
            $model->likeCodeDesc($this->form->newName);
            $model->save();
        } catch (Exception $ex) {
        }

        return 'LikeCodeActivity.php';
    }

    protected function get_view()
    {
        $query = "
            SELECT l.likeCode,
                l.likeCodeDesc,
                COUNT(DISTINCT u.upc) AS items,
                MAX(p.last_sold) AS lastSold,
                MAX(p.created) AS lastAdded
            FROM likeCodes AS l
                LEFT JOIN upcLike AS u ON l.likeCode=u.likeCode
                LEFT JOIN products AS p ON p.upc=u.upc";
        $args = array();
        $query .= " GROUP BY l.likeCode, l.likeCodeDesc ORDER BY l.likeCode";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);

        $ret = '<table class="table table-bordered table-striped sortable">';
        $ret .= '<thead><tr style="background-color:#fff"><th>Like Code</th><th>Description</th><th># of Items</th>
            <th>Last Sold</th><th>Last Added</th></tr></thead><tbody>';
        $lastYear = strtotime('366 days ago');
        $prevCode = false;
        while ($row = $this->connection->fetchRow($res)) {
            if ($prevCode !== false && ($prevCode+1) != $row['likeCode'] && ($row['likeCode'] - $prevCode) < 25) {
                $prevCode++;
                while ($prevCode < $row['likeCode']) {
                    $ret .= '<tr class="success"><td>' . $prevCode . '</td><td>AVAILABLE</td><td colspan="3"></tr>';
                    $prevCode++;
                }
            }
            $sold = strtotime($row['lastSold']) !== false ? strtotime($row['lastSold']) : 0;
            $added = strtotime($row['lastAdded']) !== false ? strtotime($row['lastAdded']) : 0;
            $recent = $sold ? $sold : $added;
            $class = '';
            if ($row['items'] == 0) {
                $class = 'danger';
                $row['lastSold'] = sprintf('<form class="form-inline small" method="post">
                        <input type="hidden" name="id" value="%d" />
                        <input type="text" name="newName" class="form-control input-sm" />
                        <button type="submit" class="btn btn-default btn-sm">Rename</button>
                        </form>',
                        $row['likeCode']);
            } elseif ($recent < $lastYear) {
                $class = 'warning';
            }
            $ret .= sprintf('<tr class="%s"><td>%d</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>',
                $class, $row['likeCode'], $row['likeCodeDesc'], $row['items'], $row['lastSold'], $row['lastAdded']);
            $prevCode = $row['likeCode'];
        }
        $ret .= '</tbody></table>';

        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.js');
        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.widgets.js');
        $this->addScript('../../src/javascript/jquery.floatThead.min.js');
        $this->addOnloadCommand("\$.tablesorter.themes.bootstrap['active'] = 'info';");
        $this->addOnloadCommand("\$.tablesorter.themes.bootstrap['table'] += ' tablesorter-core table-condensed small';");
        $this->addOnloadCommand("\$.tablesorter.themes.bootstrap['header'] += ' table-condensed small';");
        $this->addOnloadCommand("\$('.sortable').tablesorter({sortList: [[0,0]], theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");
        $this->addOnloadCommand("\$('.sortable').floatThead();\n");

        return $ret;
    }
}

FannieDispatch::conditionalExec();

