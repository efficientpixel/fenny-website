<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * Backup Pro - Helper Functions
 *
 * Helper Functions
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/helpers/utilities_helper.php
 */

if( ! function_exists('m62_format_date'))
{
	/**
	 * Wrapper to format a date using the Backup Pro settings
	 * @param mixed $date
	 * @param string $format
	 * @param bool $html
	 * @return string
	 */
	function m62_format_date($date, $format = FALSE, $html = FALSE)
	{
		if(ee()->backup_pro->settings['relative_time'] == '1')
		{
			if($html)
			{
				$date = '<span class="backup_pro_timeago" title="'.m62_convert_timestamp($date).'">'.m62_relative_datetime($date).'</span>';
			}
			else
			{
				$date = m62_relative_datetime($date);
			}
		}
		else
		{
			$date = m62_convert_timestamp($date, $format);
		}
	
		return $date;
	}	
}

if( ! function_exists('m62_filesize_format') )
{
	function m62_filesize_format($filesize)
	{
		return ee()->backup_pro->filesize_format($filesize);
	}
}

if ( ! function_exists('m62_convert_timestamp'))
{
	/**
	 * Timestamp Format
	 * Wrapper that takes a string and converts it according to CT Admin settings
	 * @param string $date
	 * @param string $format
	 */
	function m62_convert_timestamp($date, $format = FALSE)
	{
		if(!is_numeric($date))
		{
			$date = strtotime($date);
		}
	
		$EE =& get_instance();
		$EE->load->helper('date');
		if(!$format)
		{
			$format = ee()->backup_pro->settings['date_format'];
		}
	
		return $EE->localize->format_date($format, $date);
	}
}

if( !function_exists('m62_encode_backup') )
{
	/**
	 * Handles encoding string for user facing display
	 * @param string $string
	 * @return string
	 */
	function m62_encode_backup($string)
	{
		return base64_encode(ee()->encrypt->encode($string));
	}
}

if( !function_exists('m62_decode_backup') )
{
	/**
	 * Handles decoding a string encoded from m62_encode_backup()
	 * @param unknown $string
	 */
	function m62_decode_backup($string)
	{
		return ee()->encrypt->decode(base64_decode($string));
	}
}

if( ! function_exists('m62_relative_datetime') )
{
	/**
	 * Creates a date in human readable format (1 hour, 7 years, etc...)
	 * @param string $timestamp
	 * @param string $ending
	 * @return string
	 */
	function m62_relative_datetime($timestamp, $ending = true)
	{
		if(!$timestamp)
		{
			return 'N/A';
		}
	
		if(!is_numeric($timestamp))
		{
			$timestamp = (int)strtotime($timestamp);
		}
	
		if($timestamp == '0')
		{
			return 'N/A';
		}
	
		$difference = time() - $timestamp;
		$periods = array("sec", "min", "hour", "day", "week","month", "year", "decade");
		$lengths = array("60","60","24","7","4.35","12","10");
		$total_lengths = count($lengths);
	
		if($ending)
		{
			if ($difference > 0)
			{
				// this was in the past
				$ending = "ago";
			}
			else
			{
				// this was in the future
				$difference = -$difference;
				$ending = " from now";
			}
		}
	
		for($j = 0; $difference > $lengths[$j] && $total_lengths > $j; $j++)
		{
			$difference /= $lengths[$j];
		}
	
		$difference = round($difference);
		if($difference != 1)
		{
			$periods[$j].= "s";
		}
	
		$text = "$difference $periods[$j] $ending";
		return trim($text);
	}
}

if(!function_exists('mime_content_type')) 
{

	/**
	 * Mimics the behavior of PHP's build in function if it doens't exist
	 * @param string $filename
	 * @return Ambigous <string>|unknown|string
	 */
	function mime_content_type($filename) {

		$mime_types = array(

			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',

			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',

			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = strtolower(array_pop(explode('.',$filename)));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
		elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		}
		else {
			return 'application/octet-stream';
		}
	}	
}

if(!function_exists('m62_theme_url'))
{
	/**
	 * Sets up the third party theme URL
	 * @return string
	 */
	function m62_theme_url()
	{
		$url = '';
		if(defined('URL_THIRD_THEMES'))
		{
			$url = URL_THIRD_THEMES;
		}
		else
		{
			$url = rtrim(ee()->config->config['theme_folder_url'], '/') .'/third_party/';
		}

		return $url;
	}
}

if(!function_exists('m62_theme_path'))
{
	/**
	 * Sets up the third party themes path
	 * @return string
	 */
	function m62_theme_path()
	{
		$path = '';
		if(defined('PATH_THIRD_THEMES'))
		{
			$path = PATH_THIRD_THEMES;
		}
		else
		{
			$path = rtrim(ee()->config->config['theme_folder_path'], '/') .'/third_party/';
		}

		return $path;
	}
}

if(!function_exists('m62_third_party_path'))
{
	/**
	 * Sets up the third party add-ons path
	 * @return string
	 */
	function m62_third_party_path()
	{
		$path = '';
		if(defined('PATH_THIRD'))
		{
			$path = PATH_THIRD;
		}
		else
		{
			$path = APPPATH.'third_party/';
		}

		return $path;
	}
}