<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: users.php,v 1.8 2003/11/27 06:08:18 andrewziem Exp $
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
    
    
    
    // todo: check permissions
    
    // add or update mode?
    
    $mode_update = array_key_exists('button_user_update', $_POST);
    
    // validate form input
    
    $errors_found = 0;
    
    if (!isset($_POST['personalname']) or 0 == strlen(trim($_POST['personalname'])))
    {
       save_message(_("Personal name is blank."), MSG_USER_WARNING);
    }

    if (!isset($_POST['username']) or 4 > strlen(trim($_POST['username'])))
    {
       save_message(_("Username is too short: 4 or more characters reqired."), MSG_USER_ERROR);
       $errors_found++;
    }

    if (!$mode_update and (!isset($_POST['password1']) or 4 > strlen(trim($_POST['password1']))))
    {
	save_message(_("Account password is too short: 4 or more characters required."), MSG_USER_ERROR);
	$errors_found++;
    }
    else    
    if (isset($_POST['password1']) and isset($_POST['password2']))
	{
	   if (0 != strcmp($_POST['password1'], $_POST['password2']))
	   {
	          save_message(_("Passwords do not match."), MSG_USER_ERROR);
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
    
	$username = $db->escape_string($_POST['username']);
	$personalname = $db->escape_string($_POST['personalname']);
	// do not escape password because of md5()
	$email = $db->escape_string($_POST['email']);
	if ($mode_update)
	{
	    $user_id = intval($_POST['user_id']);
	}
    
	if ($mode_update and 0 == $errors_found)
	{
	    $sql = 'UPDATE users SET ';
	    $sql .= " username = '$username',";
	    $sql .= " personalname = '$personalname',";
	
	    if (strlen($_POST['password1']) > 4)
	    {
		$sql .= " password = '".md5($_POST['password1'])."',";	
	    }
	
	    $sql .= " access_admin = $access_admin,";
	    $sql .= " email =  '$email',";
	    $sql .= " access_change_vol = $access_change_vol ";
	    $sql .= " WHERE user_id = $user_id LIMIT 1 ";
	    
	}
	else if (0 == $errors_found)
	{
	    $sql = "INSERT INTO users (personalname, username, password, access_admin, " .
		   "access_change_vol) " .
		   "VALUES ('$personalname'," .
		           " '$username',".
		    	   "'".md5($_POST['password1']) . "',".
			"'$access_admin', '$access_change_vol')";
	}				   
	$result = $db->query($sql);

	if (!$result) 
	{ 
	    // unsuccessful save
            save_message(_("Error saving data to database."), MSG_SYSTEM_ERROR, array('debug'=> $db->get_error()));
	}
	else if ($mode_update)
	{
	    save_message(_("Updated."), MSG_USER_NOTICE);
	}
	else
	{
	    save_message(_("Saved."), MSG_USER_NOTICE);
	}
    
	// redirect to GET to prevent POST form reposting
	header("Location: " . SOS_PATH . "admin/?users");
    }

} /* user_save() */


function user_addedit_form()
{
    global $db;


    // todo: check permissions
    
    $mode_edit = (array_key_exists('user_id', $_POST) and preg_match('/^[0-9]+$/', $_POST['user_id']));
    
    if ($mode_edit)
    {
	echo ("<H2>Edit user</H2>\n");

	echo ("<P class=\"instructiontext\">Leave the password fields blank to retain the old password.</P>\n");
    
	$user_id = intval($_POST['user_id']);
    
	$result = $db->query("SELECT * FROM users WHERE user_id = $user_id");
	
	if (!$result)
	{
	    process_system_error(_("Error querying database."));	    	    
	    return FALSE;
	}
	
	if (1 != $db->num_rows($result))
	{
	    process_system_error(_("User not found."));
	    return FALSE;
	}
	
	unset($result['password']);
	
	$form_values = $db->fetch_array($result);
    }	
    else
    {
	//echo ("<H2>Add new user</H2>\n");
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
$form->setValuesArray($form_values);
$form->addField(_("Username"), 'text', 'username', array('length' => 20), 'username');
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
$form->addField(_("Personal name"), 'text', 'personalname', array('length' => 40), 'personalname');
$form->addField(_("E-mail"), 'text', 'email', array('length' => 40), 'email');

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
    
    
    echo ("<H2>List of users</H2>\n");
    
    $result = $db->query("SELECT * FROM users");
    
    if (!$result)
    {
	process_system_error(_("Error querying database."));
    }
    else if (0 == $db->num_rows($result))
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
	
	while (FALSE != ($row = $db->fetch_array($result)))
	{
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"user_id\" value=\"".$row['user_id']."\"></TD>\n");
	    echo ("<TD>".$row['username']."</TD>\n");	    
	    echo ("<TD>".$row['personalname']."</TD>\n");
	    echo ("</TR>\n");

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
    
    
    // to do: check permissions
    
    $user_id = intval($_POST['user_id']);
    
    if (array_key_exists('delete_confirm', $_POST) and 'on' == $_POST['delete_confirm'])
    {
	// delete user
	
	$result = $db->query("DELETE FROM users WHERE user_id = $user_id LIMIT 1");
	
	if (!$result)
	{
	    save_message(_("Error deleting data from database."), MSG_SYSTEM_ERROR, array('debug' => $db->get_error()));
	}
	else
	{
	    save_message(_("Deleted."), MSG_USER_NOTICE);

	    // redirect to non-POST page
	    
	    header("Location: ./?users");
	}
    }
    else
    {

	$sql = "SELECT * FROM users WHERE user_id = $user_id";
    	$result = $db->query($sql);
	
	if (!$result)
	{	
	    process_system_error(_("Error querying database."));    
	}
	else if (1 != $db->num_rows($result))
	{
	    process_system_error("User not found.");    	
	}
	else
	{
    
	    // ask for delete confirmation
	
	    echo ("<P>"._("Are you sure you want to delete this user?")."</P>\n");
	
	    $row = $db->fetch_array($result);
	
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
