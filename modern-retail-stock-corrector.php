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
		<?php cc_mrsc_print_products_with_incorrect_stock_status(); ?>
  </div>
  <?php
}


function cc_mrsc_get_products_with_stock(){
	global $wpdb;
  return $wpdb->get_results("
            SELECT p.post_parent, max(pm.meta_value) as stock, pm2.meta_value as stock_status
            FROM {$wpdb->prefix}posts as p
            JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            JOIN {$wpdb->prefix}postmeta as pm2 ON p.post_parent = pm2.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_stock'
            AND pm.meta_value IS NOT NULL
            AND pm2.meta_key = '_stock_status'
            GROUP BY p.post_parent
        ");
}


function cc_mrsc_find_incorrect_stock_status_product_ids(){
	$products = cc_mrsc_get_products_with_stock();
	$incorrect_stock_products = [];

	foreach($products as $product){
		$correct_label = ($product->stock > 0) ? "instock" : "outofstock";
		$current_label = $product->stock_status;

		if($correct_label !== $current_label){
			array_push($incorrect_stock_products, $product->post_parent);
		}
	}

	return $incorrect_stock_products;
}

function cc_mrsc_print_products_with_incorrect_stock_status(){
	$product_ids_with_incorrect_stock_status = cc_mrsc_find_incorrect_stock_status_product_ids();
	foreach($product_ids_with_incorrect_stock_status as $product_id){
		echo $product_id . "<br>";
	}
}

?>