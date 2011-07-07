<?php

/*
=====================================================

RogEE "Registration Codes"
an extension for ExpressionEngine
by Michael Rog

email Michael with questions, feedback, suggestions, bugs, etc.
>> michael@michaelrog.com
>> http://rog.ee

This extension is compatible with NSM Addon Updater:
>> http://leevigraham.com/cms-customisation/expressionengine/lg-addon-updater/

Change-log:
>> http://rog.ee/versions/registration_codes

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
	var $dev_on = TRUE;
	var $nuke_log_on_uninstall = FALSE;


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
		
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_rogee_registration_codes (
			code_id INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
			site_id INT(5) UNSIGNED NOT NULL, 
			code_string TEXT NOT NULL, 
			destination_group INT(3) UNSIGNED NOT NULL, 
			PRIMARY KEY (code_id)
			);" ;
    
        foreach ($sql as $query)
        {
            $DB->query($query);
        }		
		
		// ---------------------------------------------
		//	Log that the extension has been activated
		// ---------------------------------------------
		
		$this->debug_log("Activated: " . $this->version);
		
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

		// ---------------------------------------------
		//	Nuke all traces of the extension
		// ---------------------------------------------
	
		global $DB;
		
		$sql[] = "DELETE FROM exp_extensions WHERE class = '".__CLASS__."'";

		$sql[] = "DROP TABLE IF EXISTS exp_rogee_registration_codes";
		
		foreach ($sql as $query)
		{
			$DB->query($query);
		}

		// ---------------------------------------------
		//	Log that the extension has been deactivated
		// ---------------------------------------------
		
		$this->debug_log("Deactivated.");
		
		// ---------------------------------------------
		//	Nuke the log table (if switch is set)
		// ---------------------------------------------
		
		if ($this->nuke_log_on_uninstall)
		{
			$DB->query("DROP TABLE IF EXISTS exp_rogee_debug_log");
		}
		
		
	
	}	
	
	
	
function settings_form($current)
{
	global $DSP, $LANG, $IN;
	
	$DSP->crumbline = TRUE;
	
	$DSP->title  = $LANG->line('extension_settings');
	
	$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
	$DSP->crumb_item(
		$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager',
			$LANG->line('extensions_manager'))
	);
	$DSP->crumb .= $DSP->crumb_item($this->name);
	
	// Set up the settings form
		
	$DSP->body = $DSP->form_open(
		array(
		'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings',
		'name'   => 'rogee_registration_codes_settings',
		'id'     => 'rogee_registration_codes_settings'
		),
		array('name' => get_class($this))
	);

	// ---------------------------------------------
	//	General settings
	// ---------------------------------------------

	// OPEN General Settings table

$DSP->body .=   $DSP->table('', '10', '10', '100%','0');
	
	// General settings
	
	$DSP->body .=   $DSP->tr();
	
	// General settings: Operation
	
	$DSP->body .=   $DSP->td('','50%','','','top');
	
	$DSP->body .= "<fieldset><legend class=\"defaultBold\">General settings</legend>";
	
	$DSP->body .= $DSP->div('itemWrapper')
              .$DSP->qdiv('itemTitle', "Form field [name]")
              .$DSP->input_text('form_field', 'CODE_FIELD', '20', '60', 'input', '')
              .$DSP->div_c();
              
	$DSP->body .= $DSP->div('itemWrapper')
              .$DSP->qdiv('itemTitle', "Require valid code to register?")
              .$DSP->input_select_header('require_valid_code')
              .$DSP->input_select_option('y', $LANG->line('yes'))
              .$DSP->input_select_option('n', $LANG->line('no'))
              .$DSP->input_select_footer()
              .$DSP->div_c();

	$DSP->body .= "</fieldset>";
		
	$DSP->body .=   $DSP->td_c();
	
	// General settings: Override
	
	$DSP->body .=   $DSP->td('','50%','','','top');
	
	$DSP->body .= "<fieldset><legend class=\"defaultBold\">Override settings</legend>";
	
	$DSP->body .= $DSP->div('itemWrapper')
              .$DSP->qdiv('itemTitle', "Bypass extension if override code is present?")
              .$DSP->input_select_header('bypass_enabled')
              .$DSP->input_select_option('y', $LANG->line('yes'))
              .$DSP->input_select_option('n', $LANG->line('no'))
              .$DSP->input_select_footer()
              .$DSP->div_c();

	$DSP->body .= $DSP->div('itemWrapper')
              .$DSP->qdiv('itemTitle', "Override code form field")
              .$DSP->input_text('bypass_field', 'BYPASS_FIELD', '20', '60', 'input', '')
              .$DSP->div_c();
              
	$DSP->body .= $DSP->div('itemWrapper')
              .$DSP->qdiv('itemTitle', "Override code")
              .$DSP->input_text('bypass_code', 'BYPASS_CODE', '20', '60', 'input', '')
              .$DSP->div_c();


	$DSP->body .= "</fieldset>";
		
	$DSP->body .=   $DSP->td_c();	
	
	// END General Settings table
	
	$DSP->body .=   $DSP->table_c();

	$DSP->body .= "<br />";

	// ---------------------------------------------
	//	Registration codes
	// ---------------------------------------------

	$DSP->body .=   $DSP->table('tableBorder', '1', '0', '100%','1');
	
	$DSP->body .=   $DSP->tr();
	$DSP->body .=   $DSP->td('tableHeadingAlt', '', '3');
	$DSP->body .=   "test2";
	$DSP->body .=   $DSP->td_c();
	$DSP->body .=   $DSP->tr_c();
	
	$DSP->body .=	$DSP->table_row(array(
				'cell1' => array('valign' => "top", 'text' => $first_text), 'width' => "7%",
				'cell2' => array('valign' => "top", 'class' => "default", 'width'  => "30%"),
				'cell3' => array('valign' => "top", 'text' => $third_text, 'width'  => "35%"),
				'cell4' => array('valign' => "top", 'text' => $third_text, 'width'  => "38%"),
				);
	/*
	
	$DSP->body .=   $DSP->tr();
	$DSP->body .=   $DSP->td('tableCellOne', '25%');
	$DSP->body .=   $DSP->qdiv('defaultBold', $LANG->line('rogee_rc_name'));
	$DSP->body .=   $DSP->td_c();
	$DSP->body .=   $DSP->td('tableCellOne');
	$DSP->body .=   $DSP->input_text('log_table', ( ! isset($current['log_table'])) ? '' : $current['log_table']);
	$DSP->body .=   $DSP->td_c();
	$DSP->body .=   $DSP->tr_c();
	*/
	
	$DSP->body .=   $DSP->table_c();
	
	$DSP->body .=   $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
	$DSP->body .=   $DSP->form_c();
}

	

