<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: index.php,v 1.30 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

ob_start();

session_start();

define ('SOS_PATH', '../');

require_once (SOS_PATH . 'include/global.php');
require_once (SOS_PATH . 'functions/access.php');
require_once (SOS_PATH . 'functions/html.php');

if (!array_key_exists('download_mailing_list',$_GET))
{
    make_html_begin(_("Administrative menu"), array());
}

is_logged_in();

if (!has_permission(PC_ADMIN, PT_READ, NULL, NULL))
{
    die(_("Insufficient permissions."));
}

if (!array_key_exists('download_mailing_list',$_GET))
{
    make_nav_begin();
}    

$db = connect_db();

if ($db->_connectionID == '')
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
}


function download_mailing_list()
{
    global $db;
    
    if (!array_key_exists('type', $_GET))
    {
	die_message(MSG_SYSTEM_ERROR, _("You have reached this page incorrectly."), __FILE__, __LINE__);
    }
    
    if (!has_permission(PC_ADMIN, PT_READ, NULL, NULL))
    {
	die_message(MSG_USER_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }
    
    // todo: portable concat    
    $sql = "SELECT concat(first, ' ',middle,' ',last) as personalname, organization, street, city, state, postal_code FROM volunteers";
    
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    if (0 == $result->RecordCount())
    {
	process_user_error(_("No data found."));
    }    
    else
    {
	header("Content-disposition: attachment; filename=\"mailinglist.csv\"");
	header("Pragma: no-cache");
	header("Content-type: text/csv");
	
	require_once(SOS_PATH . 'functions/textwriter.php');
	
	$tw = new textDbWriter('csv');
	
	$i = 0;
	
	$fieldnames = array();
	
	while ($i < $result->FieldCount())
	{
	    $fld = $result->FetchField($i);
	    $fieldnames[] = $fld->name;
	    $i++;
	}
	
	$tw->setFieldNames($fieldnames);
    
	while (!$result->EOF)
	{
	    $row = $result->fields;
	    $tw->addRow($row);
	    $result->MoveNext();
	}
	
    }
    
    // todo: finish

}





function admin_menu()
{
	echo "<h3>" . _("Administrative menu") . "</h3>\n";
	echo "<ul>\n";
	echo "<li><a href=\"./?users=0\">" . _("User accounts") . "</a>\n";
	echo "<li><a href=\"./?strings=0\">" . _("Strings: relationship types, skill types, work categories"). "</a>\n";
	echo "<li><a href=\"./?add_custom_field=0\">" . _("Add custom field") . "</a>\n";
	echo "<li><a href=\"./?import_legacy=0\">" . _("Import legacy data") . "</a>\n";
	echo "<li><a href=\"./?import_ncoa=0\">" . _("Import USPS National Change of Address (NCOA)") . "</a>\n";
	//fixme: Download database dump in SQL format
	echo "<li>" . _("Download mailing list") .
		" [<a href=\"./?download_mailing_list&amp;type=postal&amp;who=all\">" . _("All") . "</a>]\n";
	echo "<li><a href=\"./?system_check=0\">". _("System check") ."</a>\n";
	echo "<li><a href=\"./?update_volunteer_stats=0\">" . _("Update volunteer statistics") . "</a>\n";
	echo "</ul>\n";

} /* admin_menu */


// Display appropriate menu

if (array_key_exists('system_check', $_GET))
{
    include('systemcheck.php');
    system_check();
}
else
// STRINGS
if (array_key_exists('button_string_add', $_POST) or array_key_exists('button_string_save', $_POST))
{
    include('strings.php');
    strings_addedit();
}
else if (array_key_exists('strings', $_GET))
{
    include('strings.php');
    strings_list();
    strings_addedit_form();    
}
else if (array_key_exists('button_string_delete', $_POST))
{
    include('strings.php');
    strings_delete();
}
else if (array_key_exists('button_string_edit', $_POST))
{
    include('strings.php');
    strings_edit();
}
else
// USERS
if (array_key_exists('users', $_GET))
{
    include('users.php');
    display_messages();
    users_list();
    user_addedit_form();
}
else
if (array_key_exists('button_user_delete', $_POST))
{
    include('users.php');
    users_delete();
}
else    
if (array_key_exists('button_user_edit', $_POST))
{
    include('users.php');
    user_addedit_form();
}
else    
if (array_key_exists('button_user_add', $_POST) or array_key_exists('button_user_update', $_POST))
{
    include('users.php');
    user_save();
}
else    
// MAILING LIST
if (array_key_exists('download_mailing_list', $_GET))
    download_mailing_list();
else    
// VOLUNTEER STATS
if (array_key_exists('update_volunteer_stats', $_GET))
{
    include_once(SOS_PATH . 'functions/stat.php');
    stats_update_volunteers($db);
    echo ("<P>Volunteer statistics updated.</P>\n");
    admin_menu();
}        
else
// IMPORT LEGACY DATA
if (array_key_exists('import_legacy', $_REQUEST))
{
    require_once('import.php');
    import_legacy();
}        
else
// IMPORT NCOA DATA
if (array_key_exists('import_ncoa', $_REQUEST))
{
    require_once('import_ncoa.php');
    import_ncoa();
}        

else


// CUSTOM FIELDS
if (array_key_exists('add_custom_field', $_REQUEST))
{
    include('custom.php');
    custom_add_field_form();
}        
else 
// MENU
{
    display_messages();
    admin_menu();
}

if (!array_key_exists('download_mailing_list',$_GET))
{
    make_html_end();
}

?>



