<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 *
 * $Id: login.php,v 1.5 2003/11/28 16:25:48 andrewziem Exp $
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

function request_login()
{
        
    echo ("<H3>Son of Service: Volunteer management database</H3>\n");

    echo ("<P>Please log in using the username and password provided by the volunteer coordinator.</P>\n");
    
    // fix me: return to refering page
    echo ("<FORM method=\"post\"  action=\"login.php\">\n");
    echo ("<TABLE border=\"0\">\n");
    echo ("<TR>\n");
    echo ("<TD>Username</TD>\n");
    echo ("<TD><INPUT name=\"u\" type=\"text\" size=\"40\"></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");
    echo ("<TD>Password</TD>\n");
    echo ("<TD><INPUT name=\"p\" type=\"password\" size=\"40\"></TD>\n");
    echo ("</TR>\n");
    echo ("</TABLE>\n");
    
    echo ("<INPUT value=\""._("Log in")."\" type=\"submit\" name=\"button_login\">\n");
    
    echo ("</FORM>\n");
}


if (isset($_POST['button_login']))
{
    $db = new voldbMySql();

    if ($db->get_error())
    {
	process_system_error(_("Unable to establish database connection."), array('debug' => $db->get_error()));    
	die();	
    }
    
    // Security: Do not allow variable poisoning
    unset($uid); 
    
    $username = $db->escape_string($_POST['u']);
    $password = md5($_POST['p']);
        
    $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
    
    $result = $db->query($sql);
	
    if ($result)
    {
	if (1 == $db->num_rows($result))
	{
	    $user = $db->fetch_assoc($result);
	    $uid = $user['user_id'];
	}
    }    

    if (!$uid)
    {
	process_user_error(_("Invalid username or password."), "Is your caps lock key on?");
	request_login();
    }
    
    unset($user['password']);
    
    $_SESSION['u'] = $_POST['u'];
    $_SESSION['u_auth'] = TRUE;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user'] = $user;
    
    $db->query("UPDATE users SET lastlogin = now() where user_id = $uid LIMIT 1");
    
    header("Location: welcome.php");
	}
else
{
    request_login();
}

?>