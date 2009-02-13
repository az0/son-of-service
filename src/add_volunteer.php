<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: add_volunteer.php,v 1.19 2009/02/13 03:52:15 andrewziem Exp $
 *
 */

ob_start();
session_start();

define('SOS_PATH', '../');

require_once (SOS_PATH . 'include/global.php');
require_once (SOS_PATH . 'include/config.php');
require_once (SOS_PATH . 'functions/html.php');


make_html_begin(_("Add a volunteer"), array());

is_logged_in();

make_nav_begin();

echo "<h3>" . _("Add a volunteer") . "</h3>\n";

function volunteer_add()
{
    global $db;
    
    
    // validate form input

    $errors_found = 0;
    
    if (2 > (strlen(trim($_POST['last'])) + strlen(trim($_POST['organization']))))
    {
       process_user_error(_("Please enter a longer last name or organization."));
       $errors_found++;
    }
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, NULL, NULL))
    {
	process_user_error(_("Insufficient permissions."));
	$errors_found++;	
    }    

    if ($errors_found)
    {    
	  echo ("<P>Try <A href=\"add_volunteer.php\">again</A>.</P>\n");
	  // todo: redisplay form here with values in place
	  die();
    }
        
    $organization = $db->qstr(htmlentities($_POST['organization']), get_magic_quotes_gpc());

    $prefix = $db->qstr(htmlentities($_POST['prefix']), get_magic_quotes_gpc()); 
    $first = $db->qstr(htmlentities($_POST['first']), get_magic_quotes_gpc());
    $middle = $db->qstr(htmlentities($_POST['middle']), get_magic_quotes_gpc());      
    $last = $db->qstr(htmlentities($_POST['last']), get_magic_quotes_gpc());      
   
    $street = $db->qstr(htmlentities($_POST['street']), get_magic_quotes_gpc());         
    $city = $db->qstr(htmlentities($_POST['city']), get_magic_quotes_gpc());            
    $state = $db->qstr(htmlentities($_POST['state']), get_magic_quotes_gpc());
    $postal_code = $db->qstr(htmlentities($_POST['postal_code']), get_magic_quotes_gpc());   
    $country = $db->qstr(htmlentities($_POST['country']), get_magic_quotes_gpc());       
   
    $email_address = $db->qstr(htmlentities($_POST['email_address']), get_magic_quotes_gpc());      
   
    $sql = 'INSERT INTO volunteers '.
	    '(prefix, first,middle,last,organization,street,city,state,postal_code,country,email_address, dt_added, uid_added, dt_modified, uid_modified) '.
	    "VALUES ($prefix, $first, $middle, $last, $organization, $street, $city, $state, $postal_code, $country, $email_address, now(), ".get_user_id().", now(), uid_added)";

    $result = $db->Execute($sql);

    if (!$result) 
    { 
	die_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
    }
    
    $vid = $db->Insert_ID();
    
    // insert phone number records
    
    if (!empty($_POST['phone_home']) or !empty($_POST['phone_work']) or !empty($_POST['phone_cell']))
    {	
	// select an empty record

	$sql = "SELECT * FROM phone_numbers WHERE 0 = 1";
	$template_result = $db->Execute($sql);
	if (!$template_result)
	{
	    die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	}
	
	$record['volunteer_id'] = $vid;
    }

    if (!empty($_POST['phone_home']))
    {	
	$record['number'] =  htmlentities($_POST['phone_home']);
	$record['memo'] = _("Home");
	$sql = $db->GetInsertSql($template_result, $record);
	$result = $db->Execute($sql);
	if (!$result)
	{
	    // todo: roll back
	    die_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
	}
    }

    if (!empty($_POST['phone_work']))
    {	    	
	$record['number'] =  htmlentities($_POST['phone_work']);
	$record['memo'] = _("Work");
	$sql = $db->GetInsertSql($template_result, $record);
	$result = $db->Execute($sql);
	if (!$result)
	{
	    // todo: roll back	
	    die_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
	}
    }	

    if (!empty($_POST['phone_cell']))
    {	    	    
	$record['number'] =  htmlentities($_POST['phone_cell']);
	$record['memo'] = _("Cell");
	$sql = $db->GetInsertSql($template_result, $record);
	$result = $db->Execute($sql);
	if (!$result)
	{
	    // todo: roll back	
	    die_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
	}	
    }
     
    // display success message
    
    $volunteer_row = volunteer_get($vid, $errstr);
    
    if ($volunteer_row)
    {
        echo ("<P>" . _("Added:") . " <A href=\"". SOS_PATH . "volunteer/?vid=$vid\">" . make_volunteer_name($volunteer_row) . ' (#'.$vid.")</A>.</P>\n");
    }
    else
    {
	echo ("<P>volunteer_get() error:  $errstr</P>\n");
    }


} /* add_volunteer() */


function volunteer_add_form()
{
    
?>    
    <form method="post" action="add_volunteer.php">

<table border="0" width="50%" cellspacing="0" cellpadding="0">
<tr>
 <th class="vert"><?php echo _("Prefix"); ?></th>
 <td><input type="Text" name="prefix"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("First name"); ?></th>
 <td><input type="Text" name="first"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Middle name"); ?></th>
 <td><input type="Text" name="middle"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Last name"); ?></th>
 <td><input type="Text" name="last"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Organization"); ?></th>
 <td><input type="text" name="organization"></td>
 </tr> 
<tr>
 <th class="vert"><?php echo _("Street"); ?></th>
 <td><input type="Text" name="street"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("City"); ?></th>
 <td><input type="Text" name="city"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("State/Province"); ?></th>
 <td><input type="Text" name="state"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Zip/Postal code"); ?></th>
 <td><input type="Text" name="postal_code"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Country"); ?></th>
 <td><input type="Text" name="country"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Home phone"); ?></th>
 <td><input type="Text" name="phone_home"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Work phone"); ?></th>
 <td><input type="Text" name="phone_work"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Cell phone"); ?></th>
 <td><input type="Text" name="phone_cell"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("E-mail"); ?></th>
 <td><input type="Text" name="email_address"></td>
 </tr>


</table>
<input type="submit" name="button_add_volunteer" value="<?php echo _("Add");?>">

</form>
<?php

} /* volunteer_add_form() */

if (array_key_exists('button_add_volunteer', $_POST)) 
{
    $db = connect_db();

    if (!$db)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);	
    }

    volunteer_add();
}
else 
{
    volunteer_add_form();  
}
  
  

make_html_end();

?>
