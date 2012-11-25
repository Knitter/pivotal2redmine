<?php

/**
 * Pivotal2Redmine is a simple set of scripts to allow importing Pivotal Tracker 
 * issues to Redmine.
 * 
 * Copyright (C) 2012  Sérgio Lopes
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
echo <<<LICENSE

    Pivotal2Redmine  Copyright (C) 2012  Sérgio Lopes
    This program comes with ABSOLUTELY NO WARRANTY;
    This is free software, and you are welcome to redistribute it
    under certain conditions;
LICENSE;

if ($argc != 3) {
    echo "\n\nInvalid parameter count. Required project ID and file name.\n";
    exit(1);
}

if (!(int) $argv[1]) {
    echo "\n\nProject ID is invalid.\n";
    exit(1);
}

$project = (int) $argv[1];
$file = $argv[2];

if (!file_exists($file)) {
    echo "\n\nImport file not found.\n";
    exit(1);
}

require 'config.php';
require 'mappings.php';

import($project, $file);


// Functions that are used by the script, just to keep a bit of sanity.

/**
 * Executes the import action, calling any helper functions along the process.
 * 
 * @global string $server
 * @global string $username
 * @global string $password
 * @global string $database
 * 
 * @param integer $project
 * @param string $file
 */
function import($project, $file) {
    global $server, $username, $password, $database;
    $failed = false;

    if (($fp = fopen($file, 'r'))) {

        if (!mysql_connect($server, $username, $password) || !mysql_select_db($database)) {
            echo "\n\nUnable to connect to the database.\n";
            exit(1);
        }

        if (!mysql_query('SET AUTOCOMMIT=0') || !mysql_query('START TRANSACTION')) {
            echo "\nWarning: Transaction not set.\n";
        }

        //simply drop first line that contains titles
        fgetcsv($fp);
        while (($line = fgetcsv($fp))) {
            $title = mysql_real_escape_string($line[1]);
            //$labels = $line[2];
            $tracker = getTracker($line[6]);
            $status = getStatus($line[8]);
            $ratio = (isClosingStatus($line[8]) ? 100 : 0);
            $created = strtotime($line[9]); //e.g.: Aug 27, 2012
            $author = getUser($line[12]);
            $assigned = getUser($line[13]);
            $description = mysql_real_escape_string($line[14] . "\n" . getExtraDescText($line[4], $line[5], $line[7], $line[15]));

            $sql = <<<SQL
INSERT INTO issues VALUES (
    NULL, {$tracker}, {$project}, '{$title}', '{$description}',
    NULL, NULL, {$status}, {$assigned}, 1, NULL, {$author}, 0, {$created}, 
    NULL, NULL, {$ratio}, NULL, NULL, NULL, NULL, NULL, 0
)        
SQL;
            if (!mysql_query($sql)) {
                echo "\nFatal: Unable to insert all records.\n" . mysql_error();
                mysql_query('ROLLBACK');
                mysql_query('SET AUTOCOMMIT=1');

                $failed = true;
                break;
            }
        }
        fclose($fp);

        if (!$failed) {
            if (!mysql_query('COMMIT')) {
                echo "\n\nFatal: Could not commit data changes.\n" . mysql_error();
            }
            mysql_query('SET AUTOCOMMIT=1');
        } else {
            echo "\n\nData import failed.";
            exit(1);
        }
    }
    echo "\n\nImport finished.\n\n";
}

/**
 * 
 * @global array $users
 * 
 * @param string $user
 * 
 * @return integer
 */
function getUser($user) {
    global $users;

    return (isset($users[$user]) ? $users[$user] : 1);
}

/**
 * 
 * @global array $statuses
 * 
 * @param string $state
 * 
 * @return integer
 */
function getStatus($state) {
    global $statuses;

    return (isset($statuses[$state]) ? $statuses[$state] : 'NULL');
}

/**
 * 
 * @global array $types
 * 
 * @param string $type
 * 
 * @return integer
 */
function getTracker($type) {
    global $types;

    return (isset($types[$type]) ? $types[$type] : 'NULL');
}

/**
 * Builds the extra text added to the description of each imported issue.
 * 
 * @param string $iterationStart
 * @param string $iterationEnd
 * @param integer $estimate
 * @param string $url
 * 
 * @return string
 */
function getExtraDescText($iterationStart, $iterationEnd, $estimate, $url) {
    return <<<TXT
Imported from pivotal tracker:
    Iteration Start: {$iterationStart};
    Iteration End: {$iterationEnd};
    Estimated Points: {$estimate};
    URL: {$url};
TXT;
}

/**
 * Determines if the given Pivotal state representes a valid
 * 
 * @global string $closingStatus From the mappings.php file.
 * 
 * @param string $state The state that was provided in the Pivotal import file.
 * 
 * @return bool True if this is a state that should indicate that the issue is closed 
 * and, usually, have a completion ratio of 100.
 */
function isClosingStatus($state) {
    global $closingStatus;

    return in_array($state, $closingStatus);
}