<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(

//----------------------------------------
// BASIC INFORMATION ABOUT THE EXT
//----------------------------------------

"registration_codes_module_name" =>
"Registration Codes (RogEE)",

"registration_codes_module_description" =>
"Automatically places new members into specified groups according to registration codes.",

//----------------------------------------

'rogee_rc_language' => 'RogEE Registration Codes speaks English!',

'rogee_rc_general_preferences' => 'General Preferences',
// -- FUTURE: -- // 'rogee_rc_replace_captcha' => 'Replace the default CAPTCHA field?',
'rogee_rc_require_valid_code' => 'Require valid code to register?',
'rogee_rc_enable_multi_site' => 'Enable multi-site codes?',
'rogee_rc_form_field' => 'Form field [name]',
'rogee_rc_instructions_enable_msm' => "<em>Enable MSM to use multi-site features.</em>",

'rogee_rc_registration_codes' => 'Registration Codes',
'rogee_rc_destination_group' => 'Destination group',
'rogee_rc_code_string' => 'Code / passphrase',
'rogee_rc_site_id' => 'Site',

'rogee_rc_new' => '(new)',
'rogee_rc_this_site' => "(This site)",
'rogee_rc_all_sites' => "(All sites)",
'rogee_rc_default_group' => "(Default group)",

'rogee_rc_instructions_code_string' => "<em>To delete a row, leave the text box blank.</em>",
'rogee_rc_instructions_site_id_disabled' => "<em>Activate MSM to enable multi-site codes.</em>",
'rogee_rc_instructions_site_id_enabled' => "",
'rogee_rc_instructions_destination_group' => "",
'rogee_rc_instructions_code_id' => "",

'rogee_rc_save' => 'Save',
'rogee_rc_save_finished' => 'Save and Exit',

'rogee_rc_found_duplicates_error' => "Some registration codes could not be saved, because duplicate codes are not allowed. The following duplicates were found in the submission: ",
'rogee_rc_form_field_error' => "Some invalid characters were removed from the Form field name setting. (Only alphanumeric characters, dashes, and underscores are allowed in form field names.)",

'rogee_rc_no_valid_code' => "You must provide a valid registration code to complete your registration. (note: Registration codes, like passwords, are case-sensitive.)",

// END
''=>''
);
/* End of file lang.registration_codes.php */
/* Location: ./system/expressionengine/third_party/registration_codes/language/english/lang.registration_codes.php */