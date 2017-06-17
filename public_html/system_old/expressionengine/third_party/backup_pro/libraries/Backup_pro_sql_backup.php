<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * mithra62 - Backup Pro
 *
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2015, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/backup-pro/
 * @version		2.0
 * @filesource 	./system/expressionengine/third_party/backup_pro/
 */

/**
 * Add the factory for the DB
 */
include_once('DB/class.factory.DB.php') ;

/**
 * Give us M62_Pclzip
 */
include_once 'pclzip.lib.php';

 /**
 * Backup Pro - SQL Backup Library
 *
 * SQL Backup Library
 *
 * @package 	mithra62\BackupPro\DbBackup
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_sql_backup.php
 */
class Backup_pro_sql_backup
{
    /**
     * @var object for the type of database to be save or restored.
     * @access private
     */
    public $m_dbObject ;
    
    /**
     * @var resource the file pointer for the input/output file.
     * @access private
     */
    public $m_fptr;
    
    /**
     * @var string the name of the output file.
     * @access private
     */
    public $m_output;
    
    /**
     * @var boolean TRUE if only the structure of the database is to be saved.
     * @access private
     */
    public $m_structureOnly;
    
    /**
     * How many statements we want to compile into 1 INSERT command
     * @var unknown
     */
    public $m_group_by = '25';
    
	public function __construct()
    {
        $this->m_structureOnly = FALSE;
        $this->settings = ee()->backup_pro->get_settings();
    }
    
    /**
     * Sets up the BP settings array
     * @param array $settings
     */
    public function set_settings($settings)
    {
    	$this->settings = $settings;
    }    

    /**
     * @desc Restore a backup file.
     * @returns void
     * @access public
     */
    public function restore($store_path, $db_info)
    {
    	$this->db_info = $db_info;
    	if (ee()->extensions->active_hook('backup_pro_db_restore_start') === TRUE)
		{
			ee()->extensions->call('backup_pro_db_restore_start', $db_info);
			if (ee()->extensions->end_script === TRUE) return;
		}
		
		//verify we're only restoring using the method the format supports!
		$parts = explode(ee()->backup_pro->name_sep, $store_path);
		if(!empty($parts['1']) && $parts['1'] == 'mysqldump')
		{
			$this->settings['db_restore_method'] = 'mysql';
		}
		
		switch($this->settings['db_restore_method'])
		{
			case 'mysql':
				$this->mysql_restore($store_path, $db_info);
				break;
		
			case 'php':
			default:
				$this->php_restore($store_path, $db_info);
				break;
		}
        
        if (ee()->extensions->active_hook('backup_pro_db_restore_end') === TRUE)
		{
			ee()->extensions->call('backup_pro_db_restore_end', $db_info);
			if (ee()->extensions->end_script === TRUE) return;
		}        
        
        return true;
    }

