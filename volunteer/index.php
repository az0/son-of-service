<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * View, change, and use a volunteer's record.
 *
 * $Id: index.php,v 1.17 2003/11/23 03:25:42 andrewziem Exp $
 *
 */

ob_start();
session_start();

//if (!empty($_POST))
//    header("Pragma: no-cache");

define('SOS_PATH', '../');

require_once (SOS_PATH . 'include/global.php');
require_once (SOS_PATH . 'functions/html.php');
require_once (SOS_PATH . 'functions/forminput.php');
require_once (SOS_PATH . 'functions/formmaker.php');

$db = new voldbMySql();

if ($db->get_error())
{
    process_system_error(_("Unable to establish database connection."), array('debug'=>$db->get_error()));    
    die();	
}

if (array_key_exists('vid', $_REQUEST))
{
    $volunteer_name = make_volunteer_name(volunteer_get(intval($_REQUEST['vid'])));

}
else
{
    $volunteer_name = "";
}

make_html_begin(_("Volunteer account: ").$volunteer_name, array());

is_logged_in();

make_nav_begin();

  if (array_key_exists('add_skill', $_POST))
  {
    //user pushed a button
    include('skills.php');
    volunteer_skill_add();
  }
  else  if (array_key_exists('button_delete_volunteer_skill', $_POST))
  {
    //user pushed a button
    include('skills.php');
    volunteer_delete_skill();
  }
  else  if (array_key_exists('availability_add', $_POST))
  {
    //user pushed a button
     include('availability.php');
     volunteer_availability_add();
     volunteer_view_availability();
  }
  else if (array_key_exists('button_delete_availability', $_POST))
  {
    //user pushed a button
    include('availability.php');
    volunteer_delete_availability();
  }
  else
  if (array_key_exists('button_add_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_save('add');
    stats_update_volunteer($db, intval($_POST['vid']));
    volunteer_view_work_history();
    work_history_addedit('add');
  }
  else if (array_key_exists('button_update_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_save('update');
    stats_update_volunteer($db, intval($_POST['vid']));
    volunteer_view_work_history(); // show history
    work_history_addedit('add');  // show form
  }
  else
  if (array_key_exists('button_delete_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_delete();
    stats_update_volunteer($db, intval($_POST['vid']));
    volunteer_view_work_history();
    work_history_addedit('add');  // show form
  }
  else
  if (array_key_exists('button_edit_work_history', $_POST))
  {
    include('workhistory.php');
    work_history_addedit('edit');
  }  
  else
  if (array_key_exists('volunteer_delete', $_POST))
  {
    volunteer_delete();
  }
  else
  if (array_key_exists('button_add_note', $_POST))
  {
    include('notes.php');
    note_add();
  }
  else 
  {
    $found = FALSE;
    foreach ($_POST as $pk => $pv)
    {
	if (preg_match('/add_relationship/', $pk))
	{
	    $found = TRUE;
	    include('relationships.php');
	    relationship_add();
	    relationships_view();
	    relationships_add_form();

	}
	else if (preg_match('/delete_relationship_/', $pk))
	{
	    $found = TRUE;
	    include('relationships.php');
	    relationship_delete();
	    relationships_view();
	    relationships_add_form();	    
	}
    }
    if (!$found)
    {
	volunteer_view();  
    }
} 


function volunteer_delete()
{
    global $db;
    
    
    // validate form input
    
    $vid = intval($_POST['vid']);

    if (!preg_match("/^[0-9]+$/", $_POST['vid']))
    {
	process_system_error(_("Bad form input:").' vid');
	die();
    }

    if (array_key_exists('delete_confirm', $_POST) and 'on' == $_POST['delete_confirm'])
    {
	include(SOS_PATH . 'functions/delete_volunteer.php');
	
        delete_volunteer($vid);
    }
    else
    {
	echo ("<P class=\"instructionstext\">Are you sure you want to permanently delete this volunteer and all his related records (work history, notes, reminders, etc.)?  If not, simply click a menu option: General, Skills, etc.</P>\n");

	$volunteer = volunteer_get($vid);
     
	echo ("<PRE>\n");
	echo $volunteer['first']. " " . $volunteer['middle'] . " " . $volunteer['last'] . " (".$volunteer['organization'].")\n";
	echo $volunteer['street'] . "\n";
	echo $volunteer['city'] . ", " . $volunteer['state']. " ". $volunteer['postal_code']." ". $volunteer['country']."\n";
	echo "</PRE>";

     ?>

<FORM method="post" action=".">
<INPUT type="hidden" name="vid" value="<?php echo $vid;?>">          

<input type="submit" name="volunteer_delete" value="<?php echo _("Delete volunteer"); ?>">
<?php echo _("Confirm"); ?> <input type="checkbox" name="delete_confirm"> 


<?php

   }


} /* volunteer_delete() */




function volunteer_save()
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
$wants_monthly_information = $db->escape_string($_POST['wants_monthly_information']);
else
$wants_monthly_information = 'N';

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


function volunteer_view()
{

global $db;
global $volunteer;
global $vid;

if (!array_key_exists('vid', $_REQUEST))
{
	process_system_error(_("You have reached this page incorrectly."));
	die();
}

$vid = intval($_REQUEST['vid']);

$volunteer = volunteer_get($vid);

// keep an array of recently opened volunteers
if (!array_key_exists('recent_vid', $_SESSION))
	$_SESSION['recent_vid']  = array();

if (!array_search($vid, $_SESSION['recent_vid']))
{
	array_pop($_SESSION['recent_vid']);
	$vname = $volunteer['first']. " ".$volunteer['middle']. " ".$volunteer['last'];
	if (!empty($volunteer['organization']))
	$vname .= "(".$volunteer['organization'].")";
	array_unshift($_SESSION['recent_vid'],array('vid'=>$vid, 'name'=>$vname));

}

// execute requested action
if (array_key_exists('volunteer_save', $_POST))
	volunteer_save();
else
{

if (array_key_exists('menu', $_GET))
{
	if ('workhistory' == $_GET['menu'])
	{
	include ('workhistory.php');
	volunteer_view_work_history();
	work_history_addedit('add');
	}
	else if ('skills' == $_GET['menu'])
	{
		include('skills.php');
		volunteer_view_skills();
	}
	else if ('availability' == $_GET['menu'])
	{
		include('availability.php');
		volunteer_view_availability();
	}
	else if ('notes' == $_GET['menu'])
	{
		include('notes.php');
		volunteer_view_notes();
		volunteer_add_note_form();
	}
	else if ('relationships' == $_GET['menu'])
	{
		include('relationships.php');
		relationships_view();
		relationships_add_form();		
	}
	else if ('general' == $_GET['menu'])
	{
		include('general.php');
		volunteer_view_general();
	}
	else
		process_system_error(_("Bad form input:").' GET[menu]');


}
	else
	{
		include('summary.php');
		volunteer_summary();
	}
//	    volunteer_view_general();
}

} /* volunteer_view() */


make_html_end();

?>
