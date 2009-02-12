<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.  
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: search_volunteer.php,v 1.34 2009/02/12 04:11:20 andrewziem Exp $
 *
 */



// todo: add to found set (vs replace) ?
// todo: found set management
// todo: e-mail found set
// todo: advanced searching (e.g., not, match exact)
// todo: query manager for saving queries
// todo: handle multiple tables better

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

make_html_begin(_('Search for volunteers'), array());

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
	$this->columns[$name] = array('display' => $display);
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
// doesn't do anything
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
$cm->setColumnLink('last', SOS_PATH . "volunteer/?vid=#volunteer_id#");
$cm->addColumn('organization', TRUE);
$cm->setColumnLink('organization', SOS_PATH  . "volunteer/?vid=#volunteer_id#");
$cm->addColumn('street');
$cm->addColumn('city');
$cm->addColumn('state');
$cm->addColumn('postal_code', TRUE);
$cm->addColumn('country');
$cm->addColumn('hours_life', TRUE);

function search_add($form_name, $column, &$where)
// Adds a field to the search SQL and enables the field's display.
{
    global $cm;
    global $db;
    

    if (array_key_exists($form_name, $_REQUEST) and trim(strlen($_REQUEST[$form_name])) > 0)
    {
	$where .= " AND $column LIKE ".$db->qstr('%'.$_REQUEST[$form_name].'%', get_magic_quotes_gpc())."";

	if ($cm->columnExists($column))		
	{
	    $cm->setDisplay($column, TRUE);		
	}
    }
}


/**
 * volunteer_search_sql()
 *
 * Given user-defined parameters in $_GET, generates an appropriate
 * SQL string.  Modifies $cm.
 *
 * @return string SQL string
 *
 */
