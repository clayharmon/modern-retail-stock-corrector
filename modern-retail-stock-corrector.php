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
