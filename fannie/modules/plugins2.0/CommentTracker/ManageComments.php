<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('CommentsModel')) {
    include(__DIR__ . '/models/CommentsModel.php');
}
if (!class_exists('CategoriesModel')) {
    include(__DIR__ . '/models/CategoriesModel.php');
}
if (!class_exists('ResponsesModel')) {
    include(__DIR__ . '/models/ResponsesModel.php');
}
if (!class_exists('CannedResponsesModel')) {
    include(__DIR__ . '/models/CannedResponsesModel.php');
}

class ManageComments extends FannieRESTfulPage
{
    protected $header = 'Comments';
    protected $title = 'Comments';
    protected $must_authenticate = true;

    public function preprocess()
    {
        $this->addRoute(
            'post<id><catID>',
            'post<id><appropriate>',
            'post<id><pnn>',
            'post<id><tags>',
            'get<new>',
            'post<new>',
            'get<canned>'
        );

        return parent::preprocess();
    }

    protected function post_id_catID_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $comment = new CommentsModel($this->connection);
        $comment->commentID($this->id);
        $comment->categoryID($this->catID);
        $comment->save();

        echo 'OK';

        return false;
    }

    /**
     * Save tags from a comma separated list
     * If the list is empty, just delete all tags
     * Otherwise:
     *  1. Delete tags that are not in the current list
     *  2. Add new tags if they don't exist. Intent is to
     *     prevent PK churn from deleting all tags and
     *     re-adding many of the same ones
     */
    protected function post_id_tags_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $tags = trim($this->tags);
        $tags = explode(',', $tags);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags, function ($t) { return $t !== ''; });
        $tags = array_map('strtolower', $tags);
        if (count($tags) == 0) {
            $prep = $this->connection->prepare('DELETE FROM CommentTags WHERE commentID=?');
            $res = $this->connection->execute($prep, array($this->id));
        } else {
            $this->connection->startTransaction();

            list($inStr, $args) = $this->connection->safeInClause($tags);
            $delP = $this->connection->prepare("DELETE FROM CommentTags WHERE tag NOT IN ({$inStr}) AND commentID=?");
            $args[] = $this->id;
            $this->connection->execute($delP, $args);

            $chkP = $this->connection->prepare('SELECT commentTagID FROM CommentTags WHERE tag=? AND commentID=?');
            $insP = $this->connection->prepare('INSERT INTO CommentTags (tag, commentID) VALUES (?, ?)');
            foreach ($tags as $t) {
                $args = array($t, $this->id);
                if ($this->connection->getValue($chkP, $args) == false) {
                    $this->connection->execute($insP, $args);
                }
            }

            $this->connection->commitTransaction();
        }

        $tags = array_map(function($t) { return "<a href=\"ManageTags.php?tag={$t}\">{$t}</a>"; }, $tags);
        $tags = implode(' ', $tags);

        echo $tags;

        return false;
    }

    protected function post_id_pnn_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $comment = new CommentsModel($this->connection);
        $comment->commentID($this->id);
        $comment->posNeg($this->pnn);
        $comment->save();

        echo 'OK';

        return false;

    }

    protected function post_id_appropriate_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $comment = new CommentsModel($this->connection);
        $comment->commentID($this->id);
        $comment->appropriate($this->appropriate);
        $comment->save();

        echo 'OK';

        return false;
    }

    protected function post_id_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);

        $comment = new CommentsModel($this->connection);
        $comment->commentID($this->id);
        $comment->load();

        $orig = explode("\n", $comment->comment());
        $orig = array_map(function ($i) { return '> ' . $i; }, $orig);
        $orig = implode("\n", $orig);
        $orig = html_entity_decode($orig, ENT_QUOTES | ENT_HTML401);

        $email = trim($comment->email());

        $response = new ResponsesModel($this->connection);
        $response->commentID($this->id);
        $response->tdate(date('Y-m-d H:i:s'));
        $response->userID(FannieAuth::getUID($this->current_user));
        $msg = trim(FormLib::get('response'));
        if ($msg != '') {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response->sent(1);
                $mail = new PHPMailer();
                $mail->From = 'info@wholefoods.coop';
                $mail->FromName = 'Whole Foods Co-op';
                $mail->addAddress($email);
                $mail->Subject = 'WFC Comment Response';
                $mail->CharSet = 'utf-8';
                $mail->Body = $msg . "\n\n" . $orig
                    . "\n\n--\nWhole Foods Co-op\n218-728-0884\ninfo@wholefoods.coop\n";
                $mail->send();
            }
            $response->response($msg);
            $response->save();

            $canName = trim(FormLib::get('saveAs'));
            if ($canName !== '') {
                $canned = new CannedResponsesModel($this->connection);
                $canned->niceName($canName);
                $canned->response($msg);
                $canned->save();
            }
        }

        return 'ManageComments.php?id=' . $this->id;
    }

    protected function post_new_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $comment = new CommentsModel($this->connection);
        $comment->categoryID(FormLib::get('cat'));
        $comment->publishable(FormLib::get('pub') ? 1 : 0);
        $comment->appropriate(FormLib::get('appr') ? 1 : 0);
        $comment->email(FormLib::get('email'));
        $comment->phone(FormLib::get('phone'));
        $comment->comment(FormLib::get('comment'));
        $comment->tdate(FormLib::get('tdate'));
        $comment->fromPaper(1);
        $cID = $comment->save();

        return 'ManageComments.php?id=' . $cID;
    }

    protected function get_canned_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $resp = new CannedResponsesModel($this->connection);
        $resp->cannedResponseID($this->canned);
        $resp->load();

        echo $resp->response();

        return false;
    }

    protected function get_new_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $categories = new CategoriesModel($this->connection);
        $categories->whichDB($settings['CommentDB']);
        $opts = $categories->toOptions();
        $today = date('Y-m-d');

        return <<<HTML