function volunteer_search_sql()
{
    global $cm;
    global $db;


    $skills_active = $extended_active = FALSE;
	
    // dummy
    $where  = " WHERE 1 ";

    // columns in volunteer table
    search_add('first', 'first', $where);		
    search_add('last', 'last', $where);		
    search_add('organization', 'organization', $where);		
    search_add('street', 'street', $where);			
    search_add('city', 'city', $where);					
    search_add('state', 'state', $where);		
    search_add('postal_code', 'postal_code', $where);				
    search_add('country', 'country', $where);					

    // add skills to SQL
    foreach ($_REQUEST as $key => $p)
    {
        if (FALSE != preg_match('/^skill_(\d+)/', $key, $matches))
        {
    	    if ('n' != $_REQUEST[$key])
    	    {		    
	        $skills_active = TRUE;	    
		$where .= " AND ( string_id = ".$matches[1]." and skill_level >= " . intval($_REQUEST[$key]) . ") ";
	    }		
	}	    
    }	

    // extended
    foreach ($_REQUEST as $key => $p)
    {
        if (FALSE != preg_match('/^extended_(.+)$/', $key, $matches))
        {
    	    if (0 < strlen(trim($_REQUEST[$key])))
    	    {	
		$sColumn = $matches[1];
		$qsColumn = $db->qstr($matches[1], get_magic_quotes_gpc());
		$qsCritiera = $db->qstr('%' . $p . '%', get_magic_quotes_gpc());
	    
		// valid column?	    
		if (!db_column_exists($qsColumn, 'extended'))
		{
		    die_message(MSG_SYSTEM_ERROR, "invalid column name passed", __FILE__, __LINE__);
		}
		
		switch (db_extended_column_type($qsColumn))
		{
		    case 'integer':
		    case 'decimal':
			$where .= " AND extended.$sColumn = " . $db->qstr($_REQUEST[$key], get_magic_quotes_gpc()) . "  ";
			break;
			
		    case 'string':
		    case 'textarea':
			$where .= " AND extended.$sColumn LIKE $qsCritiera  ";
			break;
			
		    default:
			// shouldn't get here
			die_message(MSG_SYSTEM_ERROR, "unexpected type", __FILE__, __LINE__);
			break;		    
		}

	        $extended_active = TRUE;	    
		
	    }		
	}	    
    }	


    if ($skills_active)
    {	
	$groupby = ' GROUP BY volunteer_skills.volunteer_id ';
	$from   = ' FROM volunteer_skills RIGHT JOIN volunteers ON volunteer_skills.volunteer_id = volunteers.volunteer_id ';
    }
    else
    {	
	$from   = ' FROM volunteers ';
    	$groupby = ' ';
    }
    
    if ($extended_active)
    {
	$from .= ' LEFT JOIN extended ON volunteers.volunteer_id = extended.volunteer_id ';
    }
	
    if (array_key_exists('phone_number', $_REQUEST) and strlen($_REQUEST['phone_number'] > 0))
    {
        $from .= ' RIGHT JOIN phone_numbers ON volunteers.volunteer_id = phone_numbers.volunteer_id ';
        $where .= ' AND phone_numbers.number LIKE '.$db->qstr("%".$_REQUEST['phone_number']."%", get_magic_quotes_gpc());
    }
	
    if (array_key_exists('availability_day', $_REQUEST) and is_numeric($_REQUEST['availability_day']))
    {
        $t = intval($_REQUEST['availability_time']);
        $d = intval($_REQUEST['availability_day']);
        $from .= ' RIGHT JOIN availability ON volunteers.volunteer_id = availability.volunteer_id ';
        $where .= " AND availability.start_time <= $t AND $t <= availability.end_time AND $d = availability.day_of_week";
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
    
    return $sql;
} /* volunteer_search_sql() */


/**
 * volunteer_search_display($sql)
 *
 * Given an SQL string, it executes it and displays the results.
 *
 * @return void
 */
function volunteer_search_display($sql, $offset, $results_per_page)
{
    global $db, $cm;
    

    // is offset too small?    
    if ($offset < 0)
    {
	$offset = 0;
    }

    $result = $db->Execute($sql);

    if (!$result)
    { 
	// search failed
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    { 
	// search successful
	// todo: mass-action on found set (email)
		
        if (0 == ($total_results = $result->RecordCount()))
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
    
	    echo ("<FORM method=\"post\" action=\"mass.php\">\n");	

	    $tab = new DataTableDisplay();
		    
	    $fieldnames = array();
		    
	    for ($i = 0, $max = $result->FieldCount(); $i < $max; $i++)
	    {
		$fld = $result->FetchField($i);		    
		$fieldnames[$fld->name] = array();
	    }

	    $fieldnames['volunteer_id']['checkbox'] = TRUE;
	    $fieldnames['volunteer_id']['label'] = _("Select");		    
	    $fieldnames['first']['label'] = _("First");
	    $fieldnames['first']['link'] = SOS_PATH . "volunteer/?vid=#volunteer_id#";
	    $fieldnames['last']['label'] = _("Last");
	    $fieldnames['last']['link'] = SOS_PATH . "volunteer/?vid=#volunteer_id#";		    		    
	    $fieldnames['organization']['label'] = _("Organization");
	    $fieldnames['organization']['link'] = SOS_PATH . "volunteer/?vid=#volunteer_id#";		    		    

	    if ($offset > 0)
	    {
		$result->Move($offset);	    
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
	    
	    $counter = 0;
	    while (!$result->EOF)
	    {
		$counter++;
	        $tab->addRow($result->fields);
		$result->MoveNext();
		if ($counter >= $results_per_page)
		    break;
	    }
		    
	    $tab->end();
	    
	    // todo: what other mass actions?

	    echo ("<INPUT type=\"submit\" name=\"button_email_volunteers\" value=\""._("E-mail")."\">\n");
	    if (has_permission(PC_VOLUNTEER, PT_WRITE, NULL, NULL))
	    {
    		echo ("<INPUT type=\"submit\" name=\"button_delete_volunteers\" value=\""._("Delete")."\">\n");	
	    }
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
	        echo ("<A href=\"search_volunteer.php$url&amp;offset=".($offset+$results_per_page)."\">Next</A>\n");
	        echo ("<A href=\"search_volunteer.php$url&amp;offset=".($total_results - ($total_results % $results_per_page))."\">".gettext("Last")."</A>\n");	    
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
	        if ($k != 'sortby' and !preg_match('/^button_/', $k) and session_name() != $k)
		{
	    	    echo ("<INPUT type=\"hidden\" name=\"$k\" value=\"$v\">\n");
		}
	    }
	    echo ("<SELECT name=\"sortby\">");
	    foreach ($cm->getNames() as $c)
	    {
	        echo ("<OPTION>$c</OPTION>\n");
	    }
	    echo ("</SELECT>\n");
	    echo ("<INPUT type=\"submit\" name=\"button_search\" value=\""._("Sort")."\">\n");
	    echo ("</FIELDSET>\n");    
	    echo ("</FORM>\n");    
           }
        }
}

function volunteer_search()
{
    global $db, $cm;
    
    $results_per_page = 25;
    if (array_key_exists('results_per_page', $_REQUEST) and 9 < $_REQUEST['results_per_page'])
    {
	$results_per_page = intval($_REQUEST['results_per_page']);
    }

    if (array_key_exists('results_per_page', $_REQUEST) and 'i' == $_REQUEST['results_per_page'])
    {
	$results_per_page = 999999999999;
    }

    $offset = 0;
    if (array_key_exists('offset', $_REQUEST))
    {
        $offset = intval($_REQUEST['offset']);
    }

    $sql = volunteer_search_sql();
    
    volunteer_search_display($sql, $offset, $results_per_page);

	
} /* volunteer_search () */

function volunteer_search_form()
// todo: find volunteers who helped with a project, department, or event
{
    global $db;
    global $cm;
    global $daysofweek;
    
    
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
 <th colspan="2"><?php echo _("Personal Information"); ?></th>
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
 <th class="vert"><?php echo _("Phone number"); ?></th>
 <td><input type="Text" name="phone_number"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("E-mail"); ?></th>
 <td><input type="Text" name="email_address"></td>
 </tr>
<?php
// extended fields
$sql = "SELECT * FROM extended_meta";
$result = $db->Execute($sql);
if (!$result)
{
    die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
}
else
{
    while (!$result->EOF)
    {
	echo ("<tr>\n");
	echo ("<th class=\"vert\">" . $result->fields['label'] . "</th>\n");
	echo ("<td><input type=\"text\" name=\"extended_" . $result->fields['code'] . "\"></td>\n");
	echo ("</tr>\n");
	$result->MoveNext();
    }
}

?>
</table>

<TABLE border="0" style="margin:6pt">
<TR>
<TH colspan="2"><?php echo _("Availability");?></TH>
</TR>
<TR>
<TH><?php echo _("Day of week");?></TH>
<TD>
<SELECT name="availability_day">
<?php
    echo ("<OPTION value=\"\">"._("--Select")."</OPTION>\n");
    for ($i = 1; $i <= 7; $i++)
    {
        echo ("<OPTION value=\"$i\">".$daysofweek[$i]."</OPTION>\n");
    }
?>
</SELECT>
</TD>
</TR>
<TR>
<TH><?php echo _("Time of day");?></TH>
<TD>
<SELECT name="availability_time">
<?php
    echo ("<OPTION value=\"\">"._("--Select")."</OPTION>\n");    
    echo ("<OPTION value=\"1\">"._("Morning")."</OPTION>\n");
    echo ("<OPTION value=\"2\">"._("Afternoon")."</OPTION>\n");
    echo ("<OPTION value=\"3\">"._("Evening")."</OPTION>\n");
    echo ("<OPTION value=\"3\">"._("Night")."</OPTION>\n");
?>  
</SELECT>
</TD> 
</TR>
</TABLE>

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
<?php
 // todo: user profile specifies default results per page
 ?>
 <tr>
 <th class="vert"><?php echo _("Per page"); ?></th>
 <td><select name="results_per_page"> 
  <option>10</option>
  <option selected>25</option>
  <option>50</option>
  <option>100</option>
  <option>500</option>  
  <option>1000</option>    
  <option value="i"><?php echo _("Unlimited"); ?></option>  
  </select>
  </td>  
 </tr>
</TABLE>
</TD>
<TD valign="top">
<TABLE border="0" style="margin:6pt">
<tr> <th colspan="2">Skills, interests</th></tr>

<?php

    $result = $db->Execute("SELECT s AS name, string_id FROM strings WHERE type = 'skill' ORDER BY name");

    if (!$result)
    {
    	echo ("<TR><TD>\n");
	process_system_error("Error querying database for skills.");
	echo ("</TD></TR>\n");	
    }
    else
    if (0==$result->RecordCount())
    {
	echo ("<TR><TD>\n");
	process_user_error("Cannot find any qualifications or interests to list.");
	echo ("</TD></TR>\n");
    }
    else while (!$result->EOF)
    {
    	$row = &$result->fields;
	echo ("<TR>\n");
	echo ("<TH class=\"vert\">".$row['name']."</TH>\n");
	echo ("<TD><SELECT name=\"skill_".$row['string_id']."\">\n");
        echo ("<OPTION value=\"n\">"._("Doesn't matter")."</OPTION>\n");
        echo ("<OPTION value=\"2\">"._("Amateur")."</OPTION>\n");
        echo ("<OPTION value=\"3\">"._("Some")."</OPTION>\n");    
        echo ("<OPTION value=\"4\">"._("Professional")."</OPTION>\n");        
        echo ("<OPTION value=\"5\">"._("Expert")."</OPTION>\n");            
        echo ("</SELECT>\n");
        echo ("</TR>\n");
	$result->MoveNext();	
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


$db = connect_db();

if ($db->_connectionID == '')
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
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

