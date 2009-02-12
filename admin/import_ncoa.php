<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved. 
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Import USPS National Change of Address (NCOA) data.
 *
 * $Id: import_ncoa.php,v 1.5 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/import_ncoa.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

// todo
// - option to disable notes
// - progress indicator
// - multiple instances
// - more flexible input through mapping

function import_ncoa1()
{
?>

<P class="instructionstext">Use this feature to import results from USPS
National Change of Address (NCOA).  This process will update volunteer
addresses and add notes.</P>

<P class="instructionstext">Specify the filename of a file to upload and
import.  The file must be a comma-delimited file (CSV) and the first row
must contain column heading names. The CSV file must have this layout.</p>

<table>
<tr>
<th>Column name</th>
<th>Description</th>
</tr>
<tr>
<td>volunteer_id</td>
<td>Unique volunteer record number</td>
</tr>
<tr>
<td>new_address</td>
<td>0 for no new address, 1 for new address</td>
</tr>
<tr>
<td>move_date</td>
<td>In the format YYYYMM (e.g. 200501 is January 2005)</td>
</tr>
<tr>
<td>move_type</td>
<td>I for individual, F for family, B for business</td>
</tr>
<tr>
<td>bad_address</td>
<td>0 for no, 1 for maybe (NIXIE), 2 for yes (no forwarding address)</td>
</tr>
<tr>
<td>address_error</td>
<td>A text description (such as "foreign move") used when bad_address is 1 or 2</td>
</tr>
<tr>
<td>street</td>
<td>Street address</td>
</tr>
<tr>
<td>city</td>
<td>City</td>
</tr>
<tr>
<td>state</td>
<td>State</td>
</tr>
<tr>
<td>postal_code</td>
<td>ZIP code</td>
</tr>
</table>

<P class="instructionstext">You may omit street, city, state, and postal_code for records with no new address.</p>

<P class="instructionstext">Note: It is a good idea to backup SOS data before importing NCOA.</P>

<P class="instructionstext">Note: Please wait after beginning this operation.  Some time may pass during which it seems no progress is occuring.</P>

<FORM enctype="multipart/form-data" method="post" action=".">
<INPUT type="hidden" name="import_ncoa" value="2">
<INPUT type="hidden" name="MAX_FILE_SIZE" value="2000000">
File name <INPUT type="file" name="userfile">
<INPUT type="submit" value="Send file">
</FORM>
<?php
} /* import_ncoa1() */




