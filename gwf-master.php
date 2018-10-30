<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;	
}

/*
Plugin Name: Google Web Fonts Master
Plugin URI: 
Description: <strong>Simple and Powerful Management of Google Web Fonts.</strong> Contains all of the Google fonts listed in <a href="http://www.google.com/fonts" target="_blank">http://www.google.com/fonts</a>, and let you easily customize the fonts of your WordPress powered site.
Version: 1.0
Author: imaf
Author URI: 
License: GPL2
*/
/*  
	Copyright 2013  imaf  (email : dsun3463@sina.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('PNQ_GWF_INC', '/includes/');
define('GWF_PLUGIN_MAIN_FILE', __FILE__);
require_once( plugin_dir_path(  __FILE__ ) . PNQ_GWF_INC . 'class-gwf-master.php' );

global $pnq_gwf_master;
$pnq_gwf_master = PNQ_GWF_Master::instance();

function gwf_master_on_activation() {
	
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
//	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
//	check_admin_referer( "activate-plugin_{$plugin}" );

	// init options in database if not exists
	global $pnq_gwf_master;
	$pnq_gwf_master -> init_options();
}
register_activation_hook( __FILE__, 'gwf_master_on_activation' );

function gwf_master_on_deactivation() {
	
	if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
	}
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "deactivate-plugin_{$plugin}" );
}
register_deactivation_hook( __FILE__, 'gwf_master_on_deactivation' );

function gwf_master_on_uninstall() {
	
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	check_admin_referer( 'bulk-plugins' );

//	if ( __FILE__ != WP_UNINSTALL_PLUGIN ) {
//		return;
//	}
	
	// clean database when uninstalling plugin
	global $pnq_gwf_master;
	$pnq_gwf_master -> clean_database();
}
register_uninstall_hook( __FILE__, 'gwf_master_on_uninstall' );