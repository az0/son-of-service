<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: general.php,v 1.7 2003/12/07 02:07:27 andrewziem Exp $
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
    global $db_cache_timeout;


    $vid = intval($_REQUEST['vid']);

    //    echo ("<H3>General information</H3>\n");
    
    display_messages();


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
// todo: SQL_CACHE

    $sql = "SELECT * FROM extended WHERE volunteer_id = $vid";

    $result_ext = $db->CacheExecute($db_cache_timeout, $sql);
    
    if ($result_ext)
    {
	$row_ext = $result_ext->fields;
    }
    else 
    {
	$row_ext = array();
    }
    
    $sql = "SELECT * FROM extended_meta";

    $result_meta = $db->CacheExecute($db_cache_timeout, $sql);

    if ($result_meta)
    {
	while (!$result_meta->EOF)
	{
	    $row_meta = $result_meta->fields;
	    $form->addField($row_meta['label'], $row_meta['fieldtype'], 'custom_'.$row_meta['code'], $attributes, $value);
	    $result_meta->MoveNext();
	}
	
	$result_meta->Close();
    }

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
    global $db_cache_timeout;
    
    

    // todo: validate

    // sanitize input

    $organization = $db->qstr(htmlentities($_POST['organization']), get_magic_quotes_gpc());

    $prefix = $db->qstr(htmlentities($_POST['prefix']), get_magic_quotes_gpc());
    $first = $db->qstr(htmlentities($_POST['first']), get_magic_quotes_gpc());
    $middle = $db->qstr(htmlentities($_POST['middle']), get_magic_quotes_gpc());
    $last = $db->qstr(htmlentities($_POST['last']), get_magic_quotes_gpc());
    $suffix = $db->qstr(htmlentities($_POST['suffix']), get_magic_quotes_gpc());

    $street = $db->qstr(htmlentities($_POST['street']), get_magic_quotes_gpc());
    $city = $db->qstr(htmlentities($_POST['city']), get_magic_quotes_gpc());
    $state = $db->qstr(htmlentities($_POST['state']), get_magic_quotes_gpc());
    $postal_code = $db->qstr(htmlentities($_POST['postal_code']), get_magic_quotes_gpc());
    $country = $db->qstr(htmlentities($_POST['country']), get_magic_quotes_gpc());

    $email_address = $db->qstr(htmlentities($_POST['email_address']), get_magic_quotes_gpc());
    
    $phone_home = $db->qstr(htmlentities($_POST['phone_home']), get_magic_quotes_gpc());
    $phone_work = $db->qstr(htmlentities($_POST['phone_work']), get_magic_quotes_gpc());
    $phone_cell = $db->qstr(htmlentities($_POST['phone_cell']), get_magic_quotes_gpc());

    $vid = intval($_POST['vid']);
    
    // todo: portable LIMIT for UPDATE

    $sql = "UPDATE volunteers SET " .
	"organization=$organization, ".
	"prefix=$prefix, " .
	"first=$first, " .
	"middle=$middle, " .
	"last=$last, " .
	"suffix=$suffix, " .
	"street=$street, " .
	"city=$city, " .
	"state=$state, " .
	"postal_code=$postal_code, " .
	"country=$country, " .	
	"email_address=$email_address, " .
	"phone_home=$phone_home, " .
	"phone_cell=$phone_cell, " .
	"phone_work=$phone_work " .
	"WHERE volunteer_id = $vid";

    // update primary volunteer record

    $success_primary = FALSE != $db->Execute($sql);

    if (!$success_primary)
    {
	save_message(MSG_SYSTEM_ERROR, _("Error updating primary volunteer record."), __FILE__, __LINE__, $sql);
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
    
    $sql = "SELECT * FROM extended_meta";

    $result_meta = $db->CacheExecute($db_cache_timeout, $sql);

    if ($result_meta)
    {
	while (!$result_meta->EOF)
	{
	    $row_meta = $result_meta->fields;
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
	    		$custom[$row_meta['code']]['value'] = $db->qstr(htmlentities($custom[$row_meta['code']]['value']), get_magic_quotes_gpc());
	    		$custom[$row_meta['code']]['save'] = TRUE;
		    break;

		    case 'integer':		
	    		$custom[$row_meta['code']]['value'] = intval($custom[$row_meta['code']]['value']);
	    		$custom[$row_meta['code']]['save'] = TRUE;
		    break;
	    
		}    
	    }
	    $result_meta->MoveNext();
	}
    }
    else
    {    	
	save_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);	
    }

    $result_meta->Close();

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
	$success_extended = (FALSE != $db->Execute($sql));        
    
	if (!$success_extended)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error updating extended volunteer record."), __FILE__, __LINE__, $sql);
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
	save_message(MSG_USER_NOTICE, _("Updated."));    
    }
    
    // redirect user to non-POST page
    ob_end_clean();
    header("Location: ./?vid=$vid&menu=general");


} /* volunteer_save() */


?>
