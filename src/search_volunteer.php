<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: search_volunteer.php,v 1.14 2003/11/28 16:25:48 andrewziem Exp $
 *
 */




// todo: add to found set (vs replace) ?
// todo: advanced searching (e.g., not, match exact)
// todo: search by availability
// todo: query manager for saving queries

function getmicrotime(){ 
// this function from PHP documentation
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
} 

$time_start = getmicrotime();

session_start();
ob_start();

define('SOS_PATH', '../');

require_once (SOS_PATH . 'include/global.php');
require_once (SOS_PATH . 'functions/html.php');
require_once (SOS_PATH . 'functions/table.php');

make_html_begin('Search for volunteers', array());

is_logged_in();

make_nav_begin();

class columnManager
{
    var $columns;


    function columnManager()
    {
	$this->columns = array();
    }
    
    function addColumn($name, $display = FALSE)
    {
	$this->columns[$name] = array('display' => FALSE);
	if ($display)
	{
	    $this->columns[$name] = array('display' => TRUE);
	}
    }
    
    function setRadio($name)
    {
	$this->columns[$name]['type'] = 'radio';
    }
    
    function setDisplay($column, $v= TRUE)
    {
//	echo ("$column dis = $v");
	$this->columns[$column]['display'] = $v;
    }
    
    function columnExists($name)
    {
//	echo "$name exists";
	return array_key_exists($name, $this->columns);
    }
    
    function getNames()
    {
	$names = array();
    
	foreach ($this->columns as $k => $v)
	{
	    $names[] = $k;
	}
	
	return $names;

    }
    
    function setColumnLink($column, $link= NULL)
    {
	if (NULL == $link)
	{
	    return array_key_exists('link', $this->columns[$column]) ? 
	    $this->column[$column]['link'] : FALSE;
	}
	else
	    $this->columns[$column]['link'] = $link;
    }
    
    function getSelect()
    {
	$select = "SELECT ";
	$i = 0;
    
	foreach ($this->columns as $k => $v)
	{
	    if ($v['display'])
	    {
		if ($i > 0)
		    $select .= ",";
		    
		$select .= "volunteers.$k";
		    
		$i++;
	    }
	}
	
	return $select;
    }

}

$cm = new ColumnManager();
$cm->addColumn('volunteer_id', TRUE);
$cm->setRadio('volunteer_id');
$cm->addColumn('first', TRUE);
$cm->setColumnLink('first', SOS_PATH . "volunteer/?vid=#volunteer_id#");
$cm->addColumn('middle', TRUE);
$cm->addColumn('last', TRUE);
$cm->addColumn('organization', TRUE);
$cm->setColumnLink('organization', SOS_PATH  . "volunteer/?vid=#volunteer_id#");
$cm->addColumn('street');
$cm->addColumn('city');
$cm->addColumn('state');
$cm->addColumn('postal_code');
$cm->addColumn('country');
$cm->addColumn('hours_life');

function search_add($form_name, $column, &$where)
{
    global $cm;
    global $db;
    

    if (array_key_exists($form_name, $_REQUEST) and trim(strlen($_REQUEST[$form_name])) > 0)
    {
	$where .= " AND $column LIKE '%".$db->escape_string($_REQUEST[$form_name])."%'";

	if ($cm->columnExists($column))		
	{
	    $cm->setDisplay($column, TRUE);		
	}
    }
}


