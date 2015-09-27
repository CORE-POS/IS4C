<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}

class PISignaturePage extends FannieRESTfulPage 
{
    protected function post_id_handler()
    {
        $png = $this->padToPng($this->form->sig);
        $this->shrinkFile($png);
        $mem = \COREPOS\Fannie\API\member\MemberREST::get($this->id);

        $mem['signature'] = $png;
        list($balance, $start) = $this->stockLookup();
        $mem['stock'] = $this->stockJSON($balance);
        $mem['date'] = $start;
        $pdf = new SubAgreement();
        $pdf->AliasNbPages();
        $pdf->AddPage();;
        $pdf->AutoFill($mem);
        $out = $pdf->Output($this->outputFile(), 'F');
        unlink($png);

        return 'PIDocumentsPage.php?id=' . $this->id;
    }

    private function outputFile()
    {
        $dir = '/var/www/cgi-bin/docfile/docfile/' . $this->id . '/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir . 'Generated Sub Agreement ' . $this->id . '.pdf';
    }

    private function stockLookup()
    {
        $equity = new EquityLiveBalanceModel($this->connection);
        $equity->whichDB($this->config->get('TRANS_DB'));
        $equity->memnum($this->id);
        if ($equity->load()) {
            return array($equity->payments(), $equity->startdate());
        } else {
            return array(0, date('Y-m-d'));
        }
    }

    private function stockJSON($balance)
    {
        $ret = array(
            'paid-in-full' => false,
            'total' => $balance,
            'a' => 0,
            'b' => 0,
        );
        if ($balance >= 100) {
            $ret['paid-in-full'] = true;
            $ret['a'] = 20;
            $ret['b'] = $balance-20;
        } elseif ($balance >= 20) {
            $ret['a'] = 20;
            $ret['b'] = $balance-20;
        } else {
            $ret['a'] = $balance;
        }

        return $ret;
    }

    private function shrinkFile($file)
    {
        $cmd = escapeshellcmd('convert')
            . ' -trim ' 
            . escapeshellarg($file)
            . ' ' 
            . escapeshellarg($file);
        exec($cmd, $output);

        return $output;
    }

    private function padToPng($data)
    {
        $pieces = explode(',', $data);
        $decoded = base64_decode($pieces[1]);
        $filename = tempnam(sys_get_temp_dir(), 'sub');
        file_put_contents($filename, $decoded);

        return $filename;
    }

    protected function get_id_view()
    {
        $this->addScript('../../../src/javascript/signature_pad-1.5.0/signature_pad.js');
        $this->addScript('sig.js');
        $mem = \COREPOS\Fannie\API\member\MemberREST::get($this->id);
        if ($mem === false) {
            return '<div class="alert alert-danger">Owner not found</div>' . $this->get_view();
        }
        $ret = '<form method="post" id="sign-form">
            <p>
                <label>Owner</label>
                <input type="hidden" name="id" value="' . $this->id . '" />
                ' . $mem['cardNo'] . '
            </p>
            <p id="sign-p">
                <label>Sign Here</label>
                <canvas id="sign-canvas" 
                    style="width:100%;height:200px;border-radius:4px;border-style:solid;border-color:black;border-width:1px;">
                </canvas>
            </p>
            <p class="clearfix">
                <button type="button" class="btn btn-default btn-lg pull-left" id="btn-clear">Clear</button>
                <button type="button" class="btn btn-default btn-lg pull-right" id="btn-accept">Accept</button>
            </p>
            </form>';
        return $ret;
    }

    protected function get_view()
    {
        $p = new SubAgreement();
        $this->addOnloadCommand("\$('input[name=id]').focus();\n");
        return '<form method="get">
            <div class="form-group">
                <label>Owner#</label>
                <input type="number" class="form-control" name="id" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Enter</button>
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

