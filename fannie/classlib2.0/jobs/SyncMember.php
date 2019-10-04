<?php

namespace COREPOS\Fannie\API\jobs;

class SyncMember extends Job
{
    public function run()
    {
        if (!isset($this->data['id'])) {
            echo "Error: no member ID specified" . PHP_EOL;
            return false;
        }

        \COREPOS\Fannie\API\data\MemberSync::sync($this->data['id']);
    }
}

