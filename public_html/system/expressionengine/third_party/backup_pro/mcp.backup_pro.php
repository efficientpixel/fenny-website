<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * mithra62 - Backup Pro
 *
 * @author		Eric Lamb <eric@mithra62.com>
 * @copyright	Copyright (c) 2015, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/backup-pro/
 * @version		2.0
 * @filesource 	./system/expressionengine/third_party/backup_pro/
 */
 
 /**
 * Backup Pro - CP
 *
 * Control Panel class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb <eric@mithra62.com>
 * @filesource 	./system/expressionengine/third_party/backup_pro/mcp.backup_pro.php
 */
class Backup_pro_mcp 
{
	/**
	 * The URL to access the module
	 * @var string
	 */
	public $url_base = '';
	
	/**
	 * The amount of pagination items per page
	 * @var int
	 */
	public $perpage = 10;
	
	/**
	 * The delimiter for the datatables jquery
	 * @var stirng
	 */
	public $pipe_length = 1;
	
	/**
	 * The name of the module; used for links and whatnots
	 * @var string
	 */
	private $mod_name = 'backup_pro';
	
	/**
	 * The name of the class for the module references
	 * @var string
	 */
	public $class = 'Backup_pro';
	
	/**
	 * The breadcrumb override
	 * @var array
	 */
	protected static $_breadcrumbs = array();	
	
	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->db_conf = array(
			'user' => ee()->db->username, 
			'pass' => ee()->db->password,
			'db_name' => ee()->db->database, 
			'host' => ee()->db->hostname
		);

		//load EE stuff
		ee()->load->library('javascript');
		ee()->load->library('table');
		ee()->load->library('encrypt');
		ee()->load->helper('form');
		ee()->load->library('logger');
		ee()->load->helper('file');
		ee()->load->helper('utilities');
		
		ee()->load->model('backup_pro_settings_model', 'backup_pro_settings', TRUE);	
		ee()->load->library('backup_pro_lib', null, 'backup_pro');
		ee()->load->library('backup_pro_sql_backup');	
		$this->settings = ee()->backup_pro->get_settings();
		
		$this->query_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->mod_name.AMP.'method=';
		$this->url_base = BASE.AMP.$this->query_base;
		
		ee()->backup_pro->set_url_base($this->url_base);
		ee()->backup_pro->set_backup_dir($this->settings['backup_store_location']);
		ee()->backup_pro->set_db_info($this->db_conf);
		$this->backup_location = $this->settings['backup_store_location'];
		$this->progress_tmp = ee()->backup_pro->progress_log_file = $this->settings['backup_store_location'].'/progress.data';
		$this->errors = ee()->backup_pro->error_check();
		
