<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: strings.php,v 1.5 2003/12/07 02:07:26 andrewziem Exp $
 *
 */

if (preg_match('/strings.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


$category_map = array('relationship' => _("Relationship"), 'work' => _("Work category"), 'skill' => _("Skill"));

function strings_add()
{
    global $db;
    global $category_map;
    

    if (!has_permission(PC_ADMIN, PT_WRITE))
    {
	// User should not be given option to get here.
	die_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    

    $errors_found = 0;

    if (strlen($_POST['string_name']) > 100)
    {
	save_message(MSG_USER_ERROR, _("Too long:"). ' '. _("String name"));
	$errors_found++;
    }
    
    if (strlen($_POST['string_name']) < 2)
    {
	save_message(MSG_USER_ERROR, _("Too short:"). ' '. _("String name"));
	$errors_found++;	
    }
    
    if (!array_key_exists($_POST['string_category'], $category_map))
    {
	save_message(MSG_USER_ERROR, _("Choose a category from the list."));
	$errors_found++;
    }

    if (0 == $errors_found)
    {
	$string_name = $db->qstr(htmlentities($_POST['string_name']), get_magic_quotes_gpc());
	$string_category = $db->qstr(htmlentities($_POST['string_category']), get_magic_quotes_gpc());    
	
	$sql = "INSERT INTO strings (s, type) VALUES ($string_name, $string_category)";
	
	$result = $db->Execute($sql);

	if (FALSE != $result)
	{
	    save_message(MSG_USER_NOTICE, _("Added succesfully."));	
	}
	else
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
	}
    }
    redirect("./?strings");
} /* strings_add() */


function strings_add_form()
{
    global $category_map;
    
    
    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Add a string</LEGEND>\n");
    
    echo ("<FORM method=\"POST\" action=\".\">\n");
    $i = 0;
    foreach ($category_map as $key => $value)
    {
	if ($i > 0)
	{
	    echo ("<BR>\n");
	}
	echo ("<INPUT type=\"radio\" name=\"string_category\" value=\"$key\">$value\n");
	$i++;
    }
    echo ("<BR>"._("Name") . " <INPUT type=\"type\" name=\"string_name\" maxlength=\"100\">\n");
    echo ("<BR><INPUT type=\"submit\" name=\"button_string_add\" value=\""._("Add")."\">\n");    
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
} /* strings_add_form() */


function strings_list()
{
    global $db;
    global $category_map;
    

    display_messages();

    if (!has_permission(PC_ADMIN, PT_READ))
    {
	// User should not be given option to get here.
	die_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }
    
    $sql = "SELECT strings.string_id AS string_id, strings.s AS name, strings.type AS type, count(*) AS count ".
	"FROM strings ".
	"LEFT JOIN work ".
	"ON strings.string_id = work.category_id ".
	"LEFT JOIN relationships ".
	"ON strings.string_id = relationships.string_id ".
	"LEFT JOIN volunteer_skills ".
	"ON strings.string_id = volunteer_skills.string_id ".
	"GROUP BY strings.string_id ".
	"ORDER BY type, name, count ";

    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else if (0 == $result->RecordCount())
    {
	process_user_error(_("No work categories exist."));
    }
    else
    {
	echo ("<H2>"._("Strings")."</H2>\n");
	echo ("<P style=\"instructions\">To edit or delete a string, select the radio button by it.  Then click edit or delete (respectively).</P>\n");
    
	echo ("<FORM method=\"post\" action=\".\">\n");
    
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	echo ("<TH>"._("Select")."</TH>\n");
	echo ("<TH>"._("Category")."</TH>\n");	
        echo ("<TH>"._("String")."</TH>\n");	
// todo: fixme: quantity never zero
//	echo ("<TH>"._("Quantity in use")."</TH>\n");	
	echo ("</TR>\n");
	while (!$result->EOF)
	{
	    $row = $result->fields;
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"string_id\" value=\"".$row['string_id']."\"></TD>\n");
	    echo ("<TD>".$category_map[$row['type']]."</TD>\n");	    
	    echo ("<TD>".$row['name']."</TD>\n");
//	    echo ("<TD>".$row['count']."</TD>\n");	    
	    echo ("</TR>\n");
	    $result->MoveNext();
	}
	echo ("</TABLE>\n");
	echo ("<INPUT type=\"submit\" name=\"button_string_delete\" value=\""._("Delete")."\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_string_edit\" value=\""._("Edit")."\">\n");
	echo ("</FORM>\n");
    }
} /* string_list() */


function strings_delete()
{
    global $db;
    
    
    if (!has_permission(PC_ADMIN, PT_WRITE))
    {
	// User should not be given option to get here.
	die_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    

    if (!array_key_exists('string_id', $_POST))
    {
	save_message(MSG_USER_ERROR, _("You must make a selection."));
	redirect("./?strings");
	die();
    }    
    
    $string_id = intval($_POST['string_id']);
    
    // Exists?  What type?
    $sql = "SELECT type FROM strings WHERE string_id = $string_id";
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else if (0 == $result->RecordCount())
    {
	// unusual
	die_message(MSG_SYSTEM_ERROR, "Cannot find string.", __FILE__, __LINE__, $sql);	
    }
    
    $row = $result->fields;
    
    switch ($row['type'])
    {
	case 'relationship':
	    $sql = "SELECT * FROM relationships WHERE string_id = $string_id";
	    break;
	    
	case 'work':
	    $sql = "SELECT * FROM work WHERE category_id = $string_id";
	    break;	    

	case 'skill':
	    $sql = "SELECT * FROM volunteer_skills WHERE string_id = $string_id";
	    break;	    
    
	default:
	    process_system_error(_("Unexpected type from database."));
	    $sql = "SELECT * FROM strings WHERE 0 = 1"; // find nothing
	    break;
    }
    
    $result = $db->Execute($sql);
    
    
    if (!$result)
    {
	save_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);	
    } 
    else if ($result->RecordCount() > 0)
    {
	save_message(MSG_USER_ERROR, _("Currently in use."));	
    }
    else
    {
	$sql = "DELETE FROM strings WHERE string_id = $string_id LIMIT 1";
	
	$result = $db->Execute($sql);	
	
	if ($result)
	{
	    save_message(MSG_USER_NOTICE, _("Removed."));
	}
	else
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql);	
	}
    }
    
    // redirect user to non-POST page
    redirect("./?strings");
    
} /* strings_delete() */

?>