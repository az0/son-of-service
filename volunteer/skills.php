<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: skills.php,v 1.4 2003/11/12 16:12:24 andrewziem Exp $
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
    
    $result = $db->query("DELETE FROM volunteer_skills WHERE volunteer_skill_id = $volunteer_skill_id AND volunteer_id = ".intval($vid)." LIMIT 1");

    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug'=> $db->get_error()));
    }
    else
    {
	process_user_notice(_("Deleted."));
    }
    volunteer_view_skills();

}

function volunteer_view_skills()
{
    global $db, $skill_levels;

    $vid = intval($_REQUEST['vid']);
    
    //print_r($skill_levels);

echo ("<H3>"._("Skills, interests")."</H3>\n");

echo ("<FORM action=\".\" method=\"post\">\n");
echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"skills\">\n");


$vskills_result = $db->query("SELECT volunteer_skills.string_id, volunteer_skills.volunteer_skill_id, strings.s, volunteer_skills.skill_level FROM volunteer_skills LEFT JOIN strings on strings.string_id = volunteer_skills.string_id WHERE volunteer_id = ".intval($vid)." AND strings.type = 'skill'");

if (!$vskills_result)
{
    process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
}
else
if (0 ==  $db->num_rows($vskills_result))
{
    process_user_notice(_("None found."));
}
else
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
	echo ("<TD><INPUT type=\"radio\" name=\"volunteer_skill_id\" value=\"".$vskill['volunteer_skill_id']."\"></TD>\n");
	echo ("<TD>".$vskill['s']."</TD>\n");
	echo ("<TD>".$skill_levels[$vskill['skill_level']]."</TD>\n");	
	echo ("</TR>\n");
    }
    echo ("</TABLE>\n");
    
    echo ("<INPUT type=\"submit\" name=\"button_delete_volunteer_skill\" value=\""._("Delete")."\">\n");
}    

// add skill form
$skills_list_result = $db->query("SELECT * FROM strings WHERE type = 'skill'");

if (!$skills_list_result)
{
    process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
}
elseif (1 > $db->num_rows($skills_list_result))
{
    process_user_error(_("None found."));
}
else
{
    echo ("<H4>"._("Add new skill")."</H4>\n");

    echo ("<SELECT name=\"string_id\">\n");
    while ($skill = ($db->fetch_array($skills_list_result)))
    {
	echo ("<OPTION value=\"".$skill['string_id']."\">".$skill['s']."</OPTION>\n");
    }
    echo ("</SELECT>\n");
    echo ("<SELECT name=\"skill_level\">\n");
    for ($i = 2 ; $i <= 5; $i++)
    {
	echo ("<OPTION value=\"$i\">".$skill_levels[$i]."</OPTION>\n");
    }
    echo ("</SELECT>\n");
    echo ("<INPUT type=\"submit\" name=\"add_skill\" value=\""._("Add")."\">\n");
}

echo ("</FORM>\n");

} /* volunteer_view_skills() */


function volunteer_skill_add()
{
    global $db;
      
      
    $vid = intval($_POST['vid']);
    $string_id = intval($_POST['string_id']);
    $skill_level = intval($_POST['skill_level']);
      
    // always validate form input first
    if (!(preg_match("/^[0-9]+$/", $_POST['string_id']) and preg_match("/^[0-9]+$/",$_POST['skill_level'])))
    {
	process_system_error(_("Bad form input:").' string_id, skill_level');
	die();
    }
    else
    {  
        $result = $db->query("INSERT INTO volunteer_skills (volunteer_id, string_id, skill_level) VALUES ($vid, $string_id, $skill_level)");	
    
        if (!$result)
        {
    	    process_system_error(_("Error adding data to database."));
        }      
    }
    
    volunteer_view_skills();
    
} /* volunteer_skill_add() */


?>