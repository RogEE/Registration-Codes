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

	// ---------------------------------------------
	//	MSM prefs
	// ---------------------------------------------
	
	var $msm_enabled;
	var $this_site_id;
	
	// ---------------------------------------------
	//	etc.
	// ---------------------------------------------
	
	var $member_group_data;
	var $zebra_class = "";
		

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
		$this->debug_log("Constructor.");
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
		$settings['require_valid_code'] = 'n';
		$settings['form_field'] = 'registration_code';
		$settings['bypass_enabled'] = 'n';
		$settings['bypass_code'] = '';
		$settings['bypass_form_field'] = '';
		$this->settings = $settings;
	
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
		//	Not sure why returning TRUE here is the thing to do, but all the cool EE1 kids seem to be doing it...
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
	
	
	/**
	* ==============================================
	* Settings_form
	* ==============================================
	*    
	* Draws the settings form
	*
	* @param array: current settings
	*
	*/	
	function settings_form($current)
	{
		
		global $PREFS, $DB, $DSP, $LANG, $IN;
		
		$this->msm_enabled = ($PREFS->ini('multiple_sites_enabled') == "y");
		$this->this_site_id = $PREFS->ini('site_id');
		
		$this->debug_log("Settings form.");
		
		// ---------------------------------------------
		//	Breadcrumb nav
		// ---------------------------------------------
		
		$DSP->crumbline = TRUE;
		
		$DSP->title  = $LANG->line('extension_settings');
		
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
		$DSP->crumb_item(
			$DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager',
				$LANG->line('extensions_manager'))
		);
		$DSP->crumb .= $DSP->crumb_item($this->name);
		
		// ---------------------------------------------
		//	Body output
		// ---------------------------------------------
		
		$DSP->body = "";
		
		// ---------------------------------------------
		//	Additional stylesheet
		// ---------------------------------------------
		
		ob_start(); 
		?>
		
			<style>
			.rogee_rc_form fieldset { border: 1px solid #777; background: #EEF4F9; }
			.rogee_rc_form legend { padding: 7px ; color: black; border: 1px solid #777; background: #B8C6CE; border-radius: 2px; }
			.rogee_rc_form input { padding: 5px; font-size: 110%; border-radius: 5px; max-width: 40%; border: 1px solid #aaa; }
			.rogee_rc_form .submit { background: #EEF4F9; cursor: pointer; }
			.rogee_rc_form .submit:hover { background: #B8C6CE; }
			.rogee_rc_form .submit:active { position: relative; top: 1px; }
			
			.rogee_rc_form .tableBorder td.tableHeadingAlt { padding: 10px 0; }		
			.rogee_rc_form .tableBorder td { padding: 7px 0; }
			.rogee_rc_form .tableBorder { border: 1px solid; }
					
			</style>
		
		<?php
		$style_from_buffer = ob_get_contents();
		ob_end_clean(); 
		
		$DSP->body .= $style_from_buffer;
		
		// ---------------------------------------------
		//	Set up the settings form
		// ---------------------------------------------
			
		$DSP->body .= $DSP->form_open(
			array(
				'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings',
				'name' => 'rogee_registration_codes_settings',
				'id' => 'rogee_registration_codes_settings',
				'class' => 'rogee_rc_form'
			),
			array(
				'name' => get_class($this),
				'return_location' => BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extension_settings'.AMP.'name=registration_codes'
			)
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
	        .$DSP->input_text('form_field', $current['form_field'], '20', '60', 'input')
	        .$DSP->div_c();
	              
		$DSP->body .= $DSP->div('itemWrapper')
			.$DSP->qdiv('itemTitle', "Require valid code to register?")
			.$DSP->input_select_header('require_valid_code')
			.$DSP->input_select_option('y', $LANG->line('yes'), ($current['require_valid_code'] == 'y'))
			.$DSP->input_select_option('n', $LANG->line('no'), ($current['require_valid_code'] == 'n'))
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
			.$DSP->input_select_option('y', $LANG->line('yes'), ($current['bypass_enabled'] == 'y'))
			.$DSP->input_select_option('n', $LANG->line('no'), ($current['bypass_enabled'] == 'n'))
			.$DSP->input_select_footer()
			.$DSP->div_c();
	
		$DSP->body .= $DSP->div('itemWrapper')
			.$DSP->qdiv('itemTitle', "Override code form field")
			.$DSP->input_text('bypass_form_field', $current['bypass_form_field'], '20', '60', 'input', '')
			.$DSP->div_c();
	              
		$DSP->body .= $DSP->div('itemWrapper')
			.$DSP->qdiv('itemTitle', "Override code")
			.$DSP->input_text('bypass_code', $current['bypass_code'], '20', '60', 'input', '')
			.$DSP->div_c();
	
		$DSP->body .= "</fieldset>";
			
		$DSP->body .=   $DSP->td_c();	
		
		// END General Settings table
		
		$DSP->body .=   $DSP->table_c();
	
		$DSP->body .= "<br />";
	
		// ---------------------------------------------
		//	Registration codes
		// ---------------------------------------------	
		
		// Get registration_code data from the database
		
		$registration_code_data = array();
		
		$query = $DB->query("SELECT * FROM exp_rogee_registration_codes WHERE site_id IN (0,".$this->this_site_id.") ORDER BY code_string");
	
		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				
				$row_data = array(
					'code_id' => $row['code_id'],
					'site_id' => $row['site_id'],
					'code_string' => $row['code_string'],
					'destination_group' => $row['destination_group']
				);
				
				$registration_code_data[ $row['code_id'] ] = $row_data;
	
			}
		}
		
		// Open table
		
		$DSP->body .=  $DSP->table_open(array(
			'class' => 'tableBorder',
			'width' => '100%')
		);
		
		// Header row
		
		$DSP->body .= $DSP->table_row(array(
			'cell1' => array('valign' => "middle", 'class' => 'tableHeadingAlt', 'text' => "", 'width' => "7%"),
			'cell2' => array('valign' => "middle", 'class' => 'tableHeadingAlt', 'text' => "Registration code", 'width'  => "40%"),
			'cell3' => array('valign' => "middle", 'class' => 'tableHeadingAlt', 'text' => "Destination group", 'width'  => "30%"),
			'cell4' => array('valign' => "middle", 'class' => 'tableHeadingAlt', 'text' => "Site", 'width'  => "33%")
		));
		
		// Form field rows for EXISTING CODES
		
		foreach($registration_code_data as $row)
		{
	
			$current_zebra_class = $this->zebra_stripe();
			
			$code_id_content = $DSP->input_hidden('code_id_'.$row['code_id'], $row['code_id']);
			
			$code_string_content = $DSP->input_text('code_string_'.$row['code_id'], $row['code_string'], '20', '80', 'input', '');
			
			$destination_group_content = $DSP->input_select_header('destination_group_'.$row['code_id'])
				.$this->group_menu($row['destination_group'])
				.$DSP->input_select_footer();
			
			$site_id_content = "";
		
			if (!$this->msm_enabled)
			{
				$site_id_content = "-".$DSP->input_hidden('site_id_new', $row['site_id']);
			}
			else
			{
				$site_id_content = $DSP->input_select_header('site_id_'.$row['code_id'])
					.$DSP->input_select_option(0, "All sites", ($row['site_id'] == 0))
					.$DSP->input_select_option($this->this_site_id, "This site: ".$PREFS->ini('site_label'), ($row['site_id'] == $this->this_site_id))
					.$DSP->input_select_footer();
			}
		
			$DSP->body .= $DSP->table_row(array(
				'code_id' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $code_id_content, 'align' => 'center'),
				'code_string' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $code_string_content),
				'destination_group' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $destination_group_content),
				'site_id' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $site_id_content)
			));
	
		}
		
		// Form field rows for NEW CODE
		
		$current_zebra_class = $this->zebra_stripe();
			
		$code_id_content = "(new)".$DSP->input_hidden('code_id_new', "new");
		
		$code_string_content = $DSP->input_text('code_string_new', '', '20', '80', 'input', '');
		
		$destination_group_content = $DSP->input_select_header('destination_group_new')
			.$this->group_menu(0)
			.$DSP->input_select_footer();
		
		$site_id_content = "";
	
		if (!$this->msm_enabled)
		{
			$site_id_content = "-".$DSP->input_hidden('site_id_new', $this->this_site_id);
		}
		else
		{
			$site_id_content = $DSP->input_select_header('site_id_new')
				.$DSP->input_select_option(0, "All sites")
				.$DSP->input_select_option($this->this_site_id, "This site: ".$PREFS->ini('site_label'))
				.$DSP->input_select_footer();
		}
	
		$DSP->body .= $DSP->table_row(array(
			'code_id' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $code_id_content, 'align' => 'center'),
			'code_string' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $code_string_content),
			'destination_group' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $destination_group_content),
			'site_id' => array('valign' => "middle", 'class' => $current_zebra_class, 'text' => $site_id_content)
		));	
		
		// ---------------------------------------------
		//	Close out the form (and table)
		// ---------------------------------------------
		
		$DSP->body .=   $DSP->table_close();
		
		$DSP->body .=   $DSP->qdiv('itemWrapperTop', $DSP->input_submit('Submit','submit_return')." ".$DSP->input_submit('Submit and Finished','submit_finished'));
		$DSP->body .=   $DSP->form_close();

	}



