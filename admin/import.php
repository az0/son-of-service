<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Import legacy data.
 *
 * $Id: import.php,v 1.1 2003/10/06 00:33:32 andrewziem Exp $
 *
 */

if (preg_match('/import.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

// to do
// - could use a lot of improvements

function import_legacy1()
{
?>

<P class="instructionstext">Specify the filename of a file to upload and
import.  The file must be a comma-delimited file (CSV) and the first row
must contain column heading names.</P>

<FORM enctype="multipart/form-data" method="post" action=".">
<INPUT type="hidden" name="import_legacy" value="2">
<INPUT type="hidden" name="MAX_FILE_SIZE" value="2000000">
File name <INPUT type="file" name="userfile">
<INPUT type="submit" value="Send file">
<?php
}

function import_legacy2()
{

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
    
    // really a compatible format?
    
    $f = fopen($dname, 'r');
    
    if (!$f)
    {
	process_system_error("Unable to read uploaded file.");
	return;
    }
    
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
    $fields = array('prefix', 'first', 'middle', 'last', 'suffix', 'organziation', 'street', 'city', 'state', 'zip', 'phone_home', 'phone_work', 'phone_cell', 'email_address');
    foreach ($fields as $f)
    {
	echo ("<TR>\n");
	echo ("<TH class=\"vert\">$f</TH>\n");
	echo ("<TD>");
	echo ("<SELECT name=\$f\">\n");
	echo ("<OPTION>None</OPTION>\n");
	foreach ($header as $h)
	{
	    echo ("<OPTION>$h</OPTION>\n");
	}
	echo ("</SELECT>\n");
	echo ("</TD>\n");
	echo ("</TR>\n");
    }
?>
</TABLE>
<INPUT type="hidden" name="import_legacy" value="3">
<INPUT type="submit" name="submit" value="Import"> Note: Please be patient.
</FORM>
<?php
    
}

function import_legacy()
{
    if (!empty($_POST['import_legacy']) and 2 == $_POST['import_legacy'])
    {
	import_legacy2();
    }
    else
    {
	import_legacy1();
    }
}