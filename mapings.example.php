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

/**
 * Update the following mappings to the proper values used in your redmine install.
 * Every mapping behaves in the same maner: an array where the key is the value 
 * that exists in the CSV exported file from Pivotal Tracker and the value is the 
 * ID for the database record that we want to map to.
 */
//pivotal user => redmine user ID
$users = array(
    //should always be the first user
    'admin' => 1
        //other users
        //'<user visible name>' => <user ID in redmine>,
);

//pivotal type => redmine tracker ID
$types = array(
        //'chore' => <tracker ID in redmine>,
        //'bug' => <tracker ID in redmine>,
        //'feature' => <tracker ID in redmine>,
);

//pivotal state => reminde issue status ID
$statuses = array(
    'unscheduled' => 1, //New
    'unstarted' => 1, //New
    'rejected' => 1, //New
    'started' => 2, //In Progress
    'finished' => 3, // Resolved
    'delivered' => 3, //Resolved
    'accepted' => 5//Closed
);

//list of possible "closing" states, usually just 'accepted'
$closingStatus = array('accepted');