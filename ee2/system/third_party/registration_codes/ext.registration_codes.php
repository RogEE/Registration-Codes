<?php

/*
=====================================================

RogEE "Registration Codes"
an extension for ExpressionEngine 2
by Michael Rog
version 1.3.0

email Michael with questions, feedback, suggestions, bugs, etc.
>> michael@michaelrog.com
>> http://michaelrog.com/ee

This extension is compatible with NSM Addon Updater:
>> http://ee-garage.com/nsm-addon-updater

Changelog:
>> http://michaelrog.com/ee/versions/registration-codes

=====================================================

*/

if (!defined('APP_VER') || !defined('BASEPATH')) { exit('No direct script access allowed'); }

// -----------------------------------------
//	Here goes nothin...
// -----------------------------------------

if (! defined('ROGEE_RC_VERSION'))
{
	// get the version from config.php
	require PATH_THIRD.'registration_codes/config.php';
	define('ROGEE_RC_VERSION', $config['version']);
}

/**
 * Registration Codes class, for ExpressionEngine 2
 *
 * @package   RogEE Registration Codes
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2010 Michael Rog
 */
class Registration_codes_ext
{

	var $settings = array();
    	
	var $name = "RogEE Registration Codes" ;
	var $version = ROGEE_RC_VERSION ;
	var $description = "Automatically places new members into pre-specified groups according to registration codes." ;
	var $settings_exist = "y" ;
	var $docs_url = "http//michaelrog.com/ee/registration-codes" ;

