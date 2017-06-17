<?php

//
// Edit History:
//
//  Last $Author: munroe $
//  Last Modified: $Date: 2006/07/08 17:48:00 $
//
//  Dick Munroe (munroe@csworks.com) 05-Feb-2006
//      Initial version created (derived from dm.DB/class.DB.php)
//
//  Dick Munroe (munroe@csworks.com) 28-Jun-2006
//      Add support for PostgreSQL 8.1
//      Add support for PostgreSQL 8.0
//

/**
 * @author Dick Munroe <munroe@csworks.com>
 * @copyright copyright @ 2006 by Dick Munroe, Cottage Software Works, Inc.
 * @license http://www.csworks.com/publications/ModifiedNetBSD.html
 * @version 1.1.0
 * @package dm.DB
 * @example ./example.php
 * @example ./example1.php
 */

/**
 * Definitions to use as the database type.
 */

define('dmDB_MySQL', 'MySQL') ;
define('dmDB_PostgreSQL80','PostgreSQL80') ;
define('dmDB_PostgreSQL81','PostgreSQL81') ;

/*
 * If the user doesn't specify which version of PosgreSQL is to be use, it defaults to
 * 8.1
 */

define('dmDB_PostgreSQL', dmDB_PostgreSQL81) ;

class FactoryDB
{
    /**
     * @desc Factory to return specializations for specific databases.
     * @access public
     * @param String $dblogin Login name to access the database.
     * @param String $dbpass Password to access the database.
     * @param String $dbname Name of the database being accessed.
     * @param String [optional] $dbhost Host on which the database resides, defaults
     *               to localhost.  The format of the dbhost parameter is:
     *
     *                  hostname[:port]
     *
     *               if your database server is on a non-standard port, you MUST provide
     *               both the hostname and the port, e.g.:
     *
     *                  localhost:5781
     *                  f.q.d.n:7865
     * @param String [optional] $dbtype the type of database engine to use, defaults
     *               to dmDB_MySQL
     * @return object a dm.DB object suitable for accessing the specified
     *                database.
     */

    function factory($dblogin, $dbpass, $dbname, $dbhost = null, $dbtype = dmDB_MySQL)
    {
        if ($dbtype == dmDB_MySQL)
        {
            include_once 'class.MySQL.DB.php' ;

            return new MySQLDB($dblogin, $dbpass, $dbname, $dbhost) ;
        }
        else if ($dbtype == dmDB_PostgreSQL80)
        {
            include_once 'class.PostgreSQL80.DB.php' ;

            return new PostgreSQL80DB($dblogin, $dbpass, $dbname, $dbhost) ;
        }
        else if ($dbtype == dmDB_PostgreSQL81)
        {
            include_once 'class.PostgreSQL81.DB.php' ;

            return new PostgreSQL81DB($dblogin, $dbpass, $dbname, $dbhost) ;
        }
        else
        {
            trigger_error("Unknown database type: " . $dbtype, E_USER_ERROR) ;
        }
    }

}

?>
