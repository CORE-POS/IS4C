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

class Paycards extends Plugin {

	public $description = 'Plugin for integrated payment cards';

	public $plugin_settings = array(
		'CCintegrate' => array(
		'label' => 'Live',
		'description' => 'Enable live integrated transactions',
		'default' => 1,
		'options' => array(
			'Yes' => 1,
			'No' => 0
			)
		),
        'RegisteredPaycardClasses' => array(
        'label' => 'Processor(s)',
        'description' => 'Which processors to use',
        'default' => array(),
        'options' => array(
            'Mercury E2E' => 'MercuryE2E',
            'Mercury Gift Only' => 'MercuryGift',
            'Go E Merchant' => 'GoEMerchant',
            'First Data' => 'FirstData',
            'Authorize.net' => 'AuthorizeDotNet',
            'Valutec' => 'Valutec',
            ),
        ),
        'PaycardsTerminalID' => array(
        'label' => 'Terminal ID',
        'description' => 'Unique ID for MC regs (1-3 characters, alphanumeric)',
        'default'=> '',
        ),
		'PaycardsCashierFacing' => array(
		'label' => 'Mode',
		'description' => 'Who is swiping the card?',
		'default' => 1,
		'options' => array(
			'Cashier' => 1,
			'Customer' => 0
			)
		),
		'PaycardsStateChange' => array(
		'label' => 'Communication',
		'description' => 'Should terminal switch screens 
based on direct input or
messages from POS?',
		'default' => 'direct',
		'options' => array(
			'Direct Input' => 'direct',
			'Messages' => 'coordinated' 
			)
		),
		'PaycardsSigCapture' => array(
		'label' => 'Signature Mode',
		'description' => '',
		'default' => 0,
		'options' => array(
			'Sign on termial' => 1,
			'Sign paper slip' => 0
			)
		),
        'CCSigLimit' => array(
        'label' => 'Signature Required Threshold',
        'description' => 'Require signatures on credit purchases above this amount',
        'default' => 0.00,
        ),
		'PaycardsOfferCashBack' => array(
		'label' => 'Offer Cashback',
		'description' => 'Show cashback screen on terminal',
		'default' => 1,
		'options' => array(
			'Yes' => 1,
			'No' => 0
			)
		),
        'PaycardsAllowEBT' => array(
            'label' => 'Allow EBT',
            'description' => 'Show EBT option on terminal 
                              (only works with Communication type Messages)',
            'default' => 1,
            'options' => array(
                'Yes' => 1,
                'No' => 0
                )
        ),
        'PaycardsBlockTenders' => array(
            'label' => 'Block Other Tenders',
            'description' => 'If customer card data is available, do not
                              allow other tenders',
            'default' => 0,
            'options' => array(
                'Yes' => 1,
                'No' => 0
                )
        ),
        'PaycardsBlockExceptions' => array(
            'label' => 'Blocking Exceptions',
            'description' => 'Still allow these tenders with Block Other Tenders enabled',
            'default' => 'CP IC',
        ),
        'PaycardsTenderCodeCredit' => array(
            'label' => 'Credit Tender Code',
            'description' => 'Two-letter tender code for credit transactions',
            'default' => 'CC',
        ),
        'PaycardsTenderCodeDebit' => array(
            'label' => 'Debit Tender Code',
            'description' => 'Two-letter tender code for debit transactions',
            'default' => 'DB',
        ),
        'PaycardsTenderCodeEbtFood' => array(
            'label' => 'EBT Food Tender Code',
            'description' => 'Two-letter tender code for EBT Food transactions',
            'default' => 'EF',
        ),
        'PaycardsTenderCodeEbtCash' => array(
            'label' => 'EBT Cash Tender Code',
            'description' => 'Two-letter tender code for EBT Cash transactions',
            'default' => 'EC',
        ),
        'PaycardsTenderCodeVisa' => array(
            'label' => 'Visa-Specific Tender Code',
            'description' => 'Two-letter tender code for Visa transactions. If blank, uses credit or debit code.',
            'default' => '',
        ),
        'PaycardsTenderCodeMC' => array(
            'label' => 'MasterCard-Specific Tender Code',
            'description' => 'Two-letter tender code for MasterCard transactions. If blank, uses credit or debit code.',
            'default' => '',
        ),
        'PaycardsTenderCodeDiscover' => array(
            'label' => 'Discover-Specific Tender Code',
            'description' => 'Two-letter tender code for Discover transactions. If blank, uses credit or debit code.',
            'default' => '',
        ),
        'PaycardsTenderCodeAmex' => array(
            'label' => 'American Express-Specific Tender Code',
            'description' => 'Two-letter tender code for American Express transactions. If blank, uses credit or debit code.',
            'default' => '',
        ),
        'MercuryE2ETerminalID' => array(
            'label' => 'Mercury E2E Terminal ID',
            'description' => 'Terminal ID number for use with encrypted Mercury processing',
            'default' => '',
        ),
        'MercuryE2EPassword' => array(
            'label' => 'Mercury E2E Password',
            'description' => 'Password for use with encrypted Mercury processing',
            'default' => '',
        ),
	);

	public function plugin_enable(){

	}

	public function plugin_disable(){

	}

    public function plugin_transaction_reset()
    {
        global $CORE_LOCAL;
        $CORE_LOCAL->set('paycardTendered', false);
    }

}
