<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: workhistory.php,v 1.8 2003/11/22 05:16:14 andrewziem Exp $
 *
 */
 
if (preg_match('/workhistory.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

require_once(SOS_PATH . 'functions/stat.php');


function volunteer_work_history_delete()
{
    global $db;
    
    $vid = intval($_POST['vid']);
    $work_id  = intval($_POST['work_id']);
    
    if (!is_numeric($_POST['vid']) or 0 == ($vid))
    {
	process_system_error(_("Bad form input:").' vid');
	die();
    }
    
    $result = $db->query("DELETE FROM work WHERE work_id = $work_id AND volunteer_id = ".intval($vid));

    if (!$result)
    {
	process_system_error(_("Error deleting data from database."), array('debug'=> $db->get_error()));
    }
    else
    {
	process_user_notice(_("Deleted."));
    }

}


function volunteer_work_history_save($mode)
// mode: either 'update' or 'add';
// return: nothing
{
    global $db;

    // check form input        
    $category_id = intval($_POST['category_id']);
    $date = sanitize_date($_POST['date']);
    $quality = intval($_POST['quality']);
    $vid = intval($_POST['vid']);
    
    if ('update' == $mode)
    {
	$work_id = intval($_POST['work_id']);
    }
    elseif ('add' == $mode)
    {
    
    }
    else
    {
	process_system_error('volunteers_work_history_save(): '._("Unexpected parameter."));
	return FALSE;
    }
  
    $errors_found = 0;
    
    if (0 == strlen(trim($_POST['hours'])))
    {
       process_user_error("Please return to the form and add a number to the Hours Credit of Work field.");
       $errors_found++;
    }
    
    if (preg_match('/(\d{1,2}):(\d{1,2})/',$_POST['hours'], $matches))
    {
	$hours = $matches[1] + ($matches[2]/60);
    }
    else
    if ($_POST['hours'] < 0.00 or !preg_match("/^[0-9\.]+$/",$_POST['hours']))
    {
       process_user_error("Please add more hours to the Hours Credit of Work field.");
       $errors_found++;       
    }
    else
    {
	$hours = (float) $_POST['hours'];
    }

    if (!$date)
    {
	process_user_error(_("You must give a date."));
	process_user_error(_("Use the date format YYYY-MM-DD or MM/DD/YYYY."));
	$errors_found++;       
    }
    
    $memo = $db->escape_string(sos_strip_tags($_POST['memo']));

    // add to database
    if ('add' == $mode)
    {
	$sql = "INSERT INTO work ".
	    "(date, hours, volunteer_id, category_id, uid_added, dt_added, dt_modified, uid_modified, memo, quality) ".
	    "VALUES ('$date', '$hours', $vid, $category_id, ".intval($_SESSION['user_id']).", now(), uid_added, dt_modified, '$memo', $quality)"; 
    }
	else
    {
	$sql = "UPDATE work ".
	    "SET date = '$date', hours = '$hours', category_id = $category_id, memo = '$memo', quality = '$quality', uid_modified = ".intval($_SESSION['user_id']).", dt_modified = now() ".
	    "WHERE work_id = $work_id AND volunteer_id = $vid LIMIT 1";
    }
    
    if (!$errors_found)
	// to do: show form again, already filled out    
    {
    
	$result = $db->query($sql);

	if ($result)
	{
	    process_user_notice(_("Saved."));
        }
        else
        {
            process_system_error(_("Error saving data to database."), array('debug' => $db->get_error()));
        }
     }
  } /* volunteer_work_history_save() */



function volunteer_view_work_history($brief = FALSE)
{
    global $db;
    
    $vid = intval($_REQUEST['vid']);
    
    // to do: pagination

    $sql = "SELECT work.work_id AS work_id, work.hours AS hours, work.quality AS quality, work.date AS date, work.memo AS memo, strings.s AS category ".
	"FROM work ".
	"LEFT JOIN strings ON work.category_id = strings.string_id ".
	"WHERE volunteer_id = $vid ".
	"ORDER BY date DESC";
	
    $result = $db->query($sql);

    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug'=> $db->get_error().' '.$sql));
	die();
    }


    if (!$brief or 0 < $db->num_rows($result))
    {
	echo ("<H3>"._("Work history")."</H3>\n");
    }

    if (0 == $db->num_rows($result))
    {
	if (!$brief)
	{
	    process_user_notice(_("None found."));
	}
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
	{
		echo ("<TH>"._("Select")."</TH>\n");
	}
	echo ("<TH>"._("Date")."</TH>\n");
	echo ("<TH>"._("Hours")."</TH>\n");
	echo ("<TH>"._("Category")."</TH>\n");	
	echo ("<TH>"._("Quality")."</TH>\n");
	echo ("<TH>"._("Memo")."</TH>\n");
	echo ("</TR>\n");

	while (FALSE != ($work = $db->fetch_array($result)))
	{
	    if (empty($work['memo']))
	    {
	    	$work['memo'] = '&nbsp;';
	    }
	    echo ("<TR>\n");
	    if (!$brief)
	    {
	        echo ("<TD><INPUT type=\"radio\" name=\"work_id\" value=\"".$work['work_id']."\"></TD>\n");
	    }
	    echo ("<TD>".$work['date']."</TD>\n");
	    echo ("<TD align=\"right\">".$work['hours']."</TD>\n");
	    echo ("<TD align=\"right\">".$work['category']."</TD>\n");	    
	    echo ("<TD align=\"right\">".$work['quality']."</TD>\n");
	    echo ("<TD>".$work['memo']."</TD>\n");
	}
	echo ("</TABLE>\n");
	if (!$brief)
	{
	echo ("<INPUT type=\"submit\" name=\"button_delete_work_history\" value=\""._("Delete")."\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_edit_work_history\" value=\""._("Edit")."\">\n");
	echo ("</FORM>\n");
	}
    }


    //work_history_addedit('add');
}     /* volunteer_view_work_history() */



function work_history_addedit($mode)
{
    global $db;
    

    if (!('add' == $mode or 'edit' == $mode))
    {
	process_system_error('work_history_addedit(): '._("Unexpected parameter."));
	return FALSE;
    }    

    $vid = intval($_REQUEST['vid']);

    if ('edit' == $mode)
    {
	$work_id = intval($_POST['work_id']);    
	$result = $db->query("SELECT * FROM work WHERE work_id = $work_id LIMIT 1");
	if (!$result)
	{
	    process_system_error(_("Error querying database."));
	}
	$work = $db->fetch_array($result);
	//print_r($work);
	$date = $work['date'];
	$hours = $work['hours'];	
	$memo = $work['memo'];		
	$quality = $work['quality'];			
    }
    else
    {
	$hours = $memo = '';
	$quality = 0;
	$date = date('Y-m-d');
    }

    echo ("<H4>".('add' == $mode ? _("Add work history") : _("Edit work history"))."</H4>\n");
    echo ("<FORM method=\"post\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    if ('edit' == $mode)
    {

	echo ("<INPUT type=\"hidden\" name=\"work_id\" value=\"$work_id\">\n");
    }
    echo ("<TABLE border=\"1\" class=\"form\">\n");
?>
    <tr>
 <TH class="vert"><?php echo _("Date"); ?></TH>
 <td>
 <INPUT TYPE="text" NAME="date" SIZE="10" VALUE="<?php echo $date; ?>">
 </td>
 </tr>

<tr>
<?php // to do: allow subtraction and addition 
 ?>
 <TH class="vert"><?php echo _("Hours"); ?></TH>
 <td> <INPUT TYPE="text" NAME="hours" SIZE="7" value="<?php echo $hours;?>"></td>
 </tr>
<TR>
 <TH class="vert"><?php echo _("Category"); ?></TH>
 <TD> 
 <?php
 
 $sql = "SELECT string_id, s FROM strings WHERE type = 'work'";
 
 $result = $db->query($sql);
 
 if (!$result)
 {
    process_system_error(_("Error querying database."));    
 }
 else if (0 == $db->num_rows($result))
 {
    process_user_error(_("None found."));
 }
 else
 {
    echo ("<SELECT name=\"category_id\">\n");
    while (FALSE != ($row = $db->fetch_array($result)))
    {
	echo ("<OPTION value=\"".$row['string_id']."\">".$row['s']."</OPTION>\n");
    }
    echo ("</SELECT>\n");
 }
 ?>
  <TD>
<TR>
 <TH class="vert"><?php echo _("Quality"); ?></TH>
 <TD>
  <SELECT name="quality">
  <OPTION <?php echo display_position_option(-1, $quality);?>><?php echo _("Negative"); ?></OPTION>
  <OPTION <?php echo display_position_option(0, $quality);?>><?php echo _("Neutral"); ?></OPTION>
  <OPTION <?php echo display_position_option(1, $quality);?>><?php echo _("Positive"); ?></OPTION>
  </SELECT>
<tr>
 <TH class="vert"><?php echo ("Memo"); ?></TH>
 <td><TEXTAREA name="memo" cols="45" rows="2"><?php echo $memo;?></TEXTAREA></td>
 </tr>
 

</table>

<?php

if ('add' == $mode)
{
    echo ("<INPUT type=\"submit\" name=\"button_add_work_history\" value=\""._("Add")."\">\n");
}
else
{
    echo ("<INPUT type=\"submit\" name=\"button_update_work_history\" value=\""._("Update")."\">\n");
    echo ("</FORM>\n");
}

}

?>
