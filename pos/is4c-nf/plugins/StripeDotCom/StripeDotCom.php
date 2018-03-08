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

use COREPOS\pos\plugins\Plugin;

class StripeDotCom extends Plugin 
{
    public $plugin_settings = array(
    'StripeCurrency' => array('default'=>'USD',
            'options'=>array('US Dollars'=>'USD','Euros'=>'EUR','Bitcoin'=>'BTC')
        ),
    'StripeLiveKey' => array('default'=>'','label'=>'API Secret Key (Live)',
            'description'=>'stripe.com live API private key'),
    'StripeTestKey' => array('default'=>'','label'=>'API Secret Key (Test)',
            'description'=>'stripe.com testing API private key'),
    'StripeChargeName' => array('default'=>'CORE-POS','label'=>'Charge Name',
            'description'=>'The description that is attached to Stripe charges'),
    'StripeCreditTender' => array('default'=>'CC','label'=>'Tender Code',
            'description'=>'Two-letter tender code for credit payments.'),
    'StripeBitCoinTender' => array('default'=>'BC','label'=>'Tender Code',
            'description'=>'Two-letter tender code for bitcoin payments.'),
    'StripeLivePublic' => array('default'=>'','label'=>'API Publishable Key (Live)',
            'description'=>'stripe.com live API public key (only needed for bitcoin)'),
    'StripeTestPublic' => array('default'=>'','label'=>'API Publishable Key (Test)',
            'description'=>'stripe.com test API public key (only needed for bitcoin)'),
    );

    public $plugin_description = 'Plugin for accepting payments via stripe.com';

    public function plugin_transaction_reset()
    {
        CoreLocal::set('StripeMode', 'Bitcoin');
    }
}

