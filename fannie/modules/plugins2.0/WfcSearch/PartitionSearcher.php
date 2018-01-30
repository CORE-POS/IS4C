<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class PartitionSearcher extends FannieRESTfulPage
{
    protected $title = 'Find stuff';
    protected $header = 'Find stuff';

    protected function get_id_handler()
    {
        $path = '/home/SHARE/MTM/';

        $cmd = 'find ' . $path . ' -type f';
        $search = trim($this->id);
        foreach (explode(' ', $search) as $term) {
            $term = trim($term);
            if (empty($term)) continue;
            $cmd .= ' | grep -i ' . escapeshellarg($term);
        }
        exec($cmd, $output);
        echo '<h3>Files</h3>';
        if (empty($output)) {
            echo 'No results';
        } else {
            foreach ($output as $line) {
                $base = basename($line);
                if ($base[0] == '.' || $base[0] == '~') continue;
                $rel = substr($line, 12);
                echo "<a href=\"noauto/{$rel}\">{$rel}</a><br />";
            }
        }

        /*
        $cmd = 'find ' . $path . ' -type f';
        $cmd .= ' | xargs grep -i ' . escapeshellarg($search);
        exec($cmd, $output);
        echo '<h3>Contents</h3>';
        if (empty($output)) {
            echo 'No results';
        } else {
            foreach ($output as $line) {
                $base = basename($line);
                if ($base[0] == '.' || $base[0] == '~') continue;
                echo $line . '<br />';
            }
        }
         */
    }

    protected function get_view()
    {
        return <<<HTML
<script type="text/javascript">
function runSearch() {
    var data = '&id=' + $('#searchInput').val();
    $.ajax({
        data: data
    }).success(function(resp) {
        $('#results').html(resp);
    });

    return false;
}
</script>
<div id="search">
    <p>
    <form class="form-inline" onsubmit="return runSearch();">
        <input type="text" class="form-control" name="id" id="searchInput" />
        <button type="submit" class="btn btn-default">Search</button>
    </form>
    </p>
</div>
<div id="results">
</div>
HTML;
    }
}

FannieDispatch::conditionalExec();

