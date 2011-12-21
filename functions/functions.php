<?php

/*
 * Son of Service
 * Copyright (C) 2003-2011 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: functions.php,v 1.20 2011/12/21 03:51:49 andrewziem Exp $
 *
 */


if (preg_match('/functions.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

include_once(SOS_PATH . 'functions/html.php');

/**
 * save_message($type, $message, [$file, $line, $sql])
 *
 * Saves a message to be displayed later (next page load).  Used with
 * form processing.
 * 
 * @param int type type of message
 * @param string message message
 * @param string file file in which the error occured
 * @param string line line number at which the error occured
 * @param sql SQL code that trigged the error
 * @return void
 *
 */
function save_message($type, $message, $file = NULL, $line = NULL, $sql = NULL)
{
    global $db;

    assert (is_int($type));
    $error = "";
    if (NULL != $db)
        $error = $db->ErrorMsg();
    $message = array('type' => $type, 'message' => $message, 'file' => $file, 
        'line' => $line, 'sql' => $sql, 'sql_error' => $error);
    $_SESSION['messages'][] = $message;

    // todo: log error message here if applicable (refer to configurable log level)
}

/**
 * die_message($type, $message, $file = NULL, $line = NULL, $sql = NULL)
 *
 * Displays a message then dies.  Same parameters as save_message().
 *
 */
function die_message($type, $message, $file = NULL, $line = NULL, $sql = NULL)
{
    global $db;

    assert (is_int($type));
    display_message($type, $message, $file, $line, $sql, $db->ErrorMsg());
    die();
    // todo: log error message here if applicable (refer to configurable log level)
}

/**
 * make_volunteer_name($row)
 *
 * name, e.g. John Smith (Smith Inc.)
 *
 * @param string row an array containing first, middle, last, organization
 * @return string name of t 
 */
function make_volunteer_name($row)
{
    if (!is_array($row))
    return FALSE;
    $name = trim($row['first'].' '.$row['middle'].' '.$row['last']);
    if (!empty($row['organization']))
    $name .= ' ('.$row['organization'].')';
    return $name;   
}

/**
 * get_user_id()
 * 
 * @return int user ID of current user
 */

function get_user_id()
{
    if (!array_key_exists('user_id', $_SESSION))
    {
    die_message(MSG_SYSTEM_ERROR, 'user_id missing in SESSION', __FILE__, __LINE__);
    }
    return (intval($_SESSION['user_id']));
}

/**
 * redirect($url)
 *
 * @param string url url
 * @return void
 */

function redirect($url)
{
    header("Location: $url");
}


/**
 * sqldate_to_local($sql_date)
 *
 * @param string sql_date date in the format YYYY-MM-DD
 * @return string date as given by strftime("%D")
 */
function sqldate_to_local($sql_date)
{
    global $db;


    $unixdate = $db->Unixdate($sql_date);
    if (0 == $unixdate)
    {
       return "";
    }

    // todo: localize
    $ret = strftime("%D", $unixdate);
    if (0 == strlen($ret))
        $ret = date('n/j/Y', $unixdate);
    return $ret;
}

/**
 * sqldatetime_to_local($sql_datetime)
 *
 * @param string sql_datetime datetime in SQL format
 * @return string date as given by strftime("%c")
 */
function sqldatetime_to_local($sql_datetime)
{
    global $db;


    $unixdate = $db->UnixTimeStamp($sql_datetime);
    if (0 == $unixdate)
    {
        return "";
    }

    $ret = strftime("%c", $unixdate);
    if (0 == strlen($ret))
        $ret = date('n/j/Y g:i a', $unixdate);
    return $ret;
}

/**
 * is_valid_language($language)
 *
 * Checks whether the language code is valid (contains
 * valid, safe characters).
 *
 * @param string language code (e.g. en, en_US, es_BR, es-ar)
 * @return boolean
 *
 */
function is_valid_language($language)
{
    return (is_string($language) and strlen($language) > 1 
        and preg_match('/^[a-zA-Z_-]{2,5}$/', $language));
}

/**
 * set_up_language($override)
 *
 * First, try to detect language from headers sent by web browser.
 * Second, try to find a supported language.  Third, use the
 * language.
 *
 * @param string or NULL override language as string or NULL indicating no override
 * @return none
 */
function set_up_language($override = NULL)
{
    global $default_language;
    global $languages;

    $user_languages = array(); // languages in order of preference

    // override
    if (is_valid_language($override))
        $user_languages[] = $override;

    // session
    if (array_key_exists('sos_language', $_SESSION) and is_valid_language($_SESSION['sos_language']))
    {
        $user_languages[] = $_SESSION['sos_language'];
    }

    // detect languages from headers sent by web browser
    if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER))
    {
        $browser_languages = preg_split('/[,;]/', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($browser_languages as $bl)
        {
            if (is_valid_language($bl))
                $user_languages[] = $bl;
        }
    }

    // fallbacks
    $user_languages[] = $default_language;
    $user_languages[] = 'en';
    $user_languages[] = 'en_US';

    // find a supported language
    foreach ($user_languages as $language)
    {
        // check for an alias
        if (array_key_exists($language, $languages) and array_key_exists('ALIAS', $languages[$language]))
            $language = $languages[$language]['ALIAS'];

        // try the path
        if ($language == 'en_US' ||
            file_exists(SOS_PATH . "locale/$language/LC_MESSAGES/messages.mo"))
        {
            setlocale(LC_MESSAGES, $language);
            break;
        }
    }

    if (!extension_loaded('gettext'))
    {
        if ((bool)ini_get( "safe_mode" ))
            return FALSE;
        $prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
        @dl($prefix . 'gettext.' . PHP_SHLIB_SUFFIX);
    }

    if (extension_loaded('gettext'))
    {
        // setup domain
        bindtextdomain('messages', SOS_PATH . 'locale');
        textdomain('messages');
    }

    // setup browser character set
    // fixme:  header(Content-Type: text/html; charset=' . $charset)
}


/* available languages */

$languages['en_US']['NAME'] = 'English';
$languages['en']['ALIAS'] = 'en_US';
$languages['en_GB']['ALIAS'] = 'en_US';

$languages['nl_NL']['NAME'] = 'Dutch';
$languages['nl']['ALIAS'] = 'nl_NL';

?>
