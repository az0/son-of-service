<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: availability.php,v 1.2 2003/11/07 16:59:19 andrewziem Exp $
 *
 */
 
if (preg_match('/availability.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_delete_availability()
{
    global $db;
    
    $vid = intval($_REQUEST['vid']);
    $availability_id  = intval($_POST['availability_id']);
    
    $result = $db->query("DELETE FROM availability WHERE availability_id = $availability_id AND volunteer_id = ".intval($vid));

    if (!$result)
    {
	process_system_error("Error querying database.", array('debug'=> $db->get_error()));
    }
    else
    {
	process_user_notice(_("Deleted."));
    }
    volunteer_view_availability();

}

 
  function volunteer_availability_add()
  {
      global $db;
      
      
      $vid = intval($_POST['vid']);
      $day_of_week = intval($_POST['day_of_week']);
      $start_time = $db->escape_string($_POST['start_time']); // should just be char      
      $end_time = $db->escape_string($_POST['end_time']); // should just be char
  
      // always validate form input first
      if (!(preg_match("/^[0-9]+$/", $day_of_week) and preg_match("/^[0-9]+$/",$day_of_week)))
      {
        process_system_error("Bad form input for day_of_week");
      }
      else
      {
      
        $sql = "INSERT INTO availability ".
	    "(volunteer_id, day_of_week, start_time, end_time, dt_added, uid_added, dt_modified,uid_modified) ".
	    "VALUES ($vid, $day_of_week, '$start_time', '$end_time', now(), ".$_SESSION['user_id'].", dt_added, uid_added)";
      
        $result = $db->query($sql);	
    
        if (!$result)
        {
    	    process_system_error(_("Error adding data to database."), array('debug' => $db->error()));
        }      
    }
    
    
  } /* volunteer_availability_add() */



 function volunteer_view_availability()
 {
    global $db;
    global $user;
    global $daysofweek;
    

    $vid = intval($_REQUEST['vid']);
    
    echo ("<H3>Availability</H3>\n");

    $result = $db->query("SELECT * FROM availability WHERE volunteer_id = $vid ORDER BY day_of_week");
    
    if (!$result)
    {
	process_system_error(_("Error querying database."). array('debug' => $db->geterror()));
    }
    
    
    echo ("<FORM method=\"post\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    
    if (0 == $db->num_rows($result))
    {
	process_user_notice(_("None found."));
    }
    else
    {
?>


<TABLE border="1">
<TR>
 <TH><?php echo _("Select");?></TH>
 <TH><?php echo _("Day of week");?></TH>
 <TH><?php echo _("Start");?></TH>
 <TH><?php echo _("End");?></TH>
</TR>
<?php

    while (FALSE != ($availability = ($db->fetch_array($result))))
    {
	echo ("<TR>\n");
	echo ("<TD><INPUT type=\"radio\" name=\"availability_id\" value=\"".$availability['availability_id']."\"></TD>\n");
	echo ("<TD>".(0< $availability['day_of_week'] ? $daysofweek[$availability['day_of_week']]:"bad value")."</TD>\n");
	echo ("<TD>".$availability['start_time']."</TD>\n");	
	echo ("<TD>".$availability['end_time']."</TD>\n");		
	echo ("</TR>\n");	
    }

echo ("</TABLE>\n");
echo ("<INPUT type=\"submit\" name=\"button_delete_availability\" value=\""._("Delete")."\">\n");
}


echo ("<H4>Add new availability</H4>\n");
echo ("<SELECT name=\"day_of_week\">\n");
for ($i = 1; $i <= 7; $i++)
{
    echo ("<OPTION value=\"$i\">".$daysofweek[$i]."</OPTION>\n");
}
echo ("</SELECT>\n");
echo (" From ");
echo ("<SELECT name=\"start_time\">\n");
echo ("<OPTION>"._("Morning")."</OPTION>\n");
echo ("<OPTION>"._("Afternoon")."</OPTION>\n");
echo ("<OPTION>"._("Evening")."</OPTION>\n");
echo ("<OPTION>"._("Night")."</OPTION>\n");
echo ("</SELECT>\n");
echo (" To ");

echo ("<SELECT name=\"end_time\">\n");
echo ("<OPTION>"._("Morning")."</OPTION>\n");
echo ("<OPTION>"._("Afternoon")."</OPTION>\n");
echo ("<OPTION>"._("Evening")."</OPTION>\n");
echo ("<OPTION>"._("Night")."</OPTION>\n");
echo ("</SELECT>\n");

echo ("<INPUT type=\"submit\" name=\"availability_add\" value=\""._("Add")."\">\n");

echo ("</FORM>\n");

} /* volunteer_view_availability() */


?>