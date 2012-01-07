<?php

/*
 * Son of Service
 * Copyright (C) 2003-2011 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: reports.php,v 1.24 2012/01/07 01:51:28 andrewziem Exp $
 *
 */

// todo: build better report framework
// todo: paginate long reports
// todo: make each report like a plugin

session_start();

define('SOS_PATH', '../');

require_once(SOS_PATH.'include/global.php');
require_once(SOS_PATH.'functions/html.php');
require_once(SOS_PATH.'functions/forminput.php');
require_once(SOS_PATH.'functions/textwriter.php');

is_logged_in();

if (array_key_exists('download', $_REQUEST))
{
    ob_start();
}
else
{
    make_html_begin(_("Reports"), array());

    make_nav_begin();
}

$db = connect_db();

if (!$db)
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);    
}

$steps = array(_('Day'), _('Week'), _('Month'), _('Year'));

if (array_key_exists('report_hours', $_REQUEST))
    report_hours();
else
if (array_key_exists('report_active_volunteers', $_REQUEST))
    report_active_volunteers();
else
if (array_key_exists('report_volunteers_by_skill', $_REQUEST))
    report_volunteers_by_skill();
if (array_key_exists('report_volunteers_hours_by_work_activity', $_REQUEST))
    report_volunteers_hours_by_work_activity();
else
reports_menu();


class report_display
{
    var $title;
    var $type;
    var $fields;
    var $writer;

    function report_display($title, $type, $fields, $offer_csv = TRUE)
    {
        $this->title = $title;
        $this->type = $type;
        $this->fields = $fields;
        $this->offer_csv = $offer_csv;
    }

    function begin($result)
    {
        global $db;

        if ('html' == $this->type)
        {
            echo ("<TABLE border=\"1\">\n");
            if ($this->title)
                echo ("<CAPTION>" . $this->title . "</CAPTION>\n");
            echo ("<TR>\n");
            foreach ($this->fields as $field)
                    echo ("<TH>$field</TH>\n");
            echo ("</TR>\n");
        }
        elseif ('csv'  == $this->type)
        {
            header("Content-disposition: attachment; filename=\"" . $this->title . ".csv\"");
            header("Pragma: no-cache");
            header("Content-type: text/csv");
            $this->writer = new textDbWriter('csv');
            $this->writer->setFieldNames($this->fields);
        }
    }
    function add_row($row)
    {
        if ('html' == $this->type)
        {
            echo ("<TR>\n");
            foreach ($this->fields as $field)
            {
                echo ("<TD>");
                if ('volunteer_id' == $field)
                {
                        echo ("<a href=\"../volunteer/?vid=" . $row[$field] . "\">");
                }
                if (0 == strlen(trim($row[$field])))
                {
                        $row[$field] = '&nbsp;';
                }
                echo ($row[$field]);
                if ('volunteer_id' == $field)
                {
                        echo ("</a>");
                }
                echo ("</TD>\n");
            }
            echo ("</TR>\n");
        } elseif ('csv' == $this->type)
        {
                $this->writer->addRow($row);
        }
    }

    function close()
    {
            if ($this->type == 'html')
            {
                echo ("</TABLE>\n");
                $url = make_url($_REQUEST, array());
                // todo: request gives too much
                if ($this->offer_csv)
                    echo ("<P class=\"noprint\"><A href=\"" . $_SERVER['PHP_SELF'] . "$url&amp;download=1\">Download CSV</A></P>\n");
            }
    }
}

function report_display($title, $result, $type)
// type = 'html', 'csv'
// todo: add XML
{
    $nfields = $result->FieldCount();
    $fields = array();
    for ($n = 0; $n < $nfields; $n++)
    {
        $fld = $result->FetchField($n);
        $fields[]  .= $fld->name;
    }
    $report = new report_display($title, $type, $fields);
    $report->begin($result);
    while (!$result->EOF)
    {
        $row = $result->fields;
        $report->add_row($row);
        $result->MoveNext();
    }
    $report->close();

} /* report_display() */

