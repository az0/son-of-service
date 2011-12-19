<?php

/*
 * Son of Service
 * Copyright (C) 2003-2011 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Generates artificial volunteers for testing.
 *
 * $Id: randomvolunteer.php,v 1.7 2011/12/19 04:48:33 andrewziem Exp $
 *
 */


$run_from_web_server = FALSE;

$num_volunteers = 50;
$print = TRUE;
$use_db = TRUE;

$names[] = 'Andrew';
$names[] = 'Carol';
$names[] = 'Chris';
$names[] = 'Christina';
$names[] = 'Kevin';
$names[] = 'George';
$names[] = 'Hank';
$names[] = 'Harry';
$names[] = 'Jennifer';
$names[] = 'Joe';
$names[] = 'Lucy';
$names[] = 'Luke';
$names[] = 'Mark';
$names[] = 'Matthew';
$names[] = 'Miguel';
$names[] = 'Mike';
$names[] = 'Pedro';
$names[] = 'Peter';
$names[] = 'John';

$surnames[]  = 'Anderson';
$surnames[]  = 'Bennett';
$surnames[]  = 'Black';
$surnames[]  = 'Brown';
$surnames[]  = 'Bush';
$surnames[]  = 'Daniels';
$surnames[]  = 'Green';
$surnames[]  = 'Jackson';
$surnames[]  = 'Madison';
$surnames[]  = 'Sanchez';
$surnames[]  = 'Simpson';
$surnames[]  = 'Smith';
$surnames[]  = 'Rodriguez';
$surnames[]  = 'Von Trapp';
$surnames[]  = 'White';

$streets[] = 'Broadway';
$streets[] = 'Colorado';
$streets[] = 'Elm';
$streets[] = 'Industy';
$streets[] = 'Main';
$streets[] = 'Pennsylvania';


if (!$run_from_web_server and (array_key_exists('SERVER_PORT', $_SERVER)))
{
    die("You cannot access this page from there.");
}

define ('SOS_PATH', '../');

if ($use_db)
{
    require_once(SOS_PATH . 'include/config.php');
    require_once(SOS_PATH . 'functions/db.php');

    echo ("Connecting to database.\n");
    $db = connect_db();

    if (!$db)
    {
        die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
    }
}

list($usec, $sec) = explode(' ', microtime());
$s= (float) $sec + ((float) $usec * 100000);
mt_srand($s);

while ($num_volunteers)
{
    $first = $names[array_rand($names)];
    $last = $surnames[array_rand($surnames)];
    $street = mt_rand(1,9999)." ".$streets[array_rand($streets)];
    usleep(mt_rand(1,50000));
    $sql = "INSERT INTO volunteers (first, last, street, email_address) VALUES ('$first', '$last', '$street', '$first.$last@doesnotexist.com')";
    if ($print)
        echo ($sql."\n");
    if ($use_db)
    {
        $result = $db->Execute($sql);
        if (!$result)
        {
            echo ("Fatal error: ".$db->ErrorMsg());
            die();
        }
        $id = $db->Insert_ID();
        echo "id = $id\n";
        if ($id)
        {
            // fake work history
            $t = time() - mt_rand(1, 60*60*24*365*5);
            for ($i = mt_rand(1,100); $i > 1; $i--)
            {

                $d = date('Y-m-d', $t + mt_rand(1, 60*60*24*365));
                $hours = mt_rand(1,5);
                $category_id = mt_rand(14,16);
                $sql = "INSERT INTO work (date, hours, volunteer_id, category_id) VALUES ('$d', $hours, $id, $category_id);";
                $result = $db->Execute($sql);
                if (!$result)
                {
                    die("Error inserting data into database: ".$db->ErrorMsg());
                }
            }
        }
    }

    $num_volunteers--;
}


?>
