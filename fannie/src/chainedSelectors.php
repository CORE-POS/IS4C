<?php


    /*
    ** Class: chainedSelectors
    ** Description: This class allows you to create two selectors.  Selections
    ** made in the first selector cause the second selector to be updated.
    ** PHP is used to dynamically create the necessary JavaScript.
    */
    
    //These constants make the code a bit more readable.  They should be
    //used in in the creation of the input data arrays, too.
    define("CS_FORM", 0);
    define("CS_FIRST_SELECTOR", 1);
    define("CS_SECOND_SELECTOR", 2);
    
    define("CS_SOURCE_ID", 0);
    define("CS_SOURCE_LABEL", 1);
    define("CS_TARGET_ID", 2);
    define("CS_TARGET_LABEL", 3);
    
    class chainedSelectors
    {
        /*
        ** Properties
        */
        
        //Array of names for the form and the two selectors.
        //Should take the form of array("myForm", "Selector1", "Selector2")
        var $names;
        
        //Array of data used to fill the two selectors
        var $data;
        
        //Unique set of choices for the first selector, generated on init
        var $uniqueChoices;
        
        //Calculated counts
        var $maxTargetChoices;
        var $longestTargetChoice;


        /*
        ** Methods
        */
        
        //constructor
        function chainedSelectors($names, $data)
        {
            /*
            **copy parameters into properties
            */
            $this->names = $names;
            $this->data = $data;

            /*
            ** traverse data, create uniqueChoices, get limits
            */        
            foreach($data as $row)
            {
                //create list of unique choices for first selector
                $this->uniqueChoices[($row[CS_SOURCE_ID])] = $row[CS_SOURCE_LABEL];    

                //find the maximum choices for target selector
				//added @ before var to fix maxPerChoice var error - jb
                @$maxPerChoice[($row[CS_SOURCE_ID])]++;

                //find longest value for target selector
                if(strlen($row[CS_TARGET_LABEL]) > $this->longestTargetChoice)
                {
                    $this->longestTargetChoice=strlen($row[CS_TARGET_LABEL]);
                }
            }
            
            $this->maxTargetChoices = max($maxPerChoice);
        }

        //prints the JavaScript function to update second selector
        function printUpdateFunction($selected_item)
        {
            /*
            ** Create some variables to make the code
            ** more readable.
            */

			$selected_index = 0;

            $sourceSelector = "document." . $this->names[CS_FORM] . "." . 
                $this->names[CS_FIRST_SELECTOR];
            $targetSelector = "document." . $this->names[CS_FORM] . "." . 
                $this->names[CS_SECOND_SELECTOR];
        
            /*
            ** Start the function
            */
            print("function update" .$this->names[CS_SECOND_SELECTOR] . "()\n");

            print("{\n");

            /*
            ** Add code to clear out next selector
            */
            print("\t//clear " . $this->names[CS_SECOND_SELECTOR] . "\n");
            print("\tfor(index=0; index < $this->maxTargetChoices; index++)\n");
            print("\t{\n");
            print("\t\t" . $targetSelector . ".options[index].text = '';\n");
            print("\t\t" . $targetSelector . ".options[index].value = '';\n");
            print("\t}\n\n");
            print("\t" . $targetSelector . ".options[" . $selected_index . "].selected = true;\n\n");

            /*
            ** Add code to find which was selected
            */
            print("whichSelected = " . $sourceSelector . ".selectedIndex;\n");

            /*
            ** Add giant "if" tree that puts values into target selector
            ** based on which selection was made in source selector
            */

            //loop over each value of this selector
            foreach($this->uniqueChoices as $sourceValue=>$sourceLabel)
            {
				if($sourceValue == $selected_item[12]) {		// [12] = Department number
                    // 13May14 EL Adjusted offset.
					$selected_index = $selected_item[31];		// [28] = subdept. #
					//$selected_index = $selected_item[28];		// [28] = subdept. #
                    /* the index of the subdept just happens to be preceded by the dept_no
                     * 13May14 EL I don't understand the comment above.
                     *            With this adjustment the function doesn't work; without it it does.
                     */
                    //$selected_index = $selected_index - ($sourceValue * 100);
				} else {
					$selected_index = 0;
				}

                print("\tif(" . $sourceSelector .
                    ".options[whichSelected].value == " .
                    "'$sourceValue')\n");
                print("\t{\n");

                $count=0;
                foreach($this->data as $row)
                {
                    if($row[0] == $sourceValue)
                    {
                        $optionValue = $row[CS_TARGET_ID];
                        $optionLabel = $row[CS_TARGET_LABEL];

                        print("\t\t" . $targetSelector .
                            ".options[$count].value = '$optionValue';\n");
                        print("\t\t" . $targetSelector .
                            ".options[$count].text = '$optionLabel';\n\n");
						if($count + 1 == $selected_index){
							  print("\t\t" . $targetSelector .
		                            ".options[$count].selected = true;\n");
						}
                        $count++;
                    }
                }

                print("\t}\n\n");
            }

	    print("\tif(window.cscallback){\n");
	    print("\t\tcscallback();\n");
  	    print("\t}\n");
            print("\treturn true;\n");
            print("}\n\n");

        }

        //print the two selectors
        function printSelectors($item_selected)
        {
            /*
            **create prefilled first selector
            */
            $selected=FALSE;
            print("<select name=\"" . $this->names[CS_FIRST_SELECTOR] . "\" " .
		"id=\"" .$this->names[CS_FIRST_SELECTOR] . "\" " .
                "onChange=\"update".$this->names[CS_SECOND_SELECTOR]."();\">\n");
            foreach($this->uniqueChoices as $key=>$value)
            {
                print("\t<option value=\"$key\"");
                if($key == $item_selected[12]) 	// [12] = department
                {
                    print(" selected=\"selected\"");
                    $selected=FALSE;
                }
                print(">$value</option>\n");
            }
            print("</select>\n");

            /*
            **create empty target selector
            */
            $dummyData = str_repeat("X", $this->longestTargetChoice);
            
            print("<select name=\"".$this->names[CS_SECOND_SELECTOR]."\">\n");
            for($i=0; $i < $this->maxTargetChoices; $i++)
            {
                print("\t<option value=\"\">$dummyData</option>\n");
            }
            print("</select>\n");

        }
        
        //prints a call to the update function
        function initialize()
        {
            print("update" .$this->names[CS_SECOND_SELECTOR] . "();\n");
        }
    }

//
//	PHP INPUT DEBUG SCRIPT -- very useful!
//

/*
function debug_p($var, $title) 
{
    print "<h4>$title</h4><pre>";
    print_r($var);
    print "</pre>";
}

debug_p($_REQUEST, "all the data coming in");
*/
?>
