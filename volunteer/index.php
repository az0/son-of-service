<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * View, change, and use a volunteer's record.
 *
 * $Id: index.php,v 1.35 2009/02/12 04:11:20 andrewziem Exp $
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

$db = connect_db();

if ($db->_connectionID == '')
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
}

if (!array_key_exists('vid', $_REQUEST) or !is_numeric($_REQUEST['vid']))
{
    die_message(MSG_SYSTEM_ERROR, "vid must be numeric.  System error.", __FILE__, __LINE__);
}

$volunteer = volunteer_get($_REQUEST['vid'], $errstr);
if (!$volunteer)
{
    die_message(MSG_SYSTEM_ERROR, "volunteer_get(): $errstr");
}
$volunteer_name = make_volunteer_name($volunteer);

make_html_begin(_("Volunteer account: ").htmlentities($volunteer_name), array());

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
  }
  else if (array_key_exists('button_update_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_save('update');
  }
  else
  if (array_key_exists('button_delete_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_delete();
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
  if (array_key_exists('volunteer_add_phone', $_POST))
  {
    $vid = intval($_POST['vid']);  
    if (has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
        $result = $db->Execute("INSERT INTO phone_numbers (volunteer_id) VALUES ($vid)");
	if (!$result)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	}	
	save_message(MSG_USER_NOTICE, _("Added."));
    }    
    else
    {
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);	
    }
    redirect("?vid=$vid&menu=general");
  }
  else
  if (array_key_exists('button_add_note', $_POST) or array_key_exists('button_save_note', $_POST))
  {
    include('notes.php');
    note_addedit();
  }
  else
  if (array_key_exists('button_edit_note', $_POST))
  {
    include('notes.php');
    volunteer_addedit_note_form('edit');
  }
  else if (array_key_exists('button_delete_note', $_POST))
  {
    include('notes.php');
    note_delete();
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
    global $volunteer;
    
    
    $errors_found = 0;
    
    // validate form input
    
    $vid = intval($_POST['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    

    if (!preg_match("/^[0-9]+$/", $_POST['vid']))
    {
	$errors_found++;
	save_message(MSG_USER_ERROR, _("Bad form input:").' vid');
    }
    
    if ($errors_found)
    {
	redirect("./?vid=$vid");
	exit();
    }

    if (array_key_exists('delete_confirm', $_POST) and 'on' == $_POST['delete_confirm'])
    {
	include(SOS_PATH . 'functions/delete_volunteer.php');
	
        delete_volunteer($vid);
    }
    else
    {
	echo ("<P class=\"instructionstext\">Are you sure you want to permanently delete this volunteer and all his related records (work history, notes, reminders, etc.)?  If not, simply click a menu option: General, Skills, etc.</P>\n");
     
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


function volunteer_view()
{

global $db;

if (!array_key_exists('vid', $_REQUEST))
{
	process_system_error(_("You have reached this page incorrectly."));
	die();
}

$vid = intval($_REQUEST['vid']);

$volunteer = volunteer_get($vid, $errstr);

if (!$volunteer)
{
    die_message(MSG_SYSTEM_ERROR, "volunteer_get(): $errstr");
}

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
{
    require_once('general.php');
    volunteer_save();
}
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
		volunteer_availability_add_form();
	}
	else if ('notes' == $_GET['menu'])
	{
		include('notes.php');
		volunteer_view_notes();
		volunteer_addedit_note_form('add');
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
}

} /* volunteer_view() */


make_html_end();

?>
