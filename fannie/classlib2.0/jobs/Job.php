<?php

namespace COREPOS\Fannie\API\jobs;

class Job
{
    protected $data = array();

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function run()
    {
        print_r($this->data);
    }
}

