<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 *
 * $Id: welcome.php,v 1.2 2003/10/06 00:33:33 andrewziem Exp $
 *
 */

session_start();
ob_start();

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/html.php');
require_once(SOS_PATH . 'functions/auth.php');
require_once(SOS_PATH . 'functions/db.php');

$db = new voldbMySql();

if ($db->get_error())
{
    process_system_error(_("Unable to establish database connection.").$db->get_error());    
    die();	
}

is_logged_in();

make_html_begin("Welcome", array());

make_nav_begin();

if (isset($_SESSION['user']['personalname']) and $_SESSION['user']['personalname'])
    $username = $_SESSION['user']['personalname'];
    else
    $username = $_SESSION['user']['username'];

$result = $db->query("SELECT note_id FROM notes WHERE reminder_date >= now() and uid_assigned = ".intval($_SESSION['user_id']));

$reminders = $db->num_rows($result);

echo ("<P>Welcome, $username.  You have $reminders reminders waiting.</P>\n");


?>

<P>Thank you for trying this demo of SOS.  This is not a finished
product: the program is in an early stage of development.  The primary
goal now is to identify and implement major features.  Also constructive
criticism of the overall design is appropriate now.</P>

