<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Makes comma-delimited data files for downloading.
 *
 * $Id: table.php,v 1.1 2003/10/05 16:14:35 andrewziem Exp $
 *
 */

if (preg_match('/table.php/i', $_SERVER['PHP_SELF']))
{
die('Do not access this page directly.');
}

class DataTableDisplay
{
var $headers;

function setHeaders($headers)
{
	$this->headers = $headers;
}

function begin()
{
	echo ("<TABLE border=\"1\">\n");
	if (isset($this->headers) and is_array($this->headers))
	{
	    echo ("<TR>\n");
	    foreach ($this->headers as $k => $v)
	    {
		echo ("<TH>");
		if (array_key_exists('checkbox',$v) and $v['checkbox'])
		echo ("Select");
		else
		echo ucfirst($k);
//	if (array_key_exists('sortable', $v) and $v['sortable'])
//	    echo ("<A href=\"\">[Sort Asc]</A>\n");//remove
		echo ("</TH>\n");
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
	if (!is_array($row))
	return;
	echo ("<TR>\n");
	if (isset($this->headers) and is_array($this->headers))
	foreach ($this->headers as $k => $v)
	{
		if (array_key_exists('radio',$v) and $v['radio'])
		echo ("<TD><INPUT type=\"radio\" name=\"$k\" value=\"".$row[$k]."\"></TD>\n");
		else
		if (array_key_exists('checkbox',$v) and $v['checkbox'])
		echo ("<TD><INPUT type=\"checkbox\" name=\"${k}_".$row[$k]."\" value=\"1\"></TD>\n");
		else
		{
		echo ("<TD>");

		if (empty($row[$k]))
			$c = "&nbsp;";
			else $c = $row[$k];

		if (array_key_exists('link', $v) and $v['link'])
		{
		if (preg_match_all("/\#(\w+)\#/", $v['link'], $tagss))
		foreach ($tagss as $tags)
		{
			$tag = $tags[0];
			if (!preg_match("/#/", $tag))
			{
			if (array_key_exists($tag, $row))
				$v['link'] =preg_replace("/#$tag#/", $row[$tag], $v['link']);
				else "$tag not in row";
			}
		}

		echo ("<A href=\"".$v['link']."\">$c</A>");
		}
		else
		echo $c;
		echo ("</TD>\n");
	}
	}
	else
	{
		foreach ($row as $c)
		{
			echo ("<TD>$c</TD>");
		}
	}

	echo ("</TR>\n");

}

} /* class DataTableDisplay */



?>
