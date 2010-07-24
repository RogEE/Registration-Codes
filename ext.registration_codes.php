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
//	Begin class
// -----------------------------------------

class Registration_codes_ext
{

	var $settings		= array() ;
    	
	var $name			= "RogEE Registration Codes" ;
	var $version		= "0.1.0" ;
	var $description	= "Automatically places new members into pre-specified groups according to registration codes." ;
	var $settings_exist	= "y" ;
	var $docs_url		= "http//michaelrog.com/go/ee" ;

	var $dev_on			= TRUE ;
	
	
	
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
		// $this->settings = (empty($settings)) ? $this->settings() : $settings ;
		
		// localize
		$this->EE->lang->loadfile('registration_codes');
		$this->name = $this->EE->lang->line('registration_codes_module_name') ;
		$this->description = $this->EE->lang->line('registration_codes_module_description') ;
		
		// log
		// $this->debug("Registration Codes EXT constructed.") ;
	
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
	
		// default settings
	
		$this->settings = array(
			'replace_captcha' => 'no',
			'require_valid_code' => 'no',
			'enable_multi_site' => 'no'
		);
		
		// register the hooks
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'member_member_register',
			'hook'		=> 'member_member_register',
			'settings'	=> serialize($this->settings),
			'priority'	=> 2,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);
		
		// create database table
		
		if (! $this->EE->db->table_exists('rogee_registration_codes'))
		{
			$this->EE->load->dbforge() ;
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
		$this->debug("Registration Codes EXT activated: $this->version") ;
		
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
		
		if ($current < '1.0')
		{
			// Update to version 1.0
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
		
		// drop the table
		$this->EE->load->dbforge() ;
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
	
		// helpers
		$this->EE->load->helper('form');
		$this->EE->load->library('table');
		
		$vars = array();
		
		$this->debug("Registration Codes settings form start");

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
		
		$replace_captcha_value = isset($current['replace_captcha']) ? $current['replace_captcha'] : 'no'; 
		
		$require_valid_code_value = isset($current['require_valid_code']) ? $current['require_valid_code'] : 'no';
		
		$enable_multi_site_value = isset($current['enable_multi_site']) ? $current['enable_multi_site'] : 'no';
		
		$vars['general_settings_fields'] = array(
			'replace_captcha' => form_dropdown(
				'replace_captcha',
				$options_yes_no,
				$replace_captcha_value),
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
		
		// get group IDs and names from DB, assemble options array
		
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

		// get label for [this site] from DB, assemble options array
				
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

		// load codes for [this site] and [all sites]
		$this->EE->db->select('code_id, site_id, code_string, destination_group');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->or_where('site_id', "0");
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
		
		$show_multi_site_field = (isset($current['enable_multi_site']) && $this->EE->config->item('multiple_sites_enabled') == 'y') ? $current['enable_multi_site'] : 'no';
		
		// Generate fields for existing codes...
		
		foreach ($vars['codes_data'] as $key => $data)
		{
			
			// Set backup values (We should never need these, but just in case...)
			
			$code_string_value = isset($data['code_string']) ? $data['code_string'] : "";
			$destination_group_value = isset($data['destination_group']) ? $data['destination_group'] : 0;
			$site_id_value = isset($data['site_id']) ? $data['site_id'] : 0;
			
			// Assemble form fields
						
			$vars['codes_fields'][$key] = array(
				'code_string' => form_input('code_string_'.$key, $code_string_value),
				'destination_group' => form_dropdown(
					'destination_group_'.$key,
					$options_groups,
					$destination_group_value)
			);
			
			// If multi-site is enabled, show me a drop-down.
			// If not, just show me the [immutable] info.
			
			if ($show_multi_site_field == 'yes')
			{
			
				$vars['codes_fields'][$key]['site_id'] = form_dropdown(
					'site_id_'.$key,
					$options_sites, 
					$site_id_value);
			
			} else {
			
				$vars['codes_fields'][$key]['site_id'] = $options_sites[$site_id_value].form_hidden('site_id_'.$key, $site_id_value);
			
			}
			
		}
		
		// Generate fields for a new code...
		
		// Set default values
		
		$code_string_value = "";
		$destination_group_value = 0;
		$site_id_value = ($show_multi_site_field == 'yes') ? 0 : $this->EE->config->item('site_id');
		
		// Assemble form fields
					
		$vars['codes_fields']['new'] = array(
			'code_string' => form_input('code_string_new', $code_string_value),
			'destination_group' => form_dropdown(
				'destination_group_new',
				$options_groups,
				$destination_group_value)
		);
		
		// If multi-site is enabled, show me a drop-down.
		// If not, just show me the [immutable] info.
		
		if ($show_multi_site_field == 'yes')
		{
		
			$vars['codes_fields']['new']['site_id'] = form_dropdown(
				'site_id_new',
				$options_sites, 
				$site_id_value);
		
		} else {
		
			$vars['codes_fields']['new']['site_id'] = $options_sites[$site_id_value].form_hidden('site_id_new', $site_id_value);
		
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
	
		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		unset($_POST['submit']);
	
		$this->EE->lang->loadfile('registration_codes');
	
		/*
		
		$len = $this->EE->input->post('max_link_length');
		
		if ( ! is_numeric($len) OR $len <= 0)
		{
			$this->EE->session->set_flashdata(
					'message_failure', 
					sprintf($this->EE->lang->line('max_link_length_range'),
						$len)
			);
			$this->EE->functions->redirect(
				BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=registration_codes'
			);
		}
		
		*/
		
		$this->debug(serialize($_POST));
		
		$new_settings = array(
			'replace_captcha' => $this->EE->input->post('replace_captcha'),
			'require_valid_code' => $this->EE->input->post('require_valid_code'),
			'enable_multi_site' => $this->EE->input->post('enable_multi_site')
		);
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($new_settings)));
		
		$this->EE->session->set_flashdata(
			'message_success',
		 	$this->EE->lang->line('preferences_updated')
		);
		
	} // END save_settings()



	/**
	 * -------------------------
	 * Debug
	 * -------------------------
	 *
	 * This method places a string into my debug log. For developemnt only.
	 *
	 * @return void
	 */
	function debug($debug_statement = "")
	{
		if ($this->dev_on)
		{
			
			if (! $this->EE->db->table_exists('rogee_debug_log'))
			{
				$this->EE->load->dbforge() ;
				$this->EE->dbforge->add_field(array(
					'event_id'    => array('type' => 'INT', 'constraint' => 5, 'unsigned' => TRUE, 'auto_increment' => TRUE),
					'class'    => array('type' => 'VARCHAR', 'constraint' => 100),
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
		
		return $debug_statement ;
		
	} // END debug()
	

} // END CLASS

/* End of file ext.registration_codes.php */
/* Location: ./system/expressionengine/third_party/registration_codes/ext.registration_codes.php */