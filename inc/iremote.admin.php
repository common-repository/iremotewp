<?php
/**
 * Register the irem_verify_key settings
 *
 * @return null
 */


function iremo_setup_admin() {
	register_setting( 'irem-settings', 'irem_verify_key' );
}

add_action( 'admin_menu', 'iremo_setup_admin' );
add_action( 'admin_menu', 'iRemoteWPMenu');


//admin_url("options-general.php?page=iRemoteWP");

/** Step 1. */
function iRemoteWPMenu() {
	add_menu_page( 'iRemoteWP Settings', 'iRemoteWP Settings', 'manage_options', 'iRemoteWP', 'iRemoteWPOptions','dashicons-desktop');
}


/** Step 3. */
function iRemoteWPOptions() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

function page_tabs( $current = 'settings' ) {
    $tabs = array(
        'settings'   => __( 'Settings', 'iremotewp' ), 
        'backups'  => __( 'Backups', 'iremotewp' ),
        'support'  => __( 'Support', 'iremotewp' ),
        'freebies'  => __( 'Freebies', 'iremotewp' )
    );
    
    $html = '<a href="'.admin_url("options-general.php?page=iRemoteWP").'" target="_self">
             	<img src="' . plugins_url( 'assets/img/iremotewp-settings.png' , dirname(__FILE__) ) . '" alt="">
             </a>
             
             <h2 class="nav-tab-wrapper iremotewp_settings">';
    
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? 'nav-tab-active' : '';
        $html .= '<a class="nav-tab ' . $class . '" href="options-general.php?page=iRemoteWP&tab=' . $tab . '">' . $name . '</a>';
    }

    $html .= '</h2>';
    echo $html;
}

// Code displayed before the tabs (outside)
// Tabs
$tab = ( ! empty( $_GET['tab'] ) ) ? esc_attr( $_GET['tab'] ) : 'settings';
page_tabs( $tab );

if ( $tab == 'settings' ) { ?>

    <div id="iremotewp_settings">

           <div>

			<p class="iremotewp_messages">
				<?php _e( 'iRemoteWP is almost ready. you need to enter your ','iremotewp' ); ?> <a href="<?php echo esc_url( iremo_get_irem_url() ); ?>" target="_blank"><?php _e( 'iRemoteWP site key','iremotewp' ); ?></a> <?php _e( 'below. ','iremotewp' ); ?> <?php _e( 'Once you added your key, you can start to manage your site on iremotewp.com. ','iremotewp' ); ?> <br><?php _e( 'If you didn\'t create any site key for this site ','iremotewp' ); ?> <a href="<?php echo esc_url( iremo_get_irem_url() ); ?>" target="_blank"> <?php _e( 'Click here and get your site key free.','iremotewp' ); ?></a> <?php _e( 'If you are using multisite WordPress, you need to enter site key for only main site.','iremotewp' ); ?>
			</p>

			</div>


        <div class="iremotewp_settings">

		<form method="post" action="options.php">

				<input type="text" value="<?php echo get_option( 'irem_verify_key' ); ?>" class="code regular-text irem_verify_key" id="irem_verify_key" name="irem_verify_key" />

				<input type="submit" value="<?php _e( 'Save','iremotewp' ); ?>" class="button-primary irem_save_key" />

				<style>#message { display : none; }</style>

				<?php settings_fields( 'irem-settings' );

				// Output any sections defined for page sl-settings
				do_settings_sections( 'irem-settings' ); ?>

		</form>

	</div>

<?php } ?>

<?php

if ( $tab == 'backups' ) { ?>

    <div id="iremotewp_settings">

           <div>

			<p class="iremotewp_messages">
				<?php _e( 'Congratulations you can access your backups on ','iremotewp' ); ?> 
					<a href="<?php echo esc_url( iremo_get_irem_url() ); ?>/dashboard/" target="_blank">
						<?php _e( 'iRemoteWP Dashboard','iremotewp' ); ?>
					</a>
			</p>

			</div>


<?php } ?>

<?php

if ( $tab == 'support' ) { ?>

    <div id="iremotewp_settings">

           <div>

			<p class="iremotewp_messages">
				 
				<h2 style="margin-bottom: 0;"><span class="dashicons dashicons-format-chat" style="font-size: 32px; display: inline-table;"></span> <?php _e( 'Questions Achive ? ','iremotewp' ); ?></h2>
				<?php _e( 'Before asking your question you can look or search our questions archive!.. ','iremotewp' ); ?><br>
				<a href="<?php echo esc_url( iremo_get_irem_url() ); ?>/support/questions/" target="_blank">
						<?php _e( 'Take a look at Question Archive now!','iremotewp' ); ?>
				</a>

				<br>
				<br>

				<h2 style="margin-bottom: 0;"><span class="dashicons dashicons-admin-users" style="font-size: 32px; display: inline-table;"></span> <?php _e( 'Find an Answer ? ','iremotewp' ); ?></h2>
				<?php _e( 'You are the right section! you can find all your answer about remote wp management service. ','iremotewp' ); ?><br>
				<a href="<?php echo esc_url( iremo_get_irem_url() ); ?>/support/ask-question/" target="_blank">
						<?php _e( 'Ask your question now!','iremotewp' ); ?>
				</a>
			</p>

			</div>


<?php } ?>

