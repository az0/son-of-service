<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: notes.php,v 1.4 2003/11/07 17:08:25 andrewziem Exp $
 *
 */

if (preg_match('/notes.php/i', $_SERVER['PHP_SELF']))
{
die('Do not access this page directly.');
}

function volunteer_view_notes($brief = FALSE)
{
global $db;


$vid = intval($_REQUEST['vid']);


$sql = 'SELECT notes.*,u1.username as added_by, u2.username as assigned_to '.
	'FROM notes '.
	'LEFT JOIN users as u1 ON notes.uid_added = u1.user_id '.
	'LEFT JOIN users as u2 ON notes.uid_assigned = u2.user_id '.
	"WHERE notes.volunteer_id = 1 ORDER BY dt DESC";

$result = $db->query($sql);

if (!$result)
{
	process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
	die();
}

if (!$brief or 0 < $db->num_rows($result))
echo ("<H3>"._("Notes")."</H3>\n");


if (0 == $db->num_rows($result))
{
	if (!$brief)
	process_user_notice(_("No notes for this volunteer."));
}
else
{ // display work history
	if (!$brief)
	{
		echo ("<FORM method=\"post\" action=\".\">\n");
		echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	}
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	if (!$brief)
	echo ("<TH>"._("Select")."</TH>\n");
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
	echo ("<TD><INPUT type=\"radio\" name=\"work_id\" value=\"".$note['note_id']."\"></TD>\n");
	echo ("<TD>".$note['dt']."</TD>\n");
	echo ("<TD align=\"right\">".($note['reminder_date'] == '0000-00-00' ? '&nbsp' : $note['reminder_date'])."</TD>\n");
	echo ("<TD align=\"right\">".$note['added_by']."</TD>\n");
	echo ("<TD align=\"right\">".$note['assigned_to']."</TD>\n");
echo ("<TD align=\"right\">".$note['quality']."</TD>\n");
	echo ("</TR>\n");
	echo ("<TR>\n");
	echo ("<TD colspan=\"6\">".$note['message']."</TD>\n");
	echo ("</TR>\n");
	}
	echo ("</TABLE>\n");
	if (!$brief)
	{
		echo ("<INPUT type=\"submit\" name=\"delete_note\" value=\""._("Delete")."\">\n");
		echo ("</FORM>\n");
	}
}

} /* volunteer_view_notes() */


function volunteer_add_note_form()
{
global $db,  $vid;


echo ("<H4>Add note</H4>\n");

echo ("<FORM method=\"post\" action=\".\">\n");
echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");

// to do: add reminders, assignments to notes
// to do: make searchable

?>
<table border="1" width="40%">
<tr>
 <TH class="vert">Message</TH>
 <td>
 <TEXTAREA name="message" rows="6" cols="64"></TEXTAREA>
 </td>
 </tr>
<TR>
 <TH class="vert">Quality</TH>
 <TD>
  <SELECT name="quality">
  <OPTION value="-1">Negative</OPTION>
  <OPTION value="0" SELECTED>Neutral</OPTION>
  <OPTION value="1">Positive</OPTION>
  </SELECT>
 </TR>
<TR>
 <TH class="vert">Reminder date</TH>
 <TD><INPUT type="text" name="reminder_date"></TD>
 </TR>
<TR>
 <TH class="vert">Assigned to</TH>
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
	echo ("<OPTION value=\"\">Nobody</OPTION>\n");
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

    $reminder_date = sanitize_date($_POST['reminder_date']);

//
    if (!preg_match("/^[0-9]+$/", $_POST['vid']) or (!empty($_POST['uid_assigned']) and !preg_match("/^[0-9]+$/", $_POST['uid_assigned'])))
    {
	$errors_found++;
	process_system_error("Bad POST form input");    
    }

    if (!$reminder_date and !empty($_POST['reminder_date']))
    {
	process_user_error(_("Use the date format YYYY-MM-DD or MM/DD/YYYY."));
	$errors_found++;
    }
        
    if (!array_key_exists('message', $_POST))
    {
       process_system_error("Please return to the form and add text to the note field.");
       $errors_found++;
    }        

    if ($errors_found)
    {
	echo ("Please <A href=\"javascript:history.back(1)\">return to the form</A> and supply a longer username.</P>\n");
	// to do: redisplay form
	volunteer_view_notes();
	die();

    }
    
    //to do: more validation
    
    $message = $db->escape_string(sos_strip_tags($_POST['message']));
    $vid = intval($_POST['vid']);    
    $uid_assigned = intval($_POST['uid_assigned']);        
    if (empty($uid_assigned))
	$uid_assigned = 'NULL';

    $nowdate = date("Y-m-d H:i:s");
 
    $sql = "INSERT INTO notes (message, dt, volunteer_id, quality, uid_added, uid_assigned, reminder_date) VALUES ('$message', '$nowdate', $vid, ".intval($_POST['quality']).",".intval($_SESSION['user_id']).", $uid_assigned, '$reminder_date')";

    //echo ("$sql\n");

    $result = $db->query($sql);

    if ($result)
    {
            echo ("<P>Note recorded.<P>\n");
     }
     else
     {
            process_system_error(_("Error adding data to database."), array('debug' => $db->get_error()));
     }

	volunteer_view_notes();
	volunteer_add_note_form();

} /* note_add() */


?>
