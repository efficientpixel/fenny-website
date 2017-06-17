<?php

//
// Edit History:
//
//  Last $Author: munroe $
//  Last Modified: $Date: 2006/07/02 12:15:52 $
//
//  Dick Munroe (munroe@csworks.com) 05-Feb-2006
//      Initial version created (derived from dm.DB/class.DB.php)
//
//  Dick Munroe (munroe@csworks.com) 16-Mar-2006
//      mysql_real_escape_string is only defined for version of PHP >= 4.3.
//
//  Dick Munroe (munroe@csworks.com) 01-Apr-2006
//      Minor bug in the compatibility routine for real_escape_string.
//
//  Dick Munroe (munroe@csworks.com) 23-Apr-2006
//      Return create string out of showCreateTable.
//
//  Dick Munroe (munroe@csworks.com) 28-Jun-2006
//      Add serverHasTransaction interface.
//      There has been an interface change to db_insert_id to handle general
//      sequences.
//

/**
 * @author Dick Munroe <munroe@csworks.com>
 * @copyright copyright @ 2006 by Dick Munroe, Cottage Software Works, Inc.
 * @license http://www.csworks.com/publications/ModifiedNetBSD.html
 * @version 1.2.0
 * @package dm.DB
 * @example ./example.php
 */

/**
 * MySQL specialization of the AbstractDB class.
 */

include_once 'class.abstract.DB.php' ;

if (!function_exists('mysql_real_escape_string'))
{
    function mysql_real_escape_string_replace_callback($c)
    {
        switch ($c[0])
        {
            case chr(0): return '\\0' ;
            case "\n": return '\\n' ;
            case "\r": return '\\r' ;
            case "\\": return '\\\\' ;
            case "'": return "\\'" ;
            case "\"": return '\\"' ;
            case chr(26): return '\\Z' ;
        }
    }

    /**
     * Versions of PHP prior to 4.3 do not implement the mysql_real_escape_string
     * interface.  In particular, Red Hat version 9.0's default PHP is
     * 4.2 so this is necessary for compatability reasons.
     *
     * @desc real escape string for versions of PHP prior to 4.3.
     * @param string the string to be quoted.
     * @param resource [optional] ignored, used only for compatibility.
     * @return string the MySQL quoted string.
     */

    function mysql_real_escape_string($theString, $theLink = NULL)
    {
        return preg_replace_callback('/[\n\r\\\\\'\"' . chr(26) . '\x00]/', "mysql_real_escape_string_replace_callback",
                                     $theString) ;
    }
}

class MySQLDB extends AbstractDB
{
    function db_specialization()
    {
        return 'MySQL' ;
    }

    /**
     * This is intended primarily as a mechanism for helping PostgreSQL handle
     * the MySQL "get_last_id" functionality.
     *
     * @access protected
     * @return reference to resource the query id of the last executed query.
     */

    function &getQueryid()
    {
        return $this->queryid ;
    }

    /**
     * @desc return the list of tables in the current database.
     * @return array the names of the tables in the current database.
     */

    function &showTables()
    {
        $theTableNames = array() ;

        $this->queryConstant('SHOW TABLES ;') ;

        while ($theTableName =& $this->fetchRow())
        {
            $theTableNames[] = $theTableName[0] ;
        }

        return $theTableNames ;
    }

    /**
     * @desc return the SQL query to create the specified table.
     * @param string the name of the table.
     * @return string the SQL query to create the table.
     */

    function &showCreateTable($theTableName)
    {
        $this->queryConstant(sprintf('SHOW CREATE TABLE `%s` ;', $theTableName)) ;
        $theCreateQuery = $this->fetchRow() ;
        return $theCreateQuery[1] ;
    }

    /**
     * Abstract functions for database access.  Assumes that all MySQL database interfaces
     * are functionally available using the actual database.
     */

    function db_affected_rows($dblink)
    {
        return @mysql_affected_rows($dblink) ;
    }

    function db_close($dblink)
    {
        return @mysql_close($dblink) ;
    }

    function db_connect($dbhost, $dblogin, $dbpass)
    {
        return @mysql_pconnect($dbhost, $dblogin, $dbpass) ;
    }

    function db_data_seek($queryid, $row)
    {
        return @mysql_data_seek($queryid, $row) ;
    }

    function db_error()
    {
        return @mysql_error() ;
    }

    function db_fetch_array($queryid)
    {
        return mysql_fetch_array($queryid) ;
    }

    function db_fetch_assoc($queryid)
    {
        return @mysql_fetch_assoc($queryid) ;
    }

    function db_free_result($result)
    {
        return @mysql_free_result($result) ;
    }

    function db_insert_id($dblink, $theSequenceName = NULL)
    {
        return @mysql_insert_id($dblink) ;
    }

    function db_num_rows($queryid)
    {
        return @mysql_num_rows($queryid) ;
    }

    function db_query($sql, $dblink)
    {
        return @mysql_query($sql, $dblink) ;
    }

    function db_select($dbname, $dblink)
    {
        return @mysql_select_db($dbname, $dblink) ;
    }

    function db_real_escape_string($theString, $dblink)
    {
        return @mysql_real_escape_string($theString, $dblink) ;
    }

    /**
     * Checks to see whether or not the MySQL server supports transactions.
     *
     * @param      dblink, the link (if any) to the database, unused in this implementation.
     * @return     bool
     * @access     public
     */

    function serverHasTransaction($dblink)
    {
        $this->queryConstant('SHOW VARIABLES');

        if ($this->resultExist())
        {
            while ($xxx = $this->fetchRow())
            {
                if ($xxx['Variable_name'] == 'have_bdb' && $xxx['Value'] == 'YES')
                {
                    return true;
                }
                else if ($xxx['Variable_name'] == 'have_gemini' && $xxx['Value'] == 'YES')
                {
                    return true;
                }
                else if ($xxx['Variable_name'] == 'have_innodb' && $xxx['Value'] == 'YES')
                {
                    return true;
                }
            }
        }

        return false;

    } // end function

    /**
     * Constructor
     *
     * @param      String $dblogin, String $dbpass, String $dbname
     * @return     void
     * @access     public
     */

    function MySQLDB($dblogin, $dbpass, $dbname, $dbhost = null)
    {
        $this->AbstractDB($dblogin, $dbpass, $dbname, $dbhost) ;
    }
}

?>
