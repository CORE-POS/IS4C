<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SearchComments extends FannieRESTfulPage
{
    protected $header = 'Comments';
    protected $title = 'Comments';
    protected $must_authenticate = true;

    protected function get_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['CommentDB'] . $this->connection->sep();
        $ret = $this->get_view() . '<hr />
            <p>Searching for: ' . $this->id . '</p>';

        $tagCandidates = explode(' ', $this->id);
        $tagCandidates = array_map('trim', $tagCandidates);
        list($inStr, $args) = $this->connection->safeInClause($tagCandidates);
        $tagsP = $this->connection->prepare("SELECT tag FROM {$prefix}CommentTags WHERE tag IN ({$inStr}) GROUP BY tag");
        $tags = $this->connection->getAllValues($tagsP, $args);
        if (count($tags) > 0) {
            $ret .= '<p>Tags: ';
            foreach ($tags as $t) {
                $ret .= sprintf('<a href="ManageComments.php?tag=%s">%s</a> ', $t, $t);
            }
            $ret .= '</p>';
        }

        $results = false;
        $table = '<table class="table table-bordered">';
        if (strlen($this->id) >= 3) {
            $searchP = $this->connection->prepare("
                SELECT c.commentID,
                    CASE WHEN c.categoryID=0 THEN 'n/a'
                        WHEN c.categoryID=-1 THEN 'Spam'
                        ELSE t.name 
                    END AS name,
                    c.comment,
                    c.tdate,
                    CASE WHEN r.commentID IS NULL THEN 0 ELSE 1 END AS responded
                FROM {$prefix}Comments AS c
                    LEFT JOIN {$prefix}Categories AS t ON t.categoryID=c.categoryID
                    LEFT JOIN {$prefix}Responses AS r ON r.commentID=c.commentID
                WHERE c.comment LIKE ? OR r.response LIKE ?
                ORDER BY c.commentID DESC");
            $searchR = $this->connection->execute($searchP, array('%' . $this->id . '%', '%' . $this->id . '%')); 
            $prevID = null;
            while ($row = $this->connection->fetchRow($searchR)) {
                if ($prevID == $row['commentID']) {
                    continue;
                }
                $prevID = $row['commentID'];
                $results = true;
                $table .= sprintf('<tr><td><a href="ManageComments.php?id=%d">%s</a></td>
                                <td>%s</td><td title="%s">%s</td></tr>',
                            $row['commentID'],
                            $row['tdate'],
                            $row['name'],
                            $row['comment'],
                            $this->boldWords(substr($row['comment'], 0, 100), $tagCandidates)
                );
            }
        }

        if ($results) {
            $ret .= $table . '</table>';
        } else {
            $ret .= '<p><em>No results found</em></p>';
        }

        return $ret;
    }

    private function boldWords($text, $words)
    {
        foreach ($words as $w) {
            $text = str_replace($w, '<b>' . $w . '</b>', $text);
        }

        return $text;
    }

    protected function get_view()
    {
        $this->addOnloadCommand("\$('#search-comments').focus();");
        return <<<HTML
<form method="get" action="SearchComments.php">
<p>
    <div class="input-group">
        <span class="input-group-addon">Search</span>
        <input type="text" name="id" id="search-comments" class="form-control" />
        <span class="input-group-btn">
            <button class="btn btn-default">Go</button>
        </span>
    </div>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

