<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class StripeDotCom extends Plugin 
{
    public $plugin_settings = array(
    'StripeLiveKey' => array('default'=>'','label'=>'API Secret Key (Live)',
            'description'=>'stripe.com live API private key'),
    'StripeLivePublic' => array('default'=>'','label'=>'API Publishable Key (Live)',
            'description'=>'stripe.com live API public key'),
    'StripeTestKey' => array('default'=>'','label'=>'API Secret Key (Test)',
            'description'=>'stripe.com testing API private key'),
    'StripeTestPublic' => array('default'=>'','label'=>'API Publishable Key (Test)',
            'description'=>'stripe.com test API public key'),
    'StripeBitCoinTender' => array('default'=>'BC','label'=>'Tender Code',
            'description'=>'Two-letter tender code for bitcoin payments.'),
    'StripeCurrency' => array('default'=>'USD',
            'options'=>array('US Dollars'=>'USD','Euros'=>'EUR','Bitcoin'=>'BTC')
        )
    );

    public $plugin_description = 'Plugin for accepting payments via stripe.com';
}

