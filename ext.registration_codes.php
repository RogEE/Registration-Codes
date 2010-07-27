<?php  

/*
=====================================================

RogEE "Registration Codes"
an extension for ExpressionEngine 2
by Michael Rog
v0.1

email Michael with questions, feedback, suggestions, bugs, etc.
>> michael@michaelrog.com

This extension is compatible with NSM Addon Updater:
>> http://github.com/newism/nsm.addon_updater.ee_addon

Changelog:
0.1 - dev

=====================================================

*/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// -----------------------------------------
//	Here goes nothin...
// -----------------------------------------

class Registration_codes_ext
{

	var $settings = array();
    	
	var $name = "RogEE Registration Codes" ;
	var $version = "0.1.0" ;
	var $description = "Automatically places new members into pre-specified groups according to registration codes." ;
	var $settings_exist = "y" ;
	var $docs_url = "http//michaelrog.com/go/ee" ;

	var $dev_on	= TRUE ;
	
	
	
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
		
		if ($settings == '')
		{
			$settings = array(
				// -- FUTURE: -- // 'replace_captcha' => 'no',	
				'require_valid_code' => 'no',
				'enable_multi_site' => 'no',
				'form_field' => "registration_code"
			);
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
		
		// Register the hook.
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'execute_registration_code',
			'hook'		=> 'member_member_register',
			'settings'	=> serialize($this->settings),
			'priority'	=> 3,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $hook);
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'validate_registration_code',
			'hook'		=> 'member_member_register_start',
			'settings'	=> serialize($this->settings),
			'priority'	=> 3,
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
		$this->debug("Registration Codes EXT activated: version $this->version");
		
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
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);
		
		$this->debug("Registration Codes EXT updated to version $this->version");
	
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
		$this->debug("Registration Codes EXT disabled.");
	
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
	
		$this->debug("settings form start");
	
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
		
		$form_field_value = isset($current['form_field']) ? $current['form_field'] : "registration_code";
		// -- FUTURE: -- // $replace_captcha_value = isset($current['replace_captcha']) ? $current['replace_captcha'] : 'no'; 
		$require_valid_code_value = isset($current['require_valid_code']) ? $current['require_valid_code'] : 'no';
		$enable_multi_site_value = isset($current['enable_multi_site']) ? $current['enable_multi_site'] : 'no';
		
		// Assemble the form fields.
		
		$vars['general_settings_fields'] = array(
			'form_field' => form_input('form_field', $form_field_value),
			'require_valid_code' => form_dropdown(
				'require_valid_code',
				$options_yes_no, 
				$require_valid_code_value)
			);
			
		if ($this->EE->config->item('multiple_sites_enabled') == 'y')
		{
			
			$vars['general_settings_fields']['enable_multi_site'] = form_dropdown(
				'enable_multi_site',
				$options_yes_no, 
				$enable_multi_site_value);
				
		} else {
		
			$vars['general_settings_fields']['enable_multi_site'] = "<strong>".lang($enable_multi_site_value)."</strong> ".lang('rogee_rc_instructions_enable_msm').form_hidden('enable_multi_site', $enable_multi_site_value);
		
		}

		// -------------------------------------------------
		// member groups values
		// -------------------------------------------------
		
		// Get group IDs and names from DB, assemble options array.
		
		$this->EE->db->select('group_id, site_id, group_title');
		// $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
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
		
		$vars['show_multi_site_field'] = (isset($current['enable_multi_site']) && $this->EE->config->item('multiple_sites_enabled') == 'y') ? $current['enable_multi_site'] : 'no';
		
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
		
		$this->debug("settings form end");
		
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

		$this->debug("saving settings...");

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
				
				$this->debug(($need_to_update ? "updating row $row - ".serialize($new_data) : "no need to update row $row"));

			}
			elseif (is_numeric($row) && $val === "")
			{
				
				// If a code was erased, delete the record.
				
				$this->EE->db->where('code_id', $row);
				$this->EE->db->delete('rogee_registration_codes');
				
				$this->debug("deleted row $row");
				
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
		// Serialize and save General Preferences.
		// -------------------------------------------------
		
		$new_settings = array(
			// -- FUTURE: -- // 'replace_captcha' => $this->EE->input->post('replace_captcha'),
			'require_valid_code' => $this->EE->input->post('require_valid_code'),
			'form_field' => $this->EE->input->post('form_field', TRUE),
			'enable_multi_site' => $this->EE->input->post('enable_multi_site'),
		);
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($new_settings)));
		
		// -------------------------------------------------
		// Set success message & redirct to main CP or back to EXT CP.
		// -------------------------------------------------
		
		if (count($duplicate_codes) > 0) {
			$this->EE->session->set_flashdata(
				'message_failure',
				$this->EE->lang->line('registration_codes_module_name').": ".$this->EE->lang->line('rogee_rc_found_duplicates_error').implode(", ", $duplicate_codes)
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
		
		if (!isset($current['require_valid_code']) || $current['require_valid_code'] != 'yes')
		{
			return;
		}
		
		// Figure out if there's a code submitted via $_POST.
		
		$field_name = (isset($current['field_name'])) ? $current['field_name'] : "registration_code";		
		$submitted_code = $this->EE->input->post($field_name, TRUE);

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
		
		$this->debug("new member: $member_id");
		$this->debug(serialize($data));	

		$field_name = (isset($current['field_name'])) ? $current['field_name'] : "registration_code";
		$submitted_code = $this->EE->input->post($field_name, TRUE);
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
		
		// We only need to move them into the destination group if they aren't there already.
		
		if ($match !== FALSE && $data['group_id'] != $destination_groups_list[$match])
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