function volunteer_search()
{
    global $db, $cm;
    
    $results_per_page = 25;

    $offset = 0;
    if (array_key_exists('offset', $_REQUEST))
    {
        $offset = intval($_REQUEST['offset']);
    }

    // is offset too small?    
    if ($offset < 0)
    {
	$offset = 0;
    }

    $skills_active = FALSE;
	
    foreach ($_REQUEST as $key => $p)
    {
        if (FALSE != preg_match('/^skill_(\d+)/', $key, $matches))
        {
    	    if ('n' != $_REQUEST[$key])
	    {
	        $skills_active = TRUE;
	    }
	}
    }
	
    if ($skills_active)
    {	
	$where  = " WHERE volunteers.volunteer_id > 0";
    }
    else
    {	
        $where  = " WHERE volunteers.volunteer_id > 0";
    }

	search_add('first', 'first', $where);		
	search_add('last', 'last', $where);		
	search_add('organization', 'organization', $where);		
	search_add('street', 'street', $where);			
	search_add('city', 'city', $where);					
	search_add('state', 'state', $where);		
	search_add('postal_code', 'postal_code', $where);				
	search_add('country', 'country', $where);					
	search_add('phone_home', 'phone_home', $where);		
	search_add('phone_work', 'phone_work', $where);		
	search_add('phone_cell', 'phone_cell', $where);		
	// todo: search any phone
	
	foreach ($_REQUEST as $key => $p)
	{
	    if (FALSE != preg_match('/^skill_(\d+)/', $key, $matches))
	    {
		if ('n' != $_REQUEST[$key])
		{
		    
		    $where .= " AND ( string_id = ".$matches[1]." and skill_level >= ".$_REQUEST[$key].") ";


		}		
	    }	    
	}	

	if ($skills_active)
	{	
    	    $groupby = ' GROUP BY volunteer_skills.volunteer_id ';
	    $from   = " FROM volunteer_skills RIGHT JOIN volunteers ON volunteer_skills.volunteer_id = volunteers.volunteer_id ";
	}
	else
	{	
	    $from   = ' FROM volunteers ';
    	    $groupby = ' ';
	}
	
	$orderby = "";
	if ($cm->columnExists($_REQUEST['sortby']))
	{ 
	    // is orderby valid?
	    $orderby = " ORDER BY ".$_REQUEST['sortby'].' ';
	    if ($cm->columnExists($_REQUEST['sortby']))		
	    {
		    $cm->setDisplay($_REQUEST['sortby'], TRUE);		
	    }
	}

	$total_results = -1;
		
	$sql = $cm->getSelect() . $from . $where . $groupby  . $orderby;
	
	echo ("<FORM method=\"post\" action=\"mass.php\">\n");	
	
        $result = $db->query($sql);

        if (!$result)
        { 
	    // search failed
	    process_system_error(_("Error querying database."), array('debug' => $db->get_error()." ".$sql));
        }
        else
        { 
		// search successful
		// todo: mass-action on found set (email)
		
                if (0 == ($total_results = $db->num_rows($result)))
                {
                     process_user_error(_("Found zero volunteers matching your description."));
                }
                else
		{
		
		    // is offset too large?
		    if ($offset > $total_results)
		    {
			$offset = $total_results - $results_per_page;
		    }

		    $tab = new DataTableDisplay();
		    
		    $fieldnames = $db->fieldnames($result);
		    
		    $fieldnames['first']['link'] = SOS_PATH . "volunteer/?vid=#volunteer_id#";		    
		    $fieldnames['organization']['link'] = SOS_PATH . "volunteer/?vid=#volunteer_id#";		    		    
		    $fieldnames['volunteer_id']['checkbox'] = TRUE;
			
		    if ($offset > 0)
		    {
			$db->data_seek($result, $offset);	    
		    }
		    
		    $max = $results_per_page;
		    if ($offset + $results_per_page > $total_results)
		    {
			$max = $offset + $results_per_page;
		    }
		    if ($max > $total_results)
		    {
			$max = $total_results - 1;
		    }

		    $tab->setHeaders($fieldnames);
		    $tab->begin();
		    
		    while (FALSE != ($row = $db->fetch_array($result)))
		    {
		        $tab->addRow($row);
		    }
		    
		    $db->free_result($result);

		    $tab->end();

               }

        }

        // todo: implement e-mail
	// todo: what other mass actions?

	echo ("<INPUT type=\"submit\" name=\"button_email_volunteers\" value=\""._("E-mail")."\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_delete_volunteers\" value=\""._("Delete")."\">\n");	
	echo ("</FORM>\n");
	
	// results navigation
	
	$page = 1 + ($offset / $results_per_page);
	$pages = ceil($total_results / $results_per_page);
	$last_this_page = ($offset + $results_per_page) > $total_results ?   $total_results: ($offset + $results_per_page);

	echo ("<P>Page $page of $pages showing records ".($offset + 1)." through ".($last_this_page)." of $total_results.</P>\n");

	if ($offset > 0)
	{
	    // not first result
	    
	    $url = make_url($_REQUEST, array('offset', 'button_search'));	    
	    
	    echo ("<A href=\"search_volunteer.php$url&offset=0\">"._("First")."</A>\n");
	    echo ("<A href=\"search_volunteer.php$url&offset=".($offset-$results_per_page)."\">"._("Previous")."</A>\n");	    
	}
	else
	{
	    echo (_("First")."\n");
	    echo (_("Previous")."\n");
	}

	if ($offset + $results_per_page < $total_results)
	{
	    $url = make_url($_REQUEST, array('offset', 'button_search'));
	    echo ("<A href=\"search_volunteer.php$url&offset=".($offset+$results_per_page)."\">Next</A>\n");
	    echo ("<A href=\"search_volunteer.php$url&offset=".($total_results - ($total_results % $results_per_page))."\">".gettext("Last")."</A>\n");	    
	}
	else
	{
	    echo (_("Next")."\n");
	    echo (_("Last")."\n");
	
	}

	// sorting

	echo ("<FORM method=\"get\" action=\"search_volunteer.php\">\n");
	echo ("<FIELDSET>\n");
	echo ("<LEGEND>Sort</LEGEND>\n");

	foreach ($_REQUEST as $k => $v)
	{
	    if ($k != 'sortby' and !preg_match('/^button_/', $k))
		echo ("<INPUT type=\"hidden\" name=\"$k\" value=\"$v\">\n");
	}
//	echo ("Sort by\n");
	echo ("<SELECT name=\"sortby\"");
	foreach ($cm->getNames() as $c)
        
	    echo ("<OPTION>$c</OPTION>\n");
	echo ("</SELECT>\n");
	echo ("<INPUT type=\"submit\" name=\"button_search\" value=\""._("Sort")."\">\n");

	echo ("</FIELDSET>\n");
	echo ("</FORM>\n");    
} /* volunteer_search () */