<form method="post">
    <input type="hidden" name="new" value="1" />
    <div class="form-group">
        <label>Received</label>
        <input type="text" name="tdate" value="{$today}" class="form-control date-field" />
    </div>
    <div class="form-group">
        <label>Category</label>
        <select name="cat" class="form-control">{$opts}</select>
    </div>
    <div class="form-group">
        <label>Publication Allowed
        <input type="checkbox" name="pub" value="1" checked />
        </label>
    </div>
    <div class="form-group">
        <label>Appropriate
        <input type="checkbox" name="appr" value="1" checked />
        </label>
    </div>
    <div class="form-group">
        <label>Email Address for Response</label>
        <input type="text" name="email" placeholder="If known..." class="form-control" />
    </div>
    <div class="form-group">
        <label>Or Phone Number for Response</label>
        <input type="text" name="phone" placeholder="If known..." class="form-control" />
    </div>
    <div class="form-group">
        <label>Comment</label>
        <textarea name="comment" class="form-control" rows="7"></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Enter Comment</button>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <a href="ManageComments.php" class="btn btn-default">Back</a>
    </div>
</form>
HTML;
    }
 
    protected function get_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['CommentDB'] . $this->connection->sep();

        $query = "
            SELECT c.*,
                CASE WHEN c.categoryID=0 THEN 'n/a'
                    WHEN c.categoryID=-1 THEN 'Spam'
                    ELSE t.name 
                END AS name
            FROM {$prefix}Comments AS c
                LEFT JOIN {$prefix}Categories AS t ON t.categoryID=c.categoryID
            WHERE c.commentID=?";
        $prep = $this->connection->prepare($query);
        $comment = $this->connection->getRow($prep, array($this->id));

        $prep = $this->connection->prepare("
            SELECT u.name,
                r.tdate,
                r.response
            FROM {$prefix}Responses AS r
                LEFT JOIN Users AS u ON r.userID=u.uid
            WHERE r.commentID=?
            ORDER BY r.tdate");
        $responses = '';
        $res = $this->connection->execute($prep, array($this->id));
        while ($row = $this->connection->fetchRow($res)) {
            $responses .= '<div class="panel panel-default">
                <div class="panel-heading">Response ' . $row['tdate'] . ' - ' . $row['name'] . '</div>
                <div class="panel-body">' . nl2br($row['response']) . '</div>
                </div>';
        }

        $tagR = $this->connection->execute("SELECT tag FROM {$prefix}CommentTags GROUP BY tag");
        $tags = array();
        while ($tagW = $this->connection->fetchRow($tagR)) {
            $tags[] = $tagW['tag'];
        }
        $tags = json_encode($tags);
        $myTags = array();
        $tagP = $this->connection->prepare("SELECT tag FROM {$prefix}CommentTags WHERE commentID=?");
        $tagR = $this->connection->execute($tagP, array($this->id));
        while ($tagW = $this->connection->fetchRow($tagR)) {
            $myTags[] = $tagW['tag'];
        }
        $tagLinks = array_map(function ($t) { return "<a href=\"ManageComments.php?tag={$t}\">{$t}</a>"; }, $myTags);
        $tagLinks = implode(', ', $tagLinks);
        $myTags = implode(', ', $myTags);

        $categories = new CategoriesModel($this->connection);
        $categories->whichDB($settings['CommentDB']);
        $curCat = $comment['categoryID'];
        $opts = '<option value="0" ' . (!$curCat ? 'selected' : '') . '>Not Assigned</option>';
        $opts .= $categories->toOptions($curCat);
        $opts .= '<option value="-1" ' . ($curCat == -1 ? 'selected' : '') . '>Spam</option>';

        if (is_numeric($comment['phone']) && strlen($comment['phone']) == 7) {
            $comment['phone'] = substr($comment['phone'], 0, 3) . '-' . substr($comment['phone'], 3);
        } elseif (is_numeric($comment['phone']) && strlen($comment['phone']) == 10) {
            $comment['phone'] = substr($comment['phone'], 0, 3) . '-' . substr($comment['phone'], 3, 3) . '-' . substr($comment['phone'], 6);
        }

        $canned = new CannedResponsesModel($this->connection);
        $canned->whichDB($settings['CommentDB']);
        $canned = $canned->toOptions();

        $pnn = '';
        foreach (array(1=>'Positive',0=>'Neutral',-1=>'Negative') as $k => $v) {
            $pnn .= sprintf('<option %s value="%d">%s</option>',
                $k == $comment['posNeg'] ? 'selected' : '', $k, $v);
        }

        $publishAllowed = $comment['publishable'] ? 'Yes' : 'No';
        $appropriateCheck = $comment['appropriate'] ? 'checked' : '';
        $comment['comment'] = nl2br($comment['comment']);
        $source = $comment['fromPaper'] ? 'Manual entry' : 'Website';
        $this->addScript('js/manageComments.js');
        if ($comment['email']) {
            $comment['email'] .= sprintf(' (<a href="ManageComments.php?email=%s">All Comments</a>)', $comment['email']);
            $this->addOnloadCommand("manageComments.sendMsg();");
        } else {
            $this->addOnloadCommand("manageComments.sendBtn();");
        }
        $this->addOnloadCommand("manageComments.autoTag({$tags});");

        return <<<HTML
<form method="post">
    <p>
        <a href="ManageComments.php" class="btn btn-default">Back to All Comments</a>
    </p>
    <input type="hidden" name="id" value="{$this->id}" />
    <div id="alertArea"></div>
    <table class="table table-bordered">
    <tr>
        <th>Received</th><td>{$comment['tdate']}</td>
    </tr>
    <tr>
        <th>Category</th><td><select 
            onchange="manageComments.saveCategory({$this->id}, this.value);" class="form-control">{$opts}</select></td>
    </tr>
    <tr>
        <th>Email Address</th><td>{$comment['email']}</td>
    </tr>
    <tr>
        <th>Phone Number</th><td>{$comment['phone']}</td>
    </tr>
    <tr>
        <th>Publication Allowed</th><td>{$publishAllowed}</td>
    </tr>
    <tr>
        <th>Appropriate</th><td><input type="checkbox" {$appropriateCheck}
            onchange="manageComments.saveAppropriate({$this->id}, this.checked);" /></td>
    </tr>
    <tr>
        <th>Type</th>
        <td><select class="form-control" onchange="manageComments.savePNN({$this->id}, this.value);">{$pnn}</select></td>
    </tr>
    <tr>
        <th>Tags</th>
        <td><input type="text" class="form-control" id="myTags" value="{$myTags}"
            onchange="manageComments.saveTags({$this->id}, this.value);" />
            <div id="tagLinks">{$tagLinks}</div>
        </td>
    </tr>
    <tr>
        <th>Source</th><td>{$source}</td> </tr>
    <tr>
        <td colspan="2"><strong>Comment</strong><br />{$comment['comment']}</td>
    </tr>
    </table>
    {$responses}
    <div class="panel panel-default">
        <div class="panel-heading">Enter Response</div>
        <div class="panel-body">
            <div id="sending-msg" class="alert alert-info">Nothing will be emailed to the customer</div>
            <p>
                <textarea id="resp-ta" name="response" class="form-control" rows="10"></textarea>
            </p>
            <p class="form-inline">
                <button id="send-btn" disabled type="submit" class="btn btn-default">Enter Response</button>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <select onchange="manageComments.canned(this.value);" class="form-control">
                    <option value="0">Use saved response...</option>
                    {$canned}
                </select>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                Save this Response as
                <input type="text" name="saveAs" class="form-control" />
            </p>
        </div>
    </div>
</form>
HTML;
    }

    protected function get_handler()
    {
        if (FormLib::get('email', false)) {
            $this->header = '<h3>Comments from ' . FormLib::get('email') . ' (<a href="ManageComments.php">All Users</a>)</h3>';
        }

        return true;
    }

    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['CommentDB'] . $this->connection->sep();

        $tagTable = '';
        if (FormLib::get('tag')) {
            $tagTable .= " INNER JOIN {$prefix}CommentTags AS g ON c.commentID=g.commentID ";
        }

        $query = "
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
                {$tagTable}
            WHERE 1=1 ";
        $args = array();
        if (FormLib::get('category', false)) {
            $query .= ' AND c.categoryID=?';
            $args[] = FormLib::get('category');
        } else {
            $query .= ' AND c.categoryID >= 0';
        }
        if (!FormLib::get('all', false)) {
            $query .= ' AND r.commentID IS NULL';
        }
        $hidden = '';
        $subheading = '';
        if (FormLib::get('email', false)) {
            $query .= ' AND c.email=?';
            $args[] = FormLib::get('email');
            $hidden .= sprintf('<input type="hidden" class="filter-select" name="email" value="%s" />',
                FormLib::get('email'));
        }
        if (FormLib::get('tag', false)) {
            $query .= ' AND g.tag=?';
            $args[] = FormLib::get('tag');
            $hidden .= sprintf('<input type="hidden" class="filter-select" name="tag" value="%s" />',
                FormLib::get('tag'));
        }
        $query .= ' ORDER BY c.commentID DESC, c.tdate DESC';

        $rows = '';
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $prevID = null;
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['commentID'] == $prevID) continue;
            $rows .= sprintf('<tr %s><td><a href="?id=%d" class="btn btn-default">View/Respond</a></td><td>%s</td><td>%s</td><td>%s</td></tr>',
                ($row['responded'] ? 'class="info"' : ''),
                $row['commentID'],
                $row['tdate'],
                $row['name'],
                substr($row['comment'], 0, 100));
            $prevID = $row['commentID'];
        }

        $filter = '<option value="0" '
            . (!FormLib::get('all', false) ? 'selected' : '')
            . '>Comments w/o Responses</option>'
            . '<option value="1" '
            . (FormLib::get('all', false) ? 'selected' : '')
            . '>All Comments</option>';

        $categories = new CategoriesModel($this->connection);
        $categories->whichDB($settings['CommentDB']);
        $curCat = FormLib::get('category');
        $opts = '<option value="0" ' . (!$curCat ? 'selected' : '') . '>All Categories</option>';
        $opts .= $categories->toOptions($curCat);
        $opts .= '<option value="-1" ' . ($curCat == -1 ? 'selected' : '') . '>Spam</option>';

        $this->addOnloadCommand("\$('.filter-select').change(function(){ location='ManageComments.php?' + $('.filter-select').serialize(); });");

        return <<<HTML
<p class="form-inline">
    <a href="?new=1" class="btn btn-default">Enter Paper Comment</a>
    | 
    <label>Showing</label>
    <select name="all" class="form-control filter-select">
        {$filter}
    </select>
    <select name="category" class="form-control filter-select">
        {$opts}
    </select>
    {$hidden}
    |
    <a href="CommentCategories.php">Manage Categories</a>
</p>
<table class="table table-bordered">
<thead>
    <tr><th>Respond</th><th>Received</th><th>Category</th><th>Comment</th>
</thead>
<tbody>
    {$rows}
</tbody>
</table>
HTML;
    }
}

FannieDispatch::conditionalExec();


