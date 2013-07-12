<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include('../../../config.php');
if (!class_exists('FannieAPI'))
	include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
ini_set('display_errors',1);

class PIMemberPage extends PIKillerPage {
	
	protected function get_handler(){
		global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
		$this->card_no = $this->id;
		if ($this->card_no === False)
			return $this->unknown_request_handler();

		$this->title = 'Member '.$this->card_no;

		$dbc = FannieDB::get($FANNIE_OP_DB);

		$this->models['custdata'] = $this->get_model($dbc, 'CustdataModel', 
					array('CardNo'=>$this->card_no), 'personNum');

		$this->models['meminfo'] = $this->get_model($dbc, 'MeminfoModel',
					array('card_no'=>$this->card_no));

		$this->models['memDates'] = $this->get_model($dbc, 'MemDatesModel',
					array('card_no'=>$this->card_no));

		$this->models['memberCards'] = $this->get_model($dbc, 'MemberCardsModel',
					array('card_no'=>$this->card_no));

		$susp = $this->get_model($dbc,'SuspensionsModel',array('cardno'=>$this->card_no));
		if ($susp->load()) $this->models['suspended'] = $susp;

		$dbc = FannieDB::get($FANNIE_TRANS_DB);

		$this->models['equity'] = $this->get_model($dbc, 'EquityLiveBalanceModel',
					array('memnum'=>$this->card_no));

		$this->models['ar'] = $this->get_model($dbc, 'ArLiveBalanceModel',
					array('card_no'=>$this->card_no));

		return True;
	}

	protected function post_handler(){
		global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
		$this->card_no = $this->id;
		if ($this->card_no === False)
			return $this->unknown_request_handler();

		$dbc = FannieDB::get($FANNIE_OP_DB);

		$dates = $this->get_model($dbc, 'MemDatesModel', array('card_no'=>$this->card_no));
		$dates->start_date(FormLib::get_form_value('start_date'));
		$dates->end_date(FormLib::get_form_value('end_date'));
		$dates->save();

		$upc = FormLib::get_form_value('upc');
		if ($upc != ''){
			$card = $this->get_model($dbc, 'MemberCardsModel', array('card_no'=>$this->card_no));
			$card->upc(str_pad($upc,13,'0',STR_PAD_LEFT));
			$card->save();
		}

		$meminfo = new MeminfoModel($dbc);
		$meminfo->card_no($this->card_no);
		$meminfo->city(FormLib::get_form_value('city'));
		$meminfo->state(FormLib::get_form_value('state'));
		$meminfo->zip(FormLib::get_form_value('zip'));
		$meminfo->phone(FormLib::get_form_value('phone'));
		$meminfo->email_1(FormLib::get_form_value('email'));
		$meminfo->email_2(FormLib::get_form_value('phone2'));
		$meminfo->ads_OK(FormLib::get_form_value('mailflag'));
		$street = FormLib::get_form_value('address1');
		if (FormLib::get_form_value('address2') !== '')
			$street .= "\n".FormLib::get_form_value('address2');
		$meminfo->street($street);
		$meminfo->save();

		$custdata = new CustdataModel($dbc);
		$custdata->CardNo($this->card_no);
		$custdata->personNum(1);
		$custdata->load();

		$custdata->FirstName(FormLib::get_form_value('FirstName'));
		$custdata->LastName(FormLib::get_form_value('LastName'));
		$custdata->memType(FormLib::get_form_value('memType'));
		$custdata->memDiscountLimit(FormLib::get_form_value('chargelimit'));

		$default = $this->get_model($dbc, 'MemdefaultsModel', array('memtype'=>$custdata->memType()));
		$custdata->Type($default->cd_type());
		$custdata->Discount($default->discount());
		$custdata->staff($default->staff());
		$custdata->SSI($default->SSI());

		$custdata->save();

		$personNum=2;
		$names = array('first'=>FormLib::get_form_value('fn'),
				'last'=>FormLib::get_form_value('ln'));
		foreach($names as $set){
			if ($set['first']=='' && $set['last']=='')
				continue; // deleted named
			$custdata->personNum($personNum);
			$custdata->FirstName($set['first']);
			$custdata->LastName($set['last']);
			$custdata->save();
			$personNum++;
		}

		// if submission has fewer names than
		// original form, delete the extras
		for($i=$personNum; $i<=4; $i++){
			$custdata->personNum($i);
			$custdata->delete();
		}

		header('Location: PIMemberPage.php?id='.$this->card_no);
		return False;
	}