<?php

if ( $tab == 'freebies' ) { ?>

    <div id="iremotewp_settings">

           <div>

			<p class="iremotewp_messages">
				<?php _e( 'When you purchased any iRemoteWP service you can download our premium WordPress themes & perks freely. <br> They will be ready for you! ','iremotewp' ); ?> 
				<?php _e( 'You can access your freebies on ','iremotewp' ); ?> 
					<a href="<?php echo esc_url( iremo_get_irem_url() ); ?>/perks/" target="_blank">
						<?php _e( 'iRemoteWP Freebies','iremotewp' ); ?>
					</a>
			</p>

			</div>


<?php } ?>


<?php
}

/**
 * Add API Key form
 *
 * Only shown if no API Key
 *
 * @return null
 */
function iremo_add_api_key_admin_notice() { ?>

	<div id="iremote_info" class="error">

		<form method="post" action="options.php">

				<p>
					<?php _e( 'Congratulations iRemoteWP is ready! but you should enter your site key before to use.', 'iremotewp' ); ?> <br /> <?php _e( 'Please goto the', 'iremotewp' );?> <strong><a href="<?php echo admin_url("options-general.php?page=iRemoteWP"); ?>" target="_self"> <?php _e( 'iRemoteWP settings page', 'iremotewp' );?></a></strong> <?php _e( 'and enter your site key to continue.', 'iremotewp' );?>
				</p>

				<p>
					<strong style="color: #cc0000"><?php _e( 'IF YOU DO NOT HAVE A KEY AND BEFORE MOVING FORWARD YOU SHOULD GET YOUR KEY ','iremotewp' ); ?> <a href="<?php echo esc_url( iremo_get_irem_url() ); ?>" target="_blank"><?php _e( 'FOR FREE','iremotewp' ); ?></a></strong>
				</p>

			<style>#message { display : none; }</style>

			<?php settings_fields( 'irem-settings' );

			// Output any sections defined for page sl-settings
			do_settings_sections( 'irem-settings' ); ?>

		</form>

	</div>


<?php }

if ( ! iremo_get_site_keys() && $_GET['page'] <> 'iRemoteWP')
	add_action( 'admin_notices', 'iremo_add_api_key_admin_notice' );

/**
 * Success message for a newly added API Key
 *
 * @return null
 */

function iremo_api_key_added_admin_notice() {

	if ( function_exists( 'get_current_screen' ) && get_current_screen()->base != 'plugins' || empty( $_GET['settings-updated'] ) || ! iremo_get_site_keys() )
		return; ?>

	<div id="iremote_info" class="updated">
		<p><strong><?php _e( 'iRemoteWP API Key successfully added' ); ?></strong>, close this window to go back to <a href="<?php echo esc_url( iremo_get_irem_url( '/system/' ) ); ?>"><?php _e( 'iRemoteWP','iremotewp' ); ?></a>.</p>
	</div>

<?php }
add_action( 'admin_notices', 'iremo_api_key_added_admin_notice' );

/**
 * Delete the API key on activate and deactivate
 *
 * @return null
 */
function iremo_deactivate() {
	$sitekey_url = site_url();
	$find_h = '#^http(s)?://#';
	$replace = '';
	$sitekey_url = rtrim(preg_replace( $find_h, $replace, $sitekey_url ), '/').'/';
	$sitekey_new = @file_get_contents(IREM_API_URL.'sitekey/?siteurl='.$sitekey_url);

	if($sitekey_new){
		delete_option( 'irem_verify_key' );
		delete_option( '_iremo_ipfilter_ftype' );
		delete_option( '_iremo_ipfilter_ips' );
		delete_option( '_iremo_ipfilter_bypass_url' );
		add_option( 'irem_verify_key', $sitekey_new, '', 'yes' );
	}
}

// Plugin activation and deactivation
add_action( 'activate_' . IREMOTE_PLUGIN_SLUG . '/plugin.php', 'iremo_deactivate' );
add_action( 'deactivate_' . IREMOTE_PLUGIN_SLUG . '/plugin.php', 'iremo_deactivate' );