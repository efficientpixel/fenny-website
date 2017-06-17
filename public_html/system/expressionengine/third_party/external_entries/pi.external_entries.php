<?php 

$plugin_info = array(
						'pi_name'			=> 'External Entries',
						'pi_version'		=> '2.6.4',
						'pi_author'			=> 'Engaging.net',
						'pi_author_url'		=> 'http://engaging.net',
						'pi_description'	=> 'Update, insert into, select and delete from any MySQL database table from within an EE template. Compatible with both EE v1.x and v2.x.',
						'pi_usage'			=> External_entries::usage()
					);

/**
 * External_entries class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Engaging.net
 * @copyright		Copyright (c) 2010-2015 Engaging.net
 * @link			http://engaging.net/products/external-entries/
 */

/*
 * EE Syntax changes v1 -> v2
 *
 * $TMPL -> $this->EE->TMPL
 * $DB -> $this->EE->db
 * query->result -> query->result_array()
*/

class External_entries 
{
	var $return_data;
	
	function External_entries($str = '')
	{
	}

	// ------------ HELPER FUNCTIONS -------------

	function _get_params($version)
	{
		// Set EE version 1 or 2
		if ($version == "1")
		{
			global $DB, $TMPL;
		}
		if ($version == "2")
		{
			$DB = $this->EE->db;
			$TMPL = $this->EE->TMPL;
		}

		// Get parameters

		$params['allow_php'] = $DB->escape_str( $TMPL->fetch_param('allow_php') );
		$params['debug'] = $DB->escape_str( $TMPL->fetch_param('debug') );
		$params['limit'] = $DB->escape_str( $TMPL->fetch_param('limit') );
		$params['orderby'] = $DB->escape_str( $TMPL->fetch_param('orderby') );
		$params['order_by'] = $DB->escape_str( $TMPL->fetch_param('order_by') );
		$params['operator'] = strtoupper($TMPL->fetch_param('operator')) == 'OR' ? 'OR' : 'AND';
		$params['sort'] = $DB->escape_str( $TMPL->fetch_param('sort') );
		$params['table'] = $DB->escape_str( $TMPL->fetch_param('table') );
		$params['operator'] = $DB->escape_str( $TMPL->fetch_param('operator') );
		if 
		(
			isset($params['operator'])
			&&
		 	strcasecmp($params['operator'], "OR" ) == 0
		)
		{
			$params['operator'] = "OR";
		}
		else
		{
			$params['operator'] = "AND";
		}

		$params['hostname'] = $DB->escape_str( $TMPL->fetch_param('hostname') );
		$params['username'] = $DB->escape_str( $TMPL->fetch_param('username') );
		$params['password'] = $DB->escape_str( $TMPL->fetch_param('password') );
		$params['database'] = $DB->escape_str( $TMPL->fetch_param('database') );
		$params['connection'] = $DB->escape_str( $TMPL->fetch_param('connection') );

		$params['distinct'] = $DB->escape_str( $TMPL->fetch_param('distinct') );

		return $params;
	}

	function _get_db_location($params)
	{
		$location="internal";
		if ( $params['hostname'] || $params['username'] || $params['password'] || $params['database'] )
		{
			$location="external";
		}
		return $location;
	}

	function _connect_db($params, $location)
	{
		$error_message="";
		$conni = "";
		if ( $location == "external" )
		{
			// We're on an external database
			if ($params['hostname'] && $params['username'] && $params['password'] && $params['database'])
			{	
				if ($params['connection'] == "persistent")
				{
					$conn = mysqli_connect("p:" . $params['hostname'], $params['username'], $params['password'] ) or die("<p>Cannot connect to external database.</p>");
				}
				else
				{
					$conni = mysqli_connect( $params['hostname'], $params['username'], $params['password'], $params['database'] ) or die("<p>Cannot connect to external database.</p>");
				}
			}
			else
			{
				$error_message .= "<p>Connecting to an external database requires a hostname, username, password and the database name.</p>";
			}
		}
		return $conni;
	}

	function _get_filters($version)
	{
		// Set EE version 1 or 2
		if ($version == "1")
		{
			global $TMPL;
		}
		if ($version == "2")
		{
			$TMPL = $this->EE->TMPL;
		}

		// EE has a thing for "search:"-prefixed parameters
		$filters=$TMPL->search_fields;
		return $filters;
	}


