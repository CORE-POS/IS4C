<?php

namespace COREPOS\Fannie\API\jobs;
use \FannieDB;
use \FannieConfig;
use \BatchesModel;

class ForceBatch extends Job
{
    protected $requiredFields = array('id', 'startStop');
    
    public function run()
    {
        if ($this->checkData()) {
            $config = FannieConfig::factory();
            $dbc = FannieDB::get($config->get('OP_DB'));
            $model = new BatchesModel($dbc);
            switch (strtolower($this->data['startStop'])) {
                case 'start':
                    $model->forceStartBatch($this->data['id']);
                    break;
                case 'stop':
                    $model->forceStopBatch($this->data['id']);
                    break;
            }
        }
    }
}

