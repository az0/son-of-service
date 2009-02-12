<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: notes.php,v 1.25 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/notes.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_view_notes($brief = FALSE)
{
    global $db;


    display_messages();

    $vid = intval($_GET['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_READ, $vid, NULL))
    {
	process_user_error(_("Insufficient permissions."));
	return FALSE;
    }
    
    $column_names = array('added_by', 'assigned_to', 'dt', 'reminder_date', 'quality');
    $orderby = make_orderby($_GET, $column_names, 'dt', 'DESC');

    $sql = 'SELECT notes.*,u1.username as added_by, u2.username as assigned_to '.
	'FROM notes '.
	'LEFT JOIN users as u1 ON notes.uid_added = u1.user_id '.
	'LEFT JOIN users as u2 ON notes.uid_assigned = u2.user_id '.
	"WHERE notes.volunteer_id = $vid ".
	$orderby;

    $result = $db->Execute($sql);

    if (!$result)
    {	
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }

    if (!$brief or 0 < $result->RecordCount())
    {
	echo ("<H3>"._("Notes")."</H3>\n");
    }

    if (0 == $result->RecordCount())
    {
	if (!$brief)
	process_user_notice(_("None found."));
    }
    else
    { 
	// display work history

	require_once(SOS_PATH . 'functions/table.php');

	$dtp = new DataTablePager();
	
	if ($brief)
	{
	    // show last ten	    

	    $dtp->setPrintable(TRUE);
	    $dtp->setPagination(10, $result->RecordCount() > 10 ? $result->RecordCount() - 10 : 0);
	}
	else
	{
	    $dtp->setPagination(10);
	    echo ("<FORM method=\"post\" action=\".\">\n");
	    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	}

	$headers = array();
	$headers['note_id'] = array('checkbox' => TRUE, 'label' => _("Select"));
	$headers['dt'] =  array('label' => _("Time"), 'type' => TT_DATETIME, 'sortable' => TRUE);	
	$headers['reminder_date'] =  array('label' => _("Reminder date"), 'type' => TT_DATE, 'sortable' => TRUE);		
	$headers['added_by'] =  array('label' => _("Added by"), 'sortable' => TRUE);		
	$headers['assigned_to'] =  array('label' => _("Assigned to"), 'sortable' => TRUE);		
	$headers['quality'] =  array('label' => _("Quality"), 'sortable' => TRUE);			
	$headers[] = array('break_row' => TRUE);			
	$headers['message'] = array('label' => _("Message"), 'colspan' => is_printable() ? 5 : 6, 'nl2br' => TRUE);
	$dtp->setHeaders($headers);
	$dtp->setDatabase($db, $result);
	$dtp->render();

	if (!$brief)
	{
	    echo ("<INPUT type=\"submit\" name=\"button_edit_note\" value=\""._("Edit")."\">\n");
	    echo ("<INPUT type=\"submit\" name=\"button_delete_note\" value=\""._("Delete")."\">\n");
	    echo ("</FORM>\n");
	}
    }

} /* volunteer_view_notes() */


function volunteer_addedit_note_form($mode)
{
    global $db;
    global $db_cache_timeout;


    // todo: make searchable
    
    assert('add' == $mode or 'edit' == $mode);
    
    $vid = intval($_REQUEST['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
//	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
	return FALSE;
    }    

    if ('edit' == $mode)
    {
	$title = _("Edit note");
	$note_ids = find_values_in_request($_POST, 'note_id');
	if (0 == count($note_ids))
	{
	    save_message(MSG_USER_ERROR, _("You must make a selection."));
	    redirect("?vid=$vid&menu=notes");
	    return FALSE;
	}
	
	if (1 < count($note_ids))
	{
	    process_user_warning(_("You may only edit one at a time."));
	}
	$note_id = $note_ids[0];    
	$sql = "SELECT * FROM notes WHERE note_id = $note_id";
	$result = $db->SelectLimit($sql, 1);
	if (!$result)
	{
	    die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	}
	$row = $result->fields;
	$message = $row['message'];
	$reminder_date = $row['reminder_date'];
	if ("0000-00-00" == $reminder_date)
	{
	    $reminder_date = "";
	}
	$uid_assigned = $row['uid_assigned'];
	$quality = $row['quality'];
    }
    else
    {
	$title = _("Add note");
	$message = $reminder_date = "";
	$quality = $uid_assigned = 0;
    }
    
    
    echo ("<H3>$title</H3>\n");
    $form = new formMaker();
    $form->open($title, 'post', '.', FS_TABLE);
    $form->addHiddenField('vid', $vid);    
    if ('edit' == $mode)
    {
	$form->addHiddenField('note_id', $note_id);
    }
    $form->addField(_("Message"), 'textarea', 'message', array('rows' => 6, 'cols' => 64), $message);
    $attr = array();
    $attr[] = array('value' => -1, 'label' => _("Negative"));
    $attr[] = array('value' => 0, 'label' => _("Neutral"));    
    $attr[] = array('value' => 1, 'label' => _("Positive"));
    $form->addField(_("Quality"), 'select', 'quality', $attr, $quality);
    $form->addField(_("Reminder date"), 'date', 'reminder_date', array(), $reminder_date);

    $sql = 'SELECT user_id, personalname, username FROM users';

    $result = $db->CacheExecute($db_cache_timeout, $sql);

    if (!$result)
    {
	process_system_error(_("Unable to get list of users from database."));
    }
    else
    {
	$attr = array();
	$attr[] = array('value' => '', 'label' => _("Nobody"));
	while (!$result->EOF)
	{
	    $user = $result->fields;	
	    $attr[] = array('value' => $user['user_id'], 'label' => $user['personalname']. " (".$user['username'].")");
	    $result->MoveNext();
	}
	$form->addField(_("Assigned to"), 'select', 'uid_assigned', $attr, $uid_assigned);
    }

    if ('add' == $mode)
    {
	$form->addButton('button_add_note', _("Add"));    
    }
    else
    {
	$form->addButton('button_save_note', _("Save"));
    }

    $form->close();
    
}


function note_addedit()
{
    global $db;


    // validate form input
    $errors_found = 0;
    
    $vid = intval($_POST['vid']);
    $edit_mode = array_key_exists('note_id', $_POST);

    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }
    
    $reminder_date = sanitize_date($_POST['reminder_date']);

    if (!preg_match("/^[0-9]+$/", $_POST['vid']) or (!empty($_POST['uid_assigned']) and !preg_match("/^[0-9]+$/", $_POST['uid_assigned'])))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Bad form input:".' POST: vid or uid_assigned'), __FILE__, __LINE__);    
    }

    if (!$reminder_date and !empty($_POST['reminder_date']))
    {
	save_message(MSG_USER_ERROR, _("Use the date format YYYY-MM-DD or MM/DD/YYYY."));
	$errors_found++;
    }
        
    if (!array_key_exists('message', $_POST) or strlen($_POST['message']) < 2)
    {
	save_message(MSG_USER_ERROR, _("Too short:").' '._("Message"));
	$errors_found++;
    }        
    
    if ($edit_mode)
    {
	$note_id = intval($_POST['note_id']);
    }

    if (!$errors_found)
    {
	$message = $db->qstr(sos_strip_tags($_POST['message']), get_magic_quotes_gpc());

	$uid_assigned = intval($_POST['uid_assigned']);        
	
	$quality = intval($_POST['quality']);

	if (empty($uid_assigned))
	{
	    $uid_assigned = 'NULL';
	}

//	$nowdate = date("Y-m-d H:i:s");
	$nowdate = 'now()';
	
	if ($edit_mode)
	{
	    $sql = "UPDATE notes SET ".
		    "message = $message, ".
		    "volunteer_id = $vid, ".
		    "quality = $quality, ".
		    "reminder_date = '$reminder_date', ".
		    "acknowledged = 0, " .
		    "dt_modified = now(), " .
		    "uid_modified = " . get_user_id() .  " " .
		    "WHERE note_id = $note_id";
	}
	else
	{
		$sql = "INSERT INTO notes (message, dt, volunteer_id, quality, uid_added, uid_assigned, reminder_date, acknowledged, dt_modified) ".
		"VALUES ($message, $nowdate, $vid, $quality,".get_user_id().", $uid_assigned, '$reminder_date', 0, now())";
	}

	$result = $db->Execute($sql);

	if ($result)
	{
	    save_message(MSG_USER_NOTICE, _("Recorded."));
	}
	else
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
	}

    }
    
    redirect("?vid=$vid&menu=notes");
} /* note_add() */


function note_delete()
// delete one or more notes
{
    global $db;
    
    
    $vid = intval($_POST['vid']);
    
    $errors_found = 0;
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
    
    $note_ids = array();

    foreach ($_POST as $k => $v)
    {
	if (preg_match('/^note_id_(\d+)/', $k, $matches))
	{
	    $note_ids[intval($matches[1])] = intval($matches[1]);
	}
    }    
    
    if (0 == count($note_ids))
    {
	save_message(MSG_USER_ERROR, _("Select one or more options."));
    }
    else if (0 == $errors_found)
    {    
	$sql = "DELETE FROM notes WHERE volunteer_id = $vid AND (";
	foreach ($note_ids as $nid)
	{
	    if ($c > 0)
	    {
		$sql .= ' OR ';
	    }
	    $sql .= ' note_id = '.$nid;
	    $c++;
	}
	$sql .= ')';
	
	$result = $db->Execute($sql);
	
	if (!$result)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql);	
	}
    }
        
    // todo: relative path violates HTTP standards?
    redirect("?vid=$vid&menu=notes");
}


?>
