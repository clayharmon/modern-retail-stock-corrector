<?php
/*
Plugin Name: Modern Retail Stock Corrector 
Description: Checks for incorrect stock status for product variations.
Author: Clay Harmon
Version: 1.2.0 
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
    <form style="padding:10px 0;" method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
      <?php wp_nonce_field( 'cc_mrsc_admin_update_settings', 'cc_mrsc_admin_verify' ); ?>
      
      <input type="hidden" name="action" value="cc_mrsc_admin_form" />
      <input style="margin:10px 0;" type="submit" name="mrsc_fix_all" id="submit" class="button button-primary" value="Fix All Stock Statuses" />
      <input style="margin:10px 0;" type="submit" name="mrsc_fix_five" id="submit" class="button button-primary" value="Fix 5 Stock Statuses" />
    </form>
    <h2>Products with Incorrect Stock Status:</h2>
		<?php
		cc_mrsc_print_table_products_with_incorrect_stock_status();
		?>
  </div>
  <?php
}


function cc_mrsc_get_products_with_incorrect_status($limit = -1){
	global $wpdb;
  $sql_string = "
            SELECT p.ID, p.post_parent, max(pm.meta_value) as stock, pm2.meta_value as stock_status
            FROM {$wpdb->prefix}posts as p
            JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            JOIN {$wpdb->prefix}postmeta as pm2 ON p.post_parent = pm2.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_stock'
            AND pm.meta_value > 0
            AND pm2.meta_key = '_stock_status'
            AND pm2.meta_value = 'outofstock'
            GROUP BY p.post_parent
  ";
  $sql_string .= ($limit >= 0) ? " LIMIT 0," . esc_sql( $limit ) : "";
  return $wpdb->get_results($sql_string);
}

function cc_mrsc_get_variations_with_incorrect_status($limit = -1){
	global $wpdb;
  $sql_string = "
            SELECT p.ID, p.post_parent, pm.meta_value as stock, pm2.meta_value as stock_status
            FROM {$wpdb->prefix}posts as p
            JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            JOIN {$wpdb->prefix}postmeta as pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_stock'
            AND pm.meta_value > 0
            AND pm2.meta_key = '_stock_status'
            AND pm2.meta_value = 'outofstock'
  ";
  $sql_string .= ($limit >= 0) ? " LIMIT 0," . esc_sql( $limit ) : "";
  return $wpdb->get_results($sql_string);
}

function cc_mrsc_print_table_products_with_incorrect_stock_status(){
	$products_with_incorrect_stock_status = cc_mrsc_get_products_with_incorrect_status();
	$total_products = count($products_with_incorrect_stock_status);
  
  $variations_with_incorrect_stock_status = cc_mrsc_get_variations_with_incorrect_status();
  $total_variations = count($variations_with_incorrect_stock_status);
	if($total_products !== 0 ){
	?>
    <p>Total: <?php echo $total_products ?></p>
		<table class="wp-list-table widefat fixed striped table-view-list posts">
			<thead>
				<th scope="col" id="name" class="manage-column column-name"><span>Name</span></th>
			</thead>
			<tbody id="the-list">
				<?php
				foreach($products_with_incorrect_stock_status as $queried_product){
					$product = wc_get_product( $queried_product->post_parent );
					?>
					<tr>
						<td>
							<a href='/wp-admin/post.php?post=<?php echo $queried_product->post_parent; ?>&action=edit' target='_blank'>
								<?php echo $product->get_name(); ?>
							</a>
						</td>
					</tr>
						<?php
				}
				?>
			</tbody>
		</table>
	<?php
	} else {
		echo "<p>All products are correct.</p>";
	}
	if($total_variations!== 0 ){
	?>
    <p>Total Variations: <?php echo $total_variations?></p>
		<table class="wp-list-table widefat fixed striped table-view-list posts">
			<thead>
				<th scope="col" id="name" class="manage-column column-name"><span>Name</span></th>
			</thead>
			<tbody id="the-list">
				<?php
				foreach($variations_with_incorrect_stock_status as $queried_variation){
					$product = wc_get_product( $queried_variation->ID );
					?>
					<tr>
						<td>
							<a href='/wp-admin/post.php?post=<?php echo $queried_variation->post_parent; ?>&action=edit' target='_blank'>
								<?php echo $product->get_formatted_name(); ?>
							</a>
						</td>
					</tr>
						<?php
				}
				?>
			</tbody>
		</table>
	<?php
	} else {
		echo "<p>All variations are correct.</p>";
	}
}


function cc_mrsc_correct_all_incorrect_stock_statuses($limit = -1){
  $products_with_incorrect_stock_status = cc_mrsc_get_products_with_incorrect_status($limit);
  $variations_with_incorrect_stock_status= cc_mrsc_get_variations_with_incorrect_status($limit);
  foreach($products_with_incorrect_stock_status as $queried_product){
    wc_update_product_stock_status($queried_product->ID, "instock");
	}
  foreach($variations_with_incorrect_stock_status as $queried_variation){
    $status = "instock";
    update_post_meta( $queried_variation->ID, '_stock_status', wc_clean( $status ) );
	}
}


function cc_mrsc_admin_nonce_notice() {
  if(isset($_GET['nonce_verify']) && $_GET['nonce_verify'] === 'false'){
    echo '<div class="error notice"><p>Sorry, your nonce did not verify. Please try again.</p></div>';
  }
}
add_action( 'admin_notices', 'cc_mrsc_admin_nonce_notice' );

add_action( 'admin_action_cc_mrsc_admin_form', 'cc_mrsc_admin_form_action' );
function cc_mrsc_admin_form_action(){
  if ( !isset( $_POST['cc_mrsc_admin_verify'] ) || !wp_verify_nonce( $_POST['cc_mrsc_admin_verify'], 'cc_mrsc_admin_update_settings' ) ) {
    wp_redirect( $_SERVER['HTTP_REFERER'] . '&nonce_verify=false');
    exit();
  }

  if( isset( $_POST['mrsc_fix_all'] ) ){
    cc_mrsc_correct_all_incorrect_stock_statuses();
  }
  if( isset( $_POST['mrsc_fix_five'] ) ){
    cc_mrsc_correct_all_incorrect_stock_statuses(5);
  }

  wp_redirect( $_SERVER['HTTP_REFERER'] . '&nonce_verify=true' );
  exit();
}


register_activation_hook( __FILE__, 'cc_mrsc_create_hourly_stock_corrector_schedule' );
function cc_mrsc_create_hourly_stock_corrector_schedule(){
  $timestamp = wp_next_scheduled( 'cc_mrsc_create_hourly_stock_corrector' );

  if( !$timestamp ){
    wp_schedule_event( time(), 'hourly', 'cc_mrsc_create_hourly_stock_corrector' );
  }
}

add_action( 'cc_mrsc_create_hourly_stock_corrector', 'cc_mrsc_correct_all_incorrect_stock_statuses' );

register_deactivation_hook( __FILE__, 'cc_mrsc_remove_hourly_stock_corrector_schedule' );
function cc_mrsc_remove_hourly_stock_corrector_schedule(){
  wp_clear_scheduled_hook( 'cc_mrsc_create_hourly_stock_corrector' );
}
?>