	// Updated this for v2.6 after seeing Croxton with his Search Entries went to mod.channel.php's generate_field_search_sql($search_fields, $site_ids) function to ensure things work the EE way
	function _insert_filters($filters, $operator)
	{
		$sql = '';
		$sql_conditions = '';
		$field_sql = '';
		// print_r($filters);
		foreach ($filters as $col_name => $terms)
		{
			$field_sql = $col_name; // Just cos this is what they used for Entries

			if (strncmp($terms, '=', 1) ==  0)
			{
				// echo "<li>EXACT: " . $field_sql;
				
				/** ---------------------------------------
				/**  Exact Match e.g.: search:body="=pickle"
				/** ---------------------------------------*/
				
				$terms = substr($terms, 1);
				
				// special handling for IS_EMPTY
				if (strpos($terms, 'IS_EMPTY') !== FALSE)
				{
					$terms = str_replace('IS_EMPTY', '', $terms);
					
					$add_search = $this->EE->functions->sql_andor_string($terms, $field_sql);
					
					// remove the first AND output by $this->EE->functions->sql_andor_string() so we can parenthesize this clause
					$add_search = substr($add_search, 3);
              	
					$conj = ($add_search != '' && strncmp($terms, 'not ', 4) != 0) ? 'OR' : 'AND';
              	
					if (strncmp($terms, 'not ', 4) == 0)
					{
						$sql_conditions .= $operator.' ('.$add_search.' '.$conj.' '.$field_sql.' != "") ';
					}
					else
					{
						$sql_conditions .= $operator.' ('.$add_search.' '.$conj.' '.$field_sql.' = "") ';
					}
				}
				else
				{
					 $condition = $this->EE->functions->sql_andor_string($terms, $field_sql).' ';
					// replace leading AND/OR with desired operator
					 $condition =  preg_replace('/^AND|OR/', $operator, $condition,1);
					$sql_conditions .= $condition;
				}
			}
			else
			{
				// echo "<li>LIKE: " . $field_sql;
				/** ---------------------------------------
				/**  "Contains" e.g.: search:body="pickle"
				/** ---------------------------------------*/
				
				if (strncmp($terms, 'not ', 4) == 0)
				{
					$terms = substr($terms, 4);
					$like = 'NOT LIKE';
				}
				else
				{
					$like = 'LIKE';
				}
				
				if (strpos($terms, '&&') !== FALSE)
				{
					$terms = explode('&&', $terms);
					$andor = (strncmp($like, 'NOT', 3) == 0) ? 'OR' : 'AND';
				}
				else
				{
					$terms = explode('|', $terms);
					$andor = (strncmp($like, 'NOT', 3) == 0) ? 'AND' : 'OR';
				}
				
				$sql_conditions .= ' '.$operator.' (';
				
				foreach ($terms as $term)
				{
					if ($term == 'IS_EMPTY')
					{
						$sql_conditions .= ' '.$field_sql.' '.$like.' "" '.$andor;
					}
					elseif (strpos($term, '\W') !== FALSE) // full word only, no partial matches
					{
						$not = ($like == 'LIKE') ? ' ' : ' NOT ';
						$term = '([[:<:]]|^)'.addslashes(preg_quote(str_replace('\W', '', $term))).'([[:>:]]|$)';
						$sql_conditions .= ' '.$field_sql.$not.'REGEXP "'.$this->EE->db->escape_str($term).'" '.$andor;
					}
					else
					{
						$sql_conditions .= ' '.$field_sql.' '.$like.' "%'.$this->EE->db->escape_like_str($term).'%" '.$andor;
					}
				}
				$sql_conditions = substr($sql_conditions, 0, -strlen($andor)).') ';
			} // <- END if (strncmp($terms, '=', 1) ==  0)
		} // <- END foreach ($filters as $col_name => $terms)

		if ($sql_conditions != '')
		{
			$sql_conditions = ltrim($sql_conditions, 'AND ');
			$sql_conditions = ltrim($sql_conditions, 'OR ');
			$sql = $sql.' ('.$sql_conditions.')';
		}

		return $sql;
	}
	/*
	{
		$i="0";
		$sql="";
		foreach($filters as $filter_field => $filter_value)
		{
			$j="0";
			$sql .= "(";
			foreach($filter_value as $filter_field_index => $filter_field_value)
			{
				$j++;
				if ($j > 1)
				{
					$sql .= " OR ";
				}
				$sql .= " $filter_field = '$filter_field_value' ";
			}
			$sql .= ")";
			$i++;
			if ($i > 0)
			{
				$sql .= " " . $params['operator'] . " ";
			}
		}
		$sql = rtrim($sql, " " . $params['operator'] . " ");
		return $sql;
	}
	*/
	
