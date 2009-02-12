<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: workhistory.php,v 1.28 2009/02/12 04:11:20 andrewziem Exp $
 *
 */
 
if (preg_match('/workhistory.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

require_once(SOS_PATH . 'functions/stat.php');
require_once(SOS_PATH . 'functions/table.php');

function volunteer_work_history_delete()
{
    global $db;
    
    
    $vid = intval($_POST['vid']);
    $errors_found = 0;

    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
    
    if (!is_numeric($_POST['vid']) or 0 == $vid)
    {
	save_message(MSG_SYSTEM_ERROR, _("Bad form input:").' vid', __FILE__, __LINE__);
	$errors_found++;
    }
        
    $work_ids = find_values_in_request($_POST, 'work_id');
        
    if (0 == count($work_ids))    
    {
	save_message(MSG_USER_ERROR, _("You must make a selection."));    
	$errors_found++;
    }
    
    if ($errors_found)
    {
	redirect("?vid=$vid&menu=workhistory");
    }
    
    foreach ($work_ids as $work_id)
    {
	// todo: could be faster with SQL binding
	$sql = "DELETE FROM work WHERE work_id = $work_id AND volunteer_id = $vid";
    
	$result = $db->Execute($sql);

	if (!$result)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql);
	    $errors_found++;
	}
    }
    
    if (!$errors_found)
    {
	save_message(MSG_USER_NOTICE, _("Deleted."));
	stats_update_volunteer($db, $vid);
    }
    
    // redirect user to non-POST page
    redirect("?vid=$vid&menu=workhistory");

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
    $errors_found = 0;

    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."));
    }    
    
    if ('update' == $mode)
    {
	$work_id = intval($_POST['work_id']);
    }
    elseif ('add' == $mode)
    {
    
    }
    else
    {
	save_message(MSG_SYSTEM_ERROR, 'volunteers_work_history_save(): '._("Unexpected parameter."));
	$errors_found++;
    } 
    
    if (0 == strlen(trim($_POST['hours'])))
    {
       save_message(MSG_USER_ERROR, _("Add a number to the Hours Credit of Work field."));
       $errors_found++;
    }
    
    if (preg_match('/(\d{1,2}):(\d{1,2})/',$_POST['hours'], $matches))
    {
	$hours = $matches[1] + ($matches[2]/60);
    }
    else if ($_POST['hours'] < 0.00 or !preg_match("/^[0-9\.]+$/", $_POST['hours']))
    {
       save_message(MSG_USER_ERROR, _("Please add more hours to the Hours Credit of Work field."));
       $errors_found++;       
    }
    else
    {
	$hours = (float) $_POST['hours'];
    }

    if (!$date)
    {
	save_message(MSG_USER_ERROR, _("You must give a date."));
	save_message(MSG_USER_ERROR, _("Use the date format YYYY-MM-DD or MM/DD/YYYY."));
	$errors_found++;       
    }
        
    $memo = $db->qstr(sos_strip_tags($_POST['memo']), get_magic_quotes_gpc());

    // add to database
    if ('add' == $mode)
    {
	$sql = "INSERT INTO work ".
	    "(date, hours, volunteer_id, category_id, uid_added, dt_added, dt_modified, uid_modified, memo, quality) ".
	    "VALUES ('$date', '$hours', $vid, $category_id, ".get_user_id().", now(), now(), uid_added, $memo, $quality)"; 
    }
    else
    {
	$sql = "UPDATE work ".
	"SET date = '$date', " .
	"hours = '$hours', " .
	"category_id = $category_id, " .
	"memo = $memo, " .
	"quality = '$quality', " .
	"uid_modified = ".get_user_id().", " .
	"dt_modified = now() " .
	"WHERE work_id = $work_id AND volunteer_id = $vid LIMIT 1";
    }
    
    if (!$errors_found)
	// todo: show form again, already filled out    
    {
    
	$result = $db->Execute($sql);

	if ($result)
	{
	    save_message(MSG_USER_NOTICE, _("Saved."));
	    stats_update_volunteer($db, $vid);
        }
        else
        {
            save_message(MSG_SYSTEM_ERROR, _("Error saving data to database."), __FILE__, __LINE__, $sql);
        }
     }
     
     // redirect client to non-POST page
     
     redirect("?vid=$vid&menu=workhistory");
     
  } /* volunteer_work_history_save() */


