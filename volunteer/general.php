<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: general.php,v 1.2 2003/11/23 17:14:55 andrewziem Exp $
 *
 */

if (preg_match('/notes.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_view_general()
// Displays editable general volunteer fields
{
    global $db;
    global $volunteer;

    $vid = intval($_REQUEST['vid']);

    //    echo ("<H3>General information</H3>\n");


    $form = new FormMaker();
    $form->open(FALSE, 'post', '.', FS_TABLE);
    $form->setValuesArray($volunteer);
    $form->addField(_("Prefix"), 'text', 'prefix', array('length' => 20), 'prefix');
    $form->addField(_("First name"), 'text', 'first', array('length' => 20), 'first');
    $form->addField(_("Middle name"), 'text', 'middle', array('length' => 20), 'middle');
    $form->addField(_("Last name"), 'text', 'last', array('length' => 40), 'last');
    $form->addField(_("Suffix"), 'text', 'suffix', array('length' => 10), 'suffix');
    $form->addField(_("Organization"), 'text', 'organization', array('length' => 40), 'organization');    
    $form->addField(_("Street"), 'text', 'street', array('length' => 40), 'street');
    $form->addField(_("City"), 'text', 'city', array('length' => 30), 'city');    
    $form->addField(_("State/Province"), 'text', 'state', array('length' => 40), 'state');    
    $form->addField(_("Zip/Postal code"), 'text', 'postal_code', array('length' => 10), 'postal_code');        
    $form->addField(_("Country"), 'text', 'country', array('length' => 30), 'country');            
    
    // fixme: put phone numbers into separate table
    
?>
<tr>
 <th class="vert"><?php echo _("Home phone");?></th>
 <td><input type="Text" name="phone_home" value="<?php echo ($volunteer["phone_home"]); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Work phone");?></th>
 <td><input type="Text" name="phone_work" value="<?php echo ($volunteer["phone_work"]); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Cell phone");?></th>
 <td><input type="Text" name="phone_cell" value="<?php echo ($volunteer["phone_cell"]); ?>" size="20"></td>
 </tr>
<?php

    $form->addField(_("E-mail"), 'text', 'email_address', array('length' => 40), 'email_address');            


// show custom fields
// to do: SQL_CACHE

    $result_ext = $db->query("SELECT * FROM extended WHERE volunteer_id = $vid");
    
    if ($result_ext)
    {
	$row_ext = $db->fetch_array($result_ext);
    }
    else 
    {
	$row_ext = array();
    }

    $result_meta = $db->query("SELECT * FROM extended_meta");

    if ($result_meta)
    {
	while (FALSE != ($row_meta = $db->fetch_array($result_meta)))
	{

	    $form->addField($row_meta['label'], $row_meta['fieldtype'], 'custom_'.$row_meta['code'], $attributes, $value);
	}
    }
    
    $db->free_result($result_meta);

    $form->addHiddenField('vid', $vid);
    $form->addButton('volunteer_save', _("Save"));
    $form->addButton('volunteer_delete', _("Delete"));

    $form->close();

} /* volunteer_view_general() */


