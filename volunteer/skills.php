<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: skills.php,v 1.1 2003/10/05 16:14:46 andrewziem Exp $
 *
 */

if (preg_match('/skills.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

global $skill_levels;
$skill_levels = array (1=> _("None"), _("Amatuer"), _("Some"), ("Professional"), _("Expert"));


function volunteer_delete_skill()
{
    global $db;
    
    $vid = intval($_REQUEST['vid']);
    $volunteer_skill_id  = intval($_REQUEST['volunteer_skill_id']);
    
    $result = $db->query("DELETE FROM volunteer_skills WHERE volunteer_skill_id = $volunteer_skill_id AND volunteer_id = ".intval($vid));

    if (!$result)
    {
	process_system_error("Error querying database.", array('debug'=>mysql_error()));
    }
    else
    {
	process_user_notice(_("Skill deleted"));
    }
    volunteer_view_skills();

}

function volunteer_view_skills()
{
    global $db, $skill_levels;

    $vid = intval($_REQUEST['vid']);
    
    //print_r($skill_levels);

echo ("<H3>Skills, interests</H3>\n");

echo ("<FORM action=\".\" method=\"post\">\n");
echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"skills\">\n");


$vskills_result = $db->query("SELECT volunteer_skills.skill_id, volunteer_skills.volunteer_skill_id, skills.name, volunteer_skills.skill_level FROM volunteer_skills LEFT JOIN skills on skills.skill_id = volunteer_skills.skill_id WHERE volunteer_id = ".intval($vid));

if (!$vskills_result)
{
    process_system_error("Error querying database.", array('debug'=>mysql_error()));
}
else
if (0 ==  $db->num_rows($vskills_result))
{
    process_user_notice("No skills registered with this volunteer.");
}
else
if ($vskills_result and 0 < $db->num_rows($vskills_result))
{
    echo ("<TABLE border=\"1\">\n");
    echo ("<TR>\n");
    echo ("<TH>"._("Select")."</TH>\n");
    echo ("<TH>"._("Skill")."</TH>\n");
    echo ("<TH>"._("Level")."</TH>\n");
    echo ("</TR>\n");
    
    while (FALSE != ($vskill = $db->fetch_array($vskills_result)))
    {
	echo ("<TR>\n");
//	print_r($vskill);
	echo ("<TD><INPUT type=\"radio\" name=\"volunteer_skill_id\" value=\"".$vskill['volunteer_skill_id']."\"></TD>\n");
	echo ("<TD>".$vskill['name']."</TD>\n");
	echo ("<TD>".$skill_levels[$vskill['skill_level']]."</TD>\n");	
	echo ("</TR>\n");
    }
    echo ("</TABLE>\n");
    
    echo ("<INPUT type=\"submit\" name=\"button_delete_volunteer_skill\" value=\"Delete\">\n");
}    
else
{
    echo mysql_error();
}

// add skill form
$skills_list_result = $db->query("SELECT * FROM skills");

if ($skills_list_result and 0 < $db->num_rows($skills_list_result))
{
    
    echo ("<H4>Add new skill</H4>\n");

    //echo ("<TD colspan=\"2\">\n");
    echo ("<SELECT name=\"skill_id\">\n");
    while ($skill = ($db->fetch_array($skills_list_result)))
    {
	echo ("<OPTION value=\"".$skill['skill_id']."\">".$skill['name']."</OPTION>\n");
    }
    echo ("</SELECT>\n");
    //print_r($skill_levels);
    echo ("<SELECT name=\"skill_level\">\n");
    for ($i = 2 ; $i <= 5; $i++)
	echo ("<OPTION value=\"$i\">".$skill_levels[$i]."</OPTION>\n");
    echo ("</SELECT>\n");
    echo ("<INPUT type=\"submit\" name=\"add_skill\" value=\""._("Add")."\">\n");


}

echo ("</FORM>\n");

} /* volunteer_view_skills() */


  function volunteer_skill_add()
  {
      global $db;
      
      
      $vid = intval($_POST['vid']);
      $skill_id = intval($_POST['skill_id']);
      $skill_level = intval($_POST['skill_level']);
      
      // always validate form input first
      if (!(preg_match("/^[0-9]+$/", $_POST['skill_id']) and preg_match("/^[0-9]+$/",$_POST['skill_level'])))
      {
        process_system_error("Bad form input for skill");
	die();
      }
      else
      {
      
        $result = $db->query("INSERT INTO volunteer_skills (volunteer_id, skill_id, skill_level) VALUES ($vid, $skill_id, $skill_level)");	
    
        if (!$result)
        {
    	    process_system_error("Unable to add volunteer skill");
        }      
    }
    
    volunteer_view_skills();
    
  } /* volunteer_skill_add() */


?>