function report_hours()
{
    global $db;
    global $steps;

    // validate
    $errors_found = 0;
    if (!in_array($_REQUEST['step'], $steps))
    {
        process_user_error(_("Please select a step for this report."));
        $errors_found++;
    }
    if ($errors_found)
    {
        reports_menu();
        return;
    }
    // query
    switch ($_REQUEST['step'])
    {
    case 'Day':
        $dateselect = 'date';
        $dategroup = 'group by date';
        break;
         case 'Week';
        $dateselect = 'year(date) as Year, week(date) as Week';
        $dategroup = 'group by year(date), week(date)';
        break;
         case 'Month';
        $dateselect = 'year(date) as Year, month(date) as Month';
        $dategroup = 'group by year(date), month(date)';
        break;
         case 'Year';
        $dateselect = 'year(date) as Year';
        $dategroup = 'group by year(date)';
        break;

     default:
        process_system_error(_("Unexpected step:") . ' ' . $_REQUEST['step']);
        break;
    }
    $sql = "SELECT $dateselect, sum(Hours) as Hours FROM work ";
    $category_id = intval($_REQUEST['category_id']);
    if ($category_id > 0)
    {
        $sql .= "WHERE category_id = $category_id";
    }
    $sql .= " $dategroup ";
    $result = $db->Execute($sql);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else if (0 == $result->RecordCount())
    {
        process_user_notice(("No data available for given critiera."));
    }
    else
    {
    // display

        if (array_key_exists('download', $_REQUEST))
        {
            report_display('AggregateHours', $result, 'csv');
        }
        else
        {
            report_display(_("Aggregate hours"), $result, 'html');
        }
    }
}


function report_active_volunteers()
{
    global $db;
    global $steps;
    // validate
    $errors_found = 0;
    $d1 = sanitize_date($_REQUEST['beginning_date']);
    $d2 = sanitize_date($_REQUEST['ending_date']);    
    if (!$d1 or !$d2)
    {
        //fixme: user should be able to use his own locale's format
        process_user_error(_("Please enter a valid date in the format YYYY-MM-DD or MM/DD/YYYY."));
    }
    if ($errors_found)
    {
        reports_menu();
        return;
    }
    // query
    $d1 = str_replace('-','', $d1);
    $d2 = str_replace('-','', $d2);
    $sql = "SELECT volunteers.volunteer_id, concat_ws(' ',volunteers.first, volunteers.middle, volunteers.last, volunteers.organization) as Volunteer_Name, sum(hours) as Total_Hours FROM work LEFT JOIN volunteers ON work.volunteer_id = volunteers.volunteer_id WHERE work.date between $d1 and $d2 GROUP BY volunteer_id ORDER BY Total_Hours DESC";
    $result = $db->SelectLimit($sql, 30);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    elseif (0 == $result->RecordCount())
    {
        process_user_notice("No data available for given critiera.");
    }
    else
    {
        // display
        if (array_key_exists('download',$_REQUEST))
        {
            report_display("Most active volunteers between $d1 and $d2", $result, 'csv');
        }
        else
        {
            report_display("Most active volunteers between " . htmlentities($d1) . " and " . htmlentities($d2), $result, 'html');
        }
    }
} /* report_active_volunteers() */