	function _get_set($caller, $table, $version, $conni)
	{
		// Declare some variables
		$fields="";
		
		// Set EE version 1 or 2
		if ($version == "1")
		{
			global $TMPL;
		}
		if ($version == "2")
		{
			$TMPL = $this->EE->TMPL;
		}
		
		$set = "";
		$sql = "SHOW COLUMNS FROM $table";
		$f = "";
		if ($conni != "")
		{
			$query = mysqli_query($conni, $sql);
			while ($field = mysqli_fetch_assoc($query))
			{
				$f++;
				$fields[$f] = $field['Field'];
			}
		}
		else
		{
			$query = $this->EE->db->query($sql);
			foreach($query->result_array() as $field)
			{
				$f++;
				$fields[$f] = $field['Field'];
			}
		}

		// Loop through the tagdata once for each column, finding which fields to set)
		$tagdata = $TMPL->tagdata;
		foreach($fields as $field)
	    {
			$field_start = explode("{" . $caller . ":" . $field . "}", $tagdata);
			if ( isset($field_start[1]) )
			{
				if ($field_start[1] != "" && $field_start[1] != $tagdata)
				{
					$second_half = $field_start[1];
					if ($version == "1")
					// EE v1.x
					{
						$field_surround = explode( "{&#47;" . $caller . ":" . $field . "}", $second_half);
					}
					if ($version == "2")
					// EE v2.x
					{
						$field_surround = explode( "{/" . $caller . ":" . $field . "}", $second_half);
					}
					$value = $field_surround[0];
					if ($value != $second_half)
					{
					 	$set[$field] = $value;
					}
				}
			}
	    }
		return $set;
	}
	
	function _insert_set($set, $allow_php, $version)
	{
		$sql="";
		if ($version == "1")
		{
			global $REGX;
		}
		if ($version == "2")
		{
			$REGX = $this->EE->security;
		}
		if ($set)
		{
			$sql .= "SET ";
		}
		$set_sql = "";
		if ($set)
		{
			foreach($set as $field => $insert)
			{
				$insert = addslashes($insert);
				if ($allow_php == "y" || $allow_php == "Y" || $allow_php == "Yes" || $allow_php == "yes")
				{
					$set_sql .= $field . " = " . "\"" . $insert . "\", ";
				}
				else
				{
					$set_sql .= $field . " = " . "\"" . $REGX->xss_clean($insert) . "\", ";
				}
			}
		}
		$set_sql = rtrim($set_sql, ", ");
		$sql .= $set_sql . " ";
		return $sql;
	}

	function _display_debug($caller, $error_message, $sql)
	{
		$output = "<div class='select-entries-message'><p><b>External Entries (" . $caller . ") Debug is on. Errors:</b></p><ul>";
		if ($error_message == "")
		{
			$output .= "<li>No errors!</li>";
		}
		else
		{
			$output .= "<li>" . $error_message . "</li>";
		}
		$output .= "</ul>";
		if ($sql != "")
		{
			$output .= "<p><b>External Entries (" . $caller . ") SQL:</b> " . $sql . "</p>";
		}
		$output .= "</div>";
		return $output; 
	}

	function _perform_command($caller, $debug, $error_message, $sql, $location)
	{
		if ($debug=="Yes" || $debug=="yes" || $debug=="Y" || $debug=="y")
		{ 
			$output = $this->_display_debug($caller, $error_message, $sql);
			return $output;
		}
		else
		{
			if (! $error_message)
			{
				if ($location == "external")
				{
					$query = mysqli_query($conni, $sql);
				}
				else
				{
					$query = $this->EE->db->query($sql);
				}
				
			}
		}
	}

