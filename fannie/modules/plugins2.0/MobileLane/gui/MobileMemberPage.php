<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('MobileLanePage')) {
    include(__DIR__ . '/../lib/MobileLanePage.php');
}

class MobileMemberPage extends MobileLanePage
{
    private $msg = '';
    protected $enable_linea = true;

    public function preprocess()
    {
        /**
          No statefulness. Employee and register get
          carried through on all requests
        */
        $this->emp = FormLib::get('e', 0);
        $this->reg = FormLib::get('r', 0);
        if ($this->emp == 0 || $this->reg == 0) {
            header('Location: MobileLoginPage.php');
            return false;
        }
        $this->addRoute('get<setMem><e><r>');

        return parent::preprocess();
    }

    protected function get_setMem_e_r_handler()
    {
        list($mem, $person) = explode('::', $this->setMem);
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $cdata = new CustdataModel($dbc);
        $cdata->CardNo($mem);
        $cdata->personNum($person);
        $cdata->load();
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']); 

        $setP = $dbc->prepare('
            UPDATE MobileTrans
            SET card_no=?
                percentDiscount=?,
                memType=?,
                staff=?
            WHERE emp_no=?
                AND register_no=?');
        $dbc->execute($setP, array($mem, $cdata->Discount(), $cdata->memType(), $cdata->staff(), $this->e, $this->r));

        return "MobileTenderPage.php?e={$this->e}&r={$this->r}";
    }

    protected function post_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $this->id = trim($this->id);

        $mems = array();
        if (is_numeric($this->id)) {
            $custP = $dbc->prepare('SELECT CardNo, personNum, LastName, FirstName FROM custdata WHERE CardNo=?'); 
            $custR = $dbc->execute($custP, array($this->id));
            while ($custW = $dbc->fetchRow($custR)) {
                $mems[$custW['CardNo'] . '::' . $custW['personNum']] = $custW['CardNo'] . ' ' . $custW['LastName'] . ', ' . $custW['FirstName'];
            }
            if (count($mems) == 0) {
                $custP = $dbc->prepare('SELECT 
                    CardNo, 
                    personNum, 
                    LastName, 
                    FirstName 
                    FROM memberCards AS m
                        INNER JOIN custdata AS c ON m.card_no=c.CardNo
                    WHERE m.upc=?');
                $custR = $dbc->execute($custP, array(BarcodeLib::padUPC($this->id)));
                while ($custW = $dbc->fetchRow($custR)) {
                    $mems[$custW['CardNo'] . '::' . $custW['personNum']] = $custW['CardNo'] . ' ' . $custW['LastName'] . ', ' . $custW['FirstName'];
                }
            }
        } else {
            $custP = $dbc->prepare('
                SELECT CardNo, personNum, LastName, FirstName 
                FROM custdata 
                WHERE LastName LIKE ? 
                    AND Type IN (\'REG\', \'PC\')
                ORDER BY LastName, FirstName'); 
            $custR = $dbc->execute($custP, array('%' . $this->id . '%'));
            while ($custW = $dbc->fetchRow($custR)) {
                $mems[$custW['CardNo'] . '::' . $custW['personNum']] = $custW['CardNo'] . ' ' . $custW['LastName'] . ', ' . $custW['FirstName'];
            }
        }

        $this->results = $mems;

        return true;
    }

    protected function post_id_view()
    {
        if (count($this->results) == 0) {
            $ret = '<div class="alert alert-danger">No matches</div>';
        } else {
            $ret = '';
            foreach ($this->results as $id => $name) {
                $ret .= sprintf('<p><a class="h3" href="?setMem=%s&e=%d&r=%d">%s</a></p>',
                    urlencode($id), $this->emp, $this->reg, $name);
            }
        }

        return $ret . $this->get_view();
    }

    public function get_view()
    {
        $this->addOnloadCommand("\$('#mainInput').focus();\n");
        $this->addOnloadCommand("enableLinea('#mainInput');\n");
        return <<<HTML
<form method="post">
<div class="form-group">
    <label>Member # or name</label>
    <input type="text" class="form-control" name="id" id="mainInput" 
        placeholder="Enter last name or member number" />
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default btn-block">Search</button>
    <input type="hidden" name="e" value="{$this->emp}" />
    <input type="hidden" name="r" value="{$this->reg}" />
</div>
<div class="form-group">
    <a href="MobileMainPage.php?e={$this->emp}&r={$this->reg}" class="btn btn-default btn-block">Go Back</a>
</div>
HTML;
    }
}

FannieDispatch::conditionalExec();

