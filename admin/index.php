<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: index.php,v 1.11 2003/11/23 21:39:48 andrewziem Exp $
 *
 */

ob_start();

session_start();

define (SOS_PATH, '../');

require_once (SOS_PATH . 'include/global.php');
require_once (SOS_PATH . 'functions/html.php');

if (!array_key_exists('download_mailing_list',$_GET))
{
    make_html_begin("Administrative menu", array());
}

is_logged_in();

if (!array_key_exists('download_mailing_list',$_GET))
{
    make_nav_begin();
}    

$db = new voldbMySql();

if ($db->get_error())
{
    process_system_error(_("Unable to establish database connection."), array ('debug' => $db->get_error()));    
    die();	
}

function download_mailing_list()
{
    global $db;
    
    if (!array_key_exists('type', $_GET))
    {
	process_system_error('You have reached this page incorrectly.');
	die();
    }
    
    $result = $db->query("SELECT concat(first, ' ',middle,' ',last) as personalname, organization, street, city, state, postal_code FROM volunteers");
    
    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug'=> $db->get_error()));
    }
    else
    if (0 == $db->num_rows($result))
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
	
	while ($i < mysql_num_fields($result))
	{
	    $meta = mysql_fetch_field($result);
	    $fieldnames[] = $meta->name;
	    $i++;
	}
	
	$tw->setFieldNames($fieldnames);
    
	while (FALSE != ($row = mysql_fetch_row($result)))
	{
	    $tw->addRow($row);
	}
	
    }
    
    // <LI>Download mailing list [<A href="admin.php?download_mailing_list=1&type=postal&who=all">all</A>]</LI>
    
    // to do: finish

}





function admin_menu()
{
?>
<H3>Administrative menu</H3>


<UL>
 <LI><A href="./?add_user=1">Add a user account</A>
 <LI><A href="./?users=1">User accounts</A>
 <LI><A href="./?list_strings=1">Strings: relationship types, skill types, work categories</A>
 <LI><A href="./?update_volunteer_stats=1">Update volunteer statistics</A>
 <LI><A href="./?system_check=1">System check</A>
 <LI><A href="./?add_custom_field=1">Add custom field</A>
 <LI><A href="./?import_legacy=1">Import legacy data</A>
 <LI>Download database dump in SQL format
 <LI>Download mailing list [<A href="./?download_mailing_list=1&type=postal&who=all">all</A>]</LI>

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
    strings_list();    
    strings_add_form();    
}
else if (array_key_exists('list_strings', $_GET))
{
    include('strings.php');
    strings_list();
    strings_add_form();    
}
else if (array_key_exists('button_string_delete', $_POST))
{
    include('strings.php');
    strings_delete();
    strings_list();
    strings_add_form();    
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
if (array_key_exists('button_user_edit', $_POST) or array_key_exists('add_user', $_GET))
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