	protected function get_id_view(){
		global $FANNIE_OP_DB, $FANNIE_URL;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		ob_start();
		echo '<form action="PIMemberPage.php" ';
		if (FormLib::get_form_value('edit', False) === False)
			echo 'method="get">';
		else
			echo 'method="post">';
		echo '<input type="hidden" name="id" value="'.$this->card_no.'" />';
		echo "<table>";
		echo "<tr>";
		echo "<td class=\"greenbg yellowtxt\">Owner Num</td>";
		echo "<td class=\"greenbg yellowtxt\">".$this->card_no."</td>";
		
		$status = $this->models['custdata'][0]->Type();
		if($status == 'PC') $status='ACTIVE';
		elseif($status == 'REG') $status='NONMEM';
		elseif($status == 'INACT2') $status='TERM (PENDING)';

		if (isset($this->models['suspended'])){
			echo "<td bgcolor='#cc66cc'>$status</td>";
			echo "<td colspan=3>";
			if ($this->models['suspended']->reason() != '')
				echo $this->models['suspended']->reason();
			else {
				$reasons = new ReasoncodesModel($dbc);
				foreach($reasons->find('mask') as $r){
					if (((int)$r->mask() & (int)$this->models['suspended']->reasoncode()) != 0){
						echo $r->textStr().' ';
					}
				}
			}
		}
		else {
			echo "<td>$status</td>";
		}
		echo "<td colspan=2><a href=suspensionHistory.php?memNum=".$this->card_no.">History</a>";
		echo "<td><a href=\"{$FANNIE_URL}ordering/clearinghouse.php?card_no=".$this->card_no."\">Special Orders</a></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td class=\"yellowbg\">First Name: </td>";
		echo '<td>'.$this->text_or_field('FirstName',$this->models['custdata'][0]->FirstName()).'</td>';
		echo "<td class=\"yellowbg\">Last Name: </td>";
		echo '<td>'.$this->text_or_field('LastName',$this->models['custdata'][0]->LastName()).'</td>';
		echo '</tr>';

		echo "<tr>";
		$address = explode("\n",$this->models['meminfo']->street(),2);
		echo "<td class=\"yellowbg\">Address1: </td>";
		echo '<td>'.$this->text_or_field('address1',$address[0]).'</td>';
		echo "<td class=\"yellowbg\">Gets mailings: </td>";
		echo '<td>'.$this->text_or_select('mailflag',$this->models['meminfo']->ads_OK(),
					array(1,0), array('Yes','No')).'</td>';
		echo "</tr>";

		echo "<tr>";
		echo "<td class=\"yellowbg\">Address2: </td>";
		echo '<td>'.$this->text_or_field('address2',(isset($address[1])?$address[1]:'')).'</td>';
		echo "<td class=\"yellowbg\">UPC: </td>";
		echo '<td colspan=\"2\">'.$this->text_or_field('upc',$this->models['memberCards']->upc()).'</td>';
                echo "</tr>";

		echo "<tr>";
		echo "<td class=\"yellowbg\">City: </td>";
		echo '<td>'.$this->text_or_field('city',$this->models['meminfo']->city()).'</td>';
		echo "<td class=\"yellowbg\">State: </td>";
		echo '<td>'.$this->text_or_field('state',$this->models['meminfo']->state()).'</td>';
		echo "<td class=\"yellowbg\">Zip: </td>";
		echo '<td>'.$this->text_or_field('zip',$this->models['meminfo']->zip()).'</td>';
                echo "</tr>";

                echo "<tr>";
		echo "<td class=\"yellowbg\">Phone Number: </td>";
		echo '<td>'.$this->text_or_field('phone',$this->models['meminfo']->phone()).'</td>';
		echo "<td class=\"yellowbg\">Start Date: </td>";
		$start = $this->models['memDates']->start_date();
		if (strstr($start,' ') !== False) list($start,$junk) = explode(' ',$start,2);
		if ($start == '1900-01-01' || $start == '0000-00-00') $start = '';
		echo '<td>'.$this->text_or_field('start_date',$start).'</td>';
		echo "<td class=\"yellowbg\">End Date: </td>";
		$end = $this->models['memDates']->end_date();
		if (strstr($end,' ') !== False) list($end,$junk) = explode(' ',$end,2);
		if ($end == '1900-01-01' || $end == '0000-00-00') $end = '';
		echo '<td>'.$this->text_or_field('end_date',$end).'</td>';
                echo "</tr>";

		echo "<tr>";
		echo "<td class=\"yellowbg\">Alt. Phone: </td>";
		echo '<td>'.$this->text_or_field('phone2',$this->models['meminfo']->email_2()).'</td>';
		echo "<td class=\"yellowbg\">E-mail: </td>";
		echo '<td>'.$this->text_or_field('email',$this->models['meminfo']->email_1()).'</td>';
		echo "</tr>";

                echo "<tr>";
		echo "<td class=\"yellowbg\">Stock Purchased: </td>";
		echo "<td>".sprintf('%.2f',$this->models['equity']->payments()).'</td>';
		echo "<td class=\"yellowbg\">Mem Type: </td>";
		$labels = array();
		$opts = array();
		$memtypes = new MemtypeModel($dbc);
		foreach($memtypes->find('memtype') as $mt){
			$labels[] = $mt->memDesc();
			$opts[] = $mt->memtype();
		}
		echo '<td>'.$this->text_or_select('memType',$this->models['custdata'][0]->memType(),
				$opts, $labels).'</td>';
		echo "<td class=\"yellowbg\">Discount: </td>";
		echo '<td>'.$this->models['custdata'][0]->Discount().'</td>';
		echo "</tr>";

		echo "<tr>";
		echo "<td class=\"yellowbg\">Charge Limit: </td>";
		echo '<td>'.$this->text_or_field('chargelimit',$this->models['custdata'][0]->memDiscountLimit()).'</td>';
		echo "<td class=\"yellowbg\">Current Balance: </td>";
		echo '<td>'.sprintf('%.2f',$this->models['ar']->balance()).'</td>';
		echo "</tr>";

		echo "<tr class=\"yellowbg\"><td colspan=6></td></tr>";

                echo "<tr>";
		echo '<td colspan="2" class="greenbg yellowtxt">Additional household members</td>';
		echo '<td></td>';
		echo '<td class="greenbg yellowtxt">Additional Notes</td>';
		echo "<td><a href=noteHistory.php?memNum=".$this->card_no.">Notes history</a></td>";
                echo "</tr>";

                echo "<tr>";
		echo '<td></td>';
		echo '<td class="yellowbg">First Name</td>';
		echo '<td class="yellowbg">Last Name</td>';
		echo "<td colspan=4 width=\"300px\" rowspan=8></td>";
		echo '</tr>';

		for($i=1;$i<count($this->models['custdata']);$i++){
			$cust = $this->models['custdata'][$i];
			echo '<tr>';
			echo '<td class="yellowbg">'.($i+1).'</td>';
			echo '<td>'.$this->text_or_field('fn[]',$cust->FirstName()).'</td>';
			echo '<td>'.$this->text_or_field('ln[]',$cust->LastName()).'</td>';
		}
		for ($i=count($this->models['custdata'])-1;$i<3;$i++){
			echo '<tr>';
			echo '<td class="yellowbg">'.($i+1).'</td>';
			echo '<td>'.$this->text_or_field('fn[]','').'</td>';
			echo '<td>'.$this->text_or_field('ln[]','').'</td>';

		}
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		if (FormLib::get_form_value('edit',False) === False){
			echo '<input type="hidden" name="edit" />';
			echo '<input type="submit" value="Edit Member" />';
		}
		else
			echo '<input type="submit" value="Save Member" />';
		echo '</td>';

		echo '</tr>';

		echo "</table>";
		return ob_get_clean();
	}

