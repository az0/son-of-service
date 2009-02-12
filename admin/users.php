<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.  
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: users.php,v 1.25 2009/02/12 04:11:20 andrewziem Exp $
 *
 */
 
ob_start();

if (preg_match('/users.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

require_once (SOS_PATH . 'functions/formmaker.php');

function user_save()
{
    global $db;
    

    // add or update mode?
    
    $mode_update = array_key_exists('button_user_update', $_POST);
    
    $user_id = NULL;

    if ($mode_update)
    {
        $user_id = intval($_POST['user_id']);
    }
    
    if (!has_permission(PC_ADMIN, PT_WRITE, NULL, $user_id))
    {
	message_die(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }
    
    // validate form input
    
    $errors_found = 0;
    
    if (!isset($_POST['personalname']) or 0 == strlen(trim($_POST['personalname'])))
    {
       save_message(MSG_USER_WARNING, _("Personal name is blank."));
    }

    if (!isset($_POST['username']) or 4 > strlen(trim($_POST['username'])))
    {
       save_message(MSG_USER_ERROR, _("User name is too short: 4 or more characters required."));
       $errors_found++;
    }

    if (!$mode_update and (!isset($_POST['password1']) or 4 > strlen($_POST['password1'])))
    {
	save_message(MSG_USER_ERROR, _("Account password is too short: 4 or more characters required."));
	$errors_found++;
    }
    else if (isset($_POST['password1']) and isset($_POST['password2']))
    {
       if (0 != strcmp($_POST['password1'], $_POST['password2']))
       {
	    save_message(MSG_USER_ERROR, _("Passwords do not match."));
	    $errors_found++;
	}
    }

    if (isset($_POST['access_admin']) and "y" == $_POST['access_admin'])
    {
	save_user_message(_("This user has administrative privilege."), MSG_USER_WARNING);
    }
    
    if (!$errors_found)
    {
	if ($access_change_vol = array_key_exists('access_change_vol', $_POST));
	else
	{
	    $access_change_vol = '0';
	}
    
	if ($access_admin = array_key_exists('access_admin', $_POST));
	else
	{
	    $access_admin = '0';
	}
    
	$username = $db->qstr(strip_tags($_POST['username']), get_magic_quotes_gpc());
	$personalname = $db->qstr(strip_tags($_POST['personalname']), get_magic_quotes_gpc());
	$password = $db->qstr(md5($_POST['password1']), FALSE);
	$email = $db->qstr(strip_tags($_POST['email']), get_magic_quotes_gpc());
    
	if ($mode_update and 0 == $errors_found)
	{
	    $sql = 'UPDATE users SET ';
	    $sql .= " username = $username,";
	    $sql .= " personalname = $personalname,";
	
	    if (strlen($_POST['password1']) >= 4)
	    {
		$sql .= " password = $password,";	
	    }
	
	    $sql .= " access_admin = $access_admin,";
	    $sql .= " email =  $email,";
	    $sql .= " access_change_vol = $access_change_vol ";
	    // todo: portable LIMIT
	    $sql .= " WHERE user_id = $user_id LIMIT 1 ";
	    
	}
	else if (0 == $errors_found)
	{
	    $sql = "INSERT INTO users (personalname, username, password, email, access_admin, " .
		   "access_change_vol) " .
		   "VALUES ($personalname," .
		   " $username,	$password, $email, ".
		   "$access_admin, $access_change_vol)";
	}				   

	$result = $db->Execute($sql);

	if (!$result) 
	{ 
	    // unsuccessful save
            save_message(MSG_SYSTEM_ERROR, _("Error saving data to database."), __FILE__, __LINE__, $sql);
	}
	else
	{
	    if ($mode_update)
	    {
    		if (get_user_id() == $user_id)
	    	{
	    	    // update session info
		    $_SESSION['u'] = strip_tags($_POST['username']);
                    $_SESSION['sos_user'] = array();
		    $_SESSION['sos_user']['username'] = $username;
		    $_SESSION['sos_user']['email'] = strip_tags($_POST['email']);
		    $_SESSION['sos_user']['personalname'] = strip_tags($_POST['personalname']);
		    $_SESSION['sos_user']['access_change_vol'] = $access_change_vol;
		    $_SESSION['sos_user']['access_admin'] = $access_admin;	    
		    save_message(MSG_USER_NOTICE, _("The changes for your account are now in effect for this and future sessions."));
        	}
		else
    		{
		    save_message(MSG_USER_NOTICE, _("The changes will take affect after the next login."));
		}    
	    }
	    else
	    {
		save_message(MSG_USER_NOTICE,  _("Saved."));
	    }
	}
	
	// redirect to GET to prevent POST form reposting

    }
    
    redirect("?users");

} /* user_save() */


function user_addedit_form()
{
    global $db;


    $mode_edit = (array_key_exists('user_id', $_POST) and preg_match('/^[0-9]+$/', $_POST['user_id']));
    
    if ($mode_edit)
    {
	// edit existing user mode
	$user_id = intval($_POST['user_id']);
	
	if (!has_permission(PC_ADMIN, PT_WRITE, NULL, $user_id))
	{
	    message_die(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
	}
    
	echo ("<H2>" . _("Edit user") . "</H2>\n");

	echo ("<P class=\"instructionstext\">" . _("Leave the password fields blank to retain the old password.") . "</P>\n");

	$sql = "SELECT * FROM users WHERE user_id = $user_id";
	        
	$result = $db->Execute($sql);
	
	if (!$result)
	{
	    message_die(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	}
	
	if (1 != $result->RecordCount())
	{
	    process_system_error(_("User not found."));
	    return FALSE;
	}
	
	$form_values = $result->fields;
    }	
    else
    {
	// add new user mode

	if (!has_permission(PC_ADMIN, PT_WRITE, NULL, NULL))
	{
	    return FALSE;
	}
	
	echo ("<FIELDSET>\n");
	echo ("<LEGEND>" . _("Add new user") . "</LEGEND>\n");
	echo ("<P class=\"instructionstext\">" . _("A user administrates the volunteer database.  He may view and change volunteers' accounts.") . "</P>\n");
	// form defaults
	$form_values = array('access_change_vol' => 1, 'access_admin' => 0);
	$form_values['email'] = $form_values['username'] = $form_values['personalname'] = "";
	
    }
    
    $form = new formMaker;
    $form->open(FALSE, 'post', '.', FS_TABLE);
    $form->addField(_("User name"), 'text', 'username', array('length' => 20), $form_values['username']);
    $form->addField(_("Password"), 'password', 'password1', array('length' => 20), '');
    $form->addField(_("Verify password"), 'password', 'password2', array('length' => 20), '');
    $form->addField(_("Administration privileges"), 'checkbox', 'access_admin', array(), $form_values['access_admin']);
    $form->addField(_("Change volunteers"), 'checkbox', 'access_change_vol', array(), $form_values['access_change_vol']);
    $form->addField(_("Personal name"), 'text', 'personalname', array('length' => 40), $form_values['personalname']);
    $form->addField(_("E-mail"), 'text', 'email', array('length' => 40), $form_values['email']);

    if ($mode_edit)
    {
	$form->addHiddenField('user_id', $user_id);
	$form->addButton('button_user_update', _("Save"));
    }
    else
    {
        $form->addButton('button_user_add', _("Add"));
    }
    $form->close();

    echo ("</FIELDSET>\n");
}


function users_list()
{
    global $db;
    

    if (!has_permission(PC_ADMIN, PT_READ, NULL, NULL))
    {
	message_die(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }
    
    echo "<h2>" . _("List of users") . "</h2>\n";
    
    $sql = "SELECT * FROM users";
    
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	process_system_error(_("Error querying database."));
    }
    elseif (0 == $result->RecordCount())
    {
	process_user_error(_("No user accounts."));
	user_add();
    }
    else
    {
	echo ("<FORM method=\"post\" action=\".\">\n");
	
	require_once(SOS_PATH . 'functions/table.php');
	
	$headers = array();
	$headers['user_id']['label'] = _("Select");
	$headers['user_id']['radio'] = TRUE;
	$headers['username']['label'] = _("User name");
	$headers['personalname']['label'] = _("Personal name");

	$dtp = new DataTablePager();	
	$dtp->setPagination(25);
	$dtp->setHeaders($headers);
	$dtp->setDatabase($db, $result);
	$dtp->render();

	echo ("<INPUT type=\"submit\" name=\"button_user_delete\" value=\""._("Delete")."\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_user_edit\" value=\""._("Edit")."\">\n");
	echo ("</FORM>\n");	
    }    
} /* users_list() */


function users_delete()
{
    global $db;
    
        
    $user_id = intval($_POST['user_id']);

    if (!has_permission(PC_ADMIN, PT_WRITE, NULL, $user_id))
    {
	message_die(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);	
    }
    else
    if (array_key_exists('delete_confirm', $_POST) and 'on' == $_POST['delete_confirm'])
    {
	// delete user
	
	// todo: portable LIMIT
	$sql = "DELETE FROM users WHERE user_id = $user_id LIMIT 1";
	
	$result = $db->Execute($sql);
	
	if (!$result)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql);
	}
	else
	{
	    save_message(MSG_USER_NOTICE, _("Deleted."));

	    // redirect to non-POST page
	    
	    redirect("?users");
	}
    }
    else
    {
	// request delete confirmation
	
	$sql = "SELECT * FROM users WHERE user_id = $user_id";
    	$result = $db->Execute($sql);
	
	if (!$result)
	{	
	    process_system_error(_("Error querying database."));    
	}
	else if (1 != $result->RecordCount())
	{
	    process_system_error("User not found.");    	
	}
	else
	{
    	    // ask for delete confirmation
	
	    echo ("<P>"._("Are you sure you want to delete this user?")."</P>\n");
	
	    $row = $result->fields;
	
	    echo ("<P>".$row['username'] . " / " . $row['personalname'] . " (#$user_id)</P>\n");
	
	    $form = new formMaker();
	    $form->open(FALSE, 'POST', '.', FS_PLAIN);
	    $form->addHiddenField('user_id', $user_id);
	    $form->addButton('button_user_delete', _("Delete"));
	    echo (_("Confirm")."<INPUT type=\"checkbox\" name=\"delete_confirm\"><BR>\n"); 
	    $form->close();		
	}
    }
}

?>
