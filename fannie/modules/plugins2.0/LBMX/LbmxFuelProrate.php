<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class LbmxFuelProrate extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $header = 'LBMX Fuel Pro-Rater';
    protected $title = 'LBMX Fuel Pro-Rater';

    protected $skip_first = 5;

    protected $preview_opts = array(
        'coding' => array(
            'display_name' => 'Coding',
            'default' => 6,
            'required' => true
        ),
        'amount' => array(
            'display_name' => 'Amount',
            'default' => 8,
            'required' => true
        ),
    );

    private $results = '';

    public function process_file($linedata, $indexes)
    {
        $i = 0;
        $acc = array();
        $fuel = 0;
        $fuelcode = '';
        $total = 0;
        /**
         * Pass 1:
         * Extract fuel cost and coding
         * Accumulate other codings and total amounts
         * Sum non-fuel total
         */
        foreach ($linedata as $line) {
            if ($i < $this->skip_first) {
                $i++;
                continue;
            }
            $coding = trim($line[$indexes['coding']]);
            $amount = trim($line[$indexes['amount']]);
            if (!is_numeric($amount)) {
                continue;
            }
            if ($coding === '') {
                break;
            }
            if (substr($coding, 0, 5) == '59100') {
                $fuel = $amount;
                $fuelcode = $coding;
            } else {
                $acc[$coding] = $amount;
                $total += $amount;
            }
        }

        /**
         * Pass 2
         * Calculate pro-rated fuel cost for each coding
         * Re-sum total after pro-rated amounts are rounded
         * If the total no longer matches, apply adjustment
         * to Grocery
         */
        $newttl = 0;
        foreach (array_keys($acc) as $coding) {
            $acc[$coding] = round(($acc[$coding] / $total) * $fuel, 2);
            $newttl += $acc[$coding];
        }
        $newttl = round($newttl, 2);
        if (abs($fuel - $newttl) > 0.005) {
            $diff = $fuel - $newttl;
            foreach (array_keys($acc) as $coding) {
                if (substr($coding, 0, 5) == '51400') {
                    $acc[$coding] = round($acc[$coding] + $diff, 2);
                }
            }
        }

        /**
         * Kick out the data to CSV
         */
        $this->filename = tempnam('noauto/fuel', 'fuel') . '.csv';
        $fp = fopen($this->filename, 'w');
        $stamp = strtotime(FormLib::get('tdate', date('Y-m-d')));
        $dates = array(date('ymd', $stamp), date('m/d/y', $stamp));
        foreach ($acc as $code => $amt) {
            fwrite($fp, $dates[0] . ',');
            fwrite($fp, $dates[1] . ',');
            fwrite($fp, str_replace('-', '', $code) . ',');
            fwrite($fp, $amt . ',');
            fwrite($fp, '0.00,');
            fwrite($fp, "Fuel Reallocation\r\n");
        }
        fwrite($fp, $dates[0] . ',');
        fwrite($fp, $dates[1] . ',');
        fwrite($fp, str_replace('-', '', $fuelcode) . ',');
        fwrite($fp, '0.00,');
        fwrite($fp, $fuel . ',');
        fwrite($fp, "Fuel Reallocation\r\n");
        fclose($fp);

        return true;
    }

    public function preview_content()
    {
        return <<<HTML
<b>Date</b>: <input type="text" name="tdate" class="form-control date-field" required />
HTML;
    }

    public function results_content()
    {
        $file = basename($this->filename);
        return <<<HTML
<p>
Conversion complete.
</p>
<p>
<a href="noauto/fuel/{$file}">{$file}</a><br />
</p>
<p>
<a href="LbmxFuelProrate.php">Process another file</a>
</p>
HTML;
    }

}

FannieDispatch::conditionalExec();

