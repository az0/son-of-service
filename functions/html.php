<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: html.php,v 1.2 2003/11/01 17:24:55 andrewziem Exp $
 *
 */


if (preg_match('/html.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

function make_nav_begin()
/* Builds a user navigation bar */
{
//    global $PHP_SELF;
    global $base_url;

echo ("<div class=\"tab_area\">\n");
echo ("<A class=\"tab\" href=\"${base_url}src/search_volunteer.php\">"._("Search")."</A>\n");
echo ("<A class=\"tab\" href=\"${base_url}src/add_volunteer.php\">"._("Add new volunteer")."</A>\n");
echo ("<A class=\"tab\" href=\"${base_url}src/reports.php\">".("Reports")."</A>\n");

if ('1' == $_SESSION['user']['access_admin'])
    echo ("<A class=\"tab\" href=\"${base_url}admin/\">"._("Admin")."</A>\n");
    
echo ("<A class=\"tab\" href=\"${base_url}src/login.php?logout=1\">"._("Logout")."</A>\n");
echo ("</DIV>\n");

// to do: make quick search fit aesthetically somewhere
/*
echo ("<FORM method=\"post\" action=\"${base_url}search_volunteer.php\">\n");
echo ("Quick search <INPUT type=\"text=\" name=\"fullname\" size=\"10\">\n");
echo ("</FORM>\n");
*/

if (preg_match('/\/volunteer\//i', $_SERVER['PHP_SELF']) and (array_key_exists('vid', $_GET) or array_key_exists('vid', $_POST)) and !array_key_exists('delete_confirm',$_POST))
{
    $vid = $_REQUEST['vid'];

   echo ("<DIV class=\"tab_area\">\n");    
   echo ("This volunteer \n");
   echo ("<A class=\"tab\" href=\"${base_url}volunteer/?vid=$vid\">"._("Summary")."</A>\n");         
   echo ("<A class=\"tab\" href=\"${base_url}volunteer/?vid=$vid&menu=general\">"._("General")."</A>\n");      
   echo ("<A class=\"tab\" href=\"${base_url}volunteer/?vid=$vid&menu=skills\">"._("Skills")."</A>\n");
   echo ("<A class=\"tab\" href=\"${base_url}volunteer/?vid=$vid&menu=availability\">"._("Availability")."</A>\n");      
   echo ("<A class=\"tab\" href=\"${base_url}volunteer/?vid=$vid&menu=workhistory\">"._("Work history")."</A>\n");   
   echo ("<A class=\"tab\" href=\"${base_url}volunteer/?vid=$vid&menu=notes\">"._("Notes")."</A>\n");      
   echo ("<A class=\"tab\" href=\"${base_url}volunteer/?vid=$vid&menu=relationships\">"._("Relationships")."</A>\n");      
   echo ("</DIV>\n");

}



echo ("<HR style=\"margin-top:0pt\">\n");

} /* make_nav_begin() */



function make_html_begin($title, $options)
{
    global $base_url;

    echo "<HTML>\n";
    echo "<HEAD>\n";
    echo "<TITLE>$title</TITLE>";
    echo "<STYLE type=\"text/css\" media=\"screen\">\n";
    echo "<!--   @import url($base_url/sos.css);    --></STYLE>\n";
    echo ("<META name=\"robots\" content=\"noindex,nofollow\">\n");    
    echo "</HEAD>\n";
    echo "<BODY>\n";
}


function make_html_end()
{
?>
<HR>
<P><A href="http://sos.sourceforge.net">Son of Service</A> &copy; 2003 Andrew Ziem</P>
    
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

?>