function volunteer_search_form()
// todo: find volunteers who helped with a project, department, or event
{
    global $db;
    global $cm;
?>

<P class="instructionstext">To search for a volunteer, enter the
information into the form below and click the search button or just
press enter.  To find a specific person, use the personal information
section.  To find a person for a position, use the skills and interests
section.</P>


<FORM method="get" action="search_volunteer.php">


<TABLE border="0" class="clear" cellspacing="5">
<TR>
<TD valign="top">
<TABLE border="0" style="margin:6pt">
<tr>
 <th colspan="2">Personal Information</th>
</tr>
<tr>
 <th class="vert"><?php echo _("First name"); ?></th>
 <td><input type="Text" name="first"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Last name"); ?></th>
 <td><input type="Text" name="last"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Organization"); ?></th>
 <td><INPUT type="text" name="organization"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Street"); ?></th>
 <td><input type="text" name="street"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("City"); ?></th>
 <td><input type="text" name="city"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Zip/Postal code"); ?></th>
 <td><input type="Text" name="postal_code" size="10"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Country"); ?></th>
 <td><input type="Text" name="country" size="30"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Home phone"); ?></th>
 <td><input type="Text" name="phone_home"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Work phone"); ?></th>
 <td><input type="Text" name="phone_work"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Cell phone"); ?></th>
 <td><input type="Text" name="phone_cell"></td>
 </tr>

<tr>
 <th class="vert"><?php echo _("E-mail"); ?></th>
 <td><input type="Text" name="email_address"></td>
 </tr>
</table>

<TABLE border="0" style="margin:6pt">
<tr>
 <th colspan="2"><?php echo _("Results"); ?></th>
</tr>
<tr>
 <th class="vert"><?php echo _("Sort by"); ?></th>
 <td><SELECT name="sortby">
 <?php
 foreach ($cm->getNames() as $c)
 {
    echo ("<OPTION>$c</OPTION>\n");
 }
 ?>
 </SELECT>
  </td>
 </tr>
</TABLE>
</TD>
<TD valign="top">
<TABLE border="0" style="margin:6pt">
<tr> <th colspan="2">Skills, interests</th></tr>

<?php

    $result = $db->query("SELECT s AS name, string_id FROM strings WHERE type = 'skill' ORDER BY name");

    if (!$result)
    {
    	echo ("<TR><TD>\n");
	process_system_error("Error querying database for skills.");
	echo ("</TD></TR>\n");	
    }
    else
    if (0==$db->num_rows($result))
    {
	echo ("<TR><TD>\n");
	process_user_error("Cannot find any qualifications or interests to list.");
	echo ("</TD></TR>\n");
    }
    else while (FALSE != ($row = $db->fetch_array($result)))
    {
	echo ("<TR>\n");
	echo ("<TH class=\"vert\">".$row['name']."</TH>\n");
	echo ("<TD><SELECT name=\"skill_".$row['string_id']."\">\n");
        echo ("<OPTION value=\"n\">"._("Doesn't matter")."</OPTION>\n");
        echo ("<OPTION value=\"2\">"._("Amatuer")."</OPTION>\n");
        echo ("<OPTION value=\"3\">"._("Some")."</OPTION>\n");    
        echo ("<OPTION value=\"4\">"._("Professional")."</OPTION>\n");        
        echo ("<OPTION value=\"5\">"._("Expert")."</OPTION>\n");            
        echo ("</SELECT>\n");
        echo ("</TR>\n");
	
    }
?>    


</table>
</TD>
</TR>
</TABLE>

<input type="Submit" name="button_search" value="<?php echo _("Search"); ?>">
</form>



<?php

} /* volunteer_search_form() */


$db = new voldbMySql();

if ($db->get_error())
{
    process_system_error(_("Unable to establish database connection."), array('debug' => $db->get_error()));    
    die();	
}


if (array_key_exists('button_search', $_REQUEST) or array_key_exists('postal_code', $_REQUEST))
{
    volunteer_search();

    echo ("<P class=\"anecdotetext\">Search took ".round(getmicrotime() - $time_start, 3)." seconds.</P>\n");

}    
else
{
    volunteer_search_form();
}   


make_html_end();

?>

