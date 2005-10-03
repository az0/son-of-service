<?php

/*
 * Son of Service
 * Copyright (C) 2003-2005 by Andrew Ziem.  All rights reserved. 
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Import legacy data.
 *
 * $Id: import.php,v 1.14 2005/10/03 21:25:40 andrewziem Exp $
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
<?php
} /* import_legacy1() */

// 'phone_home', 'phone_work', 'phone_cell'
$importable_fields = array('prefix', 'first', 'middle', 'last', 'suffix', 'organization', 'street', 'city', 'state', 'postal_code', 'country', 'email_address');

function import_legacy2()
{
    global $importable_fields;

    // move file (safely)
    
    $dname = SOS_PATH . 'data/'. $_FILES['userfile']['name'];
    
    $_SESSION['import']['dname'] = $dname;
    
    echo ("<P>Debug: from ". $_FILES['userfile']['tmp_name']. " to $dname</P>");
    print_r($_FILES);
    
    if (@move_uploaded_file($_FILES['userfile']['tmp_name'], $dname))
    {
    
    }
    else
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
    
    $import_map = array();
    
    //    print_r($_POST);
    
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
	die();
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
    
    $sql_names = array();
    
    $max_column_i = 0;
    
//    echo ("import map <PRE>"); print_r($import_map); echo ("</PRE>");
    
    foreach ($import_map as $imk => $imv)
    {
	if ($imk > $max_column_i)
	    $max_column_i =  $imk;
	$sql_names[] = $imk;
    }
    
    if (max($import_map) > count($header))
    {
	//this shouldn't happen
	process_user_error("The specified import map does not match the import file.");
	die();
    }
    
    // Import
    
    $lc = 0; // line counter
    $ic = 0; // import counter
    
//    print_r($header);
    
    while (FALSE != ($row = fgetcsv($f, 1000, ",")))
    {
	$lc++;
	
	if (count($row) != count($header))
	{
	    // this shouldn't happen
	    process_user_error("Number of columns in line $lc does not match number of columns in header.");
	    die();
	}
	else
	{
	    $sql_values = array();
	    
	    foreach ($sql_names as $n)
	    {
		// sanitize file input
		$sql_values[] = $db->qstr(htmlentities($row[$import_map[$n] - 1]), get_magic_quotes_gpc());
	    }
	    
	    // build SQL INSERT query
	    
	    $sql = "INSERT INTO volunteers ";
	    
	    $i = 0;
	    
	    foreach  ($sql_names as $sv)
	    {
		$i++;
		if (1 == $i)
		{
		    $sql .= '(';
		}	
		else
		{
		    $sql .= ',';
		}
		$sql .= $sv;
	    }
	    
	    $sql .= ') VALUES ';
	    
	    $i = 0;
	    
	    foreach  ($sql_values as $sv)
	    {
		$i++;
		if (1 == $i)
		{
		    $sql .= '(';
		}	
		else
		{
		    $sql .= ',';
		}
		$sql .= "'".$sv."'";
	    }
	    
	    $sql .= ')';

	    $result = $db->Execute($sql);
	    
	    if (!$result)
	    {
		die_message(MSG_SYSTEM_ERROR, "Unable to add volunteer: line $lc", __FILE__, __LINE__, $sql);
	    }
	    else
	    {
		$ic++;
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
