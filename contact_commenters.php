<?php
/*
Plugin Name: Contact Commenters
Plugin URI: http://www.dollarshower.com/contact-commenters-wordpress-plugin/
Description: Improves your interaction levels with commenters and hence increases your readership by contacting selected set of commenters via email from the 'Manage' or 'Tools' page (Sample usage scenarios: Thank your new commenters for the visit, thank long term active commenters for the continued support, inform them about new services, send them greetings on special occasions or events, analyze the commenters' contribution etc)

Version: 1.0
Author: Ajith Prasad Edassery
Author URI: http://www.dollarshower.com
*/


// Admin -> Manage tab)
function contact_commenters_manage_page() {
    include(dirname(__FILE__).'/contact_commenters_manage.php');
}


// Hook functions
function addContactCommentersAdminPage() {

    if (function_exists('add_management_page')) {
		 add_management_page('Contact Commenters', 'Contact Commenters', 8, __FILE__, 'contact_commenters_manage_page');
    }
}

// Hook
add_action('admin_menu', 'addContactCommentersAdminPage');

?>
