<?php

class Mercato extends \COREPOS\Fannie\API\FanniePlugin 
{
    public $plugin_settings = array(
    'MercatoFtpHost' => array('default'=>'', 'label'=>'SFTP Hostname',
            'description'=>'Mercato SFTP server'), 
    'MercatoFtpUser' => array('default'=>'', 'label'=>'SFTP Username',
            'description'=>'Mercato SFTP credentials'), 
    'MercatoFtpPw' => array('default'=>'', 'label'=>'SFTP Password',
            'description'=>'Mercato credentials'), 
    'MercatoBotUser' => array('default'=>'', 'label'=>'Bot Username',
            'description'=>'Mercato dashboard credentials'), 
    'MercatoBotPw' => array('default'=>'', 'label'=>'Bot Password',
            'description'=>'Mercato credentials'), 
    );

    public $plugin_description = 'Plugin for submitting Mercato data. You may need
        to install flysystem/sftp via composer to actually transmit data.';
}