function volunteer_view_work_history($brief = FALSE)
{
    global $db;

    
    display_messages();
    
    $vid = intval($_GET['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_READ, $vid, NULL))
    {
	process_system_error(_("Insufficient permissions."), MSG_SYSTEM_ERROR);
	return FALSE;
    }    
    
    $column_names = array('hours', 'quality', 'date', 'memo', 'category');
    $orderby = make_orderby($_GET, $column_names, 'date', 'DESC');
        
    $sql = "SELECT work.work_id AS work_id, work.hours AS hours, work.quality AS quality, work.date AS date, work.memo AS memo, strings.s AS category ".
	"FROM work ".
	"LEFT JOIN strings ON work.category_id = strings.string_id ".
	"WHERE volunteer_id = $vid ".
	$orderby;
	
    $result = $db->Execute($sql);

    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }

    if (!$brief or 0 < $result->RecordCount())
    {
	echo ("<H3>"._("Work history")."</H3>\n");
    }

    if (0 == $result->RecordCount())
    {
	if (!$brief)
	{
	    process_user_notice(_("None found."));
	}
    }
    else
    { 
	// display work history
	
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
	$headers['work_id'] = array('checkbox' => TRUE, 'label' => _("Select"));
	$headers['date'] =  array('label' => _("Date"), 'type' => TT_DATE, 'sortable' => TRUE);	
	$headers['hours'] = array('label' => _("Hours"), 'type' => TT_NUMBER, 'sortable' => TRUE);
	$headers['category'] = array('label' => _("Category"), 'sortable' => TRUE);		
	$headers['quality'] = array('label' => _("Quality"), 'sortable' => TRUE);			
	$headers['memo'] = array('label' => _("Memo"), 'sortable' => TRUE);	
	$dtp->setHeaders($headers);
	$dtp->setDatabase($db, $result);
	$dtp->render();
	if (!$brief)
	{
	    echo ("<INPUT type=\"submit\" name=\"button_delete_work_history\" value=\""._("Delete")."\">\n");
	    echo ("<INPUT type=\"submit\" name=\"button_edit_work_history\" value=\""._("Edit")."\">\n");
	    echo ("</FORM>\n");
	}
    }
}     /* volunteer_view_work_history() */


function work_history_addedit($mode)
// creates a form
{
    global $db;
    

    assert('add' == $mode or 'edit' == $mode);

    $vid = intval($_REQUEST['vid']);

    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
//	process_system_error(_("Insufficient permissions."), MSG_SYSTEM_ERROR);
	return FALSE;
    }    
    
    if ('edit' == $mode)
    {
	$work_ids = find_values_in_request($_POST, 'work_id');
	if (0 == count($work_ids))
	{
	    save_message(MSG_USER_ERROR, _("You must make a selection."));
	    redirect("?vid=$vid&menu=workhistory");
	    return FALSE;
	}
	
	if (1 < count($work_ids))
	{
	    process_user_warning(_("You may only edit one at a time."));
	}
	$work_id = $work_ids[0];    
	$sql = "SELECT * FROM work WHERE work_id = $work_id";
	$result = $db->SelectLimit($sql, 1);
	if (!$result)
	{
	    die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	}
	$work = $result->fields;
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
<?php // todo: allow subtraction and addition 
 ?>
 <TH class="vert"><?php echo _("Hours"); ?></TH>
 <td> <INPUT TYPE="text" NAME="hours" SIZE="7" value="<?php echo $hours;?>"></td>
 </tr>
<TR>
 <TH class="vert"><?php echo _("Category"); ?></TH>
 <TD> 
 <?php
 
 // get a list of categories
 
 $sql = "SELECT string_id, s FROM strings WHERE type = 'work' ORDER BY s";
 
 $result = $db->Execute($sql);
 
 if (!$result)
 {
    process_system_error(_("Error querying database."));    
 }
 else if (0 == $result->RecordCount())
 {
    process_user_error(_("None found."));
 }
 else
 {
    echo ("<SELECT name=\"category_id\">\n");
    while (!$result->EOF)
    {
	$row = $result->fields;
	echo ("<OPTION value=\"".$row['string_id']."\">".$row['s']."</OPTION>\n");
	$result->MoveNext();
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
 <td><TEXTAREA name="memo" cols="45" rows="2"><?php echo htmlentities($memo);?></TEXTAREA></td>
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
}

echo ("</FORM>\n");
}

?>