function import_ncoa2()
{
	global $db;


	// todo: there is some code duplicate between here and import_legacy2


	// move file safely
    
	$dname = SOS_PATH . 'data/'. $_FILES['userfile']['name'];
    
	$_SESSION['import']['dname'] = $dname;
    
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
    
	if (0 == substr_count($line, ',') or preg_match('/.(xls|sxc|ods)/i', $dname))
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

	// todo: lowercase values in $header?


	// check header

	$valid_column_names = array('volunteer_id', 'new_address', 'move_date', 'move_type', 'bad_address', 
		'address_error', 'street', 'city', 'state', 'postal_code');

	if (count($header) != count($valid_column_names))
	{
		process_user_error("Number of columns in header does not match 10 expected.");
		return;
	}

	if (0 == count(array_diff($header, $valid_column_names)))
	{
		process_user_error("Unexpected column names.");
		return;
	}
	

	// check column count consistency and validate data types

	$lc = 1; // line counter

	while (FALSE != ($row = fgetcsv($f, 1000, ",")))
	{
        	$lc++;

		if (count($row) != count($header))
		{
			process_user_error("Number of columns in line $lc does not match number of columns in header.");
			return;
		}

	 	//todo: validate more	

		if (!is_numeric($row[array_search('volunteer_id', $valid_column_names)]))
		{
			process_user_error("Not a valid volunteer_id on line $lc");
			return;
		}

		$move_date = $row[array_search('move_date', $valid_column_names)];
		
		if (strlen($move_date) > 0 and !preg_match('/\d{6}/',$move_date))
		{
			process_user_error("Not a valid move_date on line $lc");
			return;
		}

		$move_type = intval($row[array_search('move_type', $valid_column_names)]);

		if (strlen($move_type) > 0)
		{
			switch ($move_type)
			{
				case 'I':
				case 'F':
				case 'B':
					break;
				default:
				{
				    process_user_error("Not a valid move_type on line $lc");
				    return;
				}
			}
		}
	}


	// import

	$new_address_counter = 0;
	$bad_address_counter = 0;
	$lc = 1; // line counter

	rewind($f);
	$row = fgetcsv($f, 1000, ","); // skip header
	while (FALSE != ($row = fgetcsv($f, 1000, ",")))
	{
        	$lc++;

		$volunteer_id = intval($row[array_search('volunteer_id', $valid_column_names)]);
		$new_address = intval($row[array_search('new_address', $valid_column_names)]);
		$move_date = $row[array_search('move_date', $valid_column_names)];
		$move_type = $row[array_search('move_type', $valid_column_names)];
		$bad_address = intval($row[array_search('bad_address', $valid_column_names)]);
		$address_error = $row[array_search('address_error', $valid_column_names)];
		$street = $row[array_search('street', $valid_column_names)];
		$city = $row[array_search('city', $valid_column_names)];
		$state = $row[array_search('state', $valid_column_names)];
		$postal_code = $row[array_search('postal_code', $valid_column_names)];

		// prepare some strings

		$volunteer = volunteer_get($volunteer_id, $errstr);
		if (!$volunteer)
		{
			die_message(MSG_SYSTEM_ERROR, "volunteer_get(): $errstr");
		}

		$wholeoldaddress = $volunteer['street'] . ", " . $volunteer['city'] . " " . $volunteer['state'] . " " . $volunteer['postal_code'];

		$wholenewaddress = "$street, $city $state $postal_code";
	
		$message = "";
		if (1 == $new_address)
		{
			$message = "NCOA indicates $move_type move from $wholeoldaddress to $wholenewaddress on date $move_date";
		}
		else if ($bad_address > 0)
		{
			$message = "NCOA indicates $address_error at $wholeoldaddress with move date $move_date";
		}

		// escape strings

		$address_error = $db->qstr($address_error, get_magic_quotes_gpc());
		// note: move_type was validated earlier
		$street = $db->qstr($street, get_magic_quotes_gpc());
		$city = $db->qstr($city, get_magic_quotes_gpc());
		$state = $db->qstr($state, get_magic_quotes_gpc());
		$postal_code = $db->qstr($postal_code, get_magic_quotes_gpc());
		$message = $db->qstr($message, get_magic_quotes_gpc());

		// prepare SQL

		$sql_volunteers = "UPDATE volunteers SET " .
			" street = $street , " .
			" city = $city , " .
			" state = $state , " .
			" postal_code = $postal_code " .
			" WHERE volunteer_id = $volunteer_id ";

		$sql_notes = "INSERT INTO notes " .
			" (dt, volunteer_id, message, quality, uid_added, uid_modified, dt_modified) " .
			" VALUES (now(), $volunteer_id, $message, 0, 0, 0, now()) ";
	
		// send SQL to database

		if (1 == $new_address)
		{
			// update volunteer address
			$result = $db->Execute($sql_volunteers);

			if ($result)
			{
				save_message(MSG_USER_NOTICE, _("Recorded."));
				$new_address_counter++;
			}
			else
			{
				save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql_volunteers);
			}
		}
		
		if (1 == $new_address or $bad_address > 0)
		{
			// add note
			$result = $db->Execute($sql_notes);

			if ($result)
			{
				if ($bad_address > 0)
				{
					$bad_address_counter++;
				}
			}
			else
			{
				save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql_notes);
			}

		}

	}

	// print results	

	echo "<table>\n";
	echo "<tr>\n";
	echo "<th>New addresses</th>\n";
	echo "<td>$new_address_counter</td>\n";
	echo "</tr>\n";
    	echo "<tr>\n";
	echo "<th>Bad addresses and potentially bad addresses</th>\n";
	echo "<td>$bad_address_counter</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<th>Total addresses in file</th>\n";
	echo "<td>$lc</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

    
} /* import_ncoa2() */


function import_ncoa()
{
    if (!has_permission(PC_ADMIN, PT_READ, NULL, NULL))
    {
	die_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    

    if (!empty($_POST['import_ncoa']) and 2 == $_POST['import_ncoa'])
    {
	import_ncoa2();
    }
    else
    {
	import_ncoa1();
    }
}