	// ------------ PARENT FUNCTIONS ------------------------------------------------

	function Select()
	{
		// 1. PRELIMINARIES

		// Are we running EE 1.x or 2.x?
		global $TMPL, $DB, $REGX;
		$version = "";
		if ( $TMPL )
		{
			$version = "1";
		}
		else
		{
			$version = "2";
		}
		if ($version == "2")
		{
			$this->EE =& get_instance();
			$TMPL = $this->EE->TMPL;
			$DB = $this->EE->db;
			$REGX = $this->EE->security;
		}

		// Declare variables
		$error_message="";
		$output = "";
		$location="internal";
		$conn="";
		$sql="";

		// Get parameters
		$params = $this->_get_params($version);

		// Set parameter defaults if not specified
		if ($params['allow_php'] == "")
		{
			$params['allow_php'] = "n";
		}
		if ($params['debug'] == "")
		{
			$params['debug'] = "n";
		}
		if ($params['limit'] == "")
		{
			$params['limit'] = "100";
		}

		// Required parameter
		$error_message = "<p>A table must be selected from the database.</p>";
		if ( isset($params['table']) )
		{
			if ( $params['table'] != "" )
			{
				$error_message = "";
			}
		}

		// 2. CONNECT TO DATABASE

		if ($error_message == "")
		{
			$location = $this->_get_db_location($params);
			if ($location == "external")
			{
				$conni = $this->_connect_db($params, $location);
			}
		}

		// 3. BUILD QUERY

		if ($error_message == "")
		{
			// Get the fields
			$sql = "SHOW COLUMNS FROM " . $params['table'];
			$f="";

			if ($location=="internal")
			{
				$query = $DB->query($sql);
				// EEv1.x
				if ($version == "1")
				{
					foreach($query->result as $field)
				    {
						$f++;
						$fields[$f] = $field['Field'];
				    }
				}
				else
				// EEv2.x
				{
					foreach($query->result_array() as $field)
				    {
						$f++;
						$fields[$f] = $field['Field'];
				    }
				}
			}
			if ($location=="external")
			{
				$query = mysqli_query($conni, $sql);
				while ($field = mysqli_fetch_assoc($query))
				{
					$f++;
					$fields[$f] = $field['Field'];
				}
			}

			// Begin the SQL
			$sql = "SELECT ";
			if ($params['distinct'] == "yes" || $params['distinct'] == "y" || $params['distinct'] == "true")
			{
				$sql .= "DISTINCT ";
			}
			$sql .= "* FROM " . $params['table'];

			// Get and insert the search parameters
			$filters = $this->_get_filters($version);
			if ($filters)
			{
				$sql .= " WHERE ";
				$sql .= $this->_insert_filters($filters, $params['operator']);
			}

			// Insert the sorting
			if ( isset($params['orderby']) )
			{
				if ( $params['orderby'] != "" )
				{
					$orderby_array = explode("|", $params['orderby']);
					if ($params['sort'])
					{
						$sort_array = explode("|", $params['sort']);
					}
					$sql .= " ORDER BY ";
					foreach ($orderby_array AS $key => $orderby)
					{
						if ($orderby_array[$key] == "random")
						{
							$orderby_array[$key] = "RAND()";
							$sort_array[$key] = "";
						}
						$sql .= $orderby_array[$key] . " " . $sort_array[$key] . ", ";
					}
					$sql = rtrim($sql, ", ");
				}
			}

			// Insert the limit
			$sql .= " LIMIT " . $params['limit'] . ";";
		}

		// 4. DISPLAY RESULTS & CLEAN UP

		// If in debug mode, output debug info
		if ($params['debug']=="Yes" || $params['debug']=="yes" || $params['debug']=="Y" || $params['debug']=="y")
		{ 
			$output = $this->_display_debug("Select", $error_message, $sql);
			return $output; 
		}
		else
		// Output results if no errors
		{
			$matrix = array();
			if ($error_message == "")
			{
				if ($location == "internal")
				{
					$query = $DB->query($sql);
					if ($version == "1")
					// EEv1.x
					{
						$matrix = $query->result;
					}
					// EEv2.x
					else
					{
						$matrix = $query->result_array();
					}
				}

				if ($location == "external")
				{
					$r="";
					$query = mysqli_query($conni, $sql);
					while ($entries = mysqli_fetch_assoc($query))
					{
						$r++;
						$matrix[$r] = $entries;
					}
				}
		
				$total_results = count($matrix);
				$x="";

				// Loop through each entry
				foreach($matrix as $entry)
			    {
					$x++;
					if ($params['allow_php'] == "y" || $params['allow_php'] == "Y" || $params['allow_php'] == "yes" || $params['allow_php'] == "Yes")
					{
						$outputrow = $TMPL->tagdata;
					}
					else
					{
						$outputrow = $REGX->xss_clean($TMPL->tagdata);
					}
		
					// Loop through each field to substitute values within the entry
					foreach($fields as $field)
					{
						$outputrow = str_replace("{select:" . $field . "}", $entry[$field], $outputrow);
						// Home-made variables mimicking weblog:entries functionality
						$outputrow = str_replace("{select:count}", $x, $outputrow);
						$outputrow = str_replace("{select:total_results}", $total_results, $outputrow);
					}
				 	$output .= $outputrow;
			    }

				// Clean up

				if ($version == "2")
				{
					$TMPL="";
					$DB="";
					$REGX="";
				}

				if ($location == "external")
				{
					mysqli_close($conni);
				}
				return $output;
			}
		}
		if ($location == "external")
		{
			mysqli_close($conni);
		}
	}

