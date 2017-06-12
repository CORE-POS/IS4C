<?php

class SubAgreement extends FPDF
{

    function Header()
    {
        $this->Image(dirname(__FILE__) . '/subAgreement.jpg',0,0,200);  
    }

    private function getName($json, $index)
    {
        $keys = array_keys($json);
        if (!isset($keys[$index])) {
            return '';
        }

        $index = $keys[$index];
        if (isset($json[$index])) {
            return $json[$index]['firstName'] . ' ' . $json[$index]['lastName'];
        }

        return '';
    }
    
    function AutoFill($meminfo)
    {
        $this->SetFont('Arial','',10);
        
        if ($meminfo['stock']['paid-in-full']) {
            $this->SetXY(15,94);
        } else {
            $this->SetXY(15,106);
        }
        $this->Cell(0,0, 'X', 0, 1);
      
        if ($meminfo['stock']['b'] > 0 && $meminfo['stock']['b'] < 80) {
            $this->SetXY(125,106);
            $this->Cell(0,0, sprintf('%.2f', $meminfo['stock']['b']), 0, 1);
        }
        
        if ($meminfo['stock']['total'] < 100) {
            $this->SetXY(60,113);
            $this->Cell(0,0,sprintf('%.2f', 100-$meminfo['stock']['total']), 0, 1);

            $this->SetXY(135,113);
            $start = strtotime($meminfo['date']);
            $nextyear = mktime(0,0,0, date('n',$start), date('j',$start), date('Y',$start)+1);
            $this->Cell(0,0,date('Y-m-d', $nextyear), 0, 1);
        }

        $primary = \COREPOS\Fannie\API\Member\MemberREST::getPrimary($meminfo);
        $household = \COREPOS\Fannie\API\Member\MemberREST::getHousehold($meminfo);
        
        $this->SetXY(53,134);
        $this->Cell(0,0,$this->getName($primary, 0), 0, 1);
        
        $this->SetXY(20,153);
        $this->Cell(0,0,$this->getName($household, 0), 0, 1);
       
        $this->SetXY(75,153);
        $this->Cell(0,0,$this->getName($household, 1), 0, 1);
        
        $this->SetXY(135,153);
        $this->Cell(0,0,$this->getName($household, 2), 0, 1);
        
        $this->SetXY(36,161);
        $this->Cell(0,0,$meminfo['addressFirstLine'], 0, 1);
       
        $this->SetXY(146,161);
        $this->Cell(0,0,$meminfo['addressSecondLine'], 0, 1);
        
        $this->SetXY(22,169);
        $this->Cell(0,0,$meminfo['city'], 0, 1);
       
        $this->SetXY(98,170);
        $this->Cell(0,0,$meminfo['state'], 0, 1);
        
        $this->SetXY(149,170);
        $this->Cell(0,0,$meminfo['zip'], 0, 1);
        
        $this->SetXY(25,178);
        $this->Cell(0,0,$primary[0]['phone'], 0, 1);

        $phoneAlt = ''; 
        $this->SetXY(104,179);
        $this->Cell(0,0,$phoneAlt, 0, 1);
        
        $this->SetXY(24,186);
        $this->Cell(0,0,$primary[0]['email'], 0, 1);
        
        $this->SetXY(169,188);
        $this->Cell(0,0,$meminfo['contactAllowed'] ? 'X' : '', 0, 1);
        
        $this->SetXY(176,188);
        $this->Cell(0,0,(!$meminfo['contactAllowed']) ? 'X' : '', 0, 1);
        
        $this->Image($meminfo['signature'], 35, 203, 60, 0, 'jpg');
        
        $this->SetXY(142,209);
        $this->Cell(0,0,date('Y-m-d', strtotime($meminfo['date'])), 0, 1);
        
        $this->SetXY(40,231);
        $this->Cell(0,0,$meminfo['cardNo'], 0, 1);

        $this->SetXY(151,232);
        $this->Cell(0,0,$meminfo['stock']['total'], 0, 1);
    }
}

