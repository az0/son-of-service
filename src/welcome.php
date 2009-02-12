<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 *
 * $Id: welcome.php,v 1.23 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

session_start();
ob_start();

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/html.php');
require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/db.php');

$db = connect_db();

if ($db->_connectionID == '')
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
}

is_logged_in();

make_html_begin("Welcome", array());

make_nav_begin();

if (isset($_SESSION['sos_user']['personalname']) and $_SESSION['sos_user']['personalname'])
    $username = $_SESSION['sos_user']['personalname'];
    else
    $username = $_SESSION['sos_user']['username'];

$result = $db->Execute("SELECT note_id FROM notes WHERE acknowledged != 1 and reminder_date <= now() and uid_assigned = ".get_user_id());

$reminders = $result->RecordCount();

echo ("<p>" . _("Number of reminders waiting:") . (0 == $reminders ? "0" : "<a href=\"reminders.php\">$reminders</a>") ."</p>\n");



make_html_end();

?>
