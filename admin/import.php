<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Import legacy data.
 *
 * $Id: import.php,v 1.2 2003/10/24 14:34:29 andrewziem Exp $
 *
 */

if (preg_match('/import.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

// to do
// - could use a lot of improvements
// - progress indicator

function import_legacy1()
{
?>

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

$importable_fields = array('prefix', 'first', 'middle', 'last', 'suffix', 'organziation', 'street', 'city', 'state', 'zip', 'phone_home', 'phone_work', 'phone_cell', 'email_address');

function import_legacy2()
{
    global $importable_fields;

    // move file (safely)
    
    $dname = SOS_PATH . 'data/'. $_FILES['userfile']['name'];
    
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
    
    $header = fgetcsv($f, 1000, ",");
    
    if (!$header)
    {
	process_system_error("Unable to read uploaded file.");
	return;
    }
    
    // really a compatible format?
    
    // to do: add CSV check

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
	echo ("<SELECT name=\$f\">\n");
	echo ("<OPTION>None</OPTION>\n");
	$i = 0;
	foreach ($header as $h)
	{
	    $i++;
	    if (levenshtein($h, $f) < 2)
		$selected = ' SELECTED';
		else $selected = '';
	    echo ("<OPTION".$selected." value="$i">$h</OPTION>\n");
	}
	echo ("</SELECT>\n");
	echo ("</TD>\n");
	echo ("</TR>\n");
    }
?>
</TABLE>

<P class="instructionstext">Note: Please wait after beginning this operation.  Some time may pass during which it seems no progress is occuring.</P>

<INPUT type="hidden" name="import_legacy" value="3">
<INPUT type="submit" name="submit" value="Import"> 
</FORM>
<?php
    
} /* import_legacy2() */

function import_legacy3()
{
    global $importable_fields;
    
        
    // gather and validate form input
    
    $import_map = array();
    
    foreach ($_POST as $pk=>$pv)
    {
	if (is_numeric($pv) and array_search($pk, $importable_fields))
	{
	    $import_map[$pv] = intval($pk);
	}
    }
    
    if (is_empty($import_map))
    {
	process_user_error("Please define one or more fields to import.");
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
    
    // Does import map still make sense with this file?
    
    $max_column_i = 0;
    foreach ($import_map as $imk => $impv)
    {
	if ($imk > $max_column_i)
	    $max_column_i =  $imk;
    }
    
    if ($imk > count($header))
    {
	process_user_error("The specified import map does not match the import file.");
	die();
    }
    
    // Import


} /* import_legacy3() */

function import_legacy()
{
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