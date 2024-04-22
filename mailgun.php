<?php
/**
Plugin Name: Gravity Forms Mailgun Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with Mailgun, allowing Gravity Forms notifications to be sent from your Mailgun account.
Version: 1.4.0
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityformsmailgun
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2020 Rocketgenius, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 **/

defined( 'ABSPATH' ) or die();

define( 'GF_MAILGUN_VERSION', '1.4.0' );

// If Gravity Forms is loaded, bootstrap the Mailgun Add-On.
add_action( 'gform_loaded', array( 'GF_Mailgun_Bootstrap', 'load' ), 5 );

/**
 * Class GF_Mailgun_Bootstrap
 *
 * Handles the loading of the Mailgun Add-On and registers with the Add-On Framework.
 */
class GF_Mailgun_Bootstrap {

	/**
	 * If the Add-On Framework exists, Mailgun Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load(){

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-mailgun.php' );

		GFAddOn::register( 'GF_Mailgun' );

	}

}

/**
 * Returns an instance of the GF_Mailgun class
 *
 * @see    GF_Mailgun::get_instance()
 *
 * @return GF_Mailgun
 */
function gf_mailgun() {
	return GF_Mailgun::get_instance();
}
