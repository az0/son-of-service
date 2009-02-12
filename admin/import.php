<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved. 
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Import legacy data.
 *
 * $Id: import.php,v 1.18 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/import.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

// todo
// - fix phone numbers (go into separate table)
// - progress indicator
// - multiple instances
// - automatically separate suffixes from last name field

function import_legacy1()
{
?>

<P class="instructionstext">Use this feature to import names and addresses
from a spreadsheet or other database.</P>

<P class="instructionstext">Specify the filename of a file to upload and
import.  The file must be a comma-delimited file (CSV) and the first row
must contain column heading names.</P>

<P class="instructionstext">Note: Please wait after beginning this operation.  Some time may pass during which it seems no progress is occuring.</P>

<FORM enctype="multipart/form-data" method="post" action=".">
<INPUT type="hidden" name="import_legacy" value="2">
<INPUT type="hidden" name="MAX_FILE_SIZE" value="2000000">
File name <INPUT type="file" name="userfile">
<INPUT type="submit" value="Send file">
</FORM>
<?php
} /* import_legacy1() */

// 'phone_home', 'phone_work', 'phone_cell'
$importable_fields = array('prefix', 'first', 'middle', 'last', 'suffix', 'organization', 'street', 'city', 'state', 'postal_code', 'country', 'email_address','phone_home','phone_work','phone_cell');

function import_legacy2()
{
    global $importable_fields;

    // move file (safely)
    
    $dname = SOS_PATH . 'data/'. $_FILES['userfile']['name'];
    
    $_SESSION['import']['dname'] = $dname;
    
//    echo ("<P>Debug: from ". $_FILES['userfile']['tmp_name']. " to $dname</P>");
    //print_r($_FILES);
    
    if (!@move_uploaded_file($_FILES['userfile']['tmp_name'], $dname))
    {
	process_system_error("Unable to move uploaded file.");
	return;
    }
    
    // open file to be imported
    
    $f = fopen($dname, 'r');
    
    if (!$f)
    {
	process_system_error("Unable to open uploaded file.");
	return;
    }
    
    // CSV?
    
    $line = fgets($f);
    
    if (0 == substr_count($line, ',') or preg_match('/.(xls|sxc)/i', $dname))
    {
	process_user_error(_("The file you uploaded is not a CSV file."));
	return;
    }
    
    // get header
    
    rewind($f);
    
    $header = fgetcsv($f, 1000, ",");
    
    if (!$header)
    {
	process_system_error("Unable to read uploaded file.");
	return;
    }
    
?>
<FORM method="post" action=".">
<TABLE>
<TR>
<TH>SOS Field</TH>
<TH>Legacy field</TH>
</TR>
<?php

    foreach ($importable_fields as $f)
    {
	echo ("<TR>\n");
	echo ("<TH class=\"vert\">$f</TH>\n");
	echo ("<TD>");
	echo ("<SELECT name=\"".$f."\">\n");
	echo ("<OPTION>None</OPTION>\n");
	$i = 0;
	foreach ($header as $h)
	{
	    $i++;
	    if (levenshtein($h, $f) < 2)
		$selected = ' SELECTED';
		else $selected = '';
	    echo ("<OPTION".$selected." value=\"$i\">$h</OPTION>\n");
	}
	echo ("</SELECT>\n");
	echo ("</TD>\n");
	echo ("</TR>\n");
    }
?>
</TABLE>

<P class="instructionstext">Note: Please wait after beginning this operation.  Some time may pass during which it seems no progress is occuring.</P>

<INPUT type="hidden" name="import_legacy" value="3">
<INPUT type="submit" name="submit" value="<?php echo _("Import"); ?>"> 
</FORM>
<?php
    
} /* import_legacy2() */


