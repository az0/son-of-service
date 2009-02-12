<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 *
 * $Id: login.php,v 1.19 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

ob_start();

session_start();
session_unset();
session_destroy(); // must start before destroy
session_start();

// Security: Do not allow client to remember and replay successful login
header("Pragma: no-cache"); 

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/db.php');
/*
if (array_key_exists('logout', $_GET))
{
    session_unset();
    @session_destroy();
}
*/

make_html_begin(_("Login"), NULL);

function request_login()
{
	global $languages;

	echo ("<h3>Son of Service: ". _("Volunteer management database") ."</h3>\n");

	echo ("<p>" . _("Please log in using the user name and password provided by the volunteer coordinator.") . "</p>\n");
    
	// fix me: return to refering page
	echo ("<FORM method=\"post\"  action=\"login.php\">\n");
	echo ("<TABLE border=\"0\">\n");
	echo ("<TR>\n");
	echo ("<TD>" . _("User name") . "</TD>\n");
	echo ("<TD><INPUT name=\"u\" type=\"text\" size=\"40\"></TD>\n");
	echo ("</TR>\n");
	echo ("<TR>\n");
	echo ("<TD>" . _("Password") . "</TD>\n");
	echo ("<TD><INPUT name=\"p\" type=\"password\" size=\"40\"></TD>\n");
	echo ("</TR>\n");
	echo ("</TABLE>\n");

	echo _("Language:");
	echo (" <select name=\"language\">\n");
	echo ("<option>" . _("Default") . "\n");
	foreach ($languages as $code => $language)
	{
		if (array_key_exists('ALIAS', $language))
			continue;
		echo ("<option value=\"$code\">" . $language['NAME'] . "\n");
	}
	echo ("</select>\n");
	echo ("<br>\n");
    
	echo ("<INPUT value=\""._("Log in")."\" type=\"submit\" name=\"button_login\">\n");
    
	echo ("</FORM>\n");
}


if (isset($_POST['button_login']))
{
	global $db;

	if (array_key_exists('language', $_POST) and is_valid_language($_POST['language']))
		$_SESSION['sos_language'] = $_POST['language'];

	set_up_language();

	$db = connect_db();
	if ($db->_connectionID == '')
	{
		die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
	}
    
	// Security: Do not allow variable poisoning
	unset($uid); 
    
	$username = $db->qstr($_POST['u'],get_magic_quotes_gpc());
	$password = $db->qstr(md5($_POST['p']),get_magic_quotes_gpc());
        
	$sql = "SELECT * FROM users WHERE username = $username and password = $password";
    
	$result = $db->Execute($sql);
	
	if ($result and 1 == $result->RecordCount())
	{
		$user = $result->fields;
		$uid = $user['user_id'];
	}    
	else
	{
		sleep(3);
		process_user_error(_("Invalid user name or password."), _("Is your caps lock key on?"));
		request_login();
		exit();
	}
	
	unset($user['password']);
    
	$_SESSION['u'] = $_POST['u'];
	$_SESSION['u_auth'] = TRUE;
	$_SESSION['user_id'] = $user['user_id'];
	$_SESSION['sos_user'] = $user;
    
	$r = $db->Execute("UPDATE users SET lastlogin = now() where user_id = $uid LIMIT 1");
    
	redirect('welcome.php');
	}
else
{
    request_login();
}

make_html_end();

?>
