<?php

/*
 * Son of Service
 * Copyright (C) 2003-2004 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: index.php,v 1.19 2004/02/15 15:20:06 andrewziem Exp $
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
    make_html_begin("Administrative menu", array());
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
	process_system_error('You have reached this page incorrectly.');
	die();
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
    
    // <LI>Download mailing list [<A href="admin.php?download_mailing_list=1&type=postal&who=all">all</A>]</LI>
    
    // todo: finish

}





function admin_menu()
{
?>
<H3>Administrative menu</H3>


<UL>
 <LI><A href="./?users">User accounts</A>
 <LI><A href="./?strings">Strings: relationship types, skill types, work categories</A>
 <LI><A href="./?update_volunteer_stats">Update volunteer statistics</A>
 <LI><A href="./?system_check">System check</A>
 <LI><A href="./?add_custom_field">Add custom field</A>
 <LI><A href="./?import_legacy">Import legacy data</A>
 <LI>Download database dump in SQL format
 <LI>Download mailing list [<A href="./?download_mailing_list&type=postal&who=all">all</A>]</LI>

 </UL>
<?php

} /* admin_menu */


// Display appropriate menu

if (array_key_exists('system_check', $_GET))
{
    include('systemcheck.php');
    system_check();
}
else
// STRINGS
if (array_key_exists('button_string_add', $_POST))
{
    include('strings.php');
    strings_add();
}
else if (array_key_exists('strings', $_GET))
{
    include('strings.php');
    strings_list();
    strings_add_form();    
}
else if (array_key_exists('button_string_delete', $_POST))
{
    include('strings.php');
    strings_delete();
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
// IMPORT
if (array_key_exists('import_legacy', $_REQUEST))
{
    require_once('import.php');
    import_legacy();
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
    admin_menu();
}

if (!array_key_exists('download_mailing_list',$_GET))
{
    make_html_end();
}

?>



