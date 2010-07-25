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
	
		// super object
		$this->EE =& get_instance();
		
		// settings (?)
		$this->settings = $settings;
		// $this->settings = (empty($settings)) ? $this->settings() : $settings;
		
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
	
		// Default settings
	
		$this->settings = array(
			// 'replace_captcha' => 'no',	
			'require_valid_code' => 'no',
			'enable_multi_site' => 'no'
		);
		
		// Register the hooks.
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'execute_registration_code',
			'hook'		=> 'member_member_register',
			'settings'	=> serialize($this->settings),
			'priority'	=> 2,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);
		
		$hook = array(
			'class'		=> __CLASS__,
			'method'	=> 'validate_registration_code',
			'hook'		=> 'member_member_register_start',
			'settings'	=> serialize($this->settings),
			'priority'	=> 2,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);
		
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
		
		// $replace_captcha_value = isset($current['replace_captcha']) ? $current['replace_captcha'] : 'no'; 
		
		$require_valid_code_value = isset($current['require_valid_code']) ? $current['require_valid_code'] : 'no';
		
		$enable_multi_site_value = isset($current['enable_multi_site']) ? $current['enable_multi_site'] : 'no';
		
		$vars['general_settings_fields'] = array(
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
		
			$vars['general_settings_fields']['enable_multi_site'] = $enable_multi_site_value." (Enable MSM to use this feature.)".form_hidden('enable_multi_site', $enable_multi_site_value);
		
		}

		// -------------------------------------------------
		// member groups values
		// -------------------------------------------------
		
		// Get group IDs and names from DB, assemble options array.
		
		$this->EE->db->select('group_id, site_id, group_title');
		// $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('member_groups');
		
		$options_groups = array(0 => "(Default group)");
		
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
		
		$options_sites = array(0 => "(All sites)");
		
		foreach ($query->result_array() as $row)
		{
			$options_sites[$row['site_id']] = $row['site_label']." (This site)";
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
		
			$vars['codes_fields']['new']['site_id'] = "(This site)".form_hidden('site_id_new', $site_id_value);
		
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
				$todo_list[$id] = $id;
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
		
		foreach ($todo_list as $key => $row)
		{
		
			if (is_numeric($row) && $this->EE->input->post('code_string_'.$row) != "") {
			
				// If a code changed (but is still defined), update the record.
				
				$need_to_update = FALSE;
				$new_data = array();
				
				if ($codes_data[$row]['code_string'] != $this->EE->input->post('code_string_'.$row))
				{
					$new_data['code_string'] = $this->EE->input->post('code_string_'.$row, TRUE);
					$need_to_update = TRUE;
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
				
				if ($need_to_update)
				{
					$this->EE->db->set($new_data);
					$this->EE->db->where('code_id', $row);
					$this->EE->db->update('rogee_registration_codes'); 
				}
				
				$this->debug(($need_to_update ? "updating row $row - ".serialize($new_data) : "no need to update row $row"));

			}
			elseif (is_numeric($row) && $this->EE->input->post('code_string_'.$row) === "")
			{
				
				// If a code was erased, delete the record.
				
				$this->EE->db->where('code_id', $row);
				$this->EE->db->delete('rogee_registration_codes'); 
				
			}
			elseif ($row == "new" && $this->EE->input->post('code_string_'.$row) != "")
			{
				
				// If there's a new code, insert a new record.
				
				$new_code_string = $this->EE->input->post('code_string_'.$row, TRUE);
				$new_destination_group = $this->EE->input->post('destination_group_'.$row);
				$new_site_id = $this->EE->input->post('site_id_'.$row);
				
				$new_data = array(
					'code_string' => $new_code_string,
					'destination_group' =>
						((is_numeric($new_destination_group) && ($new_destination_group >= 0)) ? $new_destination_group : 0 ),
					'site_id' =>
						((is_numeric($new_site_id) && ($new_site_id >= 0)) ? $new_site_id : 0)
				);
				
				$this->EE->db->set($new_data);
				$this->EE->db->insert('rogee_registration_codes');
				
			}
			
		}

		// -------------------------------------------------
		// Serialize and save General Preferences.
		// -------------------------------------------------
		
		$new_settings = array(
			// 'replace_captcha' => $this->EE->input->post('replace_captcha'),
			'require_valid_code' => $this->EE->input->post('require_valid_code'),
			'enable_multi_site' => $this->EE->input->post('enable_multi_site')
		);
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($new_settings)));
		
		// -------------------------------------------------
		// Set success message & redirct to main CP or back to EXT CP.
		// -------------------------------------------------
		
		if (isset($_POST['submit']) && ! isset($_POST['submit_finished']))
		{
		    
		    $this->EE->session->set_flashdata(
		    	'message_success',
		     	$this->EE->lang->line('preferences_updated')
		    );
		    $this->EE->functions->redirect(
		    	BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=registration_codes'
		    );
		    
		}
		else
		{
	
			$this->EE->session->set_flashdata(
				'message_success',
			 	$this->EE->lang->line('preferences_updated')
			);
		
		}
		
	} // END save_settings()



	/**
	 * -------------------------
	 * Debug
	 * -------------------------
	 *
	 * This method places a string into my debug log. For developemnt purposes.
	 *
	 * @return void
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