	private function text_or_field($name, $value, $attributes=array()){
		if (FormLib::get_form_value('edit',False) === False)
			return $value;
		
		$tag = '<input type="text" name="'.$name.'" value="'.$value.'"';
		foreach($attributes as $key=>$val)
			$tag .= ' '.$key.'="'.$val.'"';
		$tag .= '/>';
		return $tag;
	}

	private function text_or_select($name, $value, $opts, $labels=array(), $attributes=array()){
		if (FormLib::get_form_value('edit',False) === False){
			if (!empty($labels)){
				for($i=0;$i<count($opts);$i++){
					if ($opts[$i] == $value)
						return (isset($labels[$i])?$labels[$i]:$value);
				}
			}
			else
				return $value;
		}
	
		$tag = '<select name="'.$name.'"';
		foreach($attributes as $key=>$val)
			$tag .= ' '.$key.'="'.$val.'"';
		$tag .= '>';
		for($i=0;$i<count($opts);$i++){
			$tag .= sprintf('<option value="%s" %s>%s</option>',
				$opts[$i],
				($opts[$i]==$value ? 'selected': ''),
				(isset($labels[$i]) ? $labels[$i] : $opts[$i])
			);
		}
		$tag .= '</select>';
		return $tag;
	}

	public function css_content(){
		return '
			.greenbg { background: #006633; }
			.greentxt { color: #006633; }
			.yellowbg { background: #FFFF33; }
			.yellowtxt { color: #FFFF33; }
		';
	}
}

FannieDispatch::go();

?>
