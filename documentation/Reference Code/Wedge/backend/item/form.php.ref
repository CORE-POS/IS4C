<?php
	function form($backoffice) {
		if (isset($backoffice['product_detail'])) {
			require_once($_SERVER["DOCUMENT_ROOT"]."/lib/table_departments.php");
				$departments_result=get_departments(&$backoffice);
			require_once($_SERVER["DOCUMENT_ROOT"]."/lib/table_subdepts.php");
				$subdepartments_result=get_subdepartments(&$backoffice);
				
			$html='
			<form action="./" method="post" name="edit">
				<div class="edit_column">
					<fieldset>
						<legend>Core</legend>
						<input name="a" type="hidden" value="update"/>
						<input name="edit_id" type="hidden" value="'.$backoffice['product_detail']['id'].'"/>
						<div class="edit_row">
							<input readonly name="edit_upc" type="text" value="'.$backoffice['product_detail']['upc'].'"/>
						</div>
						<div class="edit_row">
							<span class="note">Last modified: '.$backoffice['product_detail']['modified'].'</span>
						</div>
						<div class="edit_row">
							<span class="note">Last sold: Unavailable</span>
						</div>
						</fieldset>
					<fieldset>
						<legend>Sorting</legend>
						<div class="edit_row">
							<label for="edit_description"><span class="accesskey">D</span>escription</label>
							<input accesskey="d" id="edit_description" name="edit_description" onkeyup="valid_description(this)" type="text" value="'.$backoffice['product_detail']['description'].'"/>
						</div>
						<div class="edit_row">
							<label for="edit_department">Dep<span class="accesskey">a</span>rtment</label>
							<select accesskey="a" id="edit_department" name="edit_department" size=1>';
				while ($row=mysql_fetch_array($departments_result)) {
					$html.='
								<option '.($row['dept_no']==$backoffice['product_detail']['department']?'selected ':'').'value="'.$row['dept_no'].'">'.$row['dept_name'].'</option>';
				}
				
				$html.='
							</select>
						</div>
						<div class="edit_row">
							<label for="edit_subdepartment">Subdepartme<span class="accesskey">n</span>t</label>
							<select accesskey="n" id="edit_subdepartment" name="edit_subdepartment" size=1>';
				
				while ($row=mysql_fetch_array($subdepartments_result)) {
					$html.='
								<option '.($row['subdept_no']==$backoffice['product_detail']['subdept']?'selected ':'').'value="'.$row['subdept_no'].'">'.$row['subdept_name'].'</option>';
				}
				
				$html.='
							</select>
						</div>
					</fieldset>
					<fieldset>
						<legend>Pricing</legend>
						<div class="edit_row">
							<label for="edit_price"><span class="accesskey">P</span>rice</label>
							<input accesskey="p" id="edit_price" name="edit_price" onkeyup="valid_price(this)" type="text" value="'.money_format("%!.2n", $backoffice['product_detail']['normal_price']).'"/>
						</div>
					</fieldset>
				</div>
				<div class="edit_column">
					<fieldset>
						<legend>Attributes</legend>
						<div class="edit_subcolumn">
							<label for="edit_tax"><span class="accesskey">T</span>ax</label>
							<input accesskey="t" id="edit_tax" name="edit_tax" onkeyup="valid_tax(this)" type="text" value="'.$backoffice['product_detail']['tax'].'"/>
							<label for="edit_tareweight">Tar<span class="accesskey">e</span></label>
							<input accesskey="e" id="edit_tareweight" name="edit_tareweight" onkeyup="valid_tareweight(this)" type="text" value="'.$backoffice['product_detail']['tareweight'].'"/>
							<label for="edit_size"><span class="accesskey">S</span>ize</label>
							<input accesskey="s" id="edit_size" name="edit_size" onkeyup="valid_size(this)" type="text" value="'.$backoffice['product_detail']['size'].'"/>
							<label for="edit_unitofmeasure"><span class="accesskey">U</span>nit</label>
							<input accesskey="u" id="edit_unitofmeasure" name="edit_unitofmeasure" onkeyup="valid_unitofmeasure(this)" type="text" value="'.$backoffice['product_detail']['unitofmeasure'].'"/>
							<label for="edit_deposit">Dep<span class="accesskey">o</span>sit</label>
							<input accesskey="o" id="edit_deposit" name="edit_deposit" onkeyup="valid_deposit(this)" type="text" value="'.money_format("%!.2n", $backoffice['product_detail']['deposit']).'"/>
						</div>
						<div class="edit_subcolumn">
							<label for="edit_foodstamp"><span class="accesskey">F</span>oodstamp</label>
							<input accesskey="f" '.($backoffice['product_detail']['foodstamp']?'checked ':'').'id="edit_foodstamp" name="edit_foodstamp" type="checkbox"/>
							<label for="edit_weighed"><span class="accesskey">W</span>eighed</label>
							<input accesskey="w" '.($backoffice['product_detail']['scale']?'checked ':'').'id="edit_weighed" name="edit_scale" type="checkbox"/>
							<label for="edit_advertised">Adve<span class="accesskey">r</span>tised</label>
							<input accesskey="r" '.($backoffice['product_detail']['advertised']?'checked ':'').'id="edit_advertised" name="edit_advertised" type="checkbox"/>
							<label for="edit_discount">D<span class="accesskey">i</span>scount</label>
							<input accesskey="i" '.($backoffice['product_detail']['discount']?'checked ':'').'id="edit_discount" name="edit_discount" type="checkbox"/>
							<label for="edit_wicable">WI<span class="accesskey">C</span></label>
							<input accesskey="c" '.($backoffice['product_detail']['wicable']?'checked ':'').'id="edit_wicable" name="edit_wicable" type="checkbox"/>
							<label for="edit_inuse">Acti<span class="accesskey">v</span>e</label>
							<input accesskey="v" '.($backoffice['product_detail']['inUse']?'checked ':'').'id="edit_inuse" name="edit_inuse" type="checkbox"/>
						</div>
					</fieldset>
					<fieldset>
						<legend>Actions</legend>
						<input disabled type="button" value="Clone"/>
						<input disabled type="button" value="Delete"/>
						<input disabled type="button" value="Reset"/>
						<input type="submit" value="Save"/>
					</fieldset>
				</div>
			</form>';
		} else {
			$html='
			<!-- Some default message? -->';
		}

		return $html; 		
	}
?>