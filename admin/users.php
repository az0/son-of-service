<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: users.php,v 1.13 2003/12/29 00:44:10 andrewziem Exp $
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
       save_message(MSG_USER_ERROR, _("Username is too short: 4 or more characters reqired."));
       $errors_found++;
    }

    if (!$mode_update and (!isset($_POST['password1']) or 4 > strlen(trim($_POST['password1']))))
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
	save_user_message(_("This user has administrative privilage."), MSG_USER_WARNING);
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
    
	$username = $db->qstr($_POST['username'], get_magic_quotes_gpc());
	$personalname = $db->qstr($_POST['personalname'], get_magic_quotes_gpc());
	$password = $db->qstr(md5($_POST['password1']), FALSE);
	$email = $db->qstr($_POST['email'], get_magic_quotes_gpc());
    
	if ($mode_update and 0 == $errors_found)
	{
	    $sql = 'UPDATE users SET ';
	    $sql .= " username = $username,";
	    $sql .= " personalname = $personalname,";
	
	    if (strlen($_POST['password1']) > 4)
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
	    save_message(MSG_USER_NOTICE, $mode_update ? _("Updated.") : _("Saved."));
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
	    return FALSE;
	}
    
	echo ("<H2>Edit user</H2>\n");

	echo ("<P class=\"instructiontext\">Leave the password fields blank to retain the old password.</P>\n");

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
	
	unset($result['password']);
	
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
	echo ("<LEGEND>Add new user</LEGEND>\n");
	echo ("<P class=\"instructionstext\">A user administrates the volunteer database.  He may view and change volunteers' accounts.</P>\n");
	// form defaults
	$form_values = array('access_edit_vol' => 1, 'access_add_vol' => 1);
	
    }
    
    function dvc($array, $index)
    {
	if (array_key_exists($index, $array))
	echo (" value=\"".$array[$index]."\" ");
    }
    
    function dvc_checkbox($array, $index)
    {
	if (array_key_exists($index, $array) && 0 != $array[$index])
	echo (" checked");
    }


$form = new formMaker;
$form->open(FALSE, 'post', '.', FS_TABLE);
$form->addField(_("Username"), 'text', 'username', array('length' => 20), $form_values['username']);
$form->addField(_("Password"), 'password', 'password1', array('length' => 20), '');
$form->addField(_("Verify password"), 'password', 'password1', array('length' => 20), '');
?>

<tr>
 <th class="vert">Administration privileges</th>
 <td>
   <INPUT type="checkbox" name="access_admin" <?php dvc_checkbox($form_values, 'access_admin');?>> 
 </tr>
 <th class="vert">Change volunteers</th>
 <td>
   <INPUT type="checkbox" NAME="access_change_vol" <?php dvc_checkbox($form_values, 'access_change_vol');?>>
 </tr> 
<?php
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
// close conditional statement
}


function users_list()
{
    global $db;
    


    if (!has_permission(PC_ADMIN, PT_READ, NULL, NULL))
    {
	return FALSE;
    }
    
    echo ("<H2>List of users</H2>\n");
    
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

	echo ("<TABLE border=\"1\">\n");
	echo ("<THEAD>\n");
	echo ("<TR>\n");
	echo ("<TH>"._("Select")."</TH>\n");	
	echo ("<TH>"._("Username")."</TH>\n");	
	echo ("<TH>"._("Personal Name")."</TH>\n");
	echo ("</TR>\n");
	echo ("</THEAD>\n");
	
	while (!$result->EOF)
	{
	    $row = $result->fields;
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"user_id\" value=\"".$row['user_id']."\"></TD>\n");
	    echo ("<TD>".$row['username']."</TD>\n");	    
	    echo ("<TD>".$row['personalname']."</TD>\n");
	    echo ("</TR>\n");
	    $result->MoveNext();

	}
	echo ("</TABLE>\n");	
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
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions"));
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
	
	    echo ("<P>".$row['personalname']." ($user_id)</P>\n");
	
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