function report_volunteers_by_skill()
// this is fairly similar to just searching for volunteers by a skill
{
    global $db;
    // validate
    $errors_found = 0;
    $string_id = intval($_REQUEST['string_id']);
    if ('any' == $_REQUEST['string_id'])
    {
        $string_id = 'any';
    }
    else
    {
        if (!$string_id > 0)
        {
            process_user_error(_("Please choose a skill."));
            $errors_found++;
        }
    }
    if ($errors_found)
    {
        reports_menu();
        return;
    }
    // query
    if (is_integer($string_id))
    {
    // one skill
        $sql = "SELECT volunteers.volunteer_id, concat_ws(' ',volunteers.first, volunteers.middle, volunteers.last, volunteers.organization) as Volunteer_Name, volunteers.email_address as Email_Address, strings.s as Skill " .
    "FROM volunteers " .
    "LEFT JOIN volunteer_skills ON volunteers.volunteer_id = volunteer_skills.volunteer_id " .
    "LEFT JOIN strings ON strings.string_id = volunteer_skills.string_id " .
    "WHERE volunteer_skills.string_id = $string_id " .
    "ORDER BY volunteers.volunteer_id";
    }
    else
    {
    // all skills
        $sql = "SELECT volunteers.volunteer_id, concat_ws(' ',volunteers.first, volunteers.middle, volunteers.last, volunteers.organization) as Volunteer_Name, volunteers.email_address as Email_Address " .
    "FROM volunteers " .
    "LEFT JOIN volunteer_skills ON volunteers.volunteer_id = volunteer_skills.volunteer_id " .
    "GROUP BY volunteer_id ".   
    "ORDER BY volunteers.volunteer_id";
    }
    $result = $db->SelectLimit($sql, 30);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    elseif (0 == $result->RecordCount())
    {
        process_user_notice("No data available for given critiera.");
    }
    else
    {
        $nfields = $result->FieldCount();
        $fields = array();
        for ($n = 0; $n < $nfields; $n++)
        {
            $fld = $result->FetchField($n);
            $fields[]  .= $fld->name;
        }

        if ('any' == $string_id)
        {
            $fields[] = 'Skills';
        }
        $fields[] = 'Phone_Numbers';

        if (array_key_exists('download', $_REQUEST))
        {
            $type = 'csv';
        }
        else
        {
            $type = 'html';
        }
        $report = new report_display(_("Volunteers by skill"), $type, $fields);
        $report->begin($result);
        while (!$result->EOF)
        {
            $row = $result->fields;

            // gather phone numbers
            $result2 = $db->Execute("SELECT number, memo FROM phone_numbers WHERE volunteer_id = " . $row['volunteer_id']);
            if ($result2)
            {
                $phone = "";
                while (!$result2->EOF)
                {
                    $row2 = $result2->fields;
                    if ("" != $phone)
                    {
                        $phone .= ", ";
                    }
                    $phone .= $row2['number'] . ' '. $row2['memo'];
                    $result2->MoveNext();
                }
                $row['Phone_Numbers'] = $phone;

            }
            else
            {
                $row['Phone_Numbers'] = '';
            }
            if ('any' == $string_id)
            {
                // gather skill names
                $sql = "SELECT strings.s as skill, strings.string_id as skill_id " .
                    "FROM volunteer_skills " .
                    "LEFT JOIN strings on volunteer_skills.string_id = strings.string_id " .
                    "WHERE volunteer_skills.volunteer_id = " . $row['volunteer_id'];
                $result2 = $db->Execute($sql);
                if ($result2)
                {
                    $skills = "";
                    while (!$result2->EOF)
                    {
                        $row2 = $result2->fields;
                        if ("" != $skills)
                        {
                            $skills .= ", ";
                        }
                        $skills .= $row2['skill'];
                        $result2->MoveNext();
                    }
                    $row['Skills'] = $skills;
                }
                else
                {
                    die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
                }
            }
            // display this record
            $report->add_row($row);
            $result->MoveNext();
        }
        $report->close();
    }
} /* report_volunteers_by_skill() */


function report_volunteers_hours_by_work_activity()
{
    global $db;
    // validate
    $errors_found = 0;
    $date_start = sanitize_date($_REQUEST['beginning_date']);
    $date_end = sanitize_date($_REQUEST['ending_date']);    
    if (!$date_start or !$date_end)
    {
        //fixme: user should be able to use his own locale's format
        process_user_error(_("Please enter a valid date in the format YYYY-MM-DD or MM/DD/YYYY."));
    }
    if ($errors_found)
    {
        reports_menu();
        return;
    }
    // query
    $sql = <<<EOD
SELECT volunteers.volunteer_id, concat_ws(' ',volunteers.first, volunteers.middle, volunteers.last, volunteers.organization) as Volunteer_Name, Coalesce(category.s, 'None') as Work_Category, sum(hours) as Hours
FROM volunteers
LEFT JOIN work ON work.volunteer_id = volunteers.volunteer_id
        AND "$date_start" <= work.date <= "$date_end"
LEFT JOIN strings as category on category.string_id = work.category_id
GROUP BY volunteers.volunteer_id, work.category_id
ORDER BY volunteers.volunteer_id;
EOD;

    $result = $db->Execute($sql);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    elseif (0 == $result->RecordCount())
    {
        process_user_notice("No data available for given critiera.");
    }
    else
    {
        // no errors, so display report

        // do not use report_display() because we need a subtotal
        echo "<style type=\"text/css\">table { margin-bottom: 1em; page-break-after:always }</style>\n";
        echo "<h1>". _("Volunteer hours by work activity")."</h1>\n";
        $fields = array('volunteer_id', 'Work_Category', 'Hours');
        $report = new report_display($result->fields['Volunteer_Name'], 'html', $fields, FALSE);
        $report->begin($result);
        $subtotal_hours = 0;
        $last_row = NULL;
        while (!$result->EOF)
        {
            $row = $result->fields;
            if ($last_row and $last_row['volunteer_id'] != $row['volunteer_id'])
            {
                $last_row['Work_Category'] = _("Total");
                $last_row['Hours'] = $subtotal_hours;
                $report->add_row($last_row);
                $report->close();
                $report = new report_display($row['Volunteer_Name'], 'html', $fields, FALSE);
                $report->begin($result);
                $subtotal_hours = $hours = $row['Hours'];
            }
            else
                $subtotal_hours += $row['Hours'];
            $report->add_row($row);
            $result->MoveNext();
            $last_row = $row;
        }
        //final person's total
        $last_row['Work_Category'] = _("Total");
        $last_row['Hours'] = $subtotal_hours;
        $report->add_row($last_row);
        $subtotal_hours = $row['Hours'];

        $report->close();

    }
} /* report_volunteers_hours_by_work_activity() */


