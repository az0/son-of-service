<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: functions.php,v 1.3 2003/12/03 17:23:04 andrewziem Exp $
 *
 */


if (preg_match('/functions.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


/* save_message()
 * Saves a message to be displayed later (next page load).  Used with
 * forms processing.
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

/* die_message()
 * Displays a message then dies.
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


function make_volunteer_name($row)
// $row: an array containing first, middle, last, organization
// return: name, e.g. John Smith (Smith Inc.)
{
    if (!is_array($row))
	return FALSE;
    $name = trim($row['first'].' '.$row['middle'].' '.$row['last']);
    if (!empty($row['organization']))
	$name .= ' ('.$row['organization'].')';
    return $name;	
}

function get_user_id()
// returns an integer of the current user_id
{
    if (!array_key_exists('user_id', $_SESSION))
    {
	die_message(MSG_SYSTEM_ERROR, 'user_id missing in SESSION', __FILE__, __LINE__);
    }
    return (intval($_SESSION['user_id']));
}

function redirect($url)
{
    header("Location: $url");
}

?>
