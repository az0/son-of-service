<?php

/*
 * Son of Service
 * Copyright (C) 2003-2005 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: functions.php,v 1.9 2005/10/03 21:25:40 andrewziem Exp $
 *
 */


if (preg_match('/functions.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

include_once(SOS_PATH . 'functions/html.php');

/**
 * save_message($type, $message, [$file, $line, $sql])
 *
 * Saves a message to be displayed later (next page load).  Used with
 * form processing.
 * 
 * @param int type type of message
 * @param string message message
 * @param string file file in which the error occured
 * @param string line line number at which the error occured
 * @param sql SQL code that trigged the error
 * @return void
 *
 */
function save_message($type, $message, $file = NULL, $line = NULL, $sql = NULL)
{
    global $db;

    assert (is_int($type));
    $message = array('type' => $type, 'message' => $message, 'file' => $file, 
	'line' => $line, 'sql' => $sql, 'sql_error' => $db->ErrorMsg());
    $_SESSION['messages'][] = $message;
    
    // todo: log error message here if applicable (refer to configurable log level)
}

/**
 * die_message($type, $message, $file = NULL, $line = NULL, $sql = NULL)
 *
 * Displays a message then dies.  Same parameters as save_message().
 *
 */
function die_message($type, $message, $file = NULL, $line = NULL, $sql = NULL)
{
    global $db;

    assert (is_int($type));
    display_message($type, $message, $file, $line, $sql, $db->ErrorMsg());
    die();
    
    // todo: log error message here if applicable (refer to configurable log level)
}

/**
 * make_volunteer_name($row)
 * 
 * name, e.g. John Smith (Smith Inc.)
 *
 * @param string row an array containing first, middle, last, organization
 * @return string name of t 
 */ 
function make_volunteer_name($row)
{
    if (!is_array($row))
	return FALSE;
    $name = trim($row['first'].' '.$row['middle'].' '.$row['last']);
    if (!empty($row['organization']))
	$name .= ' ('.$row['organization'].')';
    return $name;	
}

/**
 * get_user_id()
 * 
 * @return int user ID of current user
 */

function get_user_id()
{
    if (!array_key_exists('user_id', $_SESSION))
    {
	die_message(MSG_SYSTEM_ERROR, 'user_id missing in SESSION', __FILE__, __LINE__);
    }
    return (intval($_SESSION['user_id']));
}

/**
 * redirect($url)
 * 
 * @param string url url
 * @return void
 */

function redirect($url)
{
    header("Location: $url");
}


/**
 * sqldate_to_local($sql_date)
 *
 * @param string sql_date date in the format YYYY-MM-DD
 * @return string date as given by strftime("%D")
 */
function sqldate_to_local($sql_date)
{
    global $db;
    
    
    $unixdate = $db->Unixdate($sql_date);
    if (0 == $unixdate)
    {
	return "";    
    }
    
    // todo: localize    
    return (strftime("%D", $unixdate));    
}

/**
 * sqldatetime_to_local($sql_datetime)
 *
 * @param string sql_datetime datetime in SQL format
 * @return string date as given by strftime("%c")
 */
function sqldatetime_to_local($sql_datetime)
{
    global $db;
    
    
    $unixdate = $db->UnixTimeStamp($sql_datetime);
    if (0 == $unixdate)
    {
	return "";    
    }
    
    return (strftime("%c", $unixdate));    
}

?>
