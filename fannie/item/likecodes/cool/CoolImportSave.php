<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CoolImportSave extends FannieRESTfulPage
{
    protected $title = 'COOL Data Import';
    protected $header = 'COOL Data Import';

    protected function post_view()
    {
        $lcs = FormLib::get('lc');
        $cools = FormLib::get('cool');
        $lcP = $this->connection->prepare("SELECT * FROM likeCodes WHERE likeCode=?");
        $upP = $this->connection->prepare("UPDATE likeCodes SET origin=?, originChanged=? WHERE likeCode=?");
        $ret = "";
        for ($i=0; $i<count($lcs); $i++) {
            if (!$lcs[$i]) {
                continue;
            }
            $likecode = $this->connection->getRow($lcP, array($lcs[$i]));
            $cools[$i] = trim($cools[$i]);
            if (strtolower($likecode['origin']) == strtolower($cools[$i])) {
                $ret .= sprintf('%d %s is still from %s & unchanged<br />',
                    $lcs[$i], $likecode['likeCodeDesc'], strtoupper($cools[$i]));
            } else {
                $changed = $likecode['originChanged'];
                if (date('Y-m-d') == date('Y-m-d', strtotime($changed))) {
                    $curOrigins = explode('AND', strtoupper($likecode['origin']));
                    $newOrigin = strtoupper($cools[$i]);
                    foreach ($curOrigins as $co) {
                        if ($co != $newOrigin) {
                            $cools[$i] .= ' AND ' . $co;
                        }
                    }
                }
                $this->connection->execute($upP, array(strtoupper($cools[$i]), date('Y-m-d H:i:s'), $lcs[$i]));
                $ret .= sprintf('%d %s is updated to be from %s<br />',
                    $lcs[$i], $likecode['likeCodeDesc'], strtoupper($cools[$i]));
            }
        }

        return $ret . $this->get_view();
    }

    protected function get_view()
    {
        return <<<HTML
<p>
    <a href="CoolImport.php">Import COOL Invoices</a>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

