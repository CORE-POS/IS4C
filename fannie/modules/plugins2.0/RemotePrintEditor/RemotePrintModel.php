<?php

/**
  @class RemotePrintModel
*/
class RemotePrintModel extends BasicModel
{
    protected $name = "RemotePrint";
    protected $preferred_db = 'op';

    protected $columns = array(
    'remotePrintID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'identifier' => array('type'=>'VARCHAR(13)'),
    'type' => array('type'=>'VARCHAR(10)', 'default'=>"'UPC'"),
    );
}

