<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: html.php,v 1.8 2003/11/27 04:23:57 andrewziem Exp $
 *
 */


if (preg_match('/html.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

define('MSG_SYSTEM_ERROR', 1);
define('MSG_SYSTEM_WARNING', 2);
define('MSG_SYSTEM_NOTICE', 8);
define('MSG_USER_ERROR', 256);
define('MSG_USER_WARNING', 512);
define('MSG_USER_NOTICE', 1024);

/* save_message()
 * Saves a message to be displayed later (next page load).  Used with
 * forms processing.
 *
 */
function save_message($message, $type, $options = FALSE)
{
    assert (is_int($type));
    $_SESSION['messages'][] = array('message' => $message, 'type' => $type, 'options' => $options);
    
    // to do: log error message here if applicable (refer to configurable log level)
}



function display_message($message, $type, $options = FALSE)
{

    switch ($type)
    {
	case MSG_SYSTEM_ERROR:
	case MSG_SYSTEM_WARNING:
	case MSG_USER_ERROR:
	case MSG_USER_WARNING:
	    $class = " CLASS=\"errortext\"";
	    break;
	
	default:
	    $class = "";
	    break;
    }
    echo ("<P$class>$message</P>");
    
    if (is_array($options) and array_key_exists('debug', $options))
    {
	// to do: disable debug in config.php
	echo ("<P>"._("Debug:").' '.$options['debug']."</P>\n");
    }
}


/* display_message()
 * Displays a message previously stored with save_message().  Then erases
 * messages.
 *
 */
function display_messages()
{
    if (array_key_exists('messages', $_SESSION) and is_array($_SESSION['messages']))
    {
	// reverse array so FIFO
	
	$messages = array_reverse ($_SESSION['messages']);
	
	foreach ($messages as $key => $msg)
	{
	    display_message($msg['message'], $msg['type'], $msg['options']);
	    unset($_SESSION['messages'][$key]);
	}	
	
    }

}

function make_nav_begin()
/* Builds a user navigation bar */
{
//    global $PHP_SELF;


echo ("<div class=\"tab_area\">\n");
echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/search_volunteer.php\">"._("Search")."</A>\n");
echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/add_volunteer.php\">"._("Add new volunteer")."</A>\n");
echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/reports.php\">".("Reports")."</A>\n");

if ('1' == $_SESSION['user']['access_admin'])
    echo ("<A class=\"tab\" href=\"". SOS_PATH ."admin/\">"._("Admin")."</A>\n");
    
echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/login.php?logout=1\">"._("Logout")."</A>\n");
echo ("</DIV>\n");

// to do: make quick search fit aesthetically somewhere
/*
echo ("<FORM method=\"post\" action=\"". SOS_PATH . "search_volunteer.php\">\n");
echo ("Quick search <INPUT type=\"text=\" name=\"fullname\" size=\"10\">\n");
echo ("</FORM>\n");
*/

if (preg_match('/\/volunteer\//i', $_SERVER['PHP_SELF']) and (array_key_exists('vid', $_GET) or array_key_exists('vid', $_POST)) and !array_key_exists('delete_confirm',$_POST))
{
    $vid = $_REQUEST['vid'];

   echo ("<DIV class=\"tab_area\">\n");    
   echo ("This volunteer \n");
   echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid\">"._("Summary")."</A>\n");         
   echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&menu=general\">"._("General")."</A>\n");      
   echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&menu=skills\">"._("Skills")."</A>\n");
   echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&menu=availability\">"._("Availability")."</A>\n");      
   echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&menu=workhistory\">"._("Work history")."</A>\n");   
   echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&menu=notes\">"._("Notes")."</A>\n");      
   echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&menu=relationships\">"._("Relationships")."</A>\n");      
   echo ("</DIV>\n");

}



echo ("<HR style=\"margin-top:0pt\">\n");

} /* make_nav_begin() */



function make_html_begin($title, $options)
{
    echo ("<HTML>\n");
    echo ("<HEAD>\n");
    echo ("<TITLE>$title</TITLE>");
    echo ("<STYLE type=\"text/css\" media=\"screen\">\n");
    echo ("<!--   @import url(". SOS_PATH. "/sos.css);    --></STYLE>\n");
    echo ("<META name=\"robots\" content=\"noindex,nofollow\">\n");    
    echo ("</HEAD>\n");
    echo ("<BODY>\n");
}


function make_html_end()
{


?>
<HR>
<P><A href="<?php echo SOS_PATH; ?>src/about.php">Son of Service</A></P>
    
</BODY>
</HTML>
<?php

}


function display_position_option($arg_1, $arg_2)
{
  if ($arg_1 == $arg_2)
  {
    return "value=\"$arg_1\" SELECTED";
  } 
  else
  {
    return "value=\"$arg_1\" ";
  }
}


function display_position($arg_1, $arg_2)
{
  if ($arg_1 == $arg_2)
  {
    return "value=\"$arg_1\" CHECKED";
  } 
  else
  {
    return "value=\"$arg_1\" ";
  }
}

function display_position_maybeused($arg_1)
// to do: is this used?
{
  if ("y" == $arg_1)
  {
    return "<B>yes</B>";
  } 
  elseif ('n' == $arg_1)
  {
    return "no";
  }
  else
  {
    return "not defined";
  }
}


function make_url($parameters, $exclusion)
// paramaters: an array like $_GET
// exclusion: keys not to include from parameters
// return: url suitable for HREF
{
    $url = "";
    $url_i = 0;
    foreach ($parameters as $k => $v)
    {
	$excluded = FALSE;
	
	if (is_array($exclusion))
	    foreach ($exclusion as $e)
	    {
		if ($e == $k)
		    $excluded = TRUE;
	    }
	if (!$excluded)
	{
	    if (0 == $url_i)
		$url .= '?';
    	    else
		$url .= '&';
	    $url .= urlencode("$k").'='.urlencode("$v");
	    $url_i++;
	}
    }	    
    return $url;
}

function nbsp_if_null($s)
{
    if (NULL == $s or empty($s))
    {
	return "&nbsp";
    }
    
    return $s;
	
}

?>