function volunteer_save()
// Saves general volunteer form data.
{

    global $db;
    global $volunteer;

    // to do: validate

    // sanitize input

    $organization = $db->escape_string(htmlentities($_POST['organization']));

    $prefix = $db->escape_string(htmlentities($_POST['prefix']));
    $first = $db->escape_string(htmlentities($_POST['first']));
    $middle = $db->escape_string(htmlentities($_POST['middle']));
    $last = $db->escape_string(htmlentities($_POST['last']));
    $suffix = $db->escape_string(htmlentities($_POST['suffix']));

    $street = $db->escape_string(htmlentities($_POST['street']), TRUE);
    $city = $db->escape_string(htmlentities($_POST['city']), TRUE);
    $state = $db->escape_string(htmlentities($_POST['state']), TRUE);
    $postal_code = $db->escape_string(htmlentities($_POST['postal_code']));
    $country = $db->escape_string(htmlentities($_POST['country']));

    $email_address = $db->escape_string(htmlentities($_POST['email_address']));
    
    $phone_home = $db->escape_string(htmlentities($_POST['phone_home']));
    $phone_work = $db->escape_string(htmlentities($_POST['phone_work']));
    $phone_cell = $db->escape_string(htmlentities($_POST['phone_cell']));

    if (array_key_exists('wants_monthly_information', $_POST))
    {
	$wants_monthly_information = $db->escape_string($_POST['wants_monthly_information']);
    }
    else
    {
	$wants_monthly_information = 'N';
    }

    $vid = intval($_POST['vid']);

    $sql = "UPDATE volunteers SET " .
	"organization='$organization', ".
	"prefix='$prefix', " .
	"first='$first', " .
	"middle='$middle', " .
	"last='$last', " .
	"suffix='$suffix', " .
	"street='$street', " .
	"city='$city', " .
	"state='$state', " .
	"postal_code='$postal_code', " .
	"country='$country', " .	
	"email_address='$email_address', " .
	"phone_home='$phone_home', " .
	"phone_cell='$phone_cell', " .
	"phone_work='$phone_work', " .
	"wants_monthly_information='$wants_monthly_information' ".
	"WHERE volunteer_id=$vid LIMIT 1";

    // update primary volunteer record

    $success_primary = FALSE != $db->query($sql);

    if (!$success_primary)
    {
	process_system_error(_("Error updating primary volunteer record."), array('debug'=>$db->error()));
    }

    // gather custom fields from POST

    $custom = array();

    foreach ($_POST as $key => $value)
    {
	if (preg_match('/^custom_(\w{1,})$/', $key, $matches))
        {
		$custom[$matches[1]] = array('value' => $value, 'save' => FALSE);	
	}
    }

    // sanitize and validate custom fields

    // get extended fields data from database

    $result_meta = $db->query("SELECT * FROM extended_meta");

    if ($result_meta)
    {
	while (FALSE != ($row_meta = $db->fetch_array($result_meta)))
	{
    	    if (array_key_exists($row_meta['code'], $custom))
	    {
		switch ($row_meta['fieldtype'])
		{
		    case 'date':			
			$new_value = sanitize_date($custom[$row_meta['code']]['value']);		  

			if ($new_value)
			{
		    	    $custom[$row_meta['code']]['save'] = TRUE;
				$custom[$row_meta['code']]['value'] = "'$new_value'";
			}
		        elseif (empty($custom[$row_meta['code']]['value']))
			{
			    $custom[$row_meta['code']]['value'] = "NULL";
			}
			else
			{
			    process_user_error("Bad date format.");
			    $custom[$row_meta['code']]['value'] = "NULL";
			}

		    break;
		    
		    case 'string':		
		    case 'textarea':		
	    		$custom[$row_meta['code']]['value'] = "'".$db->escape_string(htmlentities($custom[$row_meta['code']]['value']))."'";
	    		$custom[$row_meta['code']]['save'] = TRUE;
		    break;

		    case 'integer':		
	    		$custom[$row_meta['code']]['value'] = intval($custom[$row_meta['code']]['value']);
	    		$custom[$row_meta['code']]['save'] = TRUE;
		    break;
	    
		}    
	    }
	}
    }
    else
    {    	
	process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
    }

    $db->free_result($result_meta);

    // save extended data

    // build SQL

    $sql = 'REPLACE into extended ';
    $sql_names = '(volunteer_id';
    $sql_values = "($vid";
    $extended_count = 0;
    foreach ($custom as $key => $value)
    {
	if ($value['save'])
	{
	    $sql_names .= ", $key";
	    $sql_values .= ", ".$value['value'];
	    $extended_count++;
	}
    }

    $sql_names .= ') ';
    $sql_values .= ') ';

    $sql .= " $sql_names VALUES $sql_values";

    // save if extended fields exist

    if ($extended_count > 0)
    {
	$success_extended = (FALSE != $db->query($sql));        
    
	if (!$success_extended)
	{
    	    process_system_error(_("Error updating extended volunteer record."), array('debug' => $db->error()));    
	}	
    }
    else
    {
	// no extended fields
	$success_extended = TRUE;
    }

    // redisplay volunteer record

    if ($success_primary and $success_extended)
    {
	echo("<P>"._("Updated.")."</P>\n");
	$volunteer = volunteer_get($vid);
	include('general.php');
	volunteer_view_general();
    }


} /* volunteer_save() */


?>
