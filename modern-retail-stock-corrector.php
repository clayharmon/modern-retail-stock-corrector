<?php
/*
Plugin Name: Modern Retail Stock Corrector 
Description: Checks for incorrect stock status for product variations.
Author: Clay Harmon
Version: 1.0.0 
Text Domain: cc-mrsc
*/

require __DIR__.'/vendor/plugin-update-checker/plugin-update-checker.php';
$cc_mrsc_update_checker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/clayharmon/modern-retail-stock-corrector/',
	__FILE__,
	'cc-mrsc'
);
$cc_mrsc_update_checker->getVcsApi()->enableReleaseAssets();

function cc_mrsc_add_settings_page(){
  add_submenu_page(
    'tools.php',
    'Fix Stocks',
    'Fix Stocks',
    'manage_options',
    'cc-mrsc',
    'cc_mrsc_settings_html'
  );
}
add_action( 'admin_menu', 'cc_mrsc_add_settings_page' );

function cc_mrsc_settings_html() {
	?>
  <div class="wrap">
    <h1 class="wp-heading-inline">Fix Stocks</h1>
    <hr class="wp-header-end">
  </div>
  <?php
}
?>