function reports_menu()
{
    global $db;

    echo ("<H2>"._("Reports")."</H2>\n");

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>" ._("Aggregate hours") . "</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\"reports.php\">\n");
    echo ("<SELECT name=\"step\">\n");
    echo ("<OPTION>--Step</OPTION>\n");
    echo ("<OPTION>"._("Day")."</OPTION>\n");    
    echo ("<OPTION>"._("Week")."</OPTION>\n");    
    echo ("<OPTION>"._("Month")."</OPTION>\n");
    echo ("<OPTION>"._("Year")."</OPTION>\n");    
    echo ("</SELECT>\n");
    $sql = "SELECT * FROM strings WHERE type = 'work'";
    $result = $db->Execute($sql);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    {
        echo ("<SELECT name=\"category_id\">\n");
        echo ("<OPTION>--Project</OPTION>\n");
        echo ("<OPTION value=\"any\">"._("Any")."</OPTION>\n"); 
        while (!$result->EOF)
        {
            $row = $result->fields;
            echo ("<OPTION value=\"".$row['string_id']."\">".$row['s']."</OPTION>\n");
            $result->MoveNext();
        }
    echo ("</SELECT>\n");
    }
    echo ("<BR><INPUT type=\"submit\" name=\"report_hours\" value=\""._("Make report")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>" ._("Most active volunteers"). "</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\"reports.php\">\n");
    echo ("Beginning <INPUT type=\"text\" name=\"beginning_date\" value=\"2000-01-01\" size=\"10\">\n");
    echo ("Ending <INPUT type=\"text\" name=\"ending_date\" value=\"".date('Y-m-d')."\" size=\"10\">\n");
    echo ("<BR><INPUT type=\"submit\" name=\"report_active_volunteers\" value=\""._("Make report")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>" . _("List of volunteers by skill") ."</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\"reports.php\">\n");
    $sql = "SELECT * FROM strings WHERE type = 'skill'";
    $result = $db->Execute($sql);
    if (!$result)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    {
        echo ("<SELECT name=\"string_id\">\n");
        echo ("<OPTION>--Skill</OPTION>\n");
        echo ("<OPTION value=\"any\">"._("Any")."</OPTION>\n"); 
        while (!$result->EOF)
        {
            $row = $result->fields;
            echo ("<OPTION value=\"".$row['string_id']."\">".$row['s']."</OPTION>\n");
            $result->MoveNext();
        }
        echo ("</SELECT>\n");
    }
    echo ("<BR><INPUT type=\"submit\" name=\"report_volunteers_by_skill\" value=\""._("Make report")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");


    echo ("<FIELDSET>\n");
    echo ("<LEGEND>" . _("Volunteer hours by work activity") ."</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\"reports.php\">\n");
    echo ("Beginning <INPUT type=\"text\" name=\"beginning_date\" value=\"2000-01-01\" size=\"10\">\n");
    echo ("Ending <INPUT type=\"text\" name=\"ending_date\" value=\"".date('Y-m-d')."\" size=\"10\">\n");
    echo ("<BR><INPUT type=\"submit\" name=\"report_volunteers_hours_by_work_activity\" value=\""._("Make report")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");

    if (!array_key_exists('download', $_REQUEST))
    {
        make_html_end();
    }
}
?>

