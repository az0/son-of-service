<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Database abstraction to MySQL and related.
 *
 * $Id: db.php,v 1.8 2003/12/03 17:23:04 andrewziem Exp $
 *
 */


if (preg_match('/db.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_get($vid)
 // get from database
 {
    //assuming $db is a fully connected ADOdb connection
    global $db;
    

    if (!is_numeric($vid))
    {
	process_system_error("volunteer_get(): Expected integer.");
	return FALSE;
    }
    
    $vid = intval($vid);
    
    $result = $db->Execute("SELECT * " .
                         "FROM volunteers " .
                         "WHERE volunteer_id=$vid");

    if ($result === false)
    {
	process_system_error("Error fetching volunteer details from database.");
	return FALSE;
    }

    if (1 != $result->RecordCount())
    {
	process_system_error(_("Volunteer not found."));
	return FALSE;
    }

    $volunteer = $result->fields;
    
    return $volunteer;
 
 } /* volunteer_get() */

function connect_db ()
{
	global $cfg; //need to import config settings
	global $db; 
	
	//for adodb methods
	require_once($cfg['ado_path'].'/adodb.inc.php');

	if (isset($db)) //check for existing connection
		return $db;

	//check for database type
	if ($cfg['dbtype'] == 'mysql')
	{
		$db = &NewADOConnection('mysql');

		// toggle persistant connections
		if ($cfg['dbpersist'] == true)
			$db->PConnect($cfg['dbhost'], $cfg['dbuser'],
				$cfg['dbpass'], $cfg['dbname']);
		else
			$db->Connect($cfg['dbhost'], $cfg['dbuser'],
				$cfg['dbpass'], $cfg['dbname']);

		/*
		 * it is not necessary to return false on failure because
		 * db itself will be false if the connect failed
		 */
		return $db;	
	}

	/*
	 * false will be returned if $cfg['dbtype'] is not set
	 * or is not supported
	 */
	return false;
}

?>
