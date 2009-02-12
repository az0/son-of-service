<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Makes comma-delimited data files for downloading.
 *
 * $Id: textwriter.php,v 1.4 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/textwriter.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

class flatDbWriter
{
    var $fieldnames;
    var $filename;
    var $f;

    
    function setFieldNames($fieldnames)
    {
	$this->fieldnames = $fieldnames;    
    }
    
    function open()
    {
    
    }
    
    function close()
    {
    
    }
}

class textDbWriter
// comma or tab delimited
{
    var $text_type; // 'csv' OR 'tab' only
    var $delimiter;

    function textDbWriter($text_type)
    {
	$this->text_type = $text_type;
	switch ($text_type)
	{
	    case 'csv':
		$this->delimiter = ',';
		break;
	    case 'tab':
		$this->delimiter = '	';
		break;		
	    default;
		process_system_error("Expecting tab or csv\n");
		break;
	}
    }
    

    function setFieldNames($fieldnames)
    {
	$this->fieldnames = $fieldnames;    
	
	$this->addRow($fieldnames);
    }

    function addRow($values)
    {
	$c= 0;
	
	if (is_array($values))
	{
	    foreach ($values as $value)
	    {
		if ($c > 0)
		    echo ($this->delimiter);
		if (FALSE != strstr($value, $this->delimiter))
		{
		    $value = "\"$value\"";
		}    
		echo $value;
		$c++;	
	    }	    
	    echo ("\n");
	}
    }
}


?>
