<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: notes.php,v 1.10 2003/11/29 22:06:38 andrewziem Exp $
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

    $sql = 'SELECT notes.*,u1.username as added_by, u2.username as assigned_to '.
	'FROM notes '.
	'LEFT JOIN users as u1 ON notes.uid_added = u1.user_id '.
	'LEFT JOIN users as u2 ON notes.uid_assigned = u2.user_id '.
	"WHERE notes.volunteer_id = $vid ORDER BY dt DESC";

    $result = $db->query($sql);

    if (!$result)
    {	
	process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
	die();
    }

    if (!$brief or 0 < $db->num_rows($result))
    {
	echo ("<H3>"._("Notes")."</H3>\n");
    }


    if (0 == $db->num_rows($result))
    {
	if (!$brief)
	process_user_notice(_("None found."));
    }
    else
    { 
	// display work history
	if (!$brief)
	{
		echo ("<FORM method=\"post\" action=\".\">\n");
		echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	}
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	if (!$brief)
	{
	    echo ("<TH>"._("Select")."</TH>\n");
	}
	echo ("<TH>"._("Time")."</TH>\n");
	echo ("<TH>"._("Reminder date")."</TH>\n");
	echo ("<TH>"._("Added by")."</TH>\n");
	echo ("<TH>"._("Assigned to")."</TH>\n");
	echo ("<TH>"._("Quality")."</TH>\n");
	echo ("</TR>\n");
	echo ("<TR>\n");
	echo ("<TH colspan=\"6\">"._("Memo")."</TH>\n");
	echo ("</TR>\n");

	while (FALSE != ($note = $db->fetch_array($result)))
	{
	echo ("<TR>\n");
	if (!$brief)
	{
	    echo ("<TD><INPUT type=\"checkbox\" name=\"note_id_".$note['note_id']."\" value=\"0\"></TD>\n");
	}
	echo ("<TD>".$note['dt']."</TD>\n");
	echo ("<TD align=\"right\">".($note['reminder_date'] == '0000-00-00' ? '&nbsp' : $note['reminder_date'])."</TD>\n");
	echo ("<TD align=\"right\">".$note['added_by']."</TD>\n");
	echo ("<TD align=\"right\">".nbsp_if_null($note['assigned_to'])."</TD>\n");
	echo ("<TD align=\"right\">".$note['quality']."</TD>\n");
	echo ("</TR>\n");
	echo ("<TR>\n");
	echo ("<TD colspan=\"6\">".$note['message']."</TD>\n");
	echo ("</TR>\n");
	}
	echo ("</TABLE>\n");
	if (!$brief)
	{
		// todo: edit
		echo ("<INPUT type=\"submit\" name=\"button_delete_note\" value=\""._("Delete")."\">\n");
		echo ("</FORM>\n");
	}
    }

} /* volunteer_view_notes() */


function volunteer_add_note_form()
{
    global $db;
    

    // todo: make searchable
    
    $vid = intval($_GET['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	return FALSE;
    }
    
    echo ("<H4>"._("Add note")."</H4>\n");

    echo ("<FORM method=\"post\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");


?>
<table border="1" width="40%">
<tr>
 <TH class="vert"><?php echo _("Message"); ?></TH>
 <td>
 <TEXTAREA name="message" rows="6" cols="64"></TEXTAREA>
 </td>
 </tr>
<TR>
 <TH class="vert"><?php echo _("Quality"); ?></TH>
 <TD>
  <SELECT name="quality">
  <OPTION value="-1"><?php echo _("Negative"); ?></OPTION>
  <OPTION value="0" SELECTED><?php echo _("Neutral"); ?></OPTION>
  <OPTION value="1"><?php echo _("Positive"); ?></OPTION>
  </SELECT>
 </TR>
<TR>
 <TH class="vert"><?php echo _("Reminder date"); ?></TH>
 <TD><INPUT type="text" name="reminder_date"></TD>
 </TR>
<TR>
 <TH class="vert"><?php echo _("Assigned to"); ?></TH>
 <TD>
<?php
    $result = $db->query('SELECT user_id, personalname, username FROM users');

    if (!$result)
    {
	process_system_error(_("Unable to get list of users from database."));
    }
    else
    {
	echo ("<SELECT name=\"uid_assigned\">\n");
	echo ("<OPTION value=\"\">"._("Nobody")."</OPTION>\n");
	while (FALSE != ($user = $db->fetch_array($result)))
	{
	    echo ('<OPTION value="'.$user['user_id'].'">'.$user['personalname'].' ('.$user['username'].")</OPTION>\n");
	}
	echo ("</SELECT>\n");

    }
?>
 </TD>
 </TR>

</table>
<input type="Submit" name="button_add_note" value="<?php echo _("Add"); ?>">
</form>
<?php
  }


function note_add()
{
    global $db;


    // validate form input
    $errors_found = 0;
    
    $vid = intval($_POST['vid']);    

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
    

    if (!$errors_found)
    {
	$message = $db->escape_string(sos_strip_tags($_POST['message']));

	$uid_assigned = intval($_POST['uid_assigned']);        
	
	$quality = intval($_POST['quality']);

	if (empty($uid_assigned))
	{
	    $uid_assigned = 'NULL';
	}

	$nowdate = date("Y-m-d H:i:s");
 
	$sql = "INSERT INTO notes (message, dt, volunteer_id, quality, uid_added, uid_assigned, reminder_date, acknowledged) VALUES ('$message', '$nowdate', $vid, $quality,".intval($_SESSION['user_id']).", $uid_assigned, '$reminder_date', 1)";

	$result = $db->query($sql);

	if ($result)
	{
	    save_message(MSG_USER_NOTICE, _("Recorded."));
	}
	else
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
	}

    }
    
    header("Location: ./?vid=$vid&menu=notes");
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
	
	$result = $db->query($sql);
	
	if (!$result)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql);	
	}
    }
        
    // todo: relative path violates HTTP standards?
    header("Location: ./?vid=$vid&menu=notes");

    
    
}


?>