function import_legacy3()
{
    global $importable_fields;
    global $db;
    
    
    // todo: validate here
    $dname = $_SESSION['import']['dname'];  
        
    // gather and validate form input
    
    $import_map = array(); // user-defined import map; key is sql_name, value is column position in sql
    
    foreach ($_POST as $pk=>$pv)
    {
	// value must be numeric
	// key must be defined in importable_fields
	
	if (is_numeric($pv) and intval($pv) > 0 and array_search($pk, $importable_fields))
	{
	    $import_map[$pk] = intval($pv);
	    // e.g. $import_name['prefix'] = 1;
	}
    }
    
    if (empty($import_map))
    {
	process_user_error(_("Please define one or more fields to import."));
	return;
    }
    
    // open file to be imported
    
    $f = fopen($dname, 'r');
    
    if (!$f)
    {
	process_system_error("Unable to open uploaded file.");
	return;
    }
    
    $header = fgetcsv($f, 1000, ",");
    
    if (!$header)
    {
	process_system_error("Unable to read uploaded file.");
	return;
    }
    
    // Sanity check: number of columns >= maximum column mapped
    
    $sql_names_volunteers = array(); // value is sql name; key refers to import_map
    $sql_names_phones = array(); // value is sql name; key refers to import_map
    
    $max_column_i = 0;
    
//    echo ("import map <PRE>"); print_r($import_map); echo ("</PRE>");
    
    $i = 0;

    foreach ($import_map as $imk => $imv)
    {
	if ($imk > $max_column_i)
	    $max_column_i =  $imk;
	// phone numbers go into separate table, so exclude for now
	if (0 === strpos($imk, 'phone_'))
	{
		$sql_names_phones[$i] = $imk;
	}
	else
	{
		$sql_names_volunteers[$i] = $imk;
	}
	$i++;
    }

//  echo "<pre>sql_names_volunteers\n"; print_r($sql_names_volunteers); echo "</pre>";
//  echo "<pre>sql_names_phones\n"; print_r($sql_names_phones); echo "</pre>";

    if (max($import_map) > count($header))
    {
	//this shouldn't happen
	process_user_error("The specified import map does not match the import file.");
	return;
    }
    
    // Import
    
    $lc = 0; // line counter
    $ic = 0; // import counter
    
    $rs_volunteer = $db->Execute("SELECT * FROM volunteers WHERE 1 = 0");
    $rs_phone = $db->Execute("SELECT * FROM phone_numbers WHERE 1 = 0");

    while (FALSE != ($row = fgetcsv($f, 1000, ",")))
    {
	$lc++;
	
	if (count($row) != count($header))
	{
	    // this shouldn't happen
	    process_user_error("Number of columns in line $lc does not match number of columns in header.");
	    return;
	}
	else
	{
            $volunteer_record = array(); // associative array where key is SQL name and value is SQL value
	    
	    foreach ($sql_names_volunteers as $n)
	    {
		$volunteer_record[$n] = $row[$import_map[$n] - 1];
	    }

            $sql = $db->GetInsertSQL($rs_volunteer, $volunteer_record);    

//          echo "$sql<br>";
	
	    $result = $db->Execute($sql);
	    
	    if (!$result)
	    {
		die_message(MSG_SYSTEM_ERROR, "Unable to add volunteer: line $lc", __FILE__, __LINE__, $sql);
	    }
	    else
	    {
		$ic++;

//		echo "debug insert id = " . $db->Insert_ID() . "<br>";

		$phone_record = array('volunteer_id' => $db->Insert_ID());
                foreach ($sql_names_phones as $n)
	        {
			$v = $row[$import_map[$n] - 1];
//			echo "phone $v <br>\n";
			if (strlen(trim($v)) > 0 and preg_match('/^phone_(.+)$/', $n, $matches))
			{
				$phone_record['memo'] = ucfirst($matches[1]);
				$phone_record['number'] = $v;
				$sql_phone = $db->GetInsertSQL($rs_phone, $phone_record);
//				echo "phone $sql_phone <br>\n";
				$result_phone = $db->Execute($sql_phone);

				if (!$result_phone)
				{
					die_message(MSG_SYSTEM_ERROR, "Unable to add phone number: line $lc", __FILE__, __LINE__, $sql_phone);
				}
			}
		}
		
	    }
	}
    }

    echo ("<P>Imported $ic volunteers.</P>");
    
} /* import_legacy3() */


function import_legacy()
{
    if (!has_permission(PC_ADMIN, PT_READ, NULL, NULL))
    {
	die_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    

    if (!empty($_POST['import_legacy']) and 2 == $_POST['import_legacy'])
    {
	import_legacy2();
    }
    else
    if (!empty($_POST['import_legacy']) and 3 == $_POST['import_legacy'])
    {
	import_legacy3();
    }
    else
    {
	import_legacy1();
    }
}
