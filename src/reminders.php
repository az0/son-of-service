<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 * Mangages a user's reminders (special kind of notes).
 *
 * $Id: reminders.php,v 1.12 2009/02/12 04:11:20 andrewziem Exp $
 *
 */
 
// todo: reminders not attached to volunteers

session_start();
ob_start();

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/db.php');
require_once(SOS_PATH . 'functions/html.php');

$db = connect_db();

if ($db->_connectionID == '')
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
}

is_logged_in();


function show_reminders()
{
    global $db;
    
    // todo: method for getting volunteer name is slow because
    // volunteer_get may be called many times
    
    $sql = "SELECT note_id, dt, reminder_date, volunteer_id, message ".
	"FROM notes ".
	"WHERE uid_assigned = ".get_user_id()." AND acknowledged = 0 ".
	"ORDER BY reminder_date DESC";
    
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    elseif (0 == $result->RecordCount())
    {
	process_user_notice(_("No reminders."));
    }
    else
    {
	display_messages();
    
	require_once(SOS_PATH . 'functions/formmaker.php');    
	require_once(SOS_PATH . 'functions/table.php');
	
	// todo: pagination
	
	$form = new formMaker();
	$form->open(_("Reminders"), 'post', 'reminders.php', FS_PLAIN);

	$table = new DataTableDisplay();

	// todo: display message on second row	
	$fieldnames['note_id'] = array('label' => _("Select"), 'checkbox' => TRUE);
	$fieldnames['reminder_date']['label'] = _("Reminder date");		
	$fieldnames['reminder_date']['type'] = TT_DATE;
	$fieldnames['dt']['label'] = _("Creation date");	
	$fieldnames['dt']['type'] = TT_DATETIME;
	$fieldnames['volunteer']['link'] = SOS_PATH . "volunteer/?vid=#volunteer_id#";
	$fieldnames[] = array('break_row' => TRUE);
	$fieldnames['message'] = array('label' => _("Message"), 'colspan' =>  is_printable() ? 3 : 4, 'nl2br' => TRUE);
	
	$table->setHeaders($fieldnames);
	$table->begin();
	while (!$result->EOF)
	{
	    $row = $result->fields;
	    $v = volunteer_get($row['volunteer_id'], $errstr);
	    if ($v)
	    {
		$row['volunteer'] = make_volunteer_name($v);
	    }
	    else
	    {
		$row['volunteer'] = $errstr;
	    }
	    $table->addRow($row);
	    $result->MoveNext();
	}
	$table->end();
	
	// todo: edit via volunteers/notes?
	
	$form->addButton('button_acknowledge_reminder', _("Acknowledge"));
	$form->close();
    }
}


if (array_key_exists('button_acknowledge_reminder', $_POST))
{
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
	save_message(MSG_USER_ERROR, _("Select one or more options."), __FILE__, __LINE__, NULL);
    }
    else
    {
	$c = 0;
	
	$sql = 'UPDATE notes SET acknowledged = 1 WHERE uid_assigned = '.get_user_id().' AND (';
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
	    save_message(MSG_SYSTEM_ERROR, _("Error updating data in database."), __FILE__, __LINE__, $sql);	
	}
    }
        
    // todo: relative path violates HTTP standards?
    redirect('reminders.php');

}
else
{	
    make_html_begin(_("Reminders"), array());

    make_nav_begin();

    show_reminders();
    
    make_html_end();
}


?>