// TODO save_settings() - IN PROGRESS
// TODO execute_registration_code()
// TODO validate_registration_code()


	/**
	* ==============================================
	* Test hook
	* ==============================================
	*
	*/
	function test_hook($str) {
	
		$this->debug_log("Hook test.");
		
	}
	// END hook_test()


	/**
	* ==============================================
	* Save settings
	* ==============================================
	*
	* Processes general settings (serializes the array and updates database rows
	* AND processes registration codes (compares POST values with current database rows, updates table)
	*
	*/
	function save_settings() {
	
		global $PREFS, $DB, $LANG, $IN;
		
		// ---------------------------------------------
		//	MSM prefs
		// ---------------------------------------------
		
		$this->msm_enabled = ($PREFS->ini('multiple_sites_enabled') == "y");
		$this->this_site_id = $PREFS->ini('site_id');

		// ---------------------------------------------
		//	Save general settings
		// ---------------------------------------------
		
		$new_settings = array();
		$new_settings['require_valid_code'] = ($IN->GBL('require_valid_code', 'POST') ? $IN->GBL('require_valid_code', 'POST') : $this->settings['require_valid_code']);
		$new_settings['form_field'] = ($IN->GBL('form_field', 'POST') ? $this->clean_string($IN->GBL('form_field', 'POST'), true) : $this->settings['form_field']);
		$new_settings['bypass_enabled'] = ($IN->GBL('bypass_enabled', 'POST') ? $IN->GBL('bypass_enabled', 'POST') : $this->settings['bypass_enabled']);
		$new_settings['bypass_code'] = ($IN->GBL('bypass_code', 'POST') ? $this->clean_string($IN->GBL('bypass_code', 'POST')) : $this->settings['bypass_code']);
		$new_settings['bypass_form_field'] = ($IN->GBL('bypass_form_field', 'POST') ? $this->clean_string($IN->GBL('bypass_form_field', 'POST'), true) : $this->settings['bypass_form_field']);
		$this->settings = $new_settings;
		
		$DB->query($DB->update_string('exp_extensions', array('settings' => serialize($new_settings)), array('class' => __CLASS__)));

		// ---------------------------------------------
		//	Set up some lists
		// ---------------------------------------------

		$db_data = array();
		$new_data = array();
		$to_do = array();
		$deletes = array();
		$dupes = array();
		
		// ---------------------------------------------
		//	Get a local copy of data from database (the "old" dataset)
		// ---------------------------------------------
		
		$query = $DB->query("SELECT * FROM exp_rogee_registration_codes WHERE site_id IN (0,".$this->this_site_id.")");
	
		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				
				$row_data = array(
					'code_id' => $row['code_id'],
					'site_id' => $row['site_id'],
					'code_string' => $row['code_string'],
					'destination_group' => $row['destination_group']
				);
				
				$db_data[ $row['code_id'] ] = $row_data;
	
			}
		}
		
		// ---------------------------------------------
		//	Get input data, assemble "new" dataset
		//	and set up $deletes and $to_do lists
		// ---------------------------------------------

		// var_dump($IN->GBL('site_id_3', 'POST') !== false);

		foreach($db_data as $row)
		{
			
			$i = $row['code_id'];
			
			$new_data_vals = array(
				'code_id' => $i,
				'code_string' => (($IN->GBL('code_string_'.$i, 'POST') !== false) ? $IN->GBL('code_string_'.$i, 'POST') : $row['code_string']),
				'destination_group' => (($IN->GBL('destination_group_'.$i, 'POST') !== false) ? $IN->GBL('destination_group_'.$i, 'POST') : $row['destination_group']),
				'site_id' => (($IN->GBL('site_id_'.$i, 'POST') !== false) ? $IN->GBL('site_id_'.$i, 'POST') : $row['site_id'])
			);
			
			$new_data[$i] = $new_data_vals;
			
			if ($new_data_vals['code_string'] === "")
			{
				$deletes[] = $i;
			}
			else
			{
				$to_do[$i] = $new_data_vals['code_string'];
			}

		}
		
		// ---------------------------------------------
		//	Delete the codes that were left blank
		// ---------------------------------------------

		if (!empty($deletes))
		{
			$DB->query("DELETE FROM exp_rogee_registration_codes WHERE code_id IN (". implode(",", $deletes) .")");
		}	

		// ---------------------------------------------
		//	Make a list of duplicate code_string values in the "new" dataset
		//	(These will be omitted from processing)
		// ---------------------------------------------

		$code_counts = array_count_values($to_do);
		
		foreach($code_counts as $code => $count)
		{
			if ($count > 1)
			{
				$dupes[] = $code;
			}
		}
		
		$to_do = array_diff($to_do, $dupes);
		
		// var_dump($code_counts);
		// var_dump($to_do);
		// var_dump($dupes);
		
		// TODO --- error message for dupes not edited
		
		// ---------------------------------------------
		//	Update the database
		//	wherever the new dataset (sans dupes) is different from the old
		// ---------------------------------------------
	
		// var_dump($new_data);
		
		foreach ($to_do as $i => $code)
		{
		
			$changes = array();
			
			if ($new_data[$i]['code_string'] != $db_data[$i]['code_string'])
			{
				$changes['code_string'] = $new_data[$i]['code_string'];
			}
			if ($new_data[$i]['destination_group'] != $db_data[$i]['destination_group'])
			{
				$changes['destination_group'] = $new_data[$i]['destination_group'];
			}
			if ($new_data[$i]['site_id'] != $db_data[$i]['site_id'])
			{
				$changes['site_id'] = $new_data[$i]['site_id'];
			}
			
			if (!empty($changes))
			{
				$DB->query($DB->update_string('exp_rogee_registration_codes', $changes, array('code_id' => $i)));
			}
			
		}
		

		// ---------------------------------------------
		//	Add a new code, if one is supplied (and it is not a dupe)
		// ---------------------------------------------		

		if ($IN->GBL('code_string_new', 'POST') != "")
		{	
		
			$i = 'new';
			
			$new_data_vals = array(
				'code_string' => $IN->GBL('code_string_'.$i, 'POST'),
				'destination_group' => (($IN->GBL('destination_group_'.$i, 'POST') !== false) ? $IN->GBL('destination_group_'.$i, 'POST') : 0),
				'site_id' => (($IN->GBL('site_id_'.$i, 'POST') !== false) ? $IN->GBL('site_id_'.$i, 'POST') : 0)
			);
			
			if (!in_array($new_data_vals['code_string'], $dupes) AND !in_array($new_data_vals['code_string'], $to_do))
			{
				$DB->query($DB->insert_string('exp_rogee_registration_codes', $new_data_vals));
			}
			else
			{
				// TODO --- error message for dupes not added
			}
			
		}

		// ---------------------------------------------
		//	Return to the settings form (if I'm not finished editing yet)
		// ---------------------------------------------
			
		if ($IN->GBL('submit_return', 'POST') && $IN->GBL('return_location', 'POST'))
		{
			global $FNS;
			$FNS->redirect($IN->GBL('return_location', 'POST'));
		}
	
	}
	// END save_settings()



	/**
	* ==============================================
	* Group menu
	* ==============================================
	*
	* Whips up the OPTIONS for a select menu using the member groups in the database,
	* optionally marking the option corresponding to the provided group_id as selected
	*
	* @param int: group_id to mark as selected
	* @return string: HTML for select menu OPTIONS
	*
	*/
	private function group_menu($selected = 0) {
		
		global $DB, $DSP;
		
		if (!isset($this->member_group_data))
		{
		
			// Get member group list from the database
			
			$this->member_group_data = array();
			
			$query = $DB->query("SELECT * FROM exp_member_groups WHERE site_id = " . $this->this_site_id);
		
			if ($query->num_rows > 0)
			{
				foreach($query->result as $row)
				{
					$row_data = array(
						'group_id' => $row['group_id'],
						'group_title' => $row['group_title']
					);	
					$this->member_group_data[ $row['group_id'] ] = $row_data;
				}
			}		
		
		}
		
		// Write the options
		
		$options_html = "";
		$options_html .= $DSP->input_select_option(0, "(Default member group)", (0 == $selected));
		foreach($this->member_group_data as $row)
		{
			$options_html .= $DSP->input_select_option($row['group_id'], $row['group_id'].": ".$row['group_title'], ($row['group_id'] == $selected));
		}
		return $options_html;
		
	}



	/**
	* ==============================================
	* Zebra stripe
	* ==============================================
	*
	* Provides a switching classname for zebra-striping tables
	*
	* @return string: table cell class name
	*
	*/
	private function zebra_stripe() {
		
		switch ($this->zebra_class) {
			case "":
				$this->zebra_class = "tableCellOne";
				return $this->zebra_class;
				break;
			case "tableCellOne":
				$this->zebra_class = "tableCellTwo";
				return $this->zebra_class;
				break;
			case "tableCellTwo":
				$this->zebra_class = "tableCellOne";
				return $this->zebra_class;
				break;
		}
		
	}



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
	private function clean_string($str, $remove_spaces) {
	
		$clean = preg_replace("/[^a-zA-Z0-9\/_| -]/", '', $str);
		$clean = trim($clean, '-');
		$clean = preg_replace("/[\/|_]+/", '_', $clean);
		if ($remove_spaces)
		{
			$clean = preg_replace("/ /", '_', $clean);
		}
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