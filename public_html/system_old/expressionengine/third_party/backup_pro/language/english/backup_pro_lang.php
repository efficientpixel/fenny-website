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
 * Language Array
 * ExpressionEngine language translation array
 * @var array
 */
$lang = array(

// Required for MODULES page

'backup_pro_module_name'		=> 'Backup Pro 2',
'backup_pro_module_description'	=> 'Interface to create database and file backups of your site. ',

//----------------------------------------

// Additional Key => Value pairs go here

// END

'index' => 'Dashboard',
'no_backups' => 'No Backups',
'backups' => 'Backups',
'database_backups' => 'Database Backups',
'backup_db' => 'Backup Database',
'db_backup_created' => 'Database Backup Created',
'file_backup_created' => 'File Backup Created',
'database_backups' => 'Database Backups',
'file_name' => 'File Name',
'backup_files' => 'Backup Files',
'file_backups' => 'File Backups',
'delete_selected' => 'Delete Selected',
'delete_backup' => 'Delete Backups',
'action' => 'Action',
'download' => 'Download',
'restore' => 'Restore',
'date_taken' => 'Date Taken',
'db_backup_not_found' => 'Database Download Not Found',
'file_size' => 'File Size',
'file_backup_failure' => 'Couldn\'t Create File Backup',
'db_backup_failure' => 'Couldn\'t Create Database Backup',
'backups_not_found' => 'Backup Not Found',
'delete_backup_confirm' => 'Are you sure you want to remove the below backups?',
'restore_db_question' => 'Are you sure you want to restore the below database?',
'database_restored' => 'Database Restored!',
'files' => 'Files',
'value' => 'Value',
'settings' => 'Settings',
'backup_file_location' => 'File Backup Location',
'backup_file_locations' => 'File Backup Locations',
'backup_file_location_instructions' => 'Put simply; what do you want to include for the file backup? Put the full path, one per line that you want to backup.',
'backup_store_location' => 'Backup Store Location',
'backup_store_location_instructions' => 'Where do you want to store your backups? Ideally, this wouldn\'t be in your site\'s document root (for security) but if it is it won\'t be included within the file backup. Remember to make this directory writable by your webserver so chmod it to either 0666 or 0777.',
'license_number' => 'License Number',
'configure_backups' => 'Backup Settings',
'configure_cron' => 'Configure Cron',
'enable_cron' => 'Enable Cron',
'cron_control' => 'Cron Control',
'cron_notify_member_ids' => 'Notification Members',
'cron_notify_member_ids_instructions' => 'The members who should recieve a notification upon successful Cron execution.',
'cron_attach_backups' => 'Attach Backups To Email',
'cron_attach_backups_instructions' => 'By default Backup Pro will send a link to download the email but if you\'d like to have the backup files sent as an attachment with the notification email we can do that too. ',
'cron_attach_threshold' => 'Attachment Max Size',
'cron_attach_threshold_instructions' => 'Depending on the size of your site the backups can get pretty large. Sometimes, too large for your email provider. If the attachment size is in bytes larger than the value here links the backups aren\'t attached and links to download are included instead. ',
'auto_threshold_instructions' => 'Over time the amount of space used by Backup Pro can be quite considerable. To keep things sane Backup Pro can watch the space used and respond accoringly by removing older backups to make space for newer backups. Be sure to enter the maximum amount in bytes. If set to 0 than no threshold is enforced.',
'auto_threshold' => 'Auto Prune Threshold',
'allowed_access_levels' => 'Allowed Access Levels',
'allowed_access_levels_instructions' => 'Backup Pro will initially only allow Super Admins access the settings, regardless of who can access the module, but if you need to allow other groups select them from the list.',
'settings_updated' => 'Settings Updated',
'settings_update_fail' => 'Couldn\'t Update Settings',

'cron_command_instructions' => 'Use the below commands for your Cron based on the type of backup you\'d like to automate. ',
'cron_control_instructions' => 'To make sure requests to the Cron functionality is secured you have to include a random query paramater to each request. Initially, Backup Pro creates this for you but if you\'d like to change it do so here',
'exclude_paths' => 'Exclude Paths',
'exclude_paths_instructions' => 'By default Backup Pro will backup everything within your site\'s document root but for some sites that just won\'t work. If you want to exclude anything from the backup put the full path to the document or file here, one per line. ',

'files_dir_not_writable' => 'Files backup directory is not writable. Make sure the permissions for "#files_dir#" are set to 0666 or 0777.',
'files_dir_missing' => 'Files backup directory is missing. Make sure "#files_dir#" exists and is writable.',
'db_dir_not_writable' => 'Database backup directory is not writable. Make sure the permissions for "#db_dir#" are set to 0666 or 0777.',
'db_dir_missing' => 'Database backup directory is missing. Make sure "#db_dir#" exists and is writable.',
'database' => 'Database',
'type' => 'Type',
'restore_db' => 'Restore Database',
'backups_deleted' => 'Backups Deleted',
'back_dir_not_writable' => 'The backup directory isn\'t writable!',
'module_instructions' => 'Backup Pro is an advanced backup management module for EE 2.0 that allows administrators the ability to 
						  backup and restore their site\'s database as well as backing up the entire file system. Both the 
						  files and database backups are compressed to save space and available for download.',

'cron_txt_message' => '',

'file_backup' => 'File Backup',
'db_backup' => 'Database Backup',
'combined' => 'Combined Backup (both file and database in one run)',

'missing_license_number' => 'Please enter your license number. <a href="#config_url#">Enter License</a> or <a href="https://mithra62.com/projects/view/backup-pro">Buy A License</a>',

'configure_ftp' => 'Configure FTP Sync',
'ftp_hostname' => 'FTP Hostname',
'ftp_hostname_instructions' => 'The address or domain to the remote server. Don\'t include any prefix like http:// or ftp://',
'ftp_username' => 'FTP Username',
'ftp_username_instructions' => 'If you don\'t know what this is there\'s a good chance you\'ll have to talk to your host to get FTP sync up and running. ',
'ftp_password' => 'FTP Password',
'ftp_password_instructions' => 'The password is encrypted for security before storage.',
'ftp_port' => 'FTP Port',
'ftp_port_instructions' => 'The default is 21 but if your host uses a differnt port for FTP update it here.',
'ftp_passive' => 'Passive Mode',
'ftp_passive_instructions' => 'If checked then all transfers will be done using the PASV method. ',
'ftp_store_location' => 'FTP Store Location',
'ftp_store_location_instructions' => 'Where on the remote server do you want to store the backups. This directory has to exist in before the settings can be saved.',
'ftp_directory_missing' => 'The FTP remote directory doesn\'t exist.',

'configure_s3' => 'Configure Amazon S3 Sync',
's3_access_key' => 'Access Key ID',
's3_access_key_instructions' => 'Your Access Key ID identifies you as the party responsible for your S3 service requests. You can find this by signing into your <a href="http://aws.amazon.com" target="_blank">Amazon Web Services account</a>',
's3_secret_key' => 'Secret Access Key',
's3_secret_key_instructions' => 'This key is just a long string of characters (and not a file) that you use to calculate the digital signature that you include in the request. For security, both your Access key and Secret key are encrypted before storage.',
's3_bucket' => 'Bucket Name',
's3_bucket_instructions' => 'This is basically the master folder name your backups will be stored in. If it doesn\'t exist it\'ll  be created. If you don\'t enter a bucket name one will be created for you.',


'configure_cf' => 'Configure Rackspace Cloud Files',
'cf_username' => 'Rackspace Username',
'cf_username_instructions' => 'Use your Rackspace Cloud username as the username for the API. For security, both your Access key and Secret key are encrypted before storage.',
'cf_api' => 'API Access key',
'cf_api_instructions' => 'Obtain your API access key from the Rackspace Cloud Control Panel in the <a href="https://manage.rackspacecloud.com/APIAccess.do" target="_blank">Your Account</a>. For security, both your Access key and Secret key are encrypted before storage.',
'cf_bucket' => 'Bucket Name',
'cf_bucket_instructions' => 'This is basically the master folder name your backups will be stored in. If it doesn\'t exist it\'ll  be created. If you don\'t enter a bucket name one will be created for you.',
'cf_connect_fail' => 'The Rackspace Cloud Files credentials aren\'t correct.',
'cf_location' => 'Account Location',
'cf_location_instructions' => 'You can determine the location to use based on the Rackspace retail site which was used to create your account. <a href="http://www.rackspacecloud.com">US</a> or <a href="http://www.rackspace.co.uk">UK</a>.',

'cf_location_types' => array('us' => 'US', 'uk' => 'UK'),
'log_database_backup' => 'Database backup taken.',
'log_file_backup' => 'File backup taken.',
'log_backup_downloaded' => 'Backup downloaded.',
'log_backup_deleted' => 'Backups deleted.',
'log_settings_updated' => 'Backup Pro settings updated',

'backup_in_progress_instructions' => '<strong>DO NOT DO THE FOLLOWING UNTIL THE BACKUP IS COMPLETE:</strong><br />
    1. Close your browser<br />
    2. Reload this page<br />
    3. Navigate away from this page<br />
',
'backup_in_progress' => 'Backup Running...',
'backup_progress_bar_start' => 'Backup Starting...',
'backup_progress_bar_table_start' => 'Starting backup of table: ',
'backup_progress_bar_table_stop' => 'Completed backup of table: ',
'backup_progress_bar_database_stop' => 'Completed database backup.',
'backup_progress_bar_start_file_exclude' => 'Starting file exclusion list...',
'backup_progress_bar_stop_file_exclude' => 'Completed file exclusion list...',
'backup_progress_bar_start_file_list' => 'Starting file generation list...',
'backup_progress_bar_stop_file_list' => 'Completed file generation list...',
'backup_progress_bar_create_archive' => 'Creating the archive...',
'backup_progress_bar_start_s3' => 'Starting transfer to S3 (this may take a minute)...',
'backup_progress_bar_stop_s3' => 'Completed transfer to S3...',
'backup_progress_bar_start_ftp' => 'Starting FTP transfer to remote server (this may take a minute)...',
'backup_progress_bar_stop_ftp' => 'Completed FTP transfer to remote server',
'backup_progress_bar_start_cf' => 'Starting transfer to Rackspace Cloud (this may take a minute)...',
'backup_progress_bar_stop_cf' => 'Completed transfer to Rackspace Cloud...',
'invalid_license_number' => 'Your license number is invalid. Please <a href="#config_url#">enter your valid license</a> or <a href="https://mithra62.com/projects/view/backup-pro">buy a license</a>.',

'backup_progress_bar_stop' => 'Backup Completed!',

'max_db_backups' => 'Maximum Database Backups',
'max_db_backups_instructions' => 'Enter the maximum amount of database backups you want to store locally. Note that only local backups (remote and local) will be removed. Enter 0 to disable.',
'max_file_backups' => 'Maximum File Backups',
'max_file_backups_instructions' => 'Enter the maximum amount of file backups you want to store locally. Note that only local backups (remote and local) will be removed. Enter 0 to disable.',

'date_format' => 'Date Format',
'date_format_instructions' => 'The date format you want Backup Pro to use when displaying backups. Note that the format should conform to the <a href="http://expressionengine.com/user_guide/templates/date_variable_formatting.html#date-formatting-codes" target="_blank">ExpressionEngine date format</a>.',

'taken_on' => 'Taken On',
'size' => 'Size',

'nav_backup_pro' => 'Backup Pro',
'nav_backup_db' => 'Backup Database',
'nav_backup_files' => 'Backup Files',
'nav_dashboard' => 'Dashboard',
'nav_backup_pro_settings' => 'Settings',
'db_backup_method' => 'Database Backup Method',
'db_backup_method_instructions' => 'Depending on how your system is setup the default mysqldump method may not work. Essentially, if you have a the "system" command disabled on your PHP server you should use the PHP method but if you\'re having performance issues you should use MySQLDUMP.',
'db_restore_method' => 'Database Restore Method',
'db_restore_method_instructions' => 'The Database restore method to use. MySQL requires access to the "system" PHP function and the "mysql" system command. Note that this is dependant on the backup method used. PHP restore can only restore PHP backups but MySQL can restore either backup method. This is handled gracefully for backwards compatibility.',
'ftp_prune_remote' => 'Prune FTP Backups',
'ftp_prune_remote_instructions' => 'Should Backup Pro include the remote files in the Auto Prune and Maximum Backup limits?.',
's3_prune_remote' => 'Prune S3 Backups',
's3_prune_remote_instructions' => 'Should Backup Pro include the remote files in the Auto Prune and Maximum Backup limits?.',
'cf_prune_remote' => 'Prune Cloud Files Backups',
'cf_prune_remote_instructions' => 'Should Backup Pro include the remote files in the Auto Prune and Maximum Backup limits?',

'no_backups_exist' => 'No backups exist yet.',
'no_database_backups' => 'No database backups exist yet.',
'no_file_backups' => 'No file backups exist yet.',
'would_you_like_to_backup_now' => 'Would you like to take a backup now?',
'would_you_like_to_backup_database_now' => 'Would you like to take a database backup now?',

'config_db' => 'Configure Database Backups',
'config_files' => 'Configure File Backups',

'backup_state_unstable' => 'A backup hasn\'t been taken in over 6 months. You should take a backup ASAP...',

//dashboard
'home_bp_dashboard_menu' => 'Dashboard',
'files_bp_dashboard_menu' => 'File Backups',
'db_bp_dashboard_menu' => 'Database Backups',
'recent_backups' => 'Recent Backups',
'database_backup' => 'DB Backup',
'total_backups' => 'Total Backups',
'total_space_used' => 'Total Space Used',
'last_backup_taken' => 'Last Backup Taken',
'total_space_available' => 'Total Space Available',
'first_backup_taken' => 'First Backup Taken',
'na' => 'N/A',
'no_backups_exist_yet' => 'No backups have been taken yet; you should create one ASAP. ',

//settings menu
'general_bp_settings_menu' => 'General',
'db_bp_settings_menu' => 'Database Backup',
'files_bp_settings_menu' => 'File Backup',
'cf_bp_settings_menu' => 'Cloud Files',
'ftp_bp_settings_menu' => 'FTP',
's3_bp_settings_menu' => 'Amazon S3',
'gcs_bp_settings_menu' => 'Google Cloud',
'cron_bp_settings_menu' => 'Cron Backup',
'integrity_agent_bp_settings_menu' => 'Integrity Agent',

//general settings
'relative_time' => 'Relative Time',
'relative_time_instructions' => 'If enabled, dates in the CP will be displayed using human readable format instead of strict dates/times.',
'dashboard_recent_total' => 'Dashboard Recent Backup Count',
'dashboard_recent_total_instructions' => 'How many backups should be displayed on the Dashboard under the Recent Backups section?',

//db settings
'config_extra_archive_sql' => 'Configure Archived SQL Dump (Advanced Users Only!)',
'db_backup_archive_pre_sql' => 'Archive Additional SQL (top)',
'db_backup_archive_pre_sql_instructions' => 'If configured, the included SQL statement(s) will be included in the database archive before anything else is added. It should be a single SQL query per line, and use proper syntax and escaping. Your SQL will NOT be modified in any way by Backup Pro. Use at your own risk.',
'db_backup_archive_post_sql' => 'Archive Additional SQL (bottom)',
'db_backup_archive_post_sql_instructions' => 'If configured, the included SQL statement(s) will be added into the database archive as the very last SQL statements in the archive. It should be a single SQL query per line, and use proper syntax and escaping. Your SQL will NOT be modified in any way by Backup Pro. Use at your own risk.',
'config_execute_sql' => 'Configure Additional SQL Commands (Advanced Users Only!)',
'db_backup_execute_pre_sql' => 'Execute Additional SQL (start)',
'db_backup_execute_pre_sql_instructions' => 'If configured, the included SQL statement will be executed against the database, using an arbitrary connection, before any backup centric SQL command is called. Your SQL will NOT be modified in any way by Backup Pro. Use at your own risk.',
'db_backup_execute_post_sql' => 'Execute Additional SQL (end)',
'db_backup_execute_post_sql_instructions' => 'If configured, the included SQL statement will be executed against the database, using an arbitrary connection, after the backup file has been completely written to the backup archive. Your SQL will NOT be modified in any way by Backup Pro. Use at your own risk.',
'config_ignore_sql' => 'Configure Exclude Data',
'db_backup_ignore_tables' => 'Exclude Tables',
'db_backup_ignore_tables_instructions' => 'Which, if any, tables would you like excluded from the database backups? Any selected tables will be ignored and not archived.',
'db_backup_ignore_table_data' => 'Exclude Data',
'db_backup_ignore_table_data_instructions' => 'Any selected tables will have only their schema archived; any associated data will be ignored. ',
'db_backup_alert_threshold' => 'Database Backup Alert Frequency',
'db_backup_alert_threshold_instructions' => 'How many days are backups supposed to be ran? If a bacukp hasn\'t been ran in as many days as set here a notification will be sent alerting the system administrators. Enter 0 to disable.',

'db_backup_past_expectation' => 'A database backup hasn\'t happened in %1$s! You should take a <a href="%2$s">database backup</a> ASAP to ensure system stability. ',
'files_backup_past_expectation' => 'A file backup hasn\'t happened in %1$s! You should take a <a href="%2$s">file backup</a> ASAP to ensure system stability. ',

//file settings
'file_backup_alert_threshold' => 'File Backup Alert Frequency',
'file_backup_alert_threshold_instructions' => 'How many days are backups supposed to be ran? If a bacukp hasn\'t been ran in as many days as set here a notification will be sent alerting the system administrators. Enter 0 to disable.',

//google cloud storage
'configure_gcs' => 'Configure Google Cloud Storage',
'gcs_access_key' => 'Access Key ID',
'gcs_access_key_instructions' => 'Your Access Key ID identifies you as the party responsible for your Google Cloud Storage service requests. You can find this by signing into your <a href="http://aws.amazon.com" target="_blank">Amazon Web Services account</a>',
'gcs_secret_key' => 'Secret Access Key',
'gcs_secret_key_instructions' => 'This key is just a long string of characters (and not a file) that you use to calculate the digital signature that you include in the request. For security, both your Access key and Secret key are encrypted before storage.',
'gcs_bucket' => 'Bucket Name',
'gcs_bucket_instructions' => 'This is basically the master folder name your backups will be stored in. If it doesn\'t exist it\'ll  be created. If you don\'t enter a bucket name one will be created for you.',
'gcs_prune_remote' => 'Prune Google Cloud Storage Backups',
'gcs_prune_remote_instructions' => 'Should Backup Pro include the remote files in the Auto Prune and Maximum Backup limits?.',

//notes
'click_to_add_note' => 'Click to add note...',
'note' => 'Note',

'no_db_backups_exist_yet' => 'No database backups exist yet; click <a href="%s">here</a> to take one.',
'no_file_backups_exist_yet' => 'No file backups exist yet; click <a href="%s">here</a> to take one.',
'unlimited' => 'Unlimited',
'taken' => 'Taken',
'remote_status' => 'Remote Status',
'md5_hash' => 'MD5 Hash',

//breadcrumbs
'settings_breadcrumb_general' => 'General',
'settings_breadcrumb_db' => 'Database Backup',
'settings_breadcrumb_files' => 'File Backup',
'settings_breadcrumb_cron' => 'Cron Backup',
'settings_breadcrumb_integrity_agent' => 'Integrity Agent',
'settings_breadcrumb_cf' => 'Cloud Files',
'settings_breadcrumb_s3' => 'Amazon S3',
'settings_breadcrumb_gcs' => 'Google Cloud',
'settings_breadcrumb_ftp' => 'FTP',

//integrity agent
'configure_integrity_agent_backup_missed_schedule' => 'Configure Missed Backup Email',
'default_backup_missed_schedule_notify_email_subject' => '{site_name} - Backup State Notification',
'default_backup_missed_schedule_notify_email_message' => 'Hello,<br /><br />

A {backup_type} backup hasn\'t been completed on {site_name} since {last_backup_date}. A {backup_type} backup is expected to run every {backup_frequency} day(s) so something is clearly wrong; you should investigate ASAP<br /><br />

{site_name}<br />
{site_url}<br /><br />

Please don\'t respond to this email; all emails are automatically deleted. ',
'backup_missed_schedule_notify_member_ids' => 'Notification Members',
'backup_missed_schedule_notify_member_ids_instructions' => 'The members who should recieve a notification upon when the backup schedule isn\'t followed.',
'backup_missed_schedule_notify_email_mailtype' => 'Email Format',
'backup_missed_schedule_notify_email_mailtype_instructions' => 'Type of mail email message the Missed Backup Schedule email should be sent in. If you send HTML email you must send it as a complete web page. Make sure you don\'t have any relative links or relative image paths otherwise they will not work.',
'backup_missed_schedule_notify_email_subject' => 'Missed Backup Schedule Email Subject',
'backup_missed_schedule_notify_email_subject_instructions' => 'The subject you want the Missed Backup Schedule email to have. You can use global template tags but nothing fancy.',
'backup_missed_schedule_notify_email_message' => 'Missed Backup Schedule Email Message',
'backup_missed_schedule_notify_email_message_instructions' => 'The email message that gets sent when a backup finishes. You can use global template tags only.',
'backup_missed_schedule_notify_email_interval' => 'Notification Interval',
'backup_missed_schedule_notify_email_interval_instructions' => 'How much time, in hours, between when an email should be sent.',

//cron
'configure_cron_email_attachment' => 'Configure Email Attachments',
'configure_cron_notification' => 'Configure Email Notification',
'default_cron_subject' => '{site_name} - Backup Pro Cron Notification ({backup_type})',
'cron_notify_email_subject' => 'Cron Complete Notification Email Subject',
'cron_notify_email_subject_instructions' => 'The subject you want the Cron Completion Notification email to have. You can use global template tags but nothing fancy.',
'cron_notify_email_message' => 'Cron Complete Notification Email Message',
'cron_notify_email_message_instructions' => 'The email message that gets sent when a backup finishes. You can use global template tags only.',
'cron_notify_email_mailtype' => 'Email Format',
'cron_notify_email_mailtype_instructions' => 'Type of mail email message the Backup Completion email should be sent in. If you send HTML email you must send it as a complete web page. Make sure you don\'t have any relative links or relative image paths otherwise they will not work.',
'default_cron_message' => 'Hello,<br /><br />

Your backup has ran successfully.<br /><br />

Filesize: {file_size}<br /><br />

{site_name}<br />
{site_url}<br /><br />

Please don\'t respond to this email; all emails are automatically deleted. ',
'backup_type' => 'Backup Type',
'cron_commands' => 'Cron Commands',
'test' => 'Test',

'nav_view_backups' => 'View Backups',
'total_items' => 'Total Items',
'raw_file_size' => 'Raw File Size',
''=>''
		
		
		
		
		
		
		
		
);