<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Database abstraction to MySQL and related.
 *
 * $Id: db.php,v 1.4 2003/11/07 16:59:19 andrewziem Exp $
 *
 */


if (preg_match('/db.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_get($vid)
 // get from database
 {
    global $db;
    

    if (!is_numeric($vid))
    {
	process_system_error("volunteer_get(): Expected integer.");
	return FALSE;
    }
    
    $vid = intval($vid);
    
    $result = $db->query("SELECT * " .
                         "FROM volunteers " .
                         "WHERE volunteer_id=$vid");

    if (!$result)
    {
	process_system_error("Error fetching volunteer details from database.");
	return FALSE;
    }

    if (1 != $db->num_rows($result))
    {
	process_system_error(_("Volunteer not found"));
	return FALSE;
    }

    $volunteer = $db->fetch_array($result);
    
    return $volunteer;
 
 } /* volunteer_get() */




class voldbDatabase
{

    var $is_error;
    var $error_message;
    
    function get_error()
    {
	if ($this->is_error)
		return $this->error_message;
	return FALSE;
    }

    function DatabaseAccess($db_host, $db_user, $db_pass, $db_file)
    {
	$this->is_error = FALSE;        
	return TRUE;
    }
    
    
    function get_user($uid)
    {
	$result = $this->query("SELECT * FROM users WHERE user_id = ".intval($uid));
	
	if ($result)
	{
		if (1 == $this->num_rows($result))
		{
		    return $this->fetch_array($result);
		}
	}
	return FALSE;
    
    }
    
    function close()
    {
    
    }
}

class voldbMySql extends voldbDatabase
{
    var $dbx;

    function voldbMySql()
    {
	global $cfg;
    
	$this->is_error = FALSE;        
	
	$this->dbx = @mysql_connect($cfg['mysqlhost'], $cfg['mysqluser'], $cfg['mysqlpassword']);

	if (!$this->dbx)
	{
	    $this->is_error = TRUE;
	    $this->error_message = mysql_error();
	    process_system_error("Could not connect to database.", array('debug'=> mysql_errno().': '.mysql_error()));
	    return FALSE;
        }

	$rc = mysql_select_db($cfg['mysqlfile'], $this->dbx);
    
        if (FALSE == $rc)
	{
	    $this->is_error = TRUE;
	    $this->error_message = mysql_error();
	    //process_system_error("Could not select database table.", array('debug'=> mysql_errno().': '.mysql_error()));
	    return FALSE;
	}

        return TRUE;
    }
    
    function num_rows($result)
    {
	return (mysql_num_rows($result));
    }
    
    function error()
    {
	return (mysql_error());
    }

    function insert_id()
    {
	return (mysql_insert_id());
    }

    function data_seek($result, $offset)
    {
	return (mysql_data_seek($result, $offset));
    }    
    
    function free_result($result)
    {
	return (mysql_free_result($result));
    }

    function fetch_assoc($result)
    {
	return (mysql_fetch_assoc($result));
    }

    
    function fetch_array($result)
    {
	$row = mysql_fetch_array($result);
	
	if (!$row)
	{
	    $this->is_error = TRUE;
	    $this->error_message = mysql_error();
	    //process_system_error("Could not execute query.<BR>Explication ".mysql_errno().": ".mysql_error());
	    return FALSE;
	}
	
	return $row;

    }
    
    function escape_string($string, $fromcgi = TRUE)
    {
	if ($fromcgi and 0==get_magic_quotes_gpc())
	{

	    if (function_exists('mysql_real_escape_string'))
		return (mysql_real_escape_string($string, $this->dbx));
	    else	
		return (mysql_escape_string($string));	
	}
	return $string;
    }
    
    function query($sql)
    {
	$result = mysql_query($sql);
	
	if (!$result)
	{
	    $this->is_error = TRUE;
	    $this->error_message = mysql_error();
	    //process_system_error("Could not execute query.<BR>Explication ".mysql_errno().": ".mysql_error());
	    return FALSE;
	}
	
	return $result	;
    }
    
    function fieldnames($result)
    {
        $i = 0;
    
	$fieldnames = array();
	
	while ($i < mysql_num_fields($result))
	{	
	    $meta = mysql_fetch_field($result);
	    $fieldnames[$meta->name]['name'] = $meta->name;
	    $i++;
	}
	return $fieldnames;
    
    }
    
    
    function close()
    {	
	$rc = mysql_close($this->dbx);
	
        if (FALSE == $rc)
	{
	    $this->is_error = TRUE;
	    $this->error_message = mysql_error();
	    process_system_error("Could not close database table.");
	    return FALSE;
	}		
    }

}




?>
