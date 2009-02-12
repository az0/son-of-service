<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Database abstraction to MySQL and related.
 *
 * $Id: db.php,v 1.16 2009/02/12 04:11:20 andrewziem Exp $
 *
 */


if (preg_match('/db.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


/**
 * volunteer_get($vid, &$errstr)
 *
 * Get volunteer's primary record.
 *
 * @param int vid volunteer_id
 * @param *string errstr will be filled with message in case of error
 * @return array record from volunteer table
 */
function volunteer_get($vid, &$errstr)
 {
    //assuming $db is a fully connected ADOdb connection
    global $db;
    

    if (!is_numeric($vid))
    {
	$errstr = "volunteer_get(): Expected integer.";
	return FALSE;
    }
    
    $vid = intval($vid);
    
    $result = $db->Execute("SELECT * FROM volunteers WHERE volunteer_id=$vid LIMIT 1");

    if (!$result)
    {
	$errstr = "Error fetching volunteer details from database.";
	return FALSE;
    }

    if (1 != $result->RecordCount())
    {
	$errstr = _("Volunteer not found.");
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
		
	// database configuration	
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;		

	//check for database type
	if ('mysql' == $cfg['dbtype'])
	{
		$db = &NewADOConnection('mysql');

		// toggle persistant connections
		if (TRUE == $cfg['dbpersist'])
		{
			$db->PConnect($cfg['dbhost'], $cfg['dbuser'],
				$cfg['dbpass'], $cfg['dbname']);
		}
		else
		{
			$db->Connect($cfg['dbhost'], $cfg['dbuser'],
				$cfg['dbpass'], $cfg['dbname']);
		}

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

function make_orderby($request, $column_names, $default_column, $default_direction)
// Makes an SQL ORDERBY.

// request: an array such as $_GET
// column_names: an array of valid column names
// default_column: string
// default_direction: string, either ASC or DESC
{
    assert(is_array($request));
    assert(is_array($column_names));    
    if (array_key_exists('orderby', $request) 
	and in_array($request['orderby'], $column_names)
	and array_key_exists('orderdir', $request)
	and in_array($request['orderdir'], array('asc', 'desc')))
    {
	return("ORDER BY ".$request['orderby'].' '.$request['orderdir']);
    }
    else
    {
	return ("ORDER BY $default_column $default_direction");	
    }
}

/**
 * db_column_exists($name, $table)
 *
 * @param string name name of column, "the needle," quoted by qstr
 * @param string table name of table, "the haystack"
 * @return boolean
 */
function db_column_exists($name, $table)
{
    global $db;
    

    $sql = "SHOW COLUMNS FROM extended LIKE " . $name;
    $result = $db->Execute($sql);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);	
    }
    
    return (1 == $result->RecordCount());
}

/**
 * db_extended_column_type($code)
 *
 * Given that code is the code name of column in the extended system
 * (user-defined fields), look up its data type.
 * 
 * Returns false on error.
 *
 * @param string code column in the extended system, quoted by qstr
 * @return char integer, decimal, string, textarea, date (or false on error)
 */

function db_extended_column_type($code)
{
    global $db;
    

    $sql = "SELECT fieldtype FROM extended_meta WHERE code = " . $code;
    $result = $db->Execute($sql);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);	
    }
    
    if (1 == $result->RecordCount())
    {
	return $result->fields['fieldtype'];
    }
    
    return FALSE;    
}

?>
