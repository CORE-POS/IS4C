<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('MobileLanePage')) {
    include(__DIR__ . '/../lib/MobileLanePage.php');
}

class MobileMenuPage extends MobileLanePage
{
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

        $this->addRoute(
            'get<cancel><e><r>',
            'get<suspend><e><r>',
            'get<signout><e><r>'
        );

        return parent::preprocess();
    }

    protected function get_signout_e_r_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        $model = new MobileSessionsModel($dbc);
        $model->empNo($e);
        $model->delete();

        return 'MobileLoginPage.php';
    }

    protected function get_cancel_e_r_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        
        $canP = $dbc->prepare("
            UPDATE MobileTrans
            SET trans_status='X'
            WHERE emp_no=?
                AND register_no=?"); 
        $dbc->execute($canP, array($this->e, $this->r));

        $mgr = new MobileTransManager($dbc, $this->config);
        $mgr->endTransaction($this->e, $this->r);

        return "MobileMainPage.php?e={$this->e}&r={$this->r}";
    }

    protected function get_suspend_e_r_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);

        $canP = $dbc->prepare("
            UPDATE MobileTrans
            SET trans_status='X',
                charflag='S'
            WHERE emp_no=?
                AND register_no=?"); 
        $dbc->execute($canP, array($this->e, $this->r));

        $model = new MobileTransModel($dbc);
        $cols = array_keys($model->getColumns());
        $cols = implode(',', $colums);
        $sus_cols = str_replace('pos_row_id', 'trans_id', $cols);

        $xfer = $dbc->prepare("
            INSERT INTO " . $this->config->get('TRANS_DB') . $dbc->sep() . "suspended
                ({$sus_cols})
            SELECT {$cols}
            FROM MobileTrans
            WHERE emp_no=?
                AND register_no=?");
        $dbc->execute($xfer, array($this->e, $this->r));

        $mgr = new MobileTransManager($dbc, $this->config);
        $mgr->endTransaction($this->e, $this->r);

        return "MobileMainPage.php?e={$this->e}&r={$this->r}";
    }

    protected function get_view()
    {
        $ret = <<<HTML
<p>
    <a href="MobileMainPage.php?e={$this->emp}&r={$this->reg}" class="btn btn-block btn-default">
    Go Back<a/>
</p>
<p>
    <a href="?cancel=1&e={$this->emp}&r={$this->reg}" class="btn btn-block btn-danger">
    Cancel Transaction<a/>
</p>
<p>
    <a href="?suspend=1&e={$this->emp}&r={$this->reg}" class="btn btn-block btn-warning">
    Suspend Transaction<a/>
</p>
HTML;

        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        $transP = $dbc->prepare('SELECT upc FROM MobileTrans WHERE emp_no=? AND register_no=?');
        $trans = $dbc->getValue($transP, array($this->emp, $this->reg));
        $suspP = $dbc->prepare('SELECT upc FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'suspended WHERE datetime >= ' . $dbc->curdate());
        $susp = $dbc->getValue($suspP);
        if ($trans === false && $susp !== false) {
            $ret .= '<p>
                <a href="?resumelist=1&e=' . $this->emp . '&r=' . $this->reg . '" 
                class="btn btn-block btn-success">
                Resume Transaction<a/>
            </p>';
        }
        if ($trans === false) {
            $ret .= '<p>
                <a href="?signout=1&e=' . $this->emp . '&r=' . $this->reg . '"
                class="btn btn-block btn-info">
                Sign Out<a/>
            </p>';
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();

