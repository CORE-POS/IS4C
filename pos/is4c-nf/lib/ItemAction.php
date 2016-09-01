<?php

namespace COREPOS\pos\lib;

/**
  @class ItemAction
  
  An item action is invoked each time an item is added 
  to a transaction
*/
class ItemAction
{
    /**
      Callback invoked after each record is added
      to the transaction

      @param $record [keyed array] last record 
        added to the transaction
    */
    // @hintable
    public function callback($record)
    {
    }
}

