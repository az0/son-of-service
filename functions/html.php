<?php

/*
 * Son of Service
 * Copyright (C) 2003-2011 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Functions related to HTML, HTTP, and URLs.
 *
 * $Id: html.php,v 1.28 2011/12/21 04:32:25 andrewziem Exp $
 *
 */


if (preg_match('/html.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/functions.php');


function display_message($type, $message, $file, $line, $sql, $sql_error)
{
    global $debug;

    assert(is_int($type));
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
    if ($debug)
    {
        if ($file != NULL && $line != NULL)
            echo ("<P>Location: $file line: $line</P>\n");
        if ($sql != NULL)
            echo ("<P>SQL: $sql</P>\n");

        if ($sql_error != NULL)
            echo ("<P>SQL Error: $sql_error</P>\n");
    }
}


/* display_message()
 * Displays a message previously stored with save_message().  Then erases
 * messages.
 *
 */
function display_messages()
{
    if (array_key_exists('messages', $_SESSION) and is_array($_SESSION['messages']) and count($_SESSION['messages']) > 0)
    {
    echo ("<DIV class=\"messages\">\n");    
    // reverse array so FIFO
    $messages = array_reverse ($_SESSION['messages']);
    foreach ($messages as $key => $msg)
    {
        display_message($msg['type'], $msg['message'], $msg['file'], $msg['line'], 
        $msg['sql'], $msg['sql_error']);
    }

    $_SESSION['messages'] = array();
    echo ("</DIV>\n");
    }

}

function make_nav_begin()
/* Builds a user navigation bar */
{
    if (is_printable())
    {
        // JavaScript print button
        echo ("<INPUT type=\"button\" value=\""._("Print")."\" onClick=\"window.print();\">\n");
        return;
    }
    echo ("<div class=\"tab_area noprint\">\n");
    echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/search_volunteer.php\">"._("Search")."</A>\n");
    if (has_permission(PC_VOLUNTEER, PT_WRITE, NULL, NULL))
        echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/add_volunteer.php\">"._("Add new volunteer")."</A>\n");
    echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/reports.php\">"._("Reports")."</A>\n");

    if (has_permission(PC_ADMIN, PT_READ, NULL, NULL))
        echo ("<A class=\"tab\" href=\"". SOS_PATH ."admin/\">"._("Admin")."</A>\n");
    echo ("<A class=\"tab\" href=\"". SOS_PATH . "src/login.php?logout=1\">"._("Logout")."</A>\n");
    echo ("</DIV>\n");

// todo: make quick search fit aesthetically somewhere
/*
echo ("<FORM method=\"post\" action=\"". SOS_PATH . "src/search_volunteer.php\">\n");
echo ("Quick search <INPUT type=\"text=\" name=\"fullname\" size=\"10\">\n");
echo ("</FORM>\n");
*/

    if (preg_match('/\/volunteer\//i', $_SERVER['PHP_SELF']) and (array_key_exists('vid', $_GET) or array_key_exists('vid', $_POST)) and !array_key_exists('delete_confirm',$_POST))
    {
        $vid = $_REQUEST['vid'];

       echo ("<DIV class=\"tab_area noprint\">\n");
       echo ("This volunteer \n");
       echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid\">"._("Summary")."</A>\n");         
       echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&amp;menu=general\">"._("General")."</A>\n");      
       echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&amp;menu=skills\">"._("Skills")."</A>\n");
       echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&amp;menu=availability\">"._("Availability")."</A>\n");
       echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&amp;menu=workhistory\">"._("Work history")."</A>\n");
       echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&amp;menu=notes\">"._("Notes")."</A>\n");      
       echo ("<A class=\"tab\" href=\"". SOS_PATH . "volunteer/?vid=$vid&amp;menu=relationships\">"._("Relationships")."</A>\n");
       echo ("</DIV>\n");

    }

    echo ("<HR style=\"margin-top:0pt\" class=\"noprint\">\n");

} /* make_nav_begin() */



function make_html_begin($title, $options)
{
    set_up_language(NULL);

    echo ("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"");
    echo ("   \"http://www.w3.org/TR/html4/loose.dtd\">\n");
    echo ("<HTML>\n");
    echo ("<HEAD>\n");
    echo ("<TITLE>$title</TITLE>\n");
    echo ("<STYLE type=\"text/css\" media=\"screen\">\n");
    echo ("<!--   @import url(". SOS_PATH. "sos.css);  -->  \n");
    echo ("</STYLE>\n");
    echo ("<STYLE type=\"text/css\" media=\"print\">\n");
    echo ("  <!--  .noprint {display:none}  -->\n");
    echo ("</STYLE>\n");
    echo ("<META name=\"robots\" content=\"noindex,nofollow\">\n");    
    echo ("</HEAD>\n");
    echo ("<BODY>\n");
}


function make_html_end()
{

    if (is_logged_in(FALSE))
    {
?>
<HR>
<P><A href="<?php echo SOS_PATH; ?>src/about.php">Son of Service</A></P>
<?php
}
?>    
</BODY>
</HTML>
<?php

    }


function display_position_option($arg_1, $arg_2)
// deprecated
{
  if ($arg_1 == $arg_2)
    return "value=\"$arg_1\" SELECTED";
  else
    return "value=\"$arg_1\" ";
}


function make_url($parameters, $exclusion)
// paramaters: an array, such as $_GET
// exclusion: keys not to include from parameters (string or array)
// return: url suitable for HREF
{
    assert(is_array($parameters));
    assert(is_array($exclusion) or is_string($exclusion));
    $url = "";
    $url_i = 0;
    if (is_string($exclusion))
        $exclusion = array($exclusion);
    foreach ($parameters as $k => $v)
    {
        $excluded = FALSE;
        if (is_array($exclusion))
        {
            foreach ($exclusion as $e)
            {
                if ($e == $k)
                    $excluded = TRUE;
            }
        }
        if (!$excluded)
        {
            if (0 == $url_i)
                $url .= '?';
            else
                $url .= '&amp;';
            $url .= urlencode("$k").'='.urlencode("$v");
            $url_i++;
        }
    }
    return $url;
}

function find_values_in_request($request, $prefix)
// Finds integers in a form request by searching for keys beginning
// with prefix.  Returns the integer part only.  Useful for processing
// input from multiple choice forms.

// $request: an array such $_POST
// $prefix: a string such as volunteer_id
// return: an array of integers
{
    assert(is_array($request));
    assert(is_string($prefix));
    $ret = array();
    foreach ($_POST as $key => $value)
    {
        if (preg_match("/${prefix}_(\d+)/", $key, $matches))
            $ret[] = $matches[1];
    }
    return ($ret);
}

function nbsp_if_null($s)
{
    if (NULL == $s or 0 == strlen($s))
        return "&nbsp;";
    return $s;
}

function is_printable()
{
    return (array_key_exists('printable', $_GET));
}

?>