	function Update($str = '')
	{
		
		// 1. PRELIMINARIES
		
		global $TMPL, $DB, $REGX;

		// Are we running EE 1.x or 2.x?
		if ( $TMPL )
		{
			$version = "1";
		}
		else
		{
			$version = "2";
			$this->EE =& get_instance();
			$TMPL = $this->EE->TMPL;
			$DB = $this->EE->db;
			$REGX = $this->EE->security;
		}

		// Declare variables;
		$error_message="";
		$output = "";
		$conni="";
		$set="";

		// Get parameters
		$params = $this->_get_params($version);

		// Set parameter defaults if not specified
		if ($params['allow_php'] == "")
		{
			$params['allow_php'] = "n";
		}
		if ($params['debug'] == "")
		{
			$params['debug'] = "y";
		}
		if ($params['limit'] == "")
		{
			$params['limit'] = "1";
		}

		// Required parameter
		$error_message = "<p>A table must be selected from the database.</p>";
		if ( isset($params['table']) )
		{
			if ( $params['table'] != "" )
			{
				$error_message = "";
			}
		}

		// 2. CONNECT TO DATABASE

		if ($error_message == "")
		{
			$location = $this->_get_db_location($params);
			if ($location == "external")
			{
				$conni = $this->_connect_db($params, $location);
			}
		}

		// 3. FETCH DATA

		if ($error_message == "")
		{

			$set = $this->_get_set("update", $params['table'], $version, $conni);
			if (! $set)
			{
				$error_message .= "<p>No fields were found to update.</p>";
			}
		}
		
		// 4. BUILD QUERY
		
		if ($error_message == "")
		{
			// Start the SQL query
			$sql = "UPDATE " . $params['table'] . " ";
			
			// Insert the SET
			$sql .= $this->_insert_set($set, $params['allow_php'], $version);

			// Get and insert the search parameters
			$filters = $this->_get_filters($version);
			if ($filters)
			{
				$sql .= " WHERE ";
				$sql .= $this->_insert_filters($filters, "");
			}

			// Get SORTed
			if ($params['orderby'])
			{
				$sql .= " ORDER BY " . $params['orderby'];
				if ($sort)
				{
					$sql .= $params['sort'];
				}
			}

			// Get LIMIT
			$sql .= " LIMIT " . $params['limit'];
		}

		// 5. PERFORM THE UPDATE

		$output = $this->_perform_command("Update", $params['debug'], $error_message, $sql, $location);

		// 6. CLEAN UP

		if ($version == "2")
		{
			$TMPL="";
			$DB="";
			$REGX="";
		}

		if ($location == "external")
		{
			mysqli_close($conni);
		}
		return $output;
	}

