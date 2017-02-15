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

class Paycards extends Plugin {

    public $plugin_description = 'Plugin for integrated payment cards';

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
        'default' => 'coordinated',
        'options' => array(
            'Messages' => 'coordinated',
            'Direct Input' => 'direct',
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
            'No' => 0,
            'Member Only' => 2,
            )
        ),
        'PaycardsTermCashBackLimit' => array(
        'label' => 'Terminal CB Max',
        'description' => 'Maximum cashback selectable on terminal (in $)',
        'default' => 40,
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
        'PaycardsDatacapMode' => array(
            'label' => 'Datacap Mode',
            'description' => 'The Datacap driver has an EMV mode or a legacy credit/debit mode',
            'default' => 0,
            'options' => array(
                'EMV' => 1,
                'Credit/Debit' => 0,
                'EMV (en-ca)' => 2,
                'EMV (fr-ca)' => 3,
                )
        ),
        'PaycardsDatacapLanHost' => array(
            'label' => 'LAN Datacap Server',
            'description' => 'Datacap server on the local network (only required for EMV)',
            'default' => '127.0.0.1',
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
        'PaycardsTenderCodeEmv' => array(
            'label' => 'EMV Tender Code',
            'description' => 'Two-letter tender code for EMV transactions',
            'default' => 'CC',
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
        'PaycardsTenderCodeGift' => array(
            'label' => 'Gift Card Tender Code',
            'description' => 'Two-letter tender code for gift transactions',
            'default' => 'GD',
        ),
        'PaycardsDepartmentGift' => array(
            'label' => 'Gift Card Issue Department',
            'description' => 'Department number used when selling/issuing gift cards',
            'default' => '902', // historically hardcoded default
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

    public function plugin_transaction_reset()
    {
        $conf = new PaycardConf();

        $conf->set('paycardTendered', false);

        /**
          @var CachePanEncBlcok
          Stores the encrypted string of card information
          provided by the CC terminal. If the terminal is
          facing the customer, the customer may swipe their
          card before the cashier is done ringing in items
          so the value is stored in session until the
          cashier is ready to process payment
        */
        $conf->set("CachePanEncBlock","");

        /**
          @var CachePinEncBlock
          Stores the encrypted string of PIN data.
          Similar to CachePanEncBlock.
        */
        $conf->set("CachePinEncBlock","");

        /**
          @var CacheCardType
          Stores the selected card type.
          Similar to CachePanEncBlock.
          Known values are:
          - CREDIT
          - DEBIT
          - EBTFOOD
          - EBTCASH
        */
        $conf->set("CacheCardType","");

        /**
          @var CacheCardCashBack
          Stores the select cashback amount.
          Similar to CachePanEncBlock.
        */
        $conf->set("CacheCardCashBack",0);

        /**
          @var ccTermState
          Stores a string representing the CC 
          terminals current display. This drives
          an optional on-screen icon to let the 
          cashier know what the CC terminal is
          doing if they cannot see its screen.
        */
        $conf->set('ccTermState','swipe');

        /**
          @var paycard_voiceauthcode
          Stores a voice authorization code for use
          with a paycard transaction. Not normally used
          but required to pass Mercury's certification
          script.
        */
        $conf->set("paycard_voiceauthcode","");

        /**
          @var ebt_authcode
          Stores a foodstamp authorization code.
          Similar to paycard_voiceauthcode.
        */
        $conf->set("ebt_authcode","");

        /**
          @var ebt_vnum
          Stores a foodstamp voucher number.
          Similar to paycard_voiceauthcode.
        */
        $conf->set("ebt_vnum","");

        /**
          @var paycard_keyed
          - True => card number was hand keyed
          - False => card was swiped

          Normally POS figures this out automatically
          but it has to be overriden to pass Mercury's
          certification script. They require some
          keyed transactions even though the CC terminal
          is only capable of producing swipe-style data.
        */
        $conf->set("paycard_keyed",False);

        $conf->reset();
    }

}

