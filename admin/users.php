<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: users.php,v 1.1 2003/10/05 16:14:46 andrewziem Exp $
 *
 */

if (preg_match('/users.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

function user_save()
{
    global $db;
    
    // add or update mode?
    
    $mode_update = array_key_exists('button_user_update', $_POST);
    
    if ($mode_update)
	echo ("<H2>Updating user</H2>\n");
	else
	echo ("<H2>Adding user</H2>\n");

    // validate form input
    
    $errors_found = 0;
    
    if (!isset($_POST['personalname']) or 0 == strlen(trim($_POST['personalname'])))
    {
       process_user_warning("Personal name is blank.");
    }

    if (!isset($_POST['username']) or 4 > strlen(trim($_POST['username'])))
    {
       process_user_error("Username is too short.");
       $errors_found++;
    }

    if (!$mode_update and (!isset($_POST['password1']) or 4 > strlen(trim($_POST['password1']))))
    {
       process_user_error("Account password is too short.");
	$errors_found++;
    }
    else    
    if (isset($_POST['password1']) and isset($_POST['password2']))
	{
	   if (0 != strcmp($_POST['password1'], $_POST['password2']))
	   {
	          process_user_error("Passwords do not match.");
		  $errors_found++;
	   }
    }

    if (isset($_POST['access_admin']) and "y" == $_POST['access_admin'])
    {
       process_user_notice("This user has administrative privilages (full access to volunteer database).\n");
    }
    
    if ($errors_found)
    {
	user_addedit_form();
	return;
    }
    
    if ($access_change_vol = array_key_exists('access_change_vol', $_POST));
    else
    $access_change_vol = '0';
    if ($access_admin = array_key_exists('access_admin', $_POST));
    else
    $access_admin = '0';
    
    $username = $db->escape_string($_POST['username']);
    $personalname = $db->escape_string($_POST['personalname']);
    // do not escape password because of md5()
    $email = $db->escape_string($_POST['email']);
    if ($mode_update)
	$user_id = intval($_POST['user_id']);
    
    if ($mode_update and 0 == $errors_found)
    {
	$sql = "UPDATE users SET ";
	$sql .= " username = '$username',";
	$sql .= " personalname = '$personalname',";
	
	if (strlen($_POST['password1']) > 4)
	$sql .= " password = '".md5($_POST['password1'])."',";	
	
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

    if (!$result) { // unsuccessful save
            process_system_error("Error committing user to database", array('debug'=>mysql_error()));
//	    echo $sql; //debug
            exit();

    }

    echo "<P>You have ".($mode_update ? "updated" : "added")." the user $personalname ($username).</P>\n";
    

} /* user_save() */

function user_addedit_form()
{
    global $PHP_SELF;
    global $db;
    
    $mode_edit = (array_key_exists('user_id', $_POST) and preg_match('/^[0-9]+$/', $_POST['user_id']));
    
    if ($mode_edit)
    {
	echo ("<H2>Edit user</H2>\n");

	echo ("<P class=\"instructiontext\">Leave the password fields blank to retain the old password.</P>\n");
    
	$user_id = intval($_POST['user_id']);
    
	$result = $db->query("SELECT * FROM users WHERE user_id = $user_id");
	
	if (!$result)
	{
	    process_system_error("Unable to lookup user in database");	    	    
	    die();
	}
	
	if (1 != $db->num_rows($result))
	{
	    process_system_error("user not found.");
	}
	
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


?>

<form method="post" action=".">

<table border="1">
<tr>
 <th class="vert">Username</th>
 <td><input type="text" name="username"<?php dvc($form_values, 'username') ?>></td>
 </tr>
<tr>
 <th class="vert"><?php echo ($mode_edit ? "New password" : "Password"); ?></th>
 <td><input type="password" name="password1"></td>
 </tr>
<tr>
 <th class="vert">Verify password</th>
 <td><input type="password" name="password2"></td>
 </tr>
<tr>
 <th colspan="2"> Access settings </th>
 </tr>



<tr>
 <th class="vert">Administration privileges</th>
 <td>
   <INPUT type="checkbox" name="access_admin" <?php dvc_checkbox($form_values, 'access_admin');?>> 
 </tr>
 <th class="vert">Change volunteers</th>
 <td>
   <INPUT type="checkbox" NAME="access_change_vol" <?php dvc_checkbox($form_values, 'access_change_vol');?>>
 </tr> 
 

 <tr>
 <th colspan="2">Personal information</th>
 </tr>
 
 <tr>
 <th class="vert">Personal name</th>
 <td><input type="text" name="personalname"<?php dvc($form_values, 'personalname') ?>></td>
 </tr>

 <tr>
 <th class="vert">E-mail address</th>
 <td><input type="text" name="email"<?php dvc($form_values, 'email') ?>></td>
 </tr>

</table>
<?php
if ($mode_edit)
{
    echo ("<INPUT type=\"hidden\" name=\"user_id\" value=\"$user_id\">\n");
    echo ("<INPUT type=\"submit\" name=\"button_user_update\" value=\"Update user\">\n");
}
else
{
    echo ("<INPUT type=\"submit\" name=\"button_user_add\" value=\"Save new user\">\n");
//    echo ("<INPUT type=\"reset\" value=\"Clear\">\n");
}

echo ("</FORM>\n");    
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
	process_system_error("Database error while querying users");
    }
    else if (0 == $db->num_rows($result))
    {
	process_user_error("No user accounts.");
	user_add();
    }
    else
    {
	echo ("<FORM method=\"post\" action=\".\">\n");

	echo ("<TABLE border=\"1\">\n");
	echo ("<THEAD>\n");
	echo ("<TR>\n");
	echo ("<TH>Select</TH>\n");	
	echo ("<TH>Username</TH>\n");	
	echo ("<TH>Personal Name</TH>\n");
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
	echo ("<INPUT type=\"submit\" name=\"button_user_delete\" value=\"Delete\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_user_edit\" value=\"Edit\">\n");
	echo ("</FORM>\n");
    }    
} /* users_list() */


?>