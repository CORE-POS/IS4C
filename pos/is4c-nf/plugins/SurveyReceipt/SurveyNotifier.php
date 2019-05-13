<?php

use COREPOS\pos\lib\Notifier;

class SurveyNotifier extends Notifier
{
    public function draw()
    {
        if (CoreLocal::get('lotterySpin') === false || CoreLocal::get('lotterySpin') >= CoreLocal::get('nthReceipt')) {
            return '';
        }

        return <<<HTML
<div style="background:#ccc;border:solid 1px black;padding:7px;text-align:center;font-size:120%;">
&#x1F381;
</div>
HTML;
    }
}