	function Insert($str = '')
	{
		// 1. PRELIMINARIES
		
		global $TMPL, $DB, $REGX;

		// Are we running EE 1.x or 2.x?
		if ( $TMPL )
		{
			$version = "1";
		}
		else
		{
			$version = "2";
			$this->EE =& get_instance();
			$TMPL = $this->EE->TMPL;
			$DB = $this->EE->db;
			$REGX = $this->EE->security;
		}

		// Declare variables;
		$error_message="";
		$output = "";
		$conn="";
		$set="";

		// Get parameters
		$params = $this->_get_params($version);

		// Set parameter defaults if not specified
		if ($params['allow_php'] == "")
		{
			$params['allow_php'] = "n";
		}
		if ($params['debug'] == "")
		{
			$params['debug'] = "y";
		}

		// Required parameter
		$error_message = "<p>A table must be selected from the database.</p>";
		if ( isset($params['table']) )
		{
			if ( $params['table'] != "" )
			{
				$error_message = "";
			}
		}

		// 2. CONNECT TO DATABASE

		if ($error_message == "")
		{
			$location = $this->_get_db_location($params);
			if ($location == "external")
			{
				$conni = $this->_connect_db($params, $location);
			}
		}
		
		// 3. GET DATA
		
		if ($error_message == "")
		{
			$set = $this->_get_set("insert", $params['table'], $version, $conni);
			if (! $set)
			{
				$error_message .= "<p>Nothing was found to insert.</p>";
			}
		}
		
		// 4. BUILD SQL QUERY
		
		if ($error_message == "")
		{
			$sql = "INSERT INTO " . $params['table'] . " ";
			$sql .= $this->_insert_set($set, $params['allow_php'], $version);
		}
		
		// 5. PERFORM THE INSERT
		
		$output = $this->_perform_command("Insert", $params['debug'], $error_message, $sql, $location);
		
		// 6. CLEAN UP

		if ($version == "2")
		{
			$TMPL="";
			$DB="";
			$REGX="";
		}

		if ($location == "external")
		{
			mysqli_close($conni);
		}
		return $output;
	}

	function Delete($str = '')
	{
		// 1. PRELIMINARIES
		
		// Are we running EE 1.x or 2.x?
		global $TMPL, $DB, $REGX; // EEv1 syntax
		$version = "";
		if ( $TMPL )
		{
			$version = "1";
		}
		else
		{
			$version = "2";
		}
		if ($version == "2")
		{
			$this->EE =& get_instance(); // EEv2 syntax
			$TMPL = $this->EE->TMPL;
			$DB = $this->EE->db;
			$REGX = $this->EE->security;
		}

		// Declare some variables
		$error_message="";
		$output = "";
		$location="internal";
		$conn="";
		$sql="";

		// Get parameters
		$params = $this->_get_params($version);

		// Set parameter defaults if not specified
		if ($params['debug'] == "")
		{
			$params['debug'] = "y";
		}
		if ($params['limit'] == "")
		{
			$params['limit'] = "1";
		}

		// Required parameter
		$error_message = "<p>A table must be selected from the database.</p>";
		if ( isset($params['table']) )
		{
			if ( $params['table'] != "" )
			{
				$error_message = "";
			}
		}

		// 2. CONNECT TO DATABASE

		if ($error_message == "")
		{
			$location = $this->_get_db_location($params);
			if ($location == "external")
			{
				$conni = $this->_connect_db($params, $location);
			}
		}

		// 3. BUILD QUERY
		
		if (! $error_message)
		{

			// Start the query
			$sql = "DELETE FROM " . $params['table'];

			// Insert the filters
			$filters = $this->_get_filters($version);
			if ($filters)
			{
				$sql .= " WHERE ";
				$sql .= $this->_insert_filters($filters);
			}

			// Insert the limit
			$sql .= " LIMIT " . $params['limit'] . ";";
		}	

		// 4. PERFORM THE DELETE
		
		$output = $this->_perform_command("Delete", $params['debug'], $error_message, $sql, $location);

		// 5. CLEAN UP

		if ($version == "2")
		{
			$TMPL="";
			$DB="";
			$REGX="";
		}

		if ($location == "external")
		{
			mysqli_close($conni);
		}
		return $output;
	}

	function usage()
	{
		ob_start(); 
		?>
			See the documentation at http://www.engaging.net/docs/external-entries
		<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}
// END CLASS