<?php

use COREPOS\Fannie\API\jobs\Job;
use COREPOS\Fannie\API\member\MemberREST;

class UpdateAddress extends Job
{
    public function run()
    {
        MemberREST::post($this->data['cardNo'], $this->data);
    }
}

