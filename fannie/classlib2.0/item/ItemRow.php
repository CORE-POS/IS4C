<?php

namespace COREPOS\Fannie\API\item;

interface ItemRow
{
    public function formRow($upc);
    public function saveFormData($upc);
}

