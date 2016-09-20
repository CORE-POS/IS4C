<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('MobileLanePage')) {
    include(__DIR__ . '/../lib/MobileLanePage.php');
}

class MobileTenderPage extends MobileLanePage
{
    private $msg = '';

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

        /**
          Go to member entry if none has been applied
        */
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prep = $dbc->prepare('
            SELECT card_no
            FROM ' . $settings['MobileLaneDB'] . $dbc->sep() . 'MobileTrans
            WHERE emp_no=?
                AND register_no=?');
        if (!$dbc->getValue($prep, array($this->emp, $this->reg))) {
            header("Location: MobileMemberPage.php?e={$this->emp}&r={$this->reg}");
            return false;
        }
        $this->addRoute('post<type><amt><e><r>');

        return parent::preprocess();
    }

    protected function post_type_amt_e_r_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $tender = new TendersModel($dbc);
        $tender->TenderCode($this->type);
        $tender->load();

        $due = $this->amtDue($dbc);

        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        $mgr = new MobileTransManager($dbc, $this->config);
        $trans = $mgr->getTransNo($this->e, $this->r);
        $model = new MobileTransModel($dbc);
        $model->datetime(date('Y-m-d H:i:s'));
        $model->emp_no($this->e); 
        $model->register_no($this->r);
        $model->trans_no($trans);
        $model->trans_type('T');
        $model->description($tender->TenderName());
        $model->upc('0');
        $model->trans_subtype($this->type);
        $model->total(sprintf('%.2f', -1*$this->amt));
        $model->save();

        if ($due - $this->amt < 0.005) { // transaction ends
            $change = sprintf('%.2f', $this->amt - $due);
            $model = new MobileTransModel($dbc);
            $model->datetime(date('Y-m-d H:i:s'));
            $model->emp_no($this->e); 
            $model->register_no($this->r);
            $model->trans_no($trans);
            $model->trans_type('T');
            $model->description('Change');
            $model->upc('0');
            $model->trans_subtype('CA');
            $model->total(sprintf('%.2f', $change));
            $model->save();

            $mgr->endTransaction($this->e, $this->r);
        }

        return "MemberMainPage.php?e={$this->e}&r={$this->r}";
    }

    private function amtDue($dbc)
    {
        $dueP = $dbc->prepare('
            SELECT SUM(total)
            FROM ' . $settings['MobileLaneDB'] . $dbc->sep() . 'MobileTrans
            WHERE emp_no=?
                AND register_no=?');
        $due = $dbc->getValue($dueP, array($this->emp, $this->reg));
        $due = sprintf('%.2f', $due);

        return $due;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $tenders = '<option value="">Select one</option>';
        $res = $dbc->query('SELECT TenderCode, TenderName FROM tenders ORDER BY TenderName');
        while ($row = $dbc->fetchRow($res)) {
            $tenders .= sprintf('<option value="%s">%s</option>', $row['TenderCode'], $row['TenderName']);
        }
        $due = $this->amtDue($dbc);

        return <<<HTML
<form method="post">
<h3>Amount due: \${$due}</h3>
<div class="form-group">
    <label>Tender as</label>
    <select class="form-control" required name="type">{$tenders}</select>
</div>
<div class="form-group">
    <div class="input-group">
        <span class="input-group input-group-addon">$</span>
        <input type="number" required name="amt" min="0.01" max="{$due}" step="0.01"
            class="form-control" placeholder="Enter an amount up to {$due}" />
    </div>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-success btn-block">Enter Tender</button>
    <input type="hidden" name="e" value="{$this->emp}" />
    <input type="hidden" name="r" value="{$this->reg}" />
</div>
<div class="form-group">
    <a href="MobileMainPage.php?e={$this->emp}&r={$this->reg}" class="btn btn-default btn-block">Go Back</a>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

