<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Generates an HTML table from a set of data.
 *
 * $Id: table.php,v 1.20 2009/11/29 17:24:28 andrewziem Exp $
 *
 */

if (preg_match('/table.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

// todo: htmlspecialchars 
// todo: same function names as for tab, csv class
// todo: localize date, datetime per user

define('TT_STRING', 1);
define('TT_NUMBER', 2);
define('TT_DATE', 3);
define('TT_DATETIME', 4);
define('TT_INPUT', 5);

class DataTableDisplay
{
    var $headers;	// information about columns
    var $printable;	// boolean


    function DataTableDisplay()
    {
	$this->printable = is_printable();
	$this->headers = NULL;
    }

    function setHeaders($headers)
    // $headers: an array of arrays
    //  key: field name (for addRow())
    //  subkey: label (string)
    //  subkey: checkbox (boolean)
    //  subkey: link
    //  subkey: type (see TT_*)
    //  subkey: sortable (boolean)
    //  subkey: break_row
    //  subkey: colspan (integer)
    //  subkey: map (array, maps one value to another such as an int to string)
    {
	assert(is_array($headers));	
	$this->headers = $headers;
    }
    
    function setPrintable($b = TRUE)
    {
	assert(is_bool($b));
	$this->printable = $b;
    }

    function begin()
    {
	echo ("<TABLE border=\"1\">\n");
	// display column headers
	if (isset($this->headers) and is_array($this->headers))
	{
	    echo ("<TR>\n");
	    foreach ($this->headers as $k => $v)
	    {
		if (array_key_exists('label', $v))
		{
		    // explicit column label
		    $label = $v['label'];
		}
		else
		{
		    // implicit column label		
		    $label = ucfirst($k);
		}
		
		if (array_key_exists('checkbox', $v) and $v['checkbox'] and $this->printable)
		{
		}
		elseif (array_key_exists('break_row', $v) and $v['break_row'])
		{
		    echo ("</TR>\n<TR>\n");
		}
		else
		{
		    $colspan = "";
		    if (array_key_exists('colspan', $v))
		    {
			$colspan = " colspan=\"".$v['colspan']."\"";
			
		    }
		    echo ("<TH$colspan>$label\n");
		    if (!$this->printable and array_key_exists('sortable', $v) and $v['sortable'])
		    {
			// display sorting option
			$url = make_url($_GET, 'orderby');
			echo ("[<A href=\"$url&amp;orderby=$k&amp;orderdir=asc\">A</A>/<A href=\"$url&amp;orderby=$k&amp;orderdir=desc\">D</A>]");
		    }
		    echo ("</TH>\n");
		}
	    }
	    echo ("</TR>\n");    
	}
    }

    function end()
    {
	echo ("</TABLE>\n");
    }

    function addRow($row)
    {
	assert(is_array($row));
	if (!is_array($row))
	{
	    return;
	}
	echo ("<TR>\n");
	if (isset($this->headers) and is_array($this->headers))
	{
	    foreach ($this->headers as $k => $v)
	    {
		if (array_key_exists('break_row', $v))
		{
		    // break row
		    echo ("</TR>\n");
		    echo ("<TR>\n");
		}
		else if (array_key_exists('radio',$v) and $v['radio'])
		{
		    // make radio
		    // todo: revisit value=
		    if (!$this->printable)
		    {
			echo ("<TD><INPUT type=\"radio\" name=\"$k\" value=\"".$row[$k]."\"></TD>\n");
		    }
		}
		else if (array_key_exists('checkbox',$v) and $v['checkbox'])
		{
		    // make checkbox
		    // todo: revisit value=
		    if (!$this->printable)
		    {
		        echo ("<TD><INPUT type=\"checkbox\" name=\"${k}_".$row[$k]."\" value=\"1\"></TD>\n");
		    }
		}
		else
		{
		    // display cell data
		    
		    $colspan = "";
		    if (array_key_exists('colspan', $v))
		    {
			$colspan = " colspan=\"".$v['colspan']."\"";
		    }
		    echo ("<TD$colspan>");

		    if (0 == strlen(trim($row[$k])))
		    {
			// blank cell
			$c = "&nbsp;";
		    }
		    elseif (array_key_exists('type', $v) and TT_DATE == $v['type'])
		    {
			// format date
			$c = nbsp_if_null(sqldate_to_local($row[$k]));
		    }
		    elseif (array_key_exists('type', $v) and TT_DATETIME == $v['type'])
		    {
			// format date+time
			$c = nbsp_if_null(sqldatetime_to_local($row[$k]));
		    }	
		    elseif (array_key_exists('map', $v))
		    {
			$c = $v['map'][$row[$k]];
		    }	    
		    else
		    {
		        $c = htmlentities($row[$k]);
		    }
		    
		    if (array_key_exists('nl2br', $v) and $v['nl2br'])
		    {
    			$c = nl2br($c);
		    }

		    if (array_key_exists('link', $v) and $v['link'] and 0 < strlen(trim($row[$k])))
		    {
			if (preg_match_all("/\#(\w+)\#/", $v['link'], $tagss))
			foreach ($tagss as $tags)
			{
			    $tag = $tags[0];
			    if (!preg_match("/#/", $tag))
			    {
				if (array_key_exists($tag, $row))
				{
				    $v['link'] = preg_replace("/#$tag#/", $row[$tag], $v['link']);
				}
			        else
				{
				    process_system_warning("$tag not in row");
				}
			    }
			}
			echo ("<A href=\"".$v['link']."\">$c</A>");
		    }
		    else
		    {
			echo $c;
		    }
		    echo ("</TD>\n");
	    
		}
	    }	    
    	}

	echo ("</TR>\n");

    } /* addRow() */

} /* class DataTableDisplay */


class DataTablePager extends DataTableDisplay
// for use with database queries
{
    var $offset;	// NULL or integer, for pagintaion
    var $rows_per_page; // NULL or integer, for pagination
    var $db;		// ADOdb database connection
    var $db_result;	// ADOdb database result
    
    
    function DataTablePager()
    {
	$this->db = NULL;
	$this->db_result = NULL;
	$this->offset = 0;
	$this->rows_per_page = NULL;	
	$this->printable = is_printable();
	$this->headers = NULL;
    }

    function setDatabase(&$db, $db_result)
    {
	$this->db = $db;
	$this->db_result = $db_result;
    }
    
    function setPagination($rows_per_page = 10, $offset = NULL)
    {
	assert(is_numeric($rows_per_page));
	if (NULL == $offset and array_key_exists('offset', $_GET))
	{
	    $this->offset = intval($_GET['offset']);
	}
	else if (NULL != $offset)
	{
	    $this->offset = intval($offset);
	}
	$this->rows_per_page = intval($rows_per_page);
    }
    
    function printNavigation()
    {
	$url = make_url($_GET, 'offset');
	
	echo ("<P>");
	// first, previous
	if ($this->offset > 0)
	{
	    // first, previous exist
	    echo ("<A title=\"" . _("First") . "\" href=\"$url&offset=0\">|&lt;</A> ");
	    $previous = $this->offset - $this->rows_per_page;
	    if ($previous < 0)
	    {
		$previous = 0;	
	    }
	    echo ("<A title=\"" . _("Previous") . "\" href=\"$url&offset=$previous\">&lt;&lt;</A> ");	    

	}
	else
	{
	    // at first: no previous records
	    echo ("|&lt &lt;&lt;\n");
	
	}
	
	// next, last
	if ($this->offset + $this->rows_per_page < $this->db_result->RecordCount())
	{
	    // next
	    echo ("<A title=\"" . _("Next") . "\" href=\"$url&offset=".($this->offset + $this->rows_per_page)."\">&gt&gt;</A> ");
	    // last
	    echo ("<A title=\"" . _("Last") . "\" href=\"$url&offset=".($this->db_result->RecordCount() - ($this->db_result->RecordCount() % $this->rows_per_page))."\">&gt;|</A> ");	    
	
	}
	else
	{
	    // last page, no more
	    echo ("&gt;&gt; &gt;|\n");	
	}
	echo ("</P>\n");	
    }
    
    function render()
    // creates the whole table including page navigation commands
    {
	assert($this->db != NULL);
	assert($this->db_result != NULL);	
	if (NULL != $this->offset)
	{
	    $this->db_result->Move($this->offset);
	}
	if (NULL == $this->rows_per_page)
	{
	    $c = 0;
	}
	else
	{
	    $c = $this->rows_per_page;	
	}
	if ($this->rows_per_page > 0 and $this->db_result->RecordCount() > $this->rows_per_page and !$this->printable)
	{
	    // open navigation table
	    echo ("<TABLE border=\"1\" class=\"pagination\"><TR><TD class=\"pagination\">\n");
	    // print navigation menu
	    $this->printNavigation();
	}	
	$this->begin();
	while (!$this->db_result->EOF and ($c > 0 or NULL == $this->rows_per_page))
	{
	    $fields  = $this->db_result->fields;
	    assert(is_array($fields));
	    $this->addRow($fields);
	    $this->db_result->MoveNext();
	    $c--;
	}
	$this->end();
	if ($this->rows_per_page > 0 and $this->db_result->RecordCount() > $this->rows_per_page and !$this->printable)
	{
	    // page number
	    echo ("<P>Page ".intval(1 + ($this->offset / $this->rows_per_page))." of ".ceil($this->db_result->RecordCount() / $this->rows_per_page)."</P>\n");
	    // close navigation table
	    echo ("</TD></TR></TABLE>\n");
	}
    }
} /* class DataTablePager */


?>