		$nav_links = ee()->backup_pro->get_right_menu();
		ee()->load->vars(
			array(
				'url_base' => $this->url_base, 
				'query_base' => $this->query_base, 
				'settings' => $this->settings, 
				'nav_links' => $nav_links,
				'theme_folder_url' => m62_theme_url()
			)
		);
		
		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->mod_name, ee()->lang->line('backup_pro_module_name'));
		ee()->cp->set_right_nav($nav_links);	
		
		$method = ee()->input->get_post('method', TRUE);
		if($this->settings['max_db_backups'] > '0' && $method != 'progress')
		{
			ee()->backup_pro->cleanup_backup_count('database', $this->settings['max_db_backups']);
		}

		if($this->settings['max_file_backups'] > '0' && $method != 'progress')
		{
			ee()->backup_pro->cleanup_backup_count('files', $this->settings['max_file_backups']);
		}

		$this->total_space_used = ee()->backup_pro->get_space_used();
		if($this->total_space_used > $this->settings['auto_threshold'] && $method != 'progress')
		{
			ee()->backup_pro->cleanup_auto_threshold_backups($this->settings['auto_threshold'], $this->total_space_used);
		}

		$ignore_methods = array('orders', 'customers', 'products');
		$method = ee()->input->get('method', TRUE);
		if($this->settings['disable_accordions'] === FALSE && !in_array($method, $ignore_methods))
		{
			//remove accordions
		}		
		
		ee()->cp->add_to_foot('<link type="text/css" rel="stylesheet" href="'.m62_theme_url().'backup_pro/css/backup_pro.css" />');
		ee()->cp->add_to_foot('<link type="text/css" rel="stylesheet" href="'.m62_theme_url().'backup_pro/css/chosen.css" />');
		ee()->cp->add_to_foot('<script type="text/javascript" src="'.m62_theme_url().'backup_pro/js/chosen.jquery.js"></script>');
		ee()->cp->add_to_foot('<script type="text/javascript" src="'.m62_theme_url().'backup_pro/js/backup_pro.js"></script>');
	}
	
	private function add_breadcrumb($link, $title)
	{
		ee()->cp->set_breadcrumb($link, $title);
	}	
	
	public function index()
	{			
		$vars = array();
		$vars['errors'] = $this->errors;
		$vars['paths'] = array(
			'db' => ee()->backup_pro->backup_db_dir, 
			'files' => ee()->backup_pro->backup_files_dir
		);
			
		ee()->view->cp_page_title = ee()->lang->line('index');
		$backups = ee()->backup_pro->get_backups();
		$vars['backup_meta'] = ee()->backup_pro->get_backup_meta($backups);
		
		$backups = $backups['database'] + $backups['files'];
		krsort($backups, SORT_NUMERIC);
		
		if(count($backups) > $this->settings['dashboard_recent_total'])
		{
			//we have to remove a few
			$filtered_backups = array();
			$count = 1;
			foreach($backups AS $time => $backup)
			{
				$filtered_backups[$time] = $backup;
				if($count >= $this->settings['dashboard_recent_total'])
				{
					break;
				}
				$count++;
			}
			$backups = $filtered_backups;
		}
		
		$vars['backups'] = $backups;
		ee()->jquery->tablesorter('#backups table', '{headers: {6: {sorter: false}, 0: {sorter: false}}, widgets: ["zebra"], sortList: [[3,1]]}'); 
		
		ee()->javascript->compile();

		$vars['settings'] = $this->settings;
		$vars['method'] = ee()->input->get_post('method');
		$vars['menu_data'] = ee()->backup_pro->get_dashboard_view_menu();
		$vars['available_space'] = ee()->backup_pro->get_available_space();
		return ee()->load->view('index', $vars, TRUE); 
	}	
	
	public function db_backups()
	{			
		$vars = array();
		$vars['errors'] = $this->errors;
		$vars['paths'] = array(
			'db' => ee()->backup_pro->backup_db_dir, 
			'files' => ee()->backup_pro->backup_files_dir
		);
			
		ee()->view->cp_page_title = ee()->lang->line('database_backups');
		$vars['backups'] = ee()->backup_pro->get_backups();	
		$vars['backup_meta'] = ee()->backup_pro->get_backup_meta($vars['backups']);
		
		ee()->jquery->tablesorter('#database_backups table', '{headers: {5: {sorter: false}, 0: {sorter: false}, 6: {sorter: false}}, widgets: ["zebra"], sortList: [[3,1]]}');
		ee()->javascript->compile();

		$vars['method'] = ee()->input->get_post('method');
		$vars['menu_data'] = ee()->backup_pro->get_dashboard_view_menu();
		return ee()->load->view('db_backups', $vars, TRUE); 
	}	
	
	public function file_backups()
	{			
		$vars = array();
		$vars['errors'] = $this->errors;
		$vars['paths'] = array(
			'db' => ee()->backup_pro->backup_db_dir, 
			'files' => ee()->backup_pro->backup_files_dir
		);
			
		ee()->view->cp_page_title = ee()->lang->line('file_backups');
		$vars['backups'] = ee()->backup_pro->get_backups();		
		$vars['backup_meta'] = ee()->backup_pro->get_backup_meta($vars['backups']);
		
		ee()->jquery->tablesorter('#file_backups table', '{headers: {5: {sorter: false}, 0: {sorter: false}, 6: {sorter: false}}, widgets: ["zebra"], sortList: [[3,1]]}');
		ee()->javascript->compile();

		$vars['method'] = ee()->input->get_post('method');
		$vars['menu_data'] = ee()->backup_pro->get_dashboard_view_menu();
		
		return ee()->load->view('file_backups', $vars, TRUE); 
	}	
	
	public function backup()
	{
		$type = ee()->input->get_post('type', TRUE);
		ee()->view->cp_page_title = ee()->lang->line($type);
		$proc_url = FALSE;
		switch($type)
		{
			case 'backup_db':
				$proc_url = $this->url_base.'backup_db';
			break;
			case 'backup_files':
				$proc_url = $this->url_base.'backup_files';
			break;
		}
		
		if(!$proc_url)
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('can_not_backup'));
			ee()->functions->redirect($this->url_base.'index');			
			exit;
		}
		
		ee()->cp->add_js_script('ui', 'progressbar'); 
		ee()->javascript->output('$("#progressbar").progressbar({ value: 0 });'); 
		ee()->javascript->compile();

		$vars = array('proc_url' => $proc_url);
		$vars['errors'] = $this->errors;
		$vars['proc_url'] = $proc_url;
		$vars['url_base'] = $this->url_base;
		$vars['backup_type'] = $type;
		$vars['menu_data'] = ee()->backup_pro->get_dashboard_view_menu();
		$vars['method'] = '';
		return ee()->load->view('backup', $vars, TRUE);
	}
	
	public function progress()
	{
		session_write_close();
		die(file_get_contents($this->progress_tmp));
	}	
	
	public function backup_db()
	{
		session_write_close();
		ini_set('memory_limit', -1);
		set_time_limit(3600);	
		$path = ee()->backup_pro->make_db_filename();
		if(ee()->backup_pro_sql_backup->backup($path, $this->db_conf))
		{	
			ee()->logger->log_action(ee()->lang->line('log_database_backup'));	
			exit;
		}	
	}
	
	public function backup_files()
	{
		session_write_close();
		//some systems have a low(ish) memory limit so we have to remove that.
		ini_set('memory_limit', -1);
		set_time_limit(3600);

		if(ee()->backup_pro->backup_files())
		{
			ee()->logger->log_action(ee()->lang->line('log_file_backup'));
			ee()->session->set_flashdata('message_success', ee()->lang->line('file_backup_created'));
			ee()->functions->redirect($this->url_base.'index');
			exit;
		}
		else
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('file_backup_failure'));
			ee()->functions->redirect($this->url_base.'index');	
			exit;			
		}		
	}
	
	public function download_backup()
	{
		$file_name = m62_decode_backup(ee()->input->get_post('id', TRUE));
		$type = ee()->input->get_post('type');
		if($type == 'files')
		{
			$file = $this->backup_location.'/files/'.$file_name;
		}
		else
		{
			$file = $this->backup_location.'/database/'.$file_name;	
		}
		
		if(file_exists($file))
		{
			ee()->logger->log_action(ee()->lang->line('log_backup_downloaded'));
			$new_name = ee()->backup_pro->make_pretty_filename($file_name, $type);
			ee()->backup_pro->file_download($file, $new_name);
			exit;
		}
		else
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('db_backup_not_found'));
			ee()->functions->redirect($this->url_base.'index');	
			exit;			
		}
	}
	
	/**
	 * The Delete Backulp Control Panel Confirmation page
	 */	
	public function delete_backup_confirm()
	{
		$toggle = ee()->input->get_post('toggle', TRUE);
		if(!$toggle || count($toggle) == 0)
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('backups_not_found'));
			ee()->functions->redirect($this->url_base.'index');	
			exit;			
		}
		
		$backups = array('database' => array(), 'files' => array());
		$i = 0;
		foreach($toggle AS $backup)
		{
			$file = m62_decode_backup($backup);
			$fullpath = ee()->backup_pro->backup_dir.'/'.$file;
			if(!file_exists($fullpath))
			{
				continue;
			}
			
			$parts = explode('/', $file);
			$type = (substr($file, 0, 5) == 'files' ? 'files' : 'database');
			$backups[$type][$i] = ee()->backup_pro->parse_filename($parts['1'], $parts['0']);
			$backups[$type][$i]['path'] = $file;
			$backups[$type][$i]['type'] = $type;
			$i++;
		}
		
		if(count($backups) == '0')
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('backups_not_found'));
			ee()->functions->redirect($this->url_base.'index');
			exit;
		}
		
		ee()->view->cp_page_title = ee()->lang->line('delete_backup');
		ee()->load->vars(array('download_delete_question' => ee()->lang->line('delete_backup_confirm')));
		
		$vars = array();
		$vars['form_action'] = $this->query_base.'delete_backups';
		$vars['backup_meta'] = ee()->backup_pro->get_backup_meta($backups);

		$backups = array_merge($backups['database'], $backups['files']);
		$vars['backups'] = $backups;
		$vars['menu_data'] = ee()->backup_pro->get_dashboard_view_menu();
		$vars['method'] = ee()->input->get_post('method');
		return ee()->load->view('delete_confirm', $vars, TRUE);
	}
	
	/**
	 * The Control Panel action page for removing a backup
	 */	
	public function delete_backups()
	{
		$backups = ee()->input->get_post('delete', TRUE);
		$remove_s3 = ee()->input->get_post('remove_s3', FALSE);
		$remove_cf = ee()->input->get_post('remove_cf', FALSE);
		$remove_ftp = ee()->input->get_post('remove_ftp', FALSE);
		$remove_gcs = ee()->input->get_post('remove_gcs', FALSE);

		$backups = array_map('m62_decode_backup',$backups);
		if(ee()->backup_pro->delete_backups($backups, $remove_ftp, $remove_s3, $remove_cf, $remove_gcs))
		{
			ee()->logger->log_action(ee()->lang->line('log_backup_deleted'));
			ee()->session->set_flashdata('message_success', ee()->lang->line('backups_deleted'));
			ee()->functions->redirect($this->url_base.'index');	
			exit;			
		}
		
		ee()->session->set_flashdata('message_failure', ee()->lang->line('backup_delete_failure'));
		ee()->functions->redirect($this->url_base.'index');
		exit;	
				
	}
	
	/**
	 * The Restore Database Control Panel Confirmation page
	 */	
	public function restore_db_confirm()
	{
		$backup = m62_decode_backup(ee()->input->get_post('id', TRUE));
		$file = $this->backup_location.'/database/'.$backup;
		if(!file_exists($file))
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('db_backup_not_found'));
			ee()->functions->redirect($this->url_base.'index');	
			exit;	
		}		
		
		$vars = array();
		$vars['backup_details'] = ee()->backup_pro->parse_filename($backup, 'database');
		$vars['backup'] = m62_encode_backup($backup);
		$vars['form_action'] = $this->query_base.'restore_db';
		ee()->view->cp_page_title = ee()->lang->line('restore_db');
		return ee()->load->view('restore_confirm', $vars, TRUE);
	}

	/**
	 * The Control Panel action page for processing a database restore
	 */	
	public function restore_db()
	{
		$path = $this->backup_location.'/database/'.m62_decode_backup(ee()->input->get_post('restore_db', TRUE));
		if(!file_exists($path))
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('db_backup_not_found'));
			ee()->functions->redirect($this->url_base.'index');	
			exit;				
		}
		
		$tmp = $this->backup_location.'/database/tmp';
		if(!file_exists($tmp))
		{
			mkdir($tmp);
		}
				
		$path = ee()->backup_pro->unzip_db_backup($path, $tmp);	
		if(ee()->backup_pro_sql_backup->restore($path, $this->db_conf))
		{
			ee()->backup_pro->delete_dir($tmp);
			ee()->session->set_flashdata('message_success', ee()->lang->line('database_restored'));
			ee()->functions->redirect($this->url_base.'index');	
			exit;	
		}
	}
	
	/**
	 * The Ajax endpoint for updating a backup's note
	 */
	public function update_backup_note()
	{
		$backup = ee()->input->get_post('backup');
		$note_text = ee()->input->get_post('note_text');
		if($note_text && $backup)
		{
			$backup = m62_decode_backup($backup);
			$file = rtrim($this->backup_location, '/').'/'.$backup;
			if(file_exists($file))
			{
				$details = pathinfo($file);
				ee()->backup_details->add_details($details['basename'], $details['dirname'], array('note' => $note_text));
				echo json_encode(array('success'));
			}
		}
		exit;
	}
	
	public function l()
	{
		session_write_close();
		ee()->backup_pro->l();
		exit;
	}
	
	/**
	 * The Settings Control Panel page
	 */
	public function settings()
	{
		if(ee()->input->get_post('go_settings'))
		{
			if(ee()->input->get_post('ftp_hostname'))
			{
				ee()->load->library('backup_pro_ftp');
				ee()->backup_pro_ftp->test_connection($_POST);
			}
			
			if(ee()->input->get_post('s3_access_key', false) != '' && ee()->input->get_post('s3_secret_key', false) != '')
			{
				ee()->load->library('backup_pro_s3');
				ee()->backup_pro_s3->test_connection($_POST);
			}

			if(ee()->input->get_post('cf_username', false) != '' && ee()->input->get_post('cf_api', false) != '')
			{
				ee()->load->library('backup_pro_cf');
				ee()->backup_pro_cf->test_connection($_POST);
			}

			if(ee()->input->get_post('gcs_access_key', false) != '' && ee()->input->get_post('gcs_secret_key', false) != '')
			{
				ee()->load->library('backup_pro_gcs');
				ee()->backup_pro_gcs->test_connection($_POST);
			}
			
			if(ee()->backup_pro->update_settings($_POST))
			{	
				ee()->logger->log_action(ee()->lang->line('log_settings_updated'));
				ee()->session->set_flashdata('message_success', ee()->lang->line('settings_updated'));
				ee()->functions->redirect($this->url_base.'settings'.AMP.'section='.ee()->input->get_post('section'));		
				exit;			
			}
			else
			{
				ee()->session->set_flashdata('message_failure', ee()->lang->line('settings_update_fail'));
				ee()->functions->redirect($this->url_base.'settings');	
				exit;					
			}
		}
		
		$vars = array();
		$vars['errors'] = $this->errors;
		$vars['paths'] = array(
			'db' => ee()->backup_pro->backup_db_dir, 
			'files' => ee()->backup_pro->backup_files_dir
		);
		
		$this->settings['ftp_username'] = ee()->encrypt->decode($this->settings['ftp_username']);
		$this->settings['ftp_password'] = ee()->encrypt->decode($this->settings['ftp_password']);
		$this->settings['s3_access_key'] = ee()->encrypt->decode($this->settings['s3_access_key']);
		$this->settings['s3_secret_key'] = ee()->encrypt->decode($this->settings['s3_secret_key']);
		$this->settings['cf_username'] = ee()->encrypt->decode($this->settings['cf_username']);
		$this->settings['cf_api'] = ee()->encrypt->decode($this->settings['cf_api']);
		$this->settings['gcs_access_key'] = ee()->encrypt->decode($this->settings['gcs_access_key']);
		$this->settings['gcs_secret_key'] = ee()->encrypt->decode($this->settings['gcs_secret_key']);
		
		$vars['settings'] = $this->settings;
		$vars['total_space_used'] = ee()->backup_pro->filesize_format($this->total_space_used);
		$vars['member_groups'] = ee()->backup_pro_settings->get_member_groups();
		$vars['db_tables'] = ee()->backup_pro_settings->get_db_tables();
		$vars['cron_commands'] = ee()->backup_pro->get_cron_commands($this->class);
		
		ee()->load->library('Backup_pro_notify', null, 'notify');
		$vars['allowed_notify_members'] = ee()->notify->get_allowed_notify_members($this->class);
		$vars['email_format_options'] = ee()->backup_pro->get_email_format_options();

		$vars['settings_disable'] = FALSE;
		$vars['threshold_options'] = ee()->backup_pro->get_threshold_options();	
		$vars['type'] = ee()->input->get_post('section', 'general');
		$vars['menu_data'] = ee()->backup_pro->get_settings_view_menu();	
		ee()->view->cp_page_title = ee()->lang->line('settings_breadcrumb_'.$vars['type']);
		
		$this->add_breadcrumb($this->url_base.'settings'.AMP.'section=general', lang('settings'));
		
		return ee()->load->view('settings', $vars, TRUE);
	}
}