	var $dev_on	= FALSE ;
	
	
	/**
	 * -------------------------
	 * Constructor
	 * -------------------------
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function Registration_codes_ext($settings='')
	{
	
		$this->EE =& get_instance();
		
		// default settings
		
		if (!is_array($settings))
		{
			$settings = array();
		}
		if (!isset($settings['require_valid_code'])){
			$settings['require_valid_code'] = 'no';
		}
		if (!isset($settings['form_field']))
		{
			$settings['form_field'] = "registration_code";
		}
		
		$this->settings = $settings;
		
		// localize
		$this->EE->lang->loadfile('registration_codes');
		$this->name = $this->EE->lang->line('registration_codes_module_name');
		$this->description = $this->EE->lang->line('registration_codes_module_description');
	
	} // END Constructor
	
	
	
	/**
	 * -------------------------
	 * Activate Extension
	 * -------------------------
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#enable
	 *
	 * @return void
	 */
	function activate_extension()
	{
		
		// Register the hooks for EE-side registrations (default EE Member module)
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'execute_registration_code',
			'hook'		=> 'member_member_register',
			'settings'	=> serialize($this->settings),
			'priority'	=> 5,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $hook);
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'validate_registration_code',
			'hook'		=> 'member_member_register_start',
			'settings'	=> serialize($this->settings),
			'priority'	=> 2,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $hook);
		
		// Register the hooks for User-side registrations (Solspace User module)
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'execute_registration_code',
			'hook'		=> 'user_register_end',
			'settings'	=> serialize($this->settings),
			'priority'	=> 5,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $hook);
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'validate_registration_code',
			'hook'		=> 'user_register_start',
			'settings'	=> serialize($this->settings),
			'priority'	=> 2,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $hook);

		// Create database table.
		
		if (! $this->EE->db->table_exists('rogee_registration_codes'))
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->add_field(array(
				'code_id'    => array('type' => 'INT', 'constraint' => 5, 'unsigned' => TRUE, 'auto_increment' => TRUE),
				'site_id'    => array('type' => 'INT', 'constraint' => 2, 'unsigned' => TRUE, 'default' => 0),				
				'code_string'   => array('type' => 'VARCHAR', 'constraint' => 100),
				'destination_group'  => array('type' => 'int', 'constraint' => 3, 'unsigned' => TRUE)
			));

			$this->EE->dbforge->add_key('code_id', TRUE);

			$this->EE->dbforge->create_table('rogee_registration_codes');
		}		
		
		// log		
		$this->debug("Registration Codes extension activated: version $this->version");
		
	} // END activate_extension()
	
	
	
	/**
	 * -------------------------
	 * Update Extension
	 * -------------------------
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#enable
	 *
	 * @return 	mixed: void on update / false if none
	 */
	function update_extension($current = '')
	{
	
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		elseif (version_compare($current, '1.1.0', '<'))
		{
	
			// un-register hooks
			$this->EE->db->where('class', __CLASS__);
			$this->EE->db->delete('extensions');
			
			// re-register hooks by running activation function
			$this->activate_extension();
		
		}
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);
	
	} // END update_extension()
	
	
	
	/**
	 * -------------------------
	 * Disable Extension
	 * -------------------------
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#disable
	 *
	 * @return void
	 */
	function disable_extension()
	{
		
		// un-register hooks
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
		
		// drop the table if it exists
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('rogee_registration_codes');
		
		// log
		$this->debug("Registration Codes extension disabled.");
	
	} // END disable_extension()




	/**
	 * -------------------------
	 * Settings Form
	 * -------------------------
	 *
	 * @param	Array	Settings
	 * @return 	void
	 */
	function settings_form($current)
	{
		
		$this->EE->load->helper('form');
		$this->EE->load->library('table');
		$this->EE->load->helper('language');
		
		$vars = array();

		// -------------------------------------------------
		// yes/no values
		// -------------------------------------------------
		
		$options_yes_no = array(
			'yes' 	=> lang('yes'), 
			'no'	=> lang('no')
		);

		// -------------------------------------------------
		// GENERAL SETTINGS form fields
		// -------------------------------------------------
		
		// Default values (We shouldn't ever need these, but just in case...)
		
		$form_field_value = isset($current['form_field']) ? $current['form_field'] : $this->settings['form_field'];
		// -- FUTURE: -- // $replace_captcha_value = isset($current['replace_captcha']) ? $current['replace_captcha'] : 'no'; 
		$require_valid_code_value = isset($current['require_valid_code']) ? $current['require_valid_code'] : $this->settings['require_valid_code'];
		
		// Assemble the form fields.
		
		$vars['general_settings_fields'] = array(
			'form_field' => form_input('form_field', $form_field_value),
			'require_valid_code' => form_dropdown(
				'require_valid_code',
				$options_yes_no, 
				$require_valid_code_value)
			);

		// -------------------------------------------------
		// member groups values
		// -------------------------------------------------
		
		// Get group IDs and names from DB, assemble options array.
		
		$this->EE->db->select('group_id, site_id, group_title');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('member_groups');
		
		$options_groups = array(0 => lang('rogee_rc_default_group'));
		
		foreach ($query->result_array() as $row)
		{
			$options_groups[$row['group_id']] = $row['group_id']." (".$row['group_title'].")";
		}
		
		// -------------------------------------------------
		// sites values
		// -------------------------------------------------

		// Get label for [this site] from DB, assemble options array.
				
		$this->EE->db->select('site_id, site_label');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('sites');
		
		$options_sites = array(0 => lang('rogee_rc_all_sites'));
		
		foreach ($query->result_array() as $row)
		{
			$options_sites[$row['site_id']] = $row['site_label']." ".lang('rogee_rc_this_site');
		}
		
		// -------------------------------------------------
		// registration codes values
		// -------------------------------------------------

		// Load codes for [this site] and [all sites].
		$this->EE->db->select('code_id, site_id, code_string, destination_group');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->or_where('site_id', "0");
		$this->EE->db->order_by('code_string', 'asc');
		$query = $this->EE->db->get('rogee_registration_codes');
		
		$vars['codes_data'] = array();
		
		foreach ($query->result_array() as $row)
		{
			$vars['codes_data'][$row['code_id']] = array(
				'code_id' => $row['code_id'],
				'site_id' => $row['site_id'],
				'code_string' => $row['code_string'],
				'destination_group' => $row['destination_group']
			);
		}

		// -------------------------------------------------
		// REGISTRATION CODES form fields
		// -------------------------------------------------
		
		$vars['codes_fields'] = array();
		
		$vars['show_multi_site_field'] = ($this->EE->config->item('multiple_sites_enabled') == 'y') ? 'yes' : 'no';
		
		// Generate fields for existing codes...
		
		foreach ($vars['codes_data'] as $key => $data)
		{
			
			// Setting backup values (We should never need these, but just in case...)
			
			$code_string_value = isset($data['code_string']) ? $data['code_string'] : "";
			$destination_group_value = isset($data['destination_group']) ? $data['destination_group'] : 0;
			$site_id_value = isset($data['site_id']) ? $data['site_id'] : 0;
			
			// Assembling form fields
						
			$vars['codes_fields'][$key] = array(
				'code_string' => form_input('code_string_'.$key, $code_string_value),
				'destination_group' => form_dropdown(
					'destination_group_'.$key,
					$options_groups,
					$destination_group_value)
			);
			
			// If multi-site is enabled, show me a drop-down.
			// If not, just show me the [immutable] info.
			
			if ($vars['show_multi_site_field'] == 'yes')
			{
			
				$vars['codes_fields'][$key]['site_id'] = form_dropdown(
					'site_id_'.$key,
					$options_sites, 
					$site_id_value);
			
			} else {
			
				$vars['codes_fields'][$key]['site_id'] = "-".form_hidden('site_id_'.$key, $site_id_value);
			
			}
			
		}
		
		// Generate fields for a new code...
		
		// Seting default values
		
		$code_string_value = "";
		$destination_group_value = 0;
		$site_id_value = ($vars['show_multi_site_field'] == 'yes') ? 0 : $this->EE->config->item('site_id');
		
		// Assembling form fields
					
		$vars['codes_fields']['new'] = array(
			'code_string' => form_input('code_string_new', $code_string_value),
			'destination_group' => form_dropdown(
				'destination_group_new',
				$options_groups,
				$destination_group_value)
		);
		
		// If multi-site is enabled, show me a drop-down.
		// If not, just show me the [immutable] info.
		
		if ($vars['show_multi_site_field'] == 'yes')
		{
		
			$vars['codes_fields']['new']['site_id'] = form_dropdown(
				'site_id_new',
				$options_sites, 
				$site_id_value);
		
		} else {
		
			$vars['codes_fields']['new']['site_id'] = lang('rogee_rc_this_site').form_hidden('site_id_new', $site_id_value);
		
		}
		
		// -------------------------------------------------
		// All done. Go go gadget view file!
		// -------------------------------------------------
		
		return $this->EE->load->view('index', $vars, TRUE);			
	
	} // END settings_form()



	/**
	 * -------------------------
	 * Save Settings
	 * -------------------------
	 *
	 * This function provides a little extra processing and validation 
	 * than the generic settings form.
	 *
	 * @return void
	 */
	function save_settings()
	{
		
		$this->EE->lang->loadfile('registration_codes');
				
		// -------------------------------------------------
		// Make sure I'm a legit CP form submission.
		// -------------------------------------------------
	
		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		// -------------------------------------------------
		// Make a list of codes in $_POST array.
		// -------------------------------------------------

		$todo_list = array();

		foreach ($_POST as $key => $val)
		{
			if (strpos($key, "code_string_") !== false)
			{
				$id = str_ireplace("code_string_", "", $key);  
				$todo_list[$id] = $this->EE->input->post($key, TRUE);
			}
		}

		// -------------------------------------------------
		// Get registration codes data, for comparison to $_POST data.
		// -------------------------------------------------

		// loading codes for [this site] and [all sites]
		$this->EE->db->select('code_id, site_id, code_string, destination_group');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->or_where('site_id', "0");
		$query = $this->EE->db->get('rogee_registration_codes');
		
		$codes_data = array();
		
		foreach ($query->result_array() as $row)
		{
			$codes_data[$row['code_id']] = array(
				'code_id' => $row['code_id'],
				'site_id' => $row['site_id'],
				'code_string' => $row['code_string'],
				'destination_group' => $row['destination_group']
			);
		}

		// -------------------------------------------------
		// Identify changed records and enter new info into DB.
		// -------------------------------------------------
		
		$duplicate_codes = array();
		
		foreach ($todo_list as $row => $val)
		{
		
			if (is_numeric($row) && $val != "") {
			
				// If a code changed (but is still defined), update the record.
				
				$need_to_update = FALSE;
				$found_duplicate = FALSE;
				$new_data = array();
				
				if ($codes_data[$row]['code_string'] != $val)
				{
					// Don't allow duplicate codes
					if(count(array_keys($todo_list, $val)) < 2)
					{
						$new_data['code_string'] = $val;
						$need_to_update = TRUE;
					}
					else
					{
						$found_duplicate = TRUE;
						$duplicate_codes[] = $val;
					}
				}
				
				if ($codes_data[$row]['destination_group'] != $this->EE->input->post('destination_group_'.$row))
				{
					$new_data['destination_group'] = $this->EE->input->post('destination_group_'.$row);
					$need_to_update = TRUE;
				}
				
				if ($codes_data[$row]['site_id'] != $this->EE->input->post('site_id_'.$row))
				{
					$new_data['site_id'] = $this->EE->input->post('site_id_'.$row);
					$need_to_update = TRUE;
				}
				
				if ($need_to_update && !$found_duplicate)
				{
					$this->EE->db->set($new_data);
					$this->EE->db->where('code_id', $row);
					$this->EE->db->update('rogee_registration_codes'); 
				}

			}
			elseif (is_numeric($row) && $val === "")
			{
				
				// If a code was erased, delete the record.
				
				$this->EE->db->where('code_id', $row);
				$this->EE->db->delete('rogee_registration_codes');
				
			}
			elseif ($row == "new" && $val != "")
			{
				
				// If there's a new code, insert a new record.
				
				// Don't allow duplicate codes
				if(count(array_keys($todo_list, $val)) < 2)
				{
				
					$new_destination_group = $this->EE->input->post('destination_group_'.$row);
					$new_site_id = $this->EE->input->post('site_id_'.$row);
					
					$new_data = array(
						'code_string' => $val,
						'destination_group' =>
							((is_numeric($new_destination_group) && ($new_destination_group >= 0)) ? $new_destination_group : 0 ),
						'site_id' =>
							((is_numeric($new_site_id) && ($new_site_id >= 0)) ? $new_site_id : 0)
					);
					
					$this->EE->db->set($new_data);
					$this->EE->db->insert('rogee_registration_codes');
				
				}
				else
				{
					$duplicate_codes[] = $val;
				}
				
			}
			
		}

		// -------------------------------------------------
		// Sanitize, serialize and save General Preferences.
		// -------------------------------------------------
		
		$form_field_input = $this->EE->input->post('form_field', TRUE);
		
		$new_settings = array(
			// -- FUTURE: -- // 'replace_captcha' => $this->EE->input->post('replace_captcha'),
			'require_valid_code' => $this->EE->input->post('require_valid_code')
		);
		
		$form_field_error = FALSE;
		
		if ($form_field_input != "")
		{
		
			$new_settings['form_field'] = $this->clean_string($form_field_input);
			
			if ($form_field_input != $new_settings['form_field'])
			{
				$form_field_error = TRUE;
			}
			
		}
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($new_settings)));
		
		// -------------------------------------------------
		// Set error/success messages & redirct to main CP or back to EXT CP.
		// -------------------------------------------------
		
		$error_string = "";
		
		if ($form_field_error)
		{
			$error_string .= $this->EE->lang->line('rogee_rc_form_field_error')." ";
		}
		if (count($duplicate_codes) > 0) {
			$error_string .= $this->EE->lang->line('rogee_rc_found_duplicates_error').implode(", ", $duplicate_codes);			
		}
		
		if ($error_string != "")
		{
			$this->EE->session->set_flashdata(
				'message_failure',
				$this->EE->lang->line('registration_codes_module_name').": ".$error_string
			);
		}
		else
		{
			$this->EE->session->set_flashdata(
				'message_success',
			 	$this->EE->lang->line('registration_codes_module_name').": ".$this->EE->lang->line('preferences_updated')
			);
		}
		
		if (isset($_POST['submit']) && ! isset($_POST['submit_finished']))
		{
		    $this->EE->functions->redirect(
		    	BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=registration_codes'
		    );   
		}
		
	} // END save_settings()



	/**
	 * -------------------------
	 * Validate registration code
	 * -------------------------
	 *
	 * This method runs before a new member registration is processed and returns an error if the registration code isn't valid.
	 *
	 * @return void
	 */
	function validate_registration_code()
	{
		
		// We only care about this function if "require_valid_code" is set.
		
		if ($this->settings['require_valid_code'] != 'yes')
		{
			return;
		}
		
		// Figure out if there's a code submitted via $_POST.
		
		$submitted_code = $this->EE->input->post($this->settings['form_field'], TRUE);

		// If there is a code submitted, see if it is valid.

		$match = FALSE ;
		
		if ($submitted_code !== FALSE)
		{

			// Loading codes for [this site] and [all sites]
			
			$this->EE->db->select('code_id, code_string');
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->or_where('site_id', "0");
			$query = $this->EE->db->get('rogee_registration_codes');
			
			// Making a list of possible valid codes
			
			$codes_list = array();
			
			foreach ($query->result_array() as $row)
			{
				$codes_list[$row['code_id']] = $row['code_string'];
			}
			
			// Checking whether the submitted code is on the list
			
			if (in_array($submitted_code, $codes_list))
			{
				$match = TRUE ;
			}
		
		}		
		
		// If there is no valid code submitted, interrupt membership processing and return the error.
		
		if (!$match)
		{
			$this->extensions->end_script = TRUE;
			$error = array($this->EE->lang->line('rogee_rc_no_valid_code'));
			return $this->EE->output->show_user_error('submission', $error);
		}
				
	} // END execute_registration_code()


	/**
	 * -------------------------
	 * Execute registration code
	 * -------------------------
	 *
	 * This method runs when a new member registration is complete and moves the new member to an appropriate group if they have provided a valid registration code.
	 *
	 * @return void
	 */
	function execute_registration_code($data, $member_id)
	{
		
		$submitted_code = $this->EE->input->post($this->settings['form_field'], TRUE);
		$match = FALSE ;
		
		if ($submitted_code !== FALSE)
		{

			// Loading codes for [this site] and [all sites]
			
			$this->EE->db->select('code_id, code_string, destination_group');
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->or_where('site_id', "0");
			$query = $this->EE->db->get('rogee_registration_codes');
			
			// Making a list of possible valid codes and corresponding destination groups
			
			$codes_list = array();
			$destination_groups_list = array();
			
			foreach ($query->result_array() as $row)
			{
				$codes_list[$row['code_id']] = $row['code_string'];
				$destination_groups_list[$row['code_id']] = $row['destination_group'];
			}
			
			// Checking whether the submitted code is on the list
			
			$match = array_search($submitted_code, $codes_list);
			
		}
		
		// Make the database change if there is a valid match
		
		if ($match !== FALSE && $destination_groups_list[$match] > 0)
		{
			$this->EE->db->where('member_id', $member_id);
			$this->EE->db->update(
				'members', 
				array('group_id' => $destination_groups_list[$match])
			);
		}
				
	} // END execute_registration_code()



	/**
	 * -------------------------
	 * Clean string
	 * -------------------------
	 *
	 * Cleans everything except alphanumeric/dash/underscore from the parameter string
	 * (used to sanitize field name)
	 *
	 * @param string: to be sanitized
	 * @return string: cleaned-up string
	 * 
	 * @see http://cubiq.org/the-perfect-php-clean-url-generator
	 */
	function clean_string($str) {
		$clean = preg_replace("/[^a-zA-Z0-9\/_| -]/", '', $str);
		$clean = trim($clean, '-');
		$clean = preg_replace("/[\/|_]+/", '_', $clean);
		return $clean;
	}



	/**
	 * -------------------------
	 * Debug
	 * -------------------------
	 *
	 * This method places a string into my debug log. For developemnt purposes.
	 *
	 * @return mixed: parameter (default: blank string)
	 */
	function debug($debug_statement = "")
	{
		
		if ($this->dev_on)
		{
			
			if (! $this->EE->db->table_exists('rogee_debug_log'))
			{
				$this->EE->load->dbforge();
				$this->EE->dbforge->add_field(array(
					'event_id'    => array('type' => 'INT', 'constraint' => 5, 'unsigned' => TRUE, 'auto_increment' => TRUE),
					'class'    => array('type' => 'VARCHAR', 'constraint' => 50),
					'event'   => array('type' => 'VARCHAR', 'constraint' => 200),
					'timestamp'  => array('type' => 'int', 'constraint' => 20, 'unsigned' => TRUE)
				));
				$this->EE->dbforge->add_key('event_id', TRUE);
				$this->EE->dbforge->create_table('rogee_debug_log');
			}
			
			$log_item = array('class' => __CLASS__, 'event' => $debug_statement, 'timestamp' => time());
			$this->EE->db->set($log_item);
			$this->EE->db->insert('rogee_debug_log');
		}
		
		return $debug_statement;
		
	} // END debug()
	

} // END CLASS

/* End of file ext.registration_codes.php */
/* Location: ./system/expressionengine/third_party/registration_codes/ext.registration_codes.php */