<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class EndCapperPage extends FannieRESTfulPage
{
    protected $header = 'End Capper';
    protected $title = 'End Capper';

    public function preprocess()
    {
        $this->addRoute('get<all>');
        return parent::preprocess();
    }

    protected function get_all_handler()
    {
        $res = $this->connection->query('SELECT endCapID, json FROM EndCaps ORDER BY endCapID DESC');
        $ret = array();
        while ($row = $this->connection->fetchRow($res)) {
            $json = json_decode($row['json'], true);
            $ret[] = array('id' => $row['endCapID'], 'name' => $json['name']);
        }

        echo json_encode($ret);
        
        return false;
    }

    protected function get_id_handler()
    {
        $ret = array('state'=>false);
        $prep = $this->connection->prepare('SELECT endCapID, json FROM EndCaps WHERE endCapID=?');
        $row = $this->connection->getRow($prep, array($this->id)); 
        if ($row) {
            $json = json_decode($row['json'], true);
            $json['permanentID'] = $this->id;
            $ret['state'] = $json;
            $ret['id'] = $this->id;
        }

        echo json_encode($ret);
        
        return false;
    }

    protected function post_handler()
    {
        $input = file_get_contents('php://input');
        $req = json_decode($input, true);
        $json = array('saved'=>false);

        $req['name'] = $req['newName'];
        unset($req['newName']);

        if ($req['permanentID']) {
            $prep = $this->connection->prepare("
                UPDATE EndCaps
                SET json=?
                WHERE endCapID=?");
            $saved = $this->connection->execute($prep, array(json_encode($req), $req['permanentID'])) ? true : false;
            $json = array('saved' => $saved, 'id' => $req['permanentID'], 'detail'=>$req);
        } else {
            $prep = $this->connection->prepare("INSERT INTO EndCaps (json) VALUES (?)");
            $saved = $this->connection->execute($prep, array(json_encode($req))); 
            if ($saved) {
                $json = array('saved'=>true, 'id'=>$this->connection->insertID());
            }
        }

        echo json_encode($json);

        return false;
    }

    protected function get_view()
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/build/asset-manifest.json'), true);
        $this->addScript('build/' . $manifest['main.js']);
        $this->addCssFile('build/' . $manifest['main.css']);

        $init = '';
        if (FormLib::get('init')) {
            $init = sprintf('<input type="hidden" id="initializeEndCap" value="%d" />', FormLib::get('init'));
        }

        return $init . '<div id="end-capper"></div>';
    }
}

FannieDispatch::conditionalExec();

