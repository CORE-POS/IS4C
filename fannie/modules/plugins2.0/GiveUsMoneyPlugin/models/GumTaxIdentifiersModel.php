<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

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

    protected $columns = array(
    'gumTaxIdentifierID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'encryptedTaxIdentifier' => array('type'=>'BLOB'),
    'maskedTaxIdentifier' => array('type'=>'CHAR(4)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function gumTaxIdentifierID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumTaxIdentifierID"])) {
                return $this->instance["gumTaxIdentifierID"];
            } else if (isset($this->columns["gumTaxIdentifierID"]["default"])) {
                return $this->columns["gumTaxIdentifierID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'gumTaxIdentifierID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumTaxIdentifierID"]) || $this->instance["gumTaxIdentifierID"] != func_get_args(0)) {
                if (!isset($this->columns["gumTaxIdentifierID"]["ignore_updates"]) || $this->columns["gumTaxIdentifierID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumTaxIdentifierID"] = func_get_arg(0);
        }
        return $this;
    }

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function encryptedTaxIdentifier()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["encryptedTaxIdentifier"])) {
                return $this->instance["encryptedTaxIdentifier"];
            } else if (isset($this->columns["encryptedTaxIdentifier"]["default"])) {
                return $this->columns["encryptedTaxIdentifier"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'encryptedTaxIdentifier',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["encryptedTaxIdentifier"]) || $this->instance["encryptedTaxIdentifier"] != func_get_args(0)) {
                if (!isset($this->columns["encryptedTaxIdentifier"]["ignore_updates"]) || $this->columns["encryptedTaxIdentifier"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["encryptedTaxIdentifier"] = func_get_arg(0);
        }
        return $this;
    }

    public function maskedTaxIdentifier()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["maskedTaxIdentifier"])) {
                return $this->instance["maskedTaxIdentifier"];
            } else if (isset($this->columns["maskedTaxIdentifier"]["default"])) {
                return $this->columns["maskedTaxIdentifier"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'maskedTaxIdentifier',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["maskedTaxIdentifier"]) || $this->instance["maskedTaxIdentifier"] != func_get_args(0)) {
                if (!isset($this->columns["maskedTaxIdentifier"]["ignore_updates"]) || $this->columns["maskedTaxIdentifier"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["maskedTaxIdentifier"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

