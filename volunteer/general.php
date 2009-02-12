<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: general.php,v 1.21 2009/02/12 04:11:20 andrewziem Exp $
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
    $form->addField(_("Prefix"), 'text', 'prefix', array('length' => 20), $volunteer['prefix']);
    $form->addField(_("First name"), 'text', 'first', array('length' => 20), $volunteer['first']);
    $form->addField(_("Middle name"), 'text', 'middle', array('length' => 20), $volunteer['middle']);
    $form->addField(_("Last name"), 'text', 'last', array('length' => 40), $volunteer['last']);
    $form->addField(_("Suffix"), 'text', 'suffix', array('length' => 10), $volunteer['suffix']);
    $form->addField(_("Organization"), 'text', 'organization', array('length' => 40), $volunteer['organization']);    
    $form->addField(_("Street"), 'text', 'street', array('length' => 40), $volunteer['street']);
    $form->addField(_("City"), 'text', 'city', array('length' => 30), $volunteer['city']);    
    $form->addField(_("State/Province"), 'text', 'state', array('length' => 40), $volunteer['state']);    
    $form->addField(_("Zip/Postal code"), 'text', 'postal_code', array('length' => 10), $volunteer['postal_code']);        
    $form->addField(_("Country"), 'text', 'country', array('length' => 30), $volunteer['country']);            
    
    $sql_phones = "SELECT * FROM phone_numbers WHERE volunteer_id = $vid";
    $result_phones = $db->Execute($sql_phones);
    if (!$result_phones)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql_phones);
    }
    
    $i = 0;
    while (!$result_phones->EOF)
    {
	$i++;
	$phone_row = $result_phones->fields;
	$form->addField(_("Phone #") . $i, 'text', 'phone_number_'.$phone_row['phone_number_id'], array('length' => 20), $phone_row['number']);            	
	$form->addField(_("Memo: Phone #") . $i, 'text', 'phone_memo_'.$phone_row['phone_number_id'], array('length' => 20), $phone_row['memo']);            		
	$result_phones->MoveNext();
    }

    $form->addField(_("E-mail"), 'text', 'email_address', array('length' => 40), $volunteer['email_address']);            
    
    // show custom data fields

    $sql = "SELECT * FROM extended WHERE volunteer_id = $vid";

    $result_ext = $db->Execute($sql);
    
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
	    switch ($row_meta['fieldtype'])
	    {
		case 'string':
		    $attributes = array('length' => $row_meta['size1']);
		    break;
		case 'textarea':
		    $attributes = array('length' => $row_meta['size1'], 'cols' => $row_meta['size2'], 'rows' => $row_meta['size3']);
		    break;
		case 'integer':
		case 'date':
		    $attributes = array();
		    break;		    
	        default:
		    process_system_error("Unexpected type in extended_meta");
		    break;
	    }
	    $value = $row_ext[$row_meta['code']];
	    $form->addField($row_meta['label'], $row_meta['fieldtype'], 'custom_'.$row_meta['code'], $attributes, $value);
	    $result_meta->MoveNext();
	}
	
	$result_meta->Close();
    }

    if (has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
        $form->addHiddenField('vid', $vid);
	$form->addButton('volunteer_save', _("Save"));
        $form->addButton('volunteer_add_phone', _("Add phone number"));    
        $form->addButton('volunteer_delete', _("Delete volunteer"));
    }

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
    
    $volunteer_record = array();

    $fields = array('organization', 'prefix', 'first', 'middle', 'last', 'suffix', 'street',
    	'city', 'state', 'postal_code', 'country', 'email_address');
	
    foreach ($fields as $field)
    {
	$volunteer_record[$field] = $_POST[$field];  
    }

    $vid = intval($_POST['vid']);    
    $volunteer_record['volunteer_id'] = $vid;    
    
    $errors_found = 0;    
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
        
    if ($errors_found)
    {
        redirect("?vid=$vid&menu=general");    
	return FALSE;
    }
    
    $rs_volunteers = $db->Execute("SELECT * FROM volunteers WHERE volunteer_id = $vid");
    
    $volunteer_record['dt_modified'] = $db->sysTimeStamp;
    $volunteer_record['uid_modified'] = get_user_id();
    
    $sql = $db->GetUpdateSQL($rs_volunteers, $volunteer_record);
    
    // update primary volunteer record

    $success_primary = FALSE != $db->Execute($sql);

    if (!$success_primary)
    {
	save_message(MSG_SYSTEM_ERROR, _("Error updating primary volunteer record."), __FILE__, __LINE__, $sql);
    }
    
    // gather phone numbers

    $phone_numbers = array();

    foreach ($_POST as $key => $value)
    {
	if (preg_match('/^phone_number_(\d{1,})$/', $key, $matches))
        {
	    // $matches[1] corresponds to database field phone_number_id (int)
	    $phone_numbers[$matches[1]]['number'] = $value;	
	}
	if (preg_match('/^phone_memo_(\d{1,})$/', $key, $matches))
        {
	    // $matches[1] corresponds to database field phone_number_id (int)	    
	    $phone_numbers[$matches[1]]['memo'] = $value;	
	}	
    }
    
    // update phone numbers
    
    foreach ($phone_numbers as $key => $phone)
    {
	if (array_key_exists('memo', $phone) and array_key_exists('number', $phone))
	{
	    // todo: validate these fields
	    $number = $db->qstr($phone['number'], get_magic_quotes_gpc());	    
	    $memo = $db->qstr($phone['memo'], get_magic_quotes_gpc());
	    $phone_number_id = intval($key);
	    // todo: portable LIMIT 1;	    	    	    
	    if (strlen(trim($number.$memo, urldecode("%27 "))) < 1)
	    {
		$sql = "DELETE FROM phone_numbers ".
		    "WHERE volunteer_id = $vid AND phone_number_id = $key";
		$result = $db->Execute($sql);
		if (!$result)
		{
		    die_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql);
		}		
	    }
	    else
	    {
		$sql = "UPDATE phone_numbers ".
		"SET number = $number, memo = $memo ".
		"WHERE volunteer_id = $vid AND phone_number_id = $key";
		$result = $db->Execute($sql);
		if (!$result)
		{
		    die_message(MSG_SYSTEM_ERROR, _("Error updating data in database."), __FILE__, __LINE__, $sql);
		}
	    }
	}
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
    
    // todo: validate custom data fields

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
    redirect("?vid=$vid&menu=general");


} /* volunteer_save() */


?>
