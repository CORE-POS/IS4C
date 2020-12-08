<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FannieDB')) {
    include_once(__DIR__ . '/../../classlib2.0/data/FannieDB.php');
}
if (!class_exists('FpdfWithBarcode')) {
    include(__DIR__ . '/../labels/FpdfWithBarcode.php');
}

class PosKeyboard extends FanniePage
{

    protected $header = 'POS Keyboard';
    protected $title = 'POS Keyboard';
    public $description = '[POS Keyboard]';
    protected $keySize = 50;
    protected $fontSize = 8;
    protected $connection;

    public function __construct($dbc)
    {
        $this->connection = $dbc;
    }

    public function drawPDF($dbc)
    {
        $keySize = 15;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage('L');
        $pdf->SetMargins(10,10,10);
        define('FPDF_FONTPATH', __DIR__. '/../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
        $pdf->SetFont('Gill','B', 7);
        $pdf->SetXY(0, 0);

        $prep = $dbc->prepare("SELECT * FROM PosKeys WHERE print = 1");
        $res = $dbc->execute($prep);
        $positionKeys = array();
        while ($row = $dbc->fetchRow($res)) {
            $position = strval($row['pos']);
            foreach ($row as $k => $v) {
                if (!is_numeric($k)) {
                    $positionKeys[$position][$k] = $v;
                }
            }
        }

        $i = 0;
        $j = 0;
        foreach ($positionKeys as $position => $row) {
            if ($i % 16 == 0) {
                $j++;
                $i = 0;
            }
            $x = ($position - floor($position)) * 100;
            $y = floor($position);
            $top = $keySize * ($j + 1);
            $left = $keySize * ($i + 1);

            $bkgC = $row['rgb'];
            $bkg = array(
                "r" => substr($bkgC, 0, 3),
                "g" => substr($bkgC, 4, 3),
                "b" => substr($bkgC, 8, 3)
            );
            $fgC = $row['labelRgb'];
            $color = array(
                "r" => substr($fgC, 0, 3),
                "g" => substr($fgC, 4, 3),
                "b" => substr($fgC, 8, 3)
            );

            $text = $row['label'];
            $lines = substr_count($text, "<br/>");
            $labels = explode("|", $text);
            $text = str_replace("<br/>", "", $text);
            $text = str_replace("<u>", "-", $text);
            $text = str_replace("</u>", "-", $text);
            $text = str_replace("<i>", "", $text);
            $text = str_replace("</i>", "", $text);
            foreach ($labels as $k => $label) {
                $labels[$k] = str_replace("<u>", "", $label);
                $labels[$k] = str_replace("</u>", "", $labels[$k]);
                $labels[$k] = str_replace("<i>", "", $labels[$k]);
                $labels[$k] = str_replace("</i>", "", $labels[$k]);
            }

            // set bkg, color, top, left
            $pdf->SetFillColor($bkg['r'], $bkg['g'], $bkg['b']);
            $pdf->SetTextColor($color['r'], $color['g'], $color['b']);
            $pdf->SetDrawColor($color['r'], $color['g'], $color['b']);
            $pdf->SetXY($left, $top);
            $pdf->cell($keySize, $keySize, "", 1, 0, 'C', 1);
            foreach ($labels as $ln => $text) {
                $mod = 6;
                $border = 0;
                if (count($labels) == 2) {
                    $mod = 5;
                } elseif (count($labels) == 3) {
                    $mod = 3; 
                }
                $pdf->SetXY($left, $top + (4 * $ln) + $mod);
                $pdf->cell($keySize, 2, $text, 0, 0, 'C', 1);
                if (($row['underline'] & (1 << $ln)) != 0) {
                    $pdf->SetXY($left + 2.5, $top + (4 * $ln) + $mod + 2.5);
                    $pdf->cell($keySize - 5, 0.1, null, 'B', 0, 'C', 1);
                }
                if ($text == 'Baked') {
                    $pdf->Image("http://key/git/fannie/admin/PosKeyboards/noauto/images/muffin-icon.jpg", $left, $top, 15, 15, 'JPG');
                }
            }
            $i++;
        }

        $pdf->Output('PosKeyLabel.pdf', 'I', 0);

        return false;
    }

    public function drawSingleKey($dbc, $id, $n)
    {
        $keySize = 15;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage('L');
        $pdf->SetMargins(10,10,10);
        define('FPDF_FONTPATH', __DIR__. '/../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
        $pdf->SetFont('Gill','B', 7);
        $pdf->SetXY(0, 0);

        $args = array($id);
        $prep = $dbc->prepare("SELECT * FROM PosKeys WHERE print = 1 AND pos = ?");
        $res = $dbc->execute($prep, $args);
        $positionKeys = array();
        $row = $dbc->fetchRow($res);

        $i = 0;
        $j = 0;

        for ($n; $n>0; $n--) {
            if ($i % 16 == 0) {
                $j++;
                $i = 0;
            }
            $x = ($position - floor($position)) * 100;
            $y = floor($position);
            $top = $keySize;
            $left = $keySize * ($n + 1);

            $bkgC = $row['rgb'];
            $bkg = array(
                "r" => substr($bkgC, 0, 3),
                "g" => substr($bkgC, 4, 3),
                "b" => substr($bkgC, 8, 3)
            );
            $fgC = $row['labelRgb'];
            $color = array(
                "r" => substr($fgC, 0, 3),
                "g" => substr($fgC, 4, 3),
                "b" => substr($fgC, 8, 3)
            );

            $text = $row['label'];
            $lines = substr_count($text, "<br/>");
            $labels = explode("|", $text);
            $text = str_replace("<br/>", "", $text);
            $text = str_replace("<u>", "-", $text);
            $text = str_replace("</u>", "-", $text);
            $text = str_replace("<i>", "", $text);
            $text = str_replace("</i>", "", $text);
            foreach ($labels as $k => $label) {
                $labels[$k] = str_replace("<u>", "", $label);
                $labels[$k] = str_replace("</u>", "", $labels[$k]);
                $labels[$k] = str_replace("<i>", "", $labels[$k]);
                $labels[$k] = str_replace("</i>", "", $labels[$k]);
            }

            // set bkg, color, top, left
            $pdf->SetFillColor($bkg['r'], $bkg['g'], $bkg['b']);
            $pdf->SetTextColor($color['r'], $color['g'], $color['b']);
            $pdf->SetDrawColor($color['r'], $color['g'], $color['b']);
            $pdf->SetXY($left, $top);
            $pdf->cell($keySize, $keySize, "", 1, 0, 'C', 1);
            foreach ($labels as $ln => $text) {
                $mod = 6;
                $border = 0;
                if (count($labels) == 2) {
                    $mod = 5;
                } elseif (count($labels) == 3) {
                    $mod = 3; 
                }
                $pdf->SetXY($left, $top + (4 * $ln) + $mod);
                $pdf->cell($keySize, 2, $text, 0, 0, 'C', 1);
                if (($row['underline'] & (1 << $ln)) != 0) {
                    $pdf->SetXY($left + 2.5, $top + (4 * $ln) + $mod + 2.5);
                    $pdf->cell($keySize - 5, 0.1, null, 'B', 0, 'C', 1);
                }
            }
            $i++;
        }

        $pdf->Output('PosKeyLabel.pdf', 'I', 0);

        return false;
    }

    public function drawKeyboard()
    {
        $dbc = $this->connection;

        $prep = $dbc->prepare("SELECT * FROM PosKeys");
        $res = $dbc->execute($prep);
        $posKeys = array();
        while ($row = $dbc->fetchRow($res)) {
            $pos = strval($row['pos']);
            foreach ($row as $k => $v) {
                if (!is_numeric($k)) {
                    $posKeys[$pos][$k] = $v;
                }
            }
        }

        $keyboard = "";
        $keySize = $this->keySize;
        $keyboard .= "<div class=\"keyrow\" style=\"position: relative; height: {$keySize}px;\">";
        foreach ($posKeys as $pos => $row) {
            $x = round(($pos - floor($pos)) * 100);
            $y = round(floor($pos));
            $underline = $row['underline'];
            $cmd = $row['cmd'];
            $code = explode("|", $cmd);
            $top = $keySize * ($y + 1) - $keySize;
            $left = $keySize * ($x + 1) - $keySize*2;
            $fullLabel = htmlspecialchars($row['label']);
            $lines = explode("|", $row['label']);
            $label = '';
            foreach ($lines as $k => $line) {
                $line = strip_tags($line);
                if (($underline & 1 << $k) != 0) {
                    $line = "<u>$line</u>";
                }
                $label .= $line."<br/>";
            }

            $cell = <<<HTML
<span class="cell" 
    style="
        background: rgb({$row['rgb']});
        background-color: rgb({$row['rgb']});
        color: rgb({$row['labelRgb']});
        padding-top: 10px;
        top: {$top}px;
        left: {$left}px; 
    "
    data-first="{$code[0]}"
    data-second="{$code[1]}"
    data-third="{$code[2]}"
    data-rgb="{$row['rgb']}"
    data-labelRgb="{$row['labelRgb']}"
    data-command="{$row['cmd']}"
    data-underline="{$row['underline']}"
    id="pos$pos" 
    data-string="$fullLabel"
    title="{$code[0]}\r\n{$code[1]}\r\n{$code[2]}">$label</span>

HTML;
            $keyboard .= $cell;
        }
        $keyboard .= <<<HTML
<br/>
HTML;
        $keyboard .= "</div>";


        return <<<HTML
<div style="position: relative; height: 800px;">
    $keyboard
</div>
<style>{$this->css_content()}</style>
HTML;
    }

    public function css_content()
    {
        $keySize = $this->keySize;
        $fontSize = $this->fontSize;

        return <<<HTML
span.cell {
    cursor: pointer;
    position: absolute;
    height: {$keySize}px;
    width: {$keySize}px;
    font-size: {$fontSize}px;
    text-align: center;
    font-weight: bold;
    border: 1px solid lightgrey;
}
HTML;
    }

    public function unitTest($phpunit)
    {
    }
}