    /**
     * @desc public interface for backup.
     * @returns void
     * @access public
     */
    public function backup($store_path, $db_info)
    {
    	$this->db_info = $db_info;
        $this->m_dbObject =& FactoryDB::factory($db_info['user'], $db_info['pass'], $db_info['db_name'], $db_info['host'], dmDB_MySQL) ;
    	$this->m_output = $store_path;
    	$this->m_fptr=fopen($this->m_output,"w");

        $total_items = count($this->m_dbObject->showTables());
        $total_items++;
        
        if($this->settings['s3_access_key'] != '' && $this->settings['s3_secret_key'] != '')
		{
			$total_items++;
		} 

        if($this->settings['cf_api'] != '' && $this->settings['cf_username'] != '')
		{
			$total_items++;
		} 		

		if($this->settings['ftp_hostname'] != '')
		{
			$total_items++;
		}

        if (ee()->extensions->active_hook('backup_pro_db_backup_start') === TRUE)
		{
			ee()->extensions->call('backup_pro_db_backup_start', $db_info);
			if (ee()->extensions->end_script === TRUE) return;
		}		
		
        $this->m_dbObject->queryConstant('SHOW TABLES');
        ee()->backup_pro->write_progress_log(lang('backup_progress_bar_start'), $total_items, 0);
        
        $count = 1;

        //add the configured SQL if it's configured
        if($this->settings['db_backup_archive_pre_sql'])
        {
        	$this->write_out(implode("\n\r", $this->settings['db_backup_archive_pre_sql'])."\n\n\n"); 
        }
        
        //add the user configured SQL if it's configured
        if($this->settings['db_backup_execute_pre_sql'])
        {
        	foreach($this->settings['db_backup_execute_pre_sql'] AS $sql)
        	{
        		if($sql != '')
        		{
        			ee()->db->query($sql);
        		}
        	}
        }

        //get the meta details now just in case we get transaction issues
        $sql = "SHOW TABLE STATUS ";
        $db_meta = ee()->db->query($sql)->result_array();
        
        $this->write_out("SET FOREIGN_KEY_CHECKS = 0;\n\r"); 
        while ($theTable =& $this->m_dbObject->fetchRow())
        {
        	$theTableName = $theTable[0];
        	
        	//ignore the configured tables we're not supposed to backup
        	if(count($this->settings['db_backup_ignore_tables']) >= 1 && in_array($theTableName, $this->settings['db_backup_ignore_tables']))
        	{
        		continue;
        	}
        	
        	//we have to do some magic to ensure we don't drop tables we only want schema for (in case of a restore)
        	if(count($this->settings['db_backup_ignore_table_data']) >= 1 && in_array($theTableName, $this->settings['db_backup_ignore_table_data']))
        	{
        		$theDB = clone($this->m_dbObject) ;
        		$theCreateTable = $theDB->showCreateTable($theTableName) ;
        		$theDB->clear() ;
        		 
        		$theCreateTable = preg_replace('/\s*\n\s*/', ' ', $theCreateTable) ;
        		$theCreateTable = preg_replace('/\(\s*/', '(', $theCreateTable) ;
        		$theCreateTable = preg_replace('/\s*\)/', ')', $theCreateTable) ;
        		        		
        		$replace = substr($theCreateTable, 0, 12);
        		if($replace == 'CREATE TABLE')
        		{
        			$this->write_out('SET sql_notes = 0;      -- Temporarily disable the "Table already exists" warning' . ";\n\r");
        			$this->write_out( str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS ', $theCreateTable) . ";\n\r");
        			$this->write_out('SET sql_notes = 1;      -- And then re-enable the warning again' . ";\n\r");
        			$this->write_out("\n\r");
        		}
        	
        		$theDB->clear() ;
        		continue;
        	}        	
        	
            ee()->backup_pro->write_progress_log(lang('backup_progress_bar_table_start').$theTableName, $total_items, $count);
			switch($this->settings['db_backup_method'])
			{
				case 'mysqldump':
					$this->mysqldump_backup($theTableName, $db_info);
				break;
				
				case 'php':
				default:
					$this->php_backup($theTableName);
				break;
			}
			
            ee()->backup_pro->write_progress_log(lang('backup_progress_bar_table_stop').$theTableName, $total_items, $count);
            $count++;
            
        }  
        
        //add the user configured SQL if it's configured
        if($this->settings['db_backup_archive_post_sql'])
        {
        	$this->write_out("\n".implode("\n", $this->settings['db_backup_archive_post_sql'])."\n\n\n"); 
        }
        
        //add the user configured SQL if it's configured
        if($this->settings['db_backup_execute_post_sql'])
        {
        	foreach($this->settings['db_backup_execute_post_sql'] AS $sql)
        	{
        		if($sql != '')
        		{
        			ee()->db->query($sql);
        		}
        	}
        }
        
        $this->m_dbObject->clear();
        
        if($this->m_fptr!=false)
        {
            fclose($this->m_fptr);
        }

        if(ee()->extensions->active_hook('backup_pro_db_backup_end') === TRUE)
		{
			ee()->extensions->call('backup_pro_db_backup_end', $db_info);
			if(ee()->extensions->end_script === TRUE) return;
		} 

        if(ee()->extensions->active_hook('backup_pro_db_backup_zip_start') === TRUE)
		{
			ee()->extensions->call('backup_pro_db_backup_zip_start', $this->m_output);
			if(ee()->extensions->end_script === TRUE) return;
		}		
        
        $zip = new PclZip62($this->m_output.'.zip');
		if($zip->create($this->m_output, PCLZIP_OPT_REMOVE_ALL_PATH) == 0) 
		{
			return FALSE;
		}      
		unlink($this->m_output);

        if(ee()->extensions->active_hook('backup_pro_db_backup_zip_end') === TRUE)
		{
			ee()->extensions->call('backup_pro_db_backup_zip_end', $this->m_output.'.zip');
			if(ee()->extensions->end_script === TRUE) return;
		}		
		
		ee()->backup_pro->write_progress_log(lang('backup_progress_bar_database_stop'), $total_items, $count);
		
		$path_parts = pathinfo($this->m_output.'.zip');
		$details_path = $path_parts['dirname'];
		$file_name = $path_parts['basename'];
		ee()->backup_details->create_details_file($file_name, $details_path);
		
		//now we save the meta details
		if( $db_meta )
		{
			$meta_details = array();
			$uncompressed_size = 0;
			foreach($db_meta AS $meta)
			{
				$uncompressed_size = $uncompressed_size+$meta['Data_length'];
				$meta_details[] = array(
					'Name' => $meta['Name'],
					'Rows' => $meta['Rows'],
					'Avg_row_length' => $meta['Avg_row_length'],
					'Data_length' => $meta['Name'],
					'Auto_increment' => $meta['Auto_increment'],
				);
			}
			
			ee()->backup_details->add_details($file_name, $details_path, array('items' => $meta_details, 'item_count' => count($meta_details), 'uncompressed_size' => $uncompressed_size));
		}		
		
    	if($this->settings['gcs_access_key'] != '' && $this->settings['gcs_secret_key'] != '')
		{
			$total_items++;
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_start_gcs'), $total_items, $count);
			ee()->load->library('backup_pro_gcs');
			ee()->backup_pro_gcs->move_backup($this->m_output.'.zip', 'database');		
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_stop_gcs'), $total_items, $count);
			ee()->backup_details->add_details($file_name, $details_path, array('GCS' => '1'));
			$count++;
		}
		
    	if($this->settings['s3_access_key'] != '' && $this->settings['s3_secret_key'] != '')
		{
			$total_items++;
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_start_s3'), $total_items, $count);
			ee()->load->library('backup_pro_s3');
			ee()->backup_pro_s3->move_backup($this->m_output.'.zip', 'database');		
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_stop_s3'), $total_items, $count);
			ee()->backup_details->add_details($file_name, $details_path, array('S3' => '1'));
			$count++;
		}

        if($this->settings['cf_api'] != '' && $this->settings['cf_username'] != '')
		{
			$total_items++;
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_start_cf'), $total_items, $count);
			ee()->load->library('backup_pro_cf');
			ee()->backup_pro_cf->move_backup($this->m_output.'.zip', 'database');		
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_stop_cf'), $total_items, $count);
			ee()->backup_details->add_details($file_name, $details_path, array('CF' => '1'));
			$count++;
		}
				
		if($this->settings['ftp_hostname'] != '')
		{
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_start_ftp'), $total_items, $count);	
			ee()->load->library('backup_pro_ftp');
			ee()->backup_pro_ftp->move_backup($this->m_output.'.zip', 'database');		
			ee()->backup_pro->write_progress_log(lang('backup_progress_bar_stop_ftp'), $total_items, $count);
			ee()->backup_details->add_details($file_name, $details_path, array('FTP' => '1'));
		}
		
		ee()->backup_pro->write_progress_log(lang('backup_progress_bar_stop'), $total_items, $total_items);	
        return $this->m_output.'.zip';
    }
    
    /**
     * Executes the command line database restore
     * @param string $store_path
     * @param array $db_info
     */
    public function mysql_restore($store_path, array $db_info)
    {
    	$cnf = $this->create_my_cnf();
    	$command = $this->settings['mysqlcli_command']." --defaults-extra-file=\"$cnf\" ".$db_info['db_name']." < $store_path";
    	system($command);   
    	$this->remove_my_cnf();	 	
    }
    
    /**
     * Executes the PHP database restore
     * @param string $store_path
     * @param array $db_info
     */    
    public function php_restore($store_path, array $db_info)
    {
    	$this->m_dbObject =& FactoryDB::factory($db_info['user'], $db_info['pass'], $db_info['db_name'], $db_info['host'], dmDB_MySQL) ;
    	$this->m_output = $store_path;
    	$this->m_fptr = fopen($this->m_output, "r") ;
    	
    	if ($this->m_fptr === FALSE)
    	{
    		die(sprintf("Can't open %s", $this->m_output)) ;
    	}
    	
    	while (!feof($this->m_fptr))
    	{
    		$theQuery = fgets($this->m_fptr) ;
    		$theQuery = substr($theQuery, 0, strlen($theQuery) - 1) ;
    	
    		if ($theQuery != '')
    		{
    			$this->m_dbObject->query($theQuery) ;
    		}
    	}
    	
    	fclose($this->m_fptr);    	
    }
    
    /**
     * Backs up a single database table using mysql
     * @param string $the_table_name
     * @param array $db_info
     */    
    public function mysqldump_backup($the_table_name, $db_info)
    {
    	$cnf = $this->create_my_cnf();
    	$temp_store = $this->m_output.$the_table_name;
    	$command = $this->settings['mysqldump_command']." --defaults-extra-file=\"$cnf\" ".$db_info['db_name']." $the_table_name > $temp_store";
    	system($command);
    	
    	//now merge the table output with database output
    	$handle = fopen($temp_store,"rtb");
    	while (($buffer = fgets($handle)) !== false) 
    	{
    		fputs($this->m_fptr, $buffer);
    	}

    	fclose($handle);    
    	unlink($temp_store);
    	$this->remove_my_cnf();	
    }
    
    /**
     * Backs up a single database table using PHP
     * @param string $theTableName
     */    
    public function php_backup($theTableName)
    {
    	$theDB = clone($this->m_dbObject) ;
    	$theCreateTable = $theDB->showCreateTable($theTableName) ;
    	$theDB->clear() ;
    	
    	$theCreateTable = preg_replace('/\s*\n\s*/', ' ', $theCreateTable) ;
    	$theCreateTable = preg_replace('/\(\s*/', '(', $theCreateTable) ;
    	$theCreateTable = preg_replace('/\s*\)/', ')', $theCreateTable) ;

    	$this->write_out(sprintf("DROP TABLE IF EXISTS `%s`; \n", $theTableName));
    	$this->write_out($theCreateTable . ";\n");
    	
    	//$this->_Out("/*!40101 SET @saved_cs_client = @@character_set_client */;\n");
    	//$this->_Out("/*!40101 SET character_set_client = utf8 */;\n");
    	//$this->_Out("/*!40101 SET character_set_client = @saved_cs_client */;\n\n");
    	
    	if ($this->m_structureOnly != true)
    	{
    		$theDB->queryConstant(sprintf('SELECT * FROM %s', $theTableName)) ;
    	
    		$theFieldNames = '' ;
    		$count = 0; //we want to compile the SQL statements by groups of $m_group_by
    		$theData = array() ;
    		$totalRows = $theDB->resultCount();
    		$group_by = $this->m_group_by;
    		while ($theDataRow =& $theDB->fetchAssoc())
    		{
    			if ($theFieldNames == '')
    			{
    				$theFieldNames = '`' . implode('`, `', array_keys($theDataRow)) . '`' ;
    			}
    			
    			if($totalRows < $group_by)
    			{
    				$group_by = $totalRows;
    			}    			
    			
    			$theData = array() ;
    			foreach ($theDataRow as $theValue)
    			{
    				$data = '';
    				if(is_null($theValue))
    				{
    					$data = 'NULL';
    				}
    				elseif(is_numeric($theValue))
    				{
    					$data = $theValue;
    				}
    				else
    				{
    					$data = "'".$theDB->escape_string($theValue)."'";
    				}
    				
    				$theData[] = $data;
    			}
    	
    			$theRows[] = '('.implode(', ', $theData).')';
    			$count++;
    			if($count == $group_by || $totalRows == '1')
    			{
    				$line = implode(', ', $theRows);
	    			$theInsert = sprintf("INSERT INTO `%s` (%s) VALUES %s ;\n",
	    					$theTableName, $theFieldNames,
	    					$line);
	
	    			$this->write_out($theInsert);
	    			$theRows = array();
	    			$count = 0;
	    			$group_by = $this->m_group_by;
    			}
    			$totalRows--;
    			
    		}
    	
    		$this->write_out("\n");
    	}
    	
    	$theDB->clear() ;    	
    }
    
    public function create_my_cnf()
    {
    	$data = array(
			'head' => '[client]',
			'user' => 'user = '.$this->db_info['user'],
			'password' => 'password = '.$this->db_info['pass'],
			'host' => 'host = '.$this->db_info['host']
		);
    	
    	$content = implode("\n", $data);
		$path = $this->settings['backup_store_location'].'/my.cnf';
		
		$fp = fopen($path,"wb");
		fwrite($fp,$content);
		fclose($fp);
		return $path;
    }
    
    public function remove_my_cnf()
    {
    	$path = $this->settings['backup_store_location'].'/my.cnf';
    	if(file_exists($path))
    	{
    		unlink($path);
    	}
    }
    
    /**
     * Write a SQL statement to the backup file.
     * @param string The string to be written.
     * @access private
     */
    public function write_out($s)
    {
    	if ($this->m_fptr === false)
    	{
    		echo("$s");
    	}
    	else
    	{
    		fputs($this->m_fptr, $s);
    	}
    }    
}
?>