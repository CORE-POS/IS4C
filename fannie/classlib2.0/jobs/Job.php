<?php

namespace COREPOS\Fannie\API\jobs;

class Job
{
    protected $data = array();
    protected $requiredFields = array();

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function run()
    {
        print_r($this->data);
    }

    protected function checkData()
    {
        foreach ($this->requiredFields as $field) {
            if (!isset($this->data[$field])) {
                echo "Error: missing {$field} in data" . PHP_EOL;
                return false;
            }
        }

        return true;
    }
}

