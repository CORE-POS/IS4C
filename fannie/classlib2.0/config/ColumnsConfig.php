<?php

namespace COREPOS\Fannie\API\config;

class ColumnsConfig implements PageConfig
{
    private $name = '';
    private $columns = array();

    public function __construct($name, $columns)
    {
        $this->name = $name;
        $this->columns = $columns;
    }

    public function render($config)
    {
        $current = $config->get($this->name, array());
        $ret = '<b>Enabled columns</b>:<br />';
        foreach ($columns as $key => $val) {
            if (is_numeric($key)) {
                $key = $val;
            }
            $ret .= sprintf('<label><input type="checkbox" name="config%s[]" %s value="%s" />%s</label><br />',
                $this->name, (in_array($val, $current) ? 'checked' : ''), $val, $key);
        }

        return $ret;
    }

    public function update($form)
    {
        $newValue = $form->tryGet('config' . $this->name);
        if (!is_array($newValue) || count($newValue) == 0) {
            $formatted = 'array()';
        } else {
            $newValue = array_map(function ($i) { return "'" . $i . "'"; }, $newValue);
            $formatted = 'array(' . implode(',', $newValue) . ')';
        }
        if (!function_exists('confset')) {
            include(__DIR__ . '/../../install/util.php');
        }
        \confset($this->name, $formatted);
    }
}

