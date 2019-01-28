<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class MagicDoc extends FannieRESTfulPage
{
    protected $header = 'Auto SQL Docs';
    protected $title = 'Auto SQL Docs';

    protected function get_id_view()
    {
        $class = base64_decode($this->id);
        $ret = '<a href="MagicDoc.php">Back</a>';
        if (class_exists($class)) {
            $obj = new $class(null);
            $ret .= '<h3>' . $obj->getName() . '</h3>';
            $ret .= '<pre>' . $obj->columnsDoc() . "\n\n" . $obj->doc() . '</pre>';
        }
        $ret .= '<a href="MagicDoc.php">Back</a>';

        return $ret;
    }

    protected function get_view()
    {
        $all = FannieAPI::listModules('BasicModel');
        $assort = array();
        foreach ($all as $a) {
            $obj = new $a(null);
            $pref = $obj->preferredDB();
            if (!isset($assort[$pref])) {
                $assort[$pref] = array();
            }
            $name = $obj->getName();
            $assort[$pref][$name] = sprintf('<a href="MagicDoc.php?id=%s">%s</a>',
                base64_encode($a), $name);
        }

        $sets = array_keys($assort);
        sort($sets);

        $ret = '<ul>';
        foreach ($sets as $s) {
            ksort($assort[$s]);
            $ret .= "<li>{$s}<ul>";
            foreach ($assort[$s] as $a) {
                $ret .= "<li>{$a}</li>";
            }
            $ret .= "</ul></li>";
        }
        $ret .= "</ul>";

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->get_view());
        $this->id = base64_encode('EmployeesModel');
        $phpunit->assertInternalType('string', $this->get_id_view());
    }
}

FannieDispatch::conditionalExec();