// TODO settings_form()
// TODO save_settings()
// TODO execute_registration_code()
// TODO validate_registration_code()


	/**
	* ==============================================
	* Clean string
	* ==============================================
	*
	* Cleans everything except alphanumeric/dash/underscore from the parameter string
	* (used to sanitize field name)
	*
	* @param string: to be sanitized
	* @return string: cleaned-up string
	* 
	* @see http://cubiq.org/the-perfect-php-clean-url-generator
	*
	*/
	private function clean_string($str) {
	
		$clean = preg_replace("/[^a-zA-Z0-9\/_| -]/", '', $str);
		$clean = trim($clean, '-');
		$clean = preg_replace("/[\/|_]+/", '_', $clean);
		return $clean;
		
	}



	/**
	* ==============================================
	* Debug log
	* ==============================================
	*
	* This method places a string into my debug log. For developemnt purposes.
	*
	* @return mixed: parameter (default: blank string)
	*
	*/
	private function debug_log($debug_statement = "")
	{
		
		if ($this->dev_on)
		{
			
			global $DB;
			
			$sql[] = "CREATE TABLE IF NOT EXISTS exp_rogee_debug_log (
				event_id INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
				class VARCHAR(50), 
				event VARCHAR(200), 
				timestamp INT(20) UNSIGNED, 
				PRIMARY KEY (event_id)
				);" ;
	    
	    	$log_item = array('class' => __CLASS__, 'event' => $debug_statement, 'timestamp' => time());
	    	$sql[] = $DB->insert_string('exp_rogee_debug_log', $log_item);
	    
	        foreach ($sql as $query)
	        {
	            $DB->query($query);
	        }
			
		}
		
		return $debug_statement;
		
	} // END debug()



}
// END CLASS

/* End of file ext.registration_codes.php */
/* Location: ./system/extensions/ext.registration_codes.php */