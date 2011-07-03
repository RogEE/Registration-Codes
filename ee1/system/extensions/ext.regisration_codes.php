<?php

/*
=====================================================

RogEE "Registration Codes"
an extension for ExpressionEngine 1
by Michael Rog
version 2.0.0

email Michael with questions, feedback, suggestions, bugs, etc.
>> michael@michaelrog.com
>> http://rog.ee

This extension is compatible with NSM Addon Updater:
>> http://leevigraham.com/cms-customisation/expressionengine/lg-addon-updater/

Change-log:
>> http://rog.ee/versions/registration-codes

=====================================================

*/

if (!defined('EXT')) exit('No direct script access allowed.');

/**
 * Registration Codes class, for ExpressionEngine 1.7+
 *
 * @package RogEE Registration Codes
 * @author Michael Rog <michael@michaelrog.com>
 * @copyright 2011 Michael Rog (http://rog.ee)
 */
 
class Registration_codes
{


	// ---------------------------------------------
	//	Extension information
	// ---------------------------------------------

	var $name = 'Registration Codes';
	var $version = '2.0.0';
	var $description = 'Sort/limit new member registrations based on custom registration codes.';
	var $settings_exist = 'y';
	var $docs_url = 'http://rog.ee/registration_codes';
    
    // ---------------------------------------------
    //	Settings
    // ---------------------------------------------
    
	var $settings = array();
	var $dev_on	= FALSE ;


	/**
	* ==============================================
	* Constructor
	* ==============================================
	* 
	* @param mixed: $settings array, or empty string if no settings exist
	*
	*/
    
	function Registration_codes($settings="")
	{
		$this->settings = $settings;
	}
	// END Constructor
    
    

	/**
	* ==============================================
	* Activate Extension
	* ==============================================
	*    
	* Register hooks and establish default settings.
	*
	*/
	function activate_extension()
	{
	
		global $DB;
	
		// ---------------------------------------------
		//	Default settings
		// ---------------------------------------------
		
		$settings =	array();
		$settings['require_valid_code'] = 'no';
		$settings['form_field'] = 'registration_code';
		$settings['bypass_enabled'] = 'no';
		$settings['bypass_code'] = 'schfiftyfive';
		$settings['bypass_form_field'] = 'bypass_code';
	
		// ---------------------------------------------
		//	Hook data
		// ---------------------------------------------
		
		$hooks = array(
			array(
				'extension_id' => '',
				'class' => __CLASS__,
				'hook' => 'member_member_register_start',
				'method' => 'validate_registration_code',
				'settings' => serialize($settings),
				'priority' => 2,
				'version' => $this->version,
				'enabled' => 'y'
			),
			array(
				'extension_id' => '',
				'class' => __CLASS__,
				'hook' => 'user_register_start',
				'method' => 'validate_registration_code',
				'settings' => serialize($settings),
				'priority' => 2,
				'version' => $this->version,
				'enabled' => 'y'
			),			
			array(
				'extension_id' => '',
				'class' => __CLASS__,
				'hook' => 'member_member_register',
				'method' => 'execute_registration_code',
				'settings' => serialize($settings),
				'priority' => 5,
				'version' => $this->version,
				'enabled' => 'y'
			),
			array(
				'extension_id' => '',
				'class' => __CLASS__,
				'hook' => 'user_register_end',
				'method' => 'execute_registration_code',
				'settings' => serialize($settings),
				'priority' => 5,
				'version' => $this->version,
				'enabled' => 'y'
			)			
		);
	
		// ---------------------------------------------
		//	Register hooks
		// ---------------------------------------------
		
		foreach ($hooks as $hook) {
    		$DB->query($DB->insert_string('exp_extensions',	$hook));
		}
		
		// ---------------------------------------------
		//	Create database table
		// ---------------------------------------------
		
		$sql[] = "CREATE TABLE IF NOT EXISTS 'exp_rogee_registration_codes' (
			'code_id' INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
			'site_id' INT(5) UNSIGNED NOT NULL, 
			'code_string' TEXT NOT NULL, 
			'destination_group' INT(3) UNSIGNED NOT NULL, 
			PRIMARY KEY ('code_id')
			);" ;
    
        foreach ($sql as $query)
        {
            $DB->query($query);
        }		
		
		// ---------------------------------------------
		//	Log that the extension has been activated
		// ---------------------------------------------
		
		$this->debug("Activated: " . $this->version);
		
	}
	// END activate_extension()



	/**
	* ==============================================
	* Update Extension
	* ==============================================
	*    
	* Compare the version recorded in the database with the version in the script,
	* run update instructions if necessary.
	*
	* (No updates yet.)
	*
	* @param $current string: currently installed version
	* @return mixed: TRUE if update required, void if not
	*
	*/
	function update_extension($current = '')
	{
	
		global $DB, $EXT;

		// ---------------------------------------------
		//	Generic update process
		// ---------------------------------------------

		if (version_compare($current, '2.0.0', '<'))
		{
			$query = $DB->query("SELECT settings FROM exp_extensions WHERE class = '".$DB->escape_str(__CLASS__)."'");
			$this->settings = unserialize($query->row['settings']);
			$DB->query($DB->update_string('exp_extensions', array('settings' => serialize($this->settings), 'version' => $this->version), array('class' => __CLASS__)));
		}
		
		// ---------------------------------------------
		//	Not sure why returning TRUE here is the thing to do, but all the cool kids seem to be doing it...
		// ---------------------------------------------
		
		return TRUE;
		
	}
	// END update_extension()
	


	/**
	* ==============================================
	* Disable Extension
	* ==============================================
	*    
	* Uninstalls the extension by deleting its row(s) in the DB table
	*
	*/
	function disable_extension()
	{
	
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '".__CLASS__."'");
	
	}	
	
	

// TODO settings_form()
// TODO save_settings()
// TODO execute_registration_code()
// TODO validate_registration_code()
// TODO debug()



}
// END CLASS