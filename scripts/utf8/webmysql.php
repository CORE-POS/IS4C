<?php

$config = __DIR__ . '/../../fannie/config.php';
if (!file_exists($config)) {
    echo "Fannie config.php not found!\n";
    exit;
}

include($config);
include(__DIR__ . '/../../fannie/classlib2.0/FannieAPI.php');

class webmysql extends FannieRESTfulPage
{
    protected $header = 'UTF-8 Test';
    protected $title = 'UTF-8 Test';

    protected function post_view()
    {
        ob_start();

        if (isset($_POST['copy'])) {
            $input = $_POST['copy'];
            if (strlen($input) !== 2) {
                echo "<div style=\"color: red\">Error: input is not two bytes</div>";
            } elseif (ord($input[0]) != 0xc2 || ord($input[1]) != 0xa9) {
                echo "<div style=\"color: red\">Error: input is not UTF-8 encoded</div>";
            } else {
                echo "<div style=\"color: green\">Success! Input is 0xc2a9</div>";
            }

            echo "<div>Storing value in database</div>";

            $dbc = FannieDB::get($this->config->get('OP_DB'));
            $res = $dbc->setCharSet('utf-8');
            if ($dbc->tableExists('TestBytesCopyright')) {
                echo "[FAIL] Table already exists!\n";
                return ob_get_clean() . $this->get_view();
            }
            $res = $dbc->query("CREATE TABLE TestBytesCopyright (
                id INT,
                string CHAR(2),
                PRIMARY KEY (id)
                )
                CHARACTER SET utf8
                COLLATE utf8_general_ci");
            $prep = $dbc->prepare("INSERT INTO TestBytesCopyright (id, string) VALUES (1, ?)");
            $res = $dbc->execute($prep, array($input));
            $res = $dbc->query("SELECT string FROM TestBytesCopyright WHERE id=1");
            $row = $dbc->fetchRow($res);
            $output = $row[0];

            if (strlen($output) !== 2) {
                echo "<div style=\"color: red\">Error: database value is not two bytes</div>";
            } elseif (ord($input[0]) != 0xc2 || ord($input[1]) != 0xa9) {
                echo "<div style=\"color: red\">Error: database value is not UTF-8 encoded</div>";
            } else {
                echo "<div style=\"color: green\">Success! Database value is 0xc2a9</div>";
            }

            $res = $dbc->query("DROP TABLE TestBytesCopyright");
        }

        return ob_get_clean() . $this->get_view();
    }

    protected function get_view()
    {
        ob_start();
        $self = 'http://' . $_SERVER['SERVER_ADDR'] . str_replace('webmysql.php', 'chkmysql.php', $_SERVER['DOCUMENT_URI']);
        $headers = get_headers($self);
        echo '<h3>HTTP Headers</h3>';
        echo '<p>';
        foreach ($headers as $k => $v) {
            if (stristr($v, 'utf')) echo '<strong>';
            echo $v;
            if (stristr($v, 'utf')) echo '</strong>';
            echo '<br />';
        }
        echo '</p>';
        ?>
        <p>
        <form method="post" action="webmysql.php">
            Test input
            <input type="text" value="&copy;" name="copy" />
            <input type="submit" />
        </form>
        </p>
        <?php

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

