<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

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

/**
  @class GumTaxIdentifiersModel

  This table stores tax IDs - e.g., social
  security numbers. The encrypted field
  should contain the full value but not
  in plaintext (duh). The masked field contains
  the last four digits. 

  RSA is the default expectation using public
  key to encrypt and private key to decrypt.
  Ideally, the private key should not exist anywhere
  on the server side. See README.PLUGIN for more
  information on setting up encryption keys.
*/
class GumTaxIdentifiersModel extends BasicModel
{

    protected $name = "GumTaxIdentifiers";
    protected $preferred_db = 'plugin:GiveUsMoneyDB';

    protected $columns = array(
    'gumTaxIdentifierID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'encryptedTaxIdentifier' => array('type'=>'BLOB'),
    'maskedTaxIdentifier' => array('type'=>'CHAR(4)'),
    );
}

