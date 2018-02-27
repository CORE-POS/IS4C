<?php

namespace COREPOS\Fannie\API\item;

interface ItemRow
{
    public function formRow($upc, $activeTab);
    public function saveFormData($upc);
    public function setConfig(\FannieConfig $c);
    public function setForm(\COREPOS\common\mvc\ValueContainer $f);
    public function setConnection(\SQLManager $s);
}

