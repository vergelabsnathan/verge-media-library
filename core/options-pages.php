<?php

if ( ! defined( 'ABSPATH' ) )
    exit;



/**
 *  vergeml_register_setting
 *
 *  @since    1.0
 *  @created  03/08/13
 */

add_action( 'admin_init', 'vergeml_register_setting' );

function vergeml_register_setting() {

    // plugin settings: media library
    register_setting(
        'media-library', //option_group
        'vergeml_lib_options', //option_name
        'vergeml_lib_options_validate' //sanitize_callback
    );

    // plugin settings: taxonomies
    register_setting(
        'media-taxonomies', //option_group
        'vergeml_taxonomies', //option_name
        'vergeml_taxonomies_validate' //sanitize_callback
    );

    // plugin settings: taxonomies options
    register_setting(
        'media-taxonomies', //option_group
        'vergeml_tax_options', //option_name
        'vergeml_tax_options_validate' //sanitize_callback
    );

    // plugin settings: mime types
    register_setting(
        'mime-types', //option_group
        'vergeml_mimes', //option_name
        'vergeml_mimes_validate' //sanitize_callback
    );

    // plugin settings: network settings
    // no validation callback here
    // called explicitly in vergeml_update_network_settings
    register_setting(
        'eml-network-settings', //option_group
        'vergeml_network_options' //option_name
    );

    // plugin settings: all settings backup before import
    register_setting(
        'vergeml_backup', //option_group
        'vergeml_backup' //option_name
    );

    // plugin settings: remote admin notices
    register_setting(
        'vergeml_notices', //option_group
        'vergeml_notices' //option_name
    );
}



/**
 *  vergeml_admin_media_menu
 *
 *  @since    2.6
 *  @created  28/04/18
 */

add_action( 'admin_menu', 'vergeml_admin_media_menu', 12 );

function vergeml_admin_media_menu() {

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['media_settings'] )
            return;
    }


    $eml_media_options_page = add_submenu_page(
        '',
        __('Media Settings','verge-media-library'), //page_title
        '',                                //menu_title
        'manage_options',                  //capability
        'media',                           //menu_slug
        'vergeml_print_media_settings'  //callback
    );

    $eml_medialibrary_options_page = add_submenu_page(
        'options-general.php',
        __('Media Library','verge-media-library') . ' &lsaquo; ' . __('Media Settings','verge-media-library'),
        __('Media Library','verge-media-library'),
        'manage_options',
        'media-library',
        'vergeml_print_media_library_options'
    );

    $eml_taxonomies_options_page = add_submenu_page(
        'options-general.php',
        __('Media Taxonomies','verge-media-library') . ' &lsaquo; ' . __('Media Settings','verge-media-library'),
        __('Media Taxonomies','verge-media-library'),
        'manage_options',
        'media-taxonomies',
        'vergeml_print_taxonomies_options'
    );

    $eml_mimetype_options_page = add_submenu_page(
        'options-general.php',
        __('MIME Types','verge-media-library') . ' &lsaquo; ' . __('Media Settings','verge-media-library'),
        __('MIME Types','verge-media-library'),
        'manage_options',
        'mime-types',
        'vergeml_print_mimetypes_options'
    );


    add_action( 'load-' . $eml_media_options_page, 'vergeml_load_media_options_page' );
    add_action( $eml_media_options_page, 'vergeml_media_options_page' );

    add_action('admin_print_scripts-' . $eml_medialibrary_options_page, 'vergeml_medialibrary_options_page_scripts');
    add_action('admin_print_scripts-' . $eml_taxonomies_options_page, 'vergeml_taxonomies_options_page_scripts');
    add_action('admin_print_scripts-' . $eml_mimetype_options_page, 'vergeml_mimetype_options_page_scripts');
}



/**
 *  vergeml_admin_utility_menu
 *
 *  @since    2.6
 *  @created  28/04/18
 */

add_action( 'admin_menu', 'vergeml_admin_utility_menu' );

function vergeml_admin_utility_menu() {

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['utilities'] )
            return;
    }


    $eml_options_page = add_options_page(
       __('Verge Media Library Utilities','verge-media-library'),
       __('Media Utilities','verge-media-library'),
       'manage_options',
       'eml-settings',
       'vergeml_print_settings'
    );

    add_action('admin_print_scripts-' . $eml_options_page, 'vergeml_options_page_scripts');
}



/**
 *  vergeml_network_admin_menu
 *
 *  @since    2.6
 *  @created  22/04/18
 */

add_action( 'network_admin_menu', 'vergeml_network_admin_menu' );

function vergeml_network_admin_menu() {

    $eml_network_options_page = add_submenu_page(
        'settings.php',
        __('Verge Media Library Utilities','verge-media-library'),
        __('Media Utilities','verge-media-library'),
        'manage_options',
        'eml-settings',
        'vergeml_print_network_settings'
    );

    add_action('admin_print_scripts-' . $eml_network_options_page, 'vergeml_options_page_scripts');
}



/**
 *  vergeml_submenu_order
 *
 *  Custom admin media menu.
 *
 *  @since    2.6
 *  @created  04/03/18
 */

add_action( 'admin_menu', 'vergeml_submenu_order', 1001 );

function vergeml_submenu_order() {

    global $submenu;


    if ( ! isset( $submenu['options-general.php'] ) ) {
        return;
    }

    $media_key = 0;
    $media_items = array();
    $page = isset( $_GET['page'] ) && in_array( $_GET['page'], array('media','media-library','media-taxonomies','mime-types') ) ? $_GET['page'] : '';
    $settings_menu = array_values( $submenu['options-general.php'] );

    foreach( $settings_menu as $key => $item ) {

        if ( 'options-media.php' === $item[2] ) {

            $media_key = $key;
            $settings_menu[$key][2] = 'options-general.php?page=media';
            $settings_menu[$key][4] = ( 'media' === $page ) ? 'eml-menu-media current' : 'eml-menu-media';
        }

        if ( in_array( $item[2], array('media-library','media-taxonomies','mime-types') ) ) {

            $item[4] = 'eml-media-submenu';
            $media_items[] = $item;

            unset( $settings_menu[$key] );
        }
    }

    array_splice( $settings_menu, $media_key+1, 0, $media_items );
    $submenu['options-general.php'] = $settings_menu;
}



/**
 *  vergeml_load_media_options_page
 *
 *  Ensure compatibility with default options-media.php for third-parties
 *
 *  @since    2.3
 *  @created  14/06/16
 */

function vergeml_load_media_options_page() {

    global $pagenow, $title;

    // to avoid the unknown global value (php 8)
    // @todo: look deeper
    $title = '';

    $hook_suffix = $pagenow = 'options-media.php';

    do_action( "load-{$hook_suffix}" );
    do_action( 'admin_enqueue_scripts', $hook_suffix );
    do_action( "admin_print_styles-{$hook_suffix}" );
    do_action( "admin_print_scripts-{$hook_suffix}" );
    do_action( "admin_head-{$hook_suffix}" );

    add_filter( 'admin_body_class', 'vergeml_admin_body_class_for_media_options_page' );
    add_filter( 'admin_title', 'vergeml_admin_title_for_media_options_page', 10, 2 );
}



/**
 *  vergeml_admin_body_class_for_media_options_page
 *
 *  Ensure compatibility with default options-media.php for third-parties
 *
 *  @since    2.3.6
 *  @created  16/12/16
 */

function vergeml_admin_body_class_for_media_options_page( $admin_body_class ) {

    $hook_suffix = 'options-media.php';

    $admin_body_class .= preg_replace('/[^a-z0-9_\-]+/i', '-', $hook_suffix);

    return $admin_body_class;
}



/**
 *  vergeml_admin_title_for_media_options_page
 *
 *  @since    2.3.6
 *  @created  16/12/16
 */

function vergeml_admin_title_for_media_options_page( $admin_title, $title ) {

    $admin_title = __('Media Settings','verge-media-library') . $admin_title;

    return $admin_title;
}



/**
 *  vergeml_media_options_page
 *
 *  Ensure compatibility with default options-media.php for third-parties
 *
 *  @since    2.3.6
 *  @created  16/12/16
 */

function vergeml_media_options_page() {

    $hook_suffix = 'options-media.php';

    do_action( $hook_suffix );
}



/**
 *  vergeml_print_media_settings_tabs
 *
 *  @since    2.2.1
 *  @created  11/04/16
 */

function vergeml_print_media_settings_tabs( $active ) { ?>

    <h2 class="nav-tab-wrapper wp-clearfix" id="eml-options-media-tabs">
        <a href="<?php echo get_admin_url( null, 'options-general.php?page=media' ); ?>" class="nav-tab<?php echo ( 'media' == $active ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'verge-media-library' ); ?></a>
        <a href="<?php echo get_admin_url( null, 'options-general.php?page=media-library' ); ?>" class="nav-tab<?php echo ( 'library' == $active ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Media Library', 'verge-media-library' ); ?></a>
        <a href="<?php echo get_admin_url( null, 'options-general.php?page=media-taxonomies' ); ?>" class="nav-tab<?php echo ( 'taxonomies' == $active ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Media Taxonomies', 'verge-media-library' ); ?></a>
        <a href="<?php echo get_admin_url( null, 'options-general.php?page=mime-types' ); ?>" class="nav-tab<?php echo ( 'mimetypes' == $active ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'MIME Types', 'verge-media-library' ); ?></a>
    </h2>

<?php
}



/**
 *  vergeml_print_media_settings
 *
 *  Based on wp-admin/options-media.php
 *
 *  @since    2.2.1
 *  @created  11/04/16
 */

function vergeml_print_media_settings() {

    if ( ! current_user_can( 'manage_options' ) )
        wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['media_settings'] )
            wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );
    }

    settings_errors();


    $title = __('Media Settings');
    ?>

    <div class="wrap">
    <h1><?php echo esc_html( $title ); ?></h1>

    <?php vergeml_print_media_settings_tabs( 'media' ); ?>

    <form action="options.php" method="post">
    <?php settings_fields( 'media' ); ?>

    <h2 class="title"><?php esc_html_e( 'Image sizes' ); ?></h2>
    <p><?php esc_html_e( 'The sizes listed below determine the maximum dimensions in pixels to use when adding an image to the Media Library.' ); ?></p>

    <table class="form-table" role="presentation">
    <tr>
    <th scope="row"><?php esc_html_e( 'Thumbnail size' ); ?></th>
    <td><fieldset><legend class="screen-reader-text"><span>
        <?php
        /* translators: Hidden accessibility text. */
        esc_html_e( 'Thumbnail size' );
        ?>
    </span></legend>
    <label for="thumbnail_size_w"><?php esc_html_e( 'Width' ); ?></label>
    <input name="thumbnail_size_w" type="number" step="1" min="0" id="thumbnail_size_w" value="<?php form_option( 'thumbnail_size_w' ); ?>" class="small-text" />
    <br />
    <label for="thumbnail_size_h"><?php esc_html_e( 'Height' ); ?></label>
    <input name="thumbnail_size_h" type="number" step="1" min="0" id="thumbnail_size_h" value="<?php form_option( 'thumbnail_size_h' ); ?>" class="small-text" />
    </fieldset>
    <input name="thumbnail_crop" type="checkbox" id="thumbnail_crop" value="1" <?php checked( '1', get_option( 'thumbnail_crop' ) ); ?>/>
    <label for="thumbnail_crop"><?php esc_html_e( 'Crop thumbnail to exact dimensions (normally thumbnails are proportional)' ); ?></label>
    </td>
    </tr>

    <tr>
    <th scope="row"><?php esc_html_e( 'Medium size' ); ?></th>
    <td><fieldset><legend class="screen-reader-text"><span>
        <?php
        /* translators: Hidden accessibility text. */
        esc_html_e( 'Medium size' );
        ?>
    </span></legend>
    <label for="medium_size_w"><?php esc_html_e( 'Max Width' ); ?></label>
    <input name="medium_size_w" type="number" step="1" min="0" id="medium_size_w" value="<?php form_option( 'medium_size_w' ); ?>" class="small-text" />
    <br />
    <label for="medium_size_h"><?php esc_html_e( 'Max Height' ); ?></label>
    <input name="medium_size_h" type="number" step="1" min="0" id="medium_size_h" value="<?php form_option( 'medium_size_h' ); ?>" class="small-text" />
    </fieldset></td>
    </tr>

    <tr>
    <th scope="row"><?php esc_html_e( 'Large size' ); ?></th>
    <td><fieldset><legend class="screen-reader-text"><span>
        <?php
        /* translators: Hidden accessibility text. */
        esc_html_e( 'Large size' );
        ?>
    </span></legend>
    <label for="large_size_w"><?php esc_html_e( 'Max Width' ); ?></label>
    <input name="large_size_w" type="number" step="1" min="0" id="large_size_w" value="<?php form_option( 'large_size_w' ); ?>" class="small-text" />
    <br />
    <label for="large_size_h"><?php esc_html_e( 'Max Height' ); ?></label>
    <input name="large_size_h" type="number" step="1" min="0" id="large_size_h" value="<?php form_option( 'large_size_h' ); ?>" class="small-text" />
    </fieldset></td>
    </tr>

    <?php do_settings_fields( 'media', 'default' ); ?>
    </table>

    <?php
    /**
     * @global array $wp_settings
     */
    if ( isset( $GLOBALS['wp_settings']['media']['embeds'] ) ) :
        ?>
    <h2 class="title"><?php esc_html_e( 'Embeds' ); ?></h2>
    <table class="form-table" role="presentation">
        <?php do_settings_fields( 'media', 'embeds' ); ?>
    </table>
    <?php endif; ?>

    <?php if ( ! is_multisite() ) : ?>
    <h2 class="title"><?php esc_html_e( 'Uploading Files' ); ?></h2>
    <table class="form-table" role="presentation">
        <?php
        /*
         * If upload_url_path is not the default (empty),
         * or upload_path is not the default ('wp-content/uploads' or empty),
         * they can be edited, otherwise they're locked.
         */
        if ( get_option( 'upload_url_path' )
            || get_option( 'upload_path' ) && 'wp-content/uploads' !== get_option( 'upload_path' ) ) :
            ?>
    <tr>
    <th scope="row"><label for="upload_path"><?php esc_html_e( 'Store uploads in this folder' ); ?></label></th>
    <td><input name="upload_path" type="text" id="upload_path" value="<?php echo esc_attr( get_option( 'upload_path' ) ); ?>" class="regular-text code" />
    <p class="description">
            <?php
            /* translators: %s: wp-content/uploads */
            printf( __( 'Default is %s' ), '<code>wp-content/uploads</code>' );
            ?>
    </p>
    </td>
    </tr>

    <tr>
    <th scope="row"><label for="upload_url_path"><?php esc_html_e( 'Full URL path to files' ); ?></label></th>
    <td><input name="upload_url_path" type="text" id="upload_url_path" value="<?php echo esc_attr( get_option( 'upload_url_path' ) ); ?>" class="regular-text code" />
    <p class="description"><?php esc_html_e( 'Configuring this is optional. By default, it should be blank.' ); ?></p>
    </td>
    </tr>
    <tr>
    <td colspan="2" class="td-full">
    <?php else : ?>
    <tr>
    <td class="td-full">
    <?php endif; ?>
    <label for="uploads_use_yearmonth_folders">
    <input name="uploads_use_yearmonth_folders" type="checkbox" id="uploads_use_yearmonth_folders" value="1"<?php checked( '1', get_option( 'uploads_use_yearmonth_folders' ) ); ?> />
        <?php esc_html_e( 'Organize my uploads into month- and year-based folders' ); ?>
    </label>
    </td>
    </tr>

        <?php do_settings_fields( 'media', 'uploads' ); ?>
    </table>
    <?php endif; ?>

    <?php do_settings_sections( 'media' ); ?>

    <?php submit_button(); ?>

    </form>

    </div>

    <?php
}



/**
 *  vergeml_medialibrary_options_page_scripts
 *
 *  @since    2.2.1
 *  @created  11/04/16
 */

function vergeml_medialibrary_options_page_scripts() {

    global $vergeml_dir;

    wp_enqueue_script(
        'vergeml-medialibrary-options-script',
        $vergeml_dir . 'js/eml-medialibrary-options.js',
        array( 'jquery' ),
        VERGEML_VERSION,
        true
    );
}



/**
 *  vergeml_taxonomies_options_page_scripts
 *
 *  @since    2.2
 *  @created  08/03/16
 */

function vergeml_taxonomies_options_page_scripts() {

    global $vergeml_dir;

    wp_enqueue_script(
        'vergeml-taxonomies-options-script'
        // $vergeml_dir . 'js/eml-taxonomies-options.js',
        // array( 'jquery', 'underscore', 'vergeml-admin-script' ),
        // VERGEML_VERSION,
        // true
    );

    $l10n_data = array(
        'edit' => __( 'Edit', 'verge-media-library' ),
        'close' => __( 'Close', 'verge-media-library' ),
        'view' => __( 'View', 'verge-media-library' ),
        'update' => __( 'Update', 'verge-media-library' ),
        'add_new' => __( 'Add New', 'verge-media-library' ),
        'new' => __( 'New', 'verge-media-library' ),
        'name' => __( 'Name', 'verge-media-library' ),
        'parent' => __( 'Parent', 'verge-media-library' ),
        'all' => __( 'All', 'verge-media-library' ),
        'search' => __( 'Search', 'verge-media-library' ),

        'tax_new' => __( 'New Taxonomy', 'verge-media-library' ),

        'tax_deletion_confirm_title' => __( 'Remove Taxonomy', 'verge-media-library' ),
        'tax_deletion_confirm_text_p1' => '<p>' . __( 'Taxonomy will be removed.', 'verge-media-library' ) . '</p>',
        'tax_deletion_confirm_text_p2' => '<p>' . __( 'Taxonomy terms (categories) will remain intact in the database. If you create a taxonomy with the same name in the future, its terms (categories) will be available again.', 'verge-media-library' ) . '</p>',
        'tax_deletion_confirm_text_p3' => '<p>' . __( 'Media items will remain intact.', 'verge-media-library' ) . '</p>',
        'tax_deletion_confirm_text_p4' => '<p>' . __( 'Are you still sure?', 'verge-media-library' ) . '</p>',
        'tax_deletion_yes' => __( 'Yes, remove taxonomy', 'verge-media-library' ),

        'tax_error_duplicate_title' => __( 'Duplicate', 'verge-media-library' ),
        'tax_error_duplicate_text' => __( 'Taxonomy with the same name already exists. Please chose other one.', 'verge-media-library' ),

        'tax_error_empty_fileds_title' => __( 'Empty Fields', 'verge-media-library' ),
        'tax_error_wrong_taxname_title' => __( 'Wrong Taxonomy Name', 'verge-media-library' ),
        'tax_error_wrong_slug_title' => __( 'Wrong Slug', 'verge-media-library' ),

        'tax_error_empty_both' => __( 'Please choose Singular and Plural names for all new taxomonies.', 'verge-media-library' ),
        'tax_error_empty_singular' => __( 'Please choose Singular name for all new taxomonies.', 'verge-media-library' ),
        'tax_error_empty_plural' => __( 'Please choose Plural name for all new taxomonies.', 'verge-media-library' ),

        'tax_error_empty_taxname' => __( 'Taxonomy Name cannot be empty. If it was not generated from the Singular name please enter it manually.', 'verge-media-library' ),
        'tax_error_wrong_taxname' => __( 'Taxonomy Name should only contain lowercase Latin letters, the underscore character ( _ ), and be 3-32 characters long.', 'verge-media-library' ),
        'tax_error_wrong_slug' => __( 'Slug should only contain lowercase Latin letters, numbers, underscore ( _ ) or hyphen ( - ) characters.', 'verge-media-library' ),

        'okay' => __( 'Ok', 'verge-media-library' ),
        'cancel' => __( 'Cancel', 'verge-media-library' ),

        'sync_warning_title' => __( 'Synchronize Now', 'verge-media-library' ),
        'sync_warning_text' => __( 'This operation cannot be canceled! Are you still sure?', 'verge-media-library' ),
        'sync_warning_yes' => __( 'Synchronize', 'verge-media-library' ),
        'sync_warning_no' => __( 'Cancel', 'verge-media-library' ),
        'in_progress_sync_text' => __( 'Synchronizing...', 'verge-media-library' ),

        'bulk_edit_nonce' => wp_create_nonce( 'eml-bulk-edit-nonce' )
    );

    wp_localize_script(
        'vergeml-taxonomies-options-script',
        'vergeml_taxonomies_options_l10n_data',
        $l10n_data
    );
}



/**
 *  vergeml_mimetype_options_page_scripts
 *
 *  @since    2.2
 *  @created  08/03/16
 */

function vergeml_mimetype_options_page_scripts() {

    global $vergeml_dir;

    wp_enqueue_script(
        'vergeml-mimetype-options-script',
        $vergeml_dir . 'js/eml-mimetype-options.js',
        array( 'jquery', 'underscore' ),
        VERGEML_VERSION,
        true
    );

    $l10n_data = array(
        'mime_restoring_confirm_title' => __( 'Restore WordPress default MIME Types', 'verge-media-library' ),
        'mime_restoring_confirm_text' => __( 'Warning! All your custom MIME Types will be deleted by this operation.', 'verge-media-library' ),
        'mime_restoring_yes' => __( 'Restore Defaults', 'verge-media-library' ),
        'in_progress_restoring_text' => __( 'Restoring...', 'verge-media-library' ),

        'okay' => __( 'Ok', 'verge-media-library' ),
        'cancel' => __( 'Cancel', 'verge-media-library' ),

        'mime_error_cannot_save_title' => __( 'MIME Types cannot be saved', 'verge-media-library' ),
        'mime_error_empty_fields' => __( 'Please fill into all fields.', 'verge-media-library' ),
        'mime_error_duplicate' => __( 'Duplicate extensions or MIME types. Please choose other one.', 'verge-media-library' )
    );

    wp_localize_script(
        'vergeml-mimetype-options-script',
        'vergeml_mimetype_options_l10n_data',
        $l10n_data
    );
}



/**
 *  vergeml_options_page_scripts
 *
 *  @since    2.2
 *  @created  08/03/16
 */

function vergeml_options_page_scripts() {

    global $vergeml_dir;


    wp_enqueue_script(
        'vergeml-options-script',
        $vergeml_dir . 'js/eml-options.js',
        array( 'jquery', 'underscore', 'vergeml-admin-script' ),
        VERGEML_VERSION,
        true
    );

    $l10n_data = array(
        'cleanup_warning_title' => __( 'Complete Cleanup', 'verge-media-library' ),
        'cleanup_warning_text_p1' => '<p>' . __( 'You are about to <strong style="text-transform:uppercase">delete all plugin data</strong> from the database including backups.', 'verge-media-library' ) . '</p>',
        'cleanup_warning_text_p2' => '<p>' . __( 'This operation cannot be canceled! Are you still sure?', 'verge-media-library') . '</p>',
        'cleanup_warning_yes' => __( 'Yes, delete all data', 'verge-media-library' ),
        'in_progress_cleanup_text' => __( 'Cleaning...', 'verge-media-library' ),
        'cancel' => __( 'Cancel', 'verge-media-library' ),

        'apply_to_network_nonce' => wp_create_nonce( 'eml-apply-to-network-nonce' ),
        'applying_settings_title' => __( 'Unify Media Settings over Network', 'verge-media-library' ),
        'applying_media_library_settings_text' => sprintf(
            'ALL Media Library Settings on the Network %s with the settings of the main website.',
            '<strong style="text-transform:uppercase">' . __( 'will be overwritten', 'verge-media-library' ) . '</strong>'
        ),
        'applying_media_taxonomies_settings_text' => sprintf(
            'ALL Media Taxonomies Settings on the Network %s with the settings of the main website. If your websites have individual taxonomies registered, they will be overwritten with the taxonomies from the main website.',
            '<strong style="text-transform:uppercase">' . __( 'will be overwritten', 'verge-media-library' ) . '</strong>'
        ),
        'applying_mime_types_settings_text' => sprintf(
            'ALL MIME Types Settings on the Network %s with the settings of the main website.',
            '<strong style="text-transform:uppercase">' . __( 'will be overwritten', 'verge-media-library' ) . '</strong>'
        ),
        'applying_settings_yes' => __( 'Apply', 'verge-media-library' ),
        'in_progress_apply_setings_text' => __( 'Applying Settings...', 'verge-media-library' )
    );

    wp_localize_script(
        'vergeml-options-script',
        'vergeml_options_l10n_data',
        $l10n_data
    );
}



/**
 *  vergeml_print_settings
 *
 *  @since    2.1
 *  @created  25/10/15
 */

function vergeml_print_settings() {

    if ( ! current_user_can( 'manage_options' ) )
        wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );


    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['utilities'] )
            wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );
    } ?>


    <div id="vergeml-global-options-wrap" class="wrap eml-options">

        <h2><?php esc_html_e( 'Verge Media Library Utilities', 'verge-media-library' ); ?></h2>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <div id="postbox-container-2" class="postbox-container">

                    <div class="postbox">

                        <h3 class="hndle"><?php esc_html_e( 'Export', 'verge-media-library' ); ?></h3>

                        <div class="inside">

                            <ul>
                                <li><strong><?php esc_html_e( 'Plugin settings to export:', 'verge-media-library' ); ?></strong></li>
                                <li><?php esc_html_e( 'Settings > Media Library', 'verge-media-library' ); ?></li>
                                <li><?php esc_html_e( 'Settings > Media Taxonomies', 'verge-media-library' ); ?></li>
                                <li><?php esc_html_e( 'Settings > MIME Types', 'verge-media-library' ); ?></li>
                            </ul>


                            <p><?php esc_html_e( 'Use generated JSON file to import the configuration into another website.', 'verge-media-library' ); ?></p>

                            <form method="post">
                                <input type='hidden' name='eml-settings-export' />
                                <?php wp_nonce_field( 'eml_settings_export_nonce', 'eml-settings-export-nonce' ); ?>
                                <?php submit_button( __( 'Export Plugin Settings', 'verge-media-library' ), 'primary', 'eml-submit-settings-export', true ); ?>
                            </form>

                        </div>

                    </div>


                    <div class="postbox">

                        <h3 class="hndle"><?php esc_html_e( 'Import', 'verge-media-library' ); ?></h3>

                        <div class="inside">

                            <ul>
                                <li><strong><?php esc_html_e( 'Plugin settings to import:', 'verge-media-library' ); ?></strong></li>
                                <li><?php esc_html_e( 'Settings > Media Library', 'verge-media-library' ); ?></li>
                                <li><?php esc_html_e( 'Settings > Media Taxonomies', 'verge-media-library' ); ?></li>
                                <li><?php esc_html_e( 'Settings > MIME Types', 'verge-media-library' ); ?></li>
                            </ul>

                            <p><?php esc_html_e( 'Plugin settings will be imported from a configuration JSON file which can be obtained by exporting the settings on another website using the export button above.', 'verge-media-library' ); ?></p>
                            <p><?php esc_html_e( 'All plugin settings will be overridden by the import. You will have a chance to restore current data from an automatic backup in case you are not satisfied with the result of the import.', 'verge-media-library' ); ?></p>

                            <form method="post" enctype="multipart/form-data">
                                <p><input type="file" name="import_file"/></p>
                                <input type='hidden' name='eml-settings-import' />
                                <?php wp_nonce_field( 'eml_settings_import_nonce', 'eml-settings-import-nonce' ); ?>
                                <?php submit_button(  __( 'Import Plugin Settings', 'verge-media-library' ), 'primary', 'eml-submit-settings-import' ); ?>
                            </form>

                        </div>

                    </div>


                    <?php $vergeml_backup = get_option( 'vergeml_backup' ); ?>

                    <div class="postbox">

                        <h3 class="hndle"><?php esc_html_e( 'Restore', 'verge-media-library' ); ?></h3>

                        <div class="inside">

                            <?php if ( empty( $vergeml_backup ) ) : ?>

                                <p><?php esc_html_e( 'No backup available at the moment.', 'verge-media-library' ); ?></p>

                                <p><?php esc_html_e( 'Backup will be created automatically before any import operation.', 'verge-media-library' ); ?></p>

                            <?php else : ?>

                                <p><?php esc_html_e( 'The backup has been automatically created before the latest import operation.', 'verge-media-library' ); ?></p>

                                <ul>
                                    <li><strong><?php esc_html_e( 'Plugin settings to restore:', 'verge-media-library' ); ?></strong></li>
                                    <li><?php esc_html_e( 'Settings > Media Library', 'verge-media-library' ); ?></li>
                                    <li><?php esc_html_e( 'Settings > Media Taxonomies', 'verge-media-library' ); ?></li>
                                    <li><?php esc_html_e( 'Settings > MIME Types', 'verge-media-library' ); ?></li>
                                </ul>

                                <form method="post">
                                    <input type='hidden' name='eml-settings-restore' />
                                    <?php wp_nonce_field( 'eml_settings_restore_nonce', 'eml-settings-restore-nonce' ); ?>
                                    <?php submit_button( __( 'Restore Settings from the Backup', 'verge-media-library' ), 'primary', 'eml-submit-settings-restore', true, array( 'id' => 'eml-submit-settings-restore' ) ); ?>
                                </form>

                            <?php endif; ?>


                        </div>

                    </div>


                    <?php if ( ! is_multisite() || is_network_admin() ) : ?>


                        <div class="postbox">

                            <h3 class="hndle"><?php esc_html_e( 'Complete Cleanup', 'verge-media-library' ); ?></h3>

                            <div class="inside">

                                <?php $vergeml_taxonomies = vergeml_get_eml_taxonomies(); ?>

                                <ul>
                                    <li><strong><?php esc_html_e( 'What will be deleted:', 'verge-media-library' ); ?></strong></li>
                                    <?php foreach( (array) $vergeml_taxonomies as $taxonomy => $params ) : ?>
                                        <li><?php esc_html_e( 'All', 'verge-media-library' );
                                        echo ' ' . esc_html( $params['labels']['name'] ); ?></li>
                                    <?php endforeach; ?>
                                    <li><?php esc_html_e( 'All plugin options', 'verge-media-library' ); ?></li>
                                    <li><?php esc_html_e( 'All plugin backups stored in the database', 'verge-media-library' ); ?></li>
                                </ul>

                                <ul>
                                    <li><strong><?php esc_html_e( 'What will remain intact:', 'verge-media-library' ); ?></strong></li>
                                    <li><?php esc_html_e( 'All media items', 'verge-media-library' ); ?></li>
                                    <li><?php esc_html_e( 'All taxonomies not listed above', 'verge-media-library' ); ?></li>
                                </ul>

                                <p><?php esc_html_e( 'The plugin cannot delete itself for security reasons. Please delete it manually from the plugin list after the cleanup is complete.', 'verge-media-library' ); ?></p>

                                <p><strong style="color:red;"><?php esc_html_e( 'If you are not sure about this operation it\'s HIGHLY RECOMMENDED to create a backup of your database prior to cleanup!', 'verge-media-library' ); ?></strong></p>

                                <form id="eml-form-cleanup" method="post">
                                    <input type='hidden' name='eml-settings-cleanup' />
                                    <?php wp_nonce_field( 'eml_settings_cleanup_nonce', 'eml-settings-cleanup-nonce' ); ?>
                                    <?php submit_button( __( 'Delete All Data & Deactivate', 'verge-media-library' ), 'primary', 'eml-submit-settings-cleanup', true ); ?>
                                </form>

                            </div>

                        </div>

                        <?php do_action( 'vergeml_extend_settings_page' ); ?>

                    <?php endif; ?>

                </div>

                <div id="postbox-container-1" class="postbox-container">

                    <?php vergeml_print_credits(); ?>

                </div>

            </div>

        </div>

    </div>

    <?php
}



/**
 *  vergeml_print_network_settings
 *
 *  @since    2.6
 *  @created  22/04/18
 */

function vergeml_print_network_settings() {

    if ( ! current_user_can( 'manage_network_options' ) )
        wp_die( __('You do not have sufficient permissions to access this page.', 'verge-media-library') );


    settings_errors();

    $vergeml_network_options = get_site_option( 'vergeml_network_options', array() ); ?>


    <div id="vergeml-global-options-wrap" class="wrap eml-options">

        <h2><?php esc_html_e( 'Verge Media Library Utilities', 'verge-media-library' ); ?></h2>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <div id="postbox-container-2" class="postbox-container">

                    <div class="postbox">

                        <h3 class="hndle" id="eml-license-key-section"><?php esc_html_e('Network Settings','verge-media-library'); ?></h3>


                        <div class="inside">

                            <?php if ( ! is_plugin_active_for_network( vergeml_get_basename() ) ) : ?>

                                <p class="description"><?php esc_html_e( 'No settings available. The plugin is not network activated.', 'verge-media-library' ); ?></p>

                            <?php else : ?>

                                <form method="post">

                                    <?php settings_fields( 'eml-network-settings' ); ?>

                                    <table class="form-table">

                                        <tr>
                                            <th scope="row"><?php esc_html_e('Media Settings per site','verge-media-library'); ?></th>
                                            <td>
                                                <fieldset>
                                                    <legend class="screen-reader-text"><span><?php esc_html_e('Enable Media Settings','verge-media-library'); ?></span></legend>
                                                    <label><input name="vergeml_network_options[media_settings]" type="hidden" value="0" /><input name="vergeml_network_options[media_settings]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_network_options['media_settings'], true ); ?> /> <?php esc_html_e('Allow an individual site admin to edit enhanced Media Settings','verge-media-library'); ?></label>
                                                    <p class="description"><?php esc_html_e( 'Otherwise, only a network (super) admin can see the menu and edit media settings.', 'verge-media-library' ); ?></p>
                                                </fieldset>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row"><?php esc_html_e('Plugin Utilities per site','verge-media-library'); ?></th>
                                            <td>
                                                <fieldset>
                                                    <legend class="screen-reader-text"><span><?php esc_html_e('Enable plugin Utilities','verge-media-library'); ?></span></legend>
                                                    <label><input name="vergeml_network_options[utilities]" type="hidden" value="0" /><input name="vergeml_network_options[utilities]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_network_options['utilities'], true ); ?> /> <?php esc_html_e('Allow an individual site admin to import / export / restore plugin settings and perform the complete cleanup for a specific site','verge-media-library'); ?></label>
                                                    <p class="description"><?php esc_html_e( 'Otherwise, only a network (super) admin can see the menu and perform those actions.', 'verge-media-library' ); ?></p>
                                                </fieldset>
                                            </td>
                                        </tr>

                                    </table>

                                    <?php submit_button( __( 'Save Changes' ), 'primary', 'eml-submit-network-settings', true ); ?>

                                </form>

                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="postbox">

                        <h3 class="hndle"><?php esc_html_e('Unify Media Settings over Network','verge-media-library'); ?></h3>


                        <div class="inside">

                            <?php if ( ! is_plugin_active_for_network( vergeml_get_basename() ) ) : ?>

                                <p class="description"><?php esc_html_e( 'No settings available. The plugin is not network activated.', 'verge-media-library' ); ?></p>

                            <?php else : ?>

                                <form method="post">

                                    <table class="form-table">

                                        <tr>
                                            <th scope="row"><?php esc_html_e('Media Library Settings','verge-media-library'); ?></th>
                                            <td>
                                                <fieldset>
                                                    <legend class="screen-reader-text"><span><?php esc_html_e('Media Library Settings','verge-media-library'); ?></span></legend>
                                                    <a class="add-new-h2 vergeml-apply-settings-to-network" data-settings="media-library" href="javascript:;"><?php esc_html_e( 'Apply to ALL Network websites', 'verge-media-library' ); ?></a>
                                                    <p class="description"><?php printf(
                                                        'Main website %s settings will be applied to all websites on the Network.',
                                                        '<a href="' . admin_url('options-general.php?page=media-library') . '" target="_blank">' . __( 'Media Library', 'verge-media-library' ) . '</a>'
                                                    ); ?></p>
                                                </fieldset>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row"><?php esc_html_e('Media Taxonomies Settings','verge-media-library'); ?></th>
                                            <td>
                                                <fieldset>
                                                    <legend class="screen-reader-text"><span><?php esc_html_e('Media Taxonomies Settings','verge-media-library'); ?></span></legend>
                                                    <a class="add-new-h2 vergeml-apply-settings-to-network" data-settings="media-taxonomies" href="javascript:;"><?php esc_html_e( 'Apply to ALL Network websites', 'verge-media-library' ); ?></a>
                                                    <p class="description"><?php printf(
                                                        'Main website %s settings will be applied to all websites on the Network.',
                                                        '<a href="' . admin_url('options-general.php?page=media-taxonomies') . '" target="_blank">' . __( 'Media Taxonomies', 'verge-media-library' ) . '</a>'
                                                    ); ?></p>
                                                </fieldset>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row"><?php esc_html_e('MIME Types Settings','verge-media-library'); ?></th>
                                            <td>
                                                <fieldset>
                                                    <legend class="screen-reader-text"><span><?php esc_html_e('MIME Types Settings','verge-media-library'); ?></span></legend>
                                                    <a class="add-new-h2 vergeml-apply-settings-to-network" data-settings="mime-types" href="javascript:;"><?php esc_html_e( 'Apply to ALL Network websites', 'verge-media-library' ); ?></a>
                                                    <p class="description"><?php printf(
                                                        'Main website %s settings will be applied to all websites on the Network.',
                                                        '<a href="' . admin_url('options-general.php?page=mime-types') . '" target="_blank">' . __( 'MIME Types', 'verge-media-library' ) . '</a>'
                                                    ); ?></p>
                                                </fieldset>
                                            </td>
                                        </tr>

                                    </table>

                                </form>

                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="postbox">

                        <h3 class="hndle"><?php esc_html_e( 'Complete Cleanup', 'verge-media-library' ); ?></h3>

                        <div class="inside">

                            <?php
                            $vergeml_taxonomies = array();

                            foreach( get_sites( array( 'fields' => 'ids' ) ) as $site_id ) :

                                switch_to_blog( $site_id );

                                $vergeml_taxonomies = array_merge( $vergeml_taxonomies, vergeml_get_eml_taxonomies() );

                                restore_current_blog();

                            endforeach; ?>


                            <ul>
                                <li><strong><?php esc_html_e( 'What will be deleted:', 'verge-media-library' ); ?></strong></li>
                                <?php foreach( (array) $vergeml_taxonomies as $taxonomy => $params ) : ?>
                                    <li><?php esc_html_e( 'All', 'verge-media-library' );
                                    echo ' ' . esc_html( $params['labels']['name'] ); ?></li>
                                <?php endforeach; ?>
                                <li><?php esc_html_e( 'All plugin options on every site', 'verge-media-library' ); ?></li>
                                <li><?php esc_html_e( 'Network settings', 'verge-media-library' ); ?></li>
                                <li><?php esc_html_e( 'All plugin backups stored in the database', 'verge-media-library' ); ?></li>
                            </ul>

                            <ul>
                                <li><strong><?php esc_html_e( 'What will remain intact:', 'verge-media-library' ); ?></strong></li>
                                <li><?php esc_html_e( 'All media items', 'verge-media-library' ); ?></li>
                                <li><?php esc_html_e( 'All taxonomies not listed above', 'verge-media-library' ); ?></li>
                            </ul>

                            <p><?php esc_html_e( 'The plugin cannot delete itself for security reasons. Please delete it manually from the plugin list after the cleanup is complete.', 'verge-media-library' ); ?></p>

                            <p><strong style="color:red;"><?php esc_html_e( 'If you are not sure about this operation it\'s HIGHLY RECOMMENDED to create a backup of your database prior to cleanup!', 'verge-media-library' ); ?></strong></p>

                            <form id="eml-form-cleanup" method="post">
                                <input type='hidden' name='eml-settings-cleanup' />
                                <?php wp_nonce_field( 'eml_settings_cleanup_nonce', 'eml-settings-cleanup-nonce' ); ?>
                                <?php submit_button( __( 'Delete All Data & Network Deactivate', 'verge-media-library' ), 'primary', 'eml-submit-settings-cleanup', true ); ?>
                            </form>

                        </div>

                    </div>

                    <?php do_action( 'vergeml_extend_settings_page' ); ?>

                </div>

                <div id="postbox-container-1" class="postbox-container">

                    <?php vergeml_print_credits(); ?>

                </div>

            </div>

        </div>

    </div>

<?php
}



/**
 *  vergeml_apply_settings_to_network
 *
 *  @since    2.7
 *  @created  21/06/18
 */

add_action( 'wp_ajax_vergeml-apply-settings-to-network', 'vergeml_apply_settings_to_network' );

function vergeml_apply_settings_to_network() {

    if ( ! isset( $_REQUEST['settings'] ) )
        wp_send_json_error();

    check_ajax_referer( 'eml-apply-to-network-nonce', 'nonce' );

    /*
     *  This writes options into every site on the network, so it needs the
     *  capability that guards network settings, not just a valid nonce.
     *  Upstream relied on the nonce alone, which only held because the nonce
     *  is printed on a super-admin screen.
     *
     *  Gated on is_multisite() because a single site grants nobody
     *  manage_network_options, not even an administrator. Checking it
     *  unconditionally would reject the very users allowed to be here.
     */

    if ( is_multisite() && ! current_user_can( 'manage_network_options' ) )
        wp_send_json_error();


    $plugins = get_site_option( 'active_sitewide_plugins');

    if ( is_multisite() && isset($plugins[vergeml_get_basename()]) ) {

        switch_to_blog( get_main_site_id() );

        $vergeml_taxonomies = get_option( 'vergeml_taxonomies', array() );
        $vergeml_lib_options = get_option( 'vergeml_lib_options', array() );
        $vergeml_tax_options = get_option( 'vergeml_tax_options', array() );
        $vergeml_mimes = get_option( 'vergeml_mimes', array() );


        foreach( get_sites( array( 'fields' => 'ids' ) ) as $site_id ) {

            switch_to_blog( $site_id );

            switch ( $_REQUEST['settings'] ) {
                case 'media-library':
                    update_option( 'vergeml_lib_options', $vergeml_lib_options );
                    break;

                case 'media-taxonomies':
                    update_option( 'vergeml_taxonomies', $vergeml_taxonomies );
                    update_option( 'vergeml_tax_options', $vergeml_tax_options );
                    break;

                case 'mime-types':
                    update_option( 'vergeml_mimes', $vergeml_mimes );
                    break;
            }

            restore_current_blog();
        }
    }

    wp_send_json_success();
}



/**
 *  vergeml_update_network_settings
 *
 *  @since    2.6
 *  @created  28/04/18
 */

add_action( 'network_admin_menu', 'vergeml_update_network_settings' );

function vergeml_update_network_settings() {

    if ( ! isset($_POST['eml-submit-network-settings']) )
        return;

    check_admin_referer( 'eml-network-settings-options' );

    if ( ! current_user_can( 'manage_network_options' ) )
        return;


    $vergeml_network_options = isset( $_POST['vergeml_network_options'] ) ? $_POST['vergeml_network_options'] : array();

    $vergeml_network_options = vergeml_tax_options_validate( $vergeml_network_options );

    update_site_option( 'vergeml_network_options', $vergeml_network_options );

    add_settings_error(
        'eml-network-settings',
        'eml_network_settings_saved',
        __('Network settings saved.', 'verge-media-library'),
        'updated'
    );
}



/**
 *  vergeml_settings_export
 *
 *  @since    2.1
 *  @created  25/10/15
 */

add_action( 'admin_init', 'vergeml_settings_export' );

function vergeml_settings_export() {

    if ( ! isset( $_POST['eml-settings-export'] ) )
        return;

    if ( ! isset( $_POST['eml-settings-export-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eml-settings-export-nonce'] ) ), 'eml_settings_export_nonce' ) )
        return;

    if ( ! current_user_can( 'manage_options' ) )
        return;

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['utilities'] )
            return;
    }


    $settings = vergeml_get_settings();

    ignore_user_abort( true );

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=eml-settings-' . date('m-d-Y_hia') . '.json' );
    header( "Expires: 0" );

    echo json_encode( $settings );

    exit;
}



/**
 *  vergeml_settings_import
 *
 *  @since    2.1
 *  @created  25/10/15
 */

add_action( 'admin_init', 'vergeml_settings_import' );

function vergeml_settings_import() {

    if ( ! isset( $_POST['eml-settings-import'] ) )
        return;

    if ( ! isset( $_POST['eml-settings-import-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eml-settings-import-nonce'] ) ), 'eml_settings_import_nonce' ) )
        return;

    if ( ! current_user_can( 'manage_options' ) )
        return;

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['utilities'] )
            return;
    }


    $import_file = $_FILES['import_file'];

    if ( empty( $import_file['tmp_name'] ) ) {

        add_settings_error(
            'eml-settings',
            'eml_settings_file_absent',
            __('Settings cannot be imported. Please upload a file to import settings.', 'verge-media-library'),
            'error'
        );

        return;
    }


    // backup settings
    $settings = vergeml_get_settings();
    update_option( 'vergeml_backup', $settings );


    $json_data = file_get_contents( $import_file['tmp_name'] );
    $settings = json_decode( $json_data, true );

    if ( empty( $settings ) ) {

        add_settings_error(
            'eml-settings',
            'eml_settings_wrong_format',
            __('Settings cannot be imported. Please upload a correct JSON file to import settings.', 'verge-media-library'),
            'error'
        );

        return;
    }


    update_option( 'vergeml_taxonomies', $settings['taxonomies'] );
    update_option( 'vergeml_lib_options', $settings['lib_options'] );
    update_option( 'vergeml_tax_options', $settings['tax_options'] );
    update_option( 'vergeml_mimes', $settings['mimes'] );

    add_settings_error(
        'eml-settings',
        'eml_settings_imported',
        __('Plugin settings imported.', 'verge-media-library'),
        'updated'
    );
}



/**
 *  vergeml_settings_restoring
 *
 *  @since    2.1
 *  @created  25/10/15
 */

add_action( 'admin_init', 'vergeml_settings_restoring' );

function vergeml_settings_restoring() {

    if ( ! isset( $_POST['eml-settings-restore'] ) )
        return;

    if ( ! isset( $_POST['eml-settings-restore-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eml-settings-restore-nonce'] ) ), 'eml_settings_restore_nonce' ) )
        return;

    if ( ! current_user_can( 'manage_options' ) )
        return;

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['utilities'] )
            return;
    }


    $vergeml_backup = get_option( 'vergeml_backup' );

    update_option( 'vergeml_taxonomies', $vergeml_backup['taxonomies'] );
    update_option( 'vergeml_lib_options', $vergeml_backup['lib_options'] );
    update_option( 'vergeml_tax_options', $vergeml_backup['tax_options'] );
    update_option( 'vergeml_mimes', $vergeml_backup['mimes'] );

    do_action( 'vergeml_pro_set_settings', $vergeml_backup );

    update_option( 'vergeml_backup', '' );

    add_settings_error(
        'eml-settings',
        'eml_settings_restored',
        __('Plugin settings restored from the backup.', 'verge-media-library'),
        'updated'
    );
}



/**
 *  vergeml_settings_cleanup
 *
 *  @since    2.2
 *  @created  23/02/16
 */

add_action( 'admin_init', 'vergeml_settings_cleanup' );

function vergeml_settings_cleanup() {

    if ( ! isset( $_POST['eml-settings-cleanup'] ) )
        return;

    if ( ! isset( $_POST['eml-settings-cleanup-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eml-settings-cleanup-nonce'] ) ), 'eml_settings_cleanup_nonce' ) )
        return;

    if ( ! current_user_can( 'manage_options' ) )
        return;

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['utilities'] )
            return;
    }


    if ( is_multisite()  ) {

        foreach( get_sites( array( 'fields' => 'ids' ) ) as $site_id ) {

            switch_to_blog( $site_id );

            vergeml_term_relationship_cleanup();
            vergeml_options_cleanup();
            deactivate_plugins( vergeml_get_basename() );

            restore_current_blog();
        }
    }
    else {

        vergeml_term_relationship_cleanup();
        vergeml_options_cleanup();
    }

    // we need this one because of = vs LIKE in the DB query
    vergeml_user_meta_cleanup();

    vergeml_site_options_cleanup();
    vergeml_transients_cleanup();
    deactivate_plugins( vergeml_get_basename(), false, is_multisite() );


    wp_safe_redirect( self_admin_url( 'plugins.php' ) );
    exit;
}



/**
 *  vergeml_term_relationship_cleanup
 *
 *  @since    2.6
 *  @created  28/04/18
 */

function vergeml_term_relationship_cleanup() {

    global $wpdb;


    foreach ( get_option( 'vergeml_taxonomies', array() ) as $taxonomy => $params ) {

        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'fields' => 'all', 'get' => 'all' ) );
        $term_pairs = vergeml_get_media_term_pairs( $terms, 'id=>tt_id' );

        if ( (bool) $params['eml_media'] ) {

            foreach ( $term_pairs as $id => $tt_id ) {
                wp_delete_term( $id, $taxonomy );
            }

            $wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
            delete_option( $taxonomy . '_children' );
        }
        elseif ( ! empty( $term_pairs ) ) {

            $deleted_tt_ids = array();
            $rows2remove_format = join( ', ', array_fill( 0, count( $term_pairs ), '%d' ) );

            $results = $wpdb->get_results( $wpdb->prepare(
                "
                    SELECT $wpdb->term_relationships.term_taxonomy_id, $wpdb->term_relationships.object_id
                    FROM $wpdb->term_relationships
                    INNER JOIN $wpdb->posts
                    ON $wpdb->term_relationships.object_id = $wpdb->posts.ID
                    WHERE $wpdb->posts.post_type = 'attachment'
                    AND $wpdb->term_relationships.term_taxonomy_id IN ($rows2remove_format)
                ",
                $term_pairs
            ) );

            foreach ( $results as $result ) {
                $deleted_tt_ids[$result->object_id][] = $result->term_taxonomy_id;
            }

            foreach( $deleted_tt_ids as $attachment_id => $tt_ids ) {
                do_action( 'delete_term_relationships', $attachment_id, $tt_ids );
            }

            $removed = $wpdb->query( $wpdb->prepare(
                "
                    DELETE $wpdb->term_relationships.* FROM $wpdb->term_relationships
                    INNER JOIN $wpdb->posts
                    ON $wpdb->term_relationships.object_id = $wpdb->posts.ID
                    WHERE $wpdb->posts.post_type = 'attachment'
                    AND $wpdb->term_relationships.term_taxonomy_id IN ($rows2remove_format)
                ",
                $term_pairs
            ) );

            if ( false !== $removed ) {

                foreach( $deleted_tt_ids as $attachment_id => $tt_ids ) {
                    do_action( 'deleted_term_relationships', $attachment_id, $tt_ids );
                }
            }
        }
    }
}



/**
 *  vergeml_user_meta_cleanup
 *
 *  @since    2.8.10
 *  @created  2024/04
 */

function vergeml_user_meta_cleanup() {

    global $wpdb;

    $meta_key  = 'vergeml_';
    $id_column = 'umeta_id';
    $table     = _get_meta_table( 'user' );


    $query     = $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key LIKE %s", $meta_key . '%' );
    $meta_ids  = $wpdb->get_col( $query );


    if ( ! count( $meta_ids ) ) {
        return;
    }

    $query = "DELETE FROM $table WHERE $id_column IN( " . implode( ',', $meta_ids ) . ' )';

    $wpdb->query( $query );
}



/**
 *  vergeml_options_cleanup
 *
 *  @since    2.6
 *  @created  28/04/18
 */

function vergeml_options_cleanup() {

    $options = array(
        'vergeml_taxonomies',
        'vergeml_lib_options',
        'vergeml_tax_options',
        'vergeml_mimes_backup', // in case it remains since previous versions
        'vergeml_mimes',
        'vergeml_backup',
        'vergeml_version',
        'vergeml_notices'
    );

    $options = apply_filters( 'vergeml_pro_add_options', $options );

    foreach ( $options as $option ) {
        delete_option( $option );
    }
}



/**
 *  vergeml_site_options_cleanup
 *
 *  @since    2.6
 *  @created  28/04/18
 */

function vergeml_site_options_cleanup() {

    $options = array(
        'vergeml_version',
        'vergeml_mimes_backup',
        'vergeml_notices'
    );

    if ( is_multisite() ) {
        $options[] = 'vergeml_network_options';
    }

    $options = apply_filters( 'vergeml_pro_add_options', $options );

    foreach ( $options as $option ) {
        delete_site_option( $option );
    }
}



/**
 *  vergeml_transients_cleanup
 *
 *  @since    2.6
 *  @created  28/04/18
 */

function vergeml_transients_cleanup() {

    $transients = array();

    $transients = apply_filters( 'vergeml_pro_add_transients', $transients );

    foreach ( $transients as $transient ) {
        delete_site_transient( $transient );
    }
}



/**
 *  vergeml_get_settings
 *
 *  @since    2.1
 *  @created  25/10/15
 */

function vergeml_get_settings() {

    $vergeml_taxonomies = get_option( 'vergeml_taxonomies' );
    $vergeml_lib_options = get_option( 'vergeml_lib_options' );
    $vergeml_tax_options = get_option( 'vergeml_tax_options' );
    $vergeml_mimes = get_option( 'vergeml_mimes' );

    $settings = array (
        'taxonomies' => $vergeml_taxonomies,
        'lib_options' => $vergeml_lib_options,
        'tax_options' => $vergeml_tax_options,
        'mimes' => $vergeml_mimes,
    );

    return $settings;
}



/**
 *  vergeml_print_media_library_options
 *
 *  @type     callback function
 *  @since    1.0
 *  @created  28/09/13
 */

function vergeml_print_media_library_options() {

    if ( ! current_user_can( 'manage_options' ) )
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'verge-media-library' ) );

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['media_settings'] )
            wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );
    }


    $vergeml_lib_options = get_option( 'vergeml_lib_options' );
    $title = __('Media Settings'); ?>


    <div id="vergeml-media-library-options-wrap" class="wrap eml-options">

        <h1><?php echo esc_html( $title ); ?></h1>

        <?php vergeml_print_media_settings_tabs( 'library' ); ?>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder">

                <div id="postbox-container-2" class="postbox-container">

                    <form id="vergeml-form-media-library" method="post" action="options.php">

                        <?php settings_fields( 'media-library' ); ?>


                        <h2><?php esc_html_e('Filters','verge-media-library'); ?></h2>

                        <div class="postbox">

                            <div class="inside">

                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Force filters','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Force filters','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[force_filters]" type="hidden" value="0" /><input name="vergeml_lib_options[force_filters]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['force_filters'], true ); ?> /> <?php esc_html_e('Show media filters for ANY Media Popup','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Try this if filters are not shown for third-party plugins or themes.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Filters to show', 'verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Filters to show', 'verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[filters_to_show][]" type="hidden" value="none" /><input name="vergeml_lib_options[filters_to_show][]" type="checkbox" value="types" <?php echo in_array('types', $vergeml_lib_options['filters_to_show']) ? 'checked' : ''; ?> /> <?php esc_html_e('Types','verge-media-library'); ?>
                                                <em>(<?php esc_html_e( 'Can be disabled for Grid Mode only', 'verge-media-library' ); ?>)</em></label><br />
                                                <label><input name="vergeml_lib_options[filters_to_show][]" type="checkbox" value="dates" <?php echo in_array('dates', $vergeml_lib_options['filters_to_show']) ? 'checked' : ''; ?> /> <?php esc_html_e('Dates','verge-media-library'); ?></label><br />
                                                <label><input name="vergeml_lib_options[filters_to_show][]" type="checkbox" value="authors" <?php echo in_array('authors', $vergeml_lib_options['filters_to_show']) ? 'checked' : ''; ?> /> <?php esc_html_e('Authors','verge-media-library'); ?></label><br />
                                                <label><input name="vergeml_lib_options[filters_to_show][]" type="checkbox" value="taxonomies" <?php echo in_array('taxonomies', $vergeml_lib_options['filters_to_show']) ? 'checked' : ''; ?> /> <?php esc_html_e('Media Taxonomies','verge-media-library'); ?></label>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Show count','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Show count','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[show_count]" type="hidden" value="0" /><input name="vergeml_lib_options[show_count]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['show_count'], true ); ?> /> <?php esc_html_e('Show item count per category for media filters','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Disable this if it slows down your site admin. The problem is resolved in the upcoming major update v3.0', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Include children','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Include children','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[include_children]" type="hidden" value="0" /><input name="vergeml_lib_options[include_children]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['include_children'], true ); ?> /> <?php esc_html_e('Show media items of child media categories as a result of filtering', 'verge-media-library'); ?></label>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Uploaded to this post by default','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Uploaded to this post by default','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[filter_uploaded]" type="hidden" value="0" /><input name="vergeml_lib_options[filter_uploaded]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['filter_uploaded'], true ); ?> /> <?php esc_html_e('Show media files initially filtered by Uploaded to this post when applicable', 'verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Enable this to get media files initially filtered by "Uploaded to this post" in a Media Popup while adding or editing them for a post, page, or custom post type.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-lib-settings-filters' ) ); ?>

                            </div>

                        </div>

                        <h2><?php esc_html_e('Scrolling','verge-media-library'); ?></h2>

                        <div class="postbox">

                            <div class="inside">

                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Infinite scrolling','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Infinite scrolling','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[infinite_scrolling]" type="hidden" value="0" /><input name="vergeml_lib_options[infinite_scrolling]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['infinite_scrolling'], true ); ?> /> <?php esc_html_e('Enable infinite scrolling','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Works for Media Library and Media Popups.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Number per page','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Number per page','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[loads_per_page]" type="number" min="40" step="10" value="<?php echo (int) $vergeml_lib_options['loads_per_page']; ?>" /> <?php esc_html_e('Load this number of media files per page','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Works for Media Library and Media Popups.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-lib-settings-scrolling' ) ); ?>

                            </div>

                        </div>


                        <?php
                            $class_name = defined( 'EML_IS_PRO' ) ? '' : ' disabled';
                            $class = defined( 'EML_IS_PRO' ) ? '' : ' class="disabled"';
                            $disabled = defined( 'EML_IS_PRO' ) ? '' : ' readonly="readonly"';
                            $pro_message = defined( 'EML_IS_PRO' ) ? '' : ' <span class="premium">/ Premium Feature</span>';
                        ?>

                        <h2<?php echo $class; ?>><?php esc_html_e('Search','verge-media-library'); echo $pro_message; ?></h2>

                        <div class="postbox<?php echo $class_name; ?>">

                            <div class="inside">

                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Enable search in','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset id="vergeml_lib_options_search_in">
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Enable search in', 'verge-media-library'); ?></span></legend>
                                                <input name="vergeml_lib_options[search_in][]" type="hidden" value="none" />
                                                
                                                <label><input name="vergeml_lib_options[search_in][]" type="checkbox" value="titles" class="search_columns" <?php echo in_array('titles', $vergeml_lib_options['search_in']) ? 'checked' : ''; echo $disabled; ?> /> <?php esc_html_e('Titles','verge-media-library'); ?></label><br />
                                                <label><input name="vergeml_lib_options[search_in][]" type="checkbox" value="captions" class="search_columns" <?php echo in_array('captions', $vergeml_lib_options['search_in']) ? 'checked' : '';  echo $disabled; ?> /> <?php esc_html_e('Captions','verge-media-library'); ?></label><br />
                                                <label><input name="vergeml_lib_options[search_in][]" type="checkbox" value="descriptions" class="search_columns" <?php echo in_array('descriptions', $vergeml_lib_options['search_in']) ? 'checked' : ''; echo $disabled; ?> /> <?php esc_html_e('Descriptions','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e('One of the three above must be ON due to WP core limitations.','verge-media-library'); ?></p>
                                                <br />

                                                <label><input name="vergeml_lib_options[search_in][]" type="checkbox" value="filenames" <?php echo in_array('filenames', $vergeml_lib_options['search_in']) ? 'checked' : ''; echo $disabled; ?> /> <?php esc_html_e('Filenames','verge-media-library'); ?></label><br />

                                                <label><input name="vergeml_lib_options[search_in][]" type="checkbox" value="authors" <?php echo in_array('authors', $vergeml_lib_options['search_in']) ? 'checked' : ''; echo $disabled; ?> /> <?php esc_html_e('Authors','verge-media-library'); ?></label><br />
                                                <label><input name="vergeml_lib_options[search_in][]" type="checkbox" value="taxonomies" <?php echo in_array('taxonomies', $vergeml_lib_options['search_in']) ? 'checked' : ''; echo $disabled; ?> /> <?php esc_html_e('Media Taxonomies','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e('Enhance default search in Media Library and Media Popups.','verge-media-library'); ?></p>
                                                <p class="description"><?php esc_html_e('By default, WordPress looks into filenames, titles, captions, and descriptions.','verge-media-library'); ?></p>
                                                <p class="description"><?php
                                                printf(
                                                    '<strong style="color:blue">%s!</strong> %s',
                                                    __( 'Note', 'verge-media-library' ),
                                                    __( 'The fewer options, the faster search.', 'verge-media-library' )
                                                ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Search on enter','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Search on enter','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[search_on_enter]" type="hidden" value="0" /><input id="vergeml_lib_options_search_on_enter" name="vergeml_lib_options[search_on_enter]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['search_on_enter'], true ); ?> /> <?php esc_html_e('Enable search on hitting Enter key','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Use in combination with the higher minimum number of letters or disable auto search at all.', 'verge-media-library' ); ?></p>
                                                <p class="description"><?php esc_html_e( 'Works for Media Library Grid Mode and Media Popups.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Auto search','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Auto search','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[search_auto]" type="hidden" value="0" /><input id="vergeml_lib_options_search_auto" name="vergeml_lib_options[search_auto]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['search_auto'], true ); ?> /> <?php esc_html_e('Enable auto search while typing search request','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Default WordPress behavior for Media Library Grid Mode and Media Popups.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr id="vergeml_lib_options_search_min_letters">
                                        <th scope="row"><?php esc_html_e('Minimun number of letters','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Minimun number of letters','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[search_min_letters]" type="number" min="2" step="1" value="<?php echo (int) $vergeml_lib_options['search_min_letters']; ?>" /> <?php esc_html_e('Set the minimum number of letters required to start the auto search','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e('Set higher number to prevent multiple search requests to the database.','verge-media-library'); ?></p>
                                                <p class="description"><?php esc_html_e( 'Using a higher number can improve auto search query performance.', 'verge-media-library' ); ?></p>
                                                <p class="description"><?php esc_html_e( 'Works for Media Library Grid Mode and Media Popups.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-lib-settings-search' ) ); ?>

                            </div>

                        </div>




                        <h2><?php esc_html_e('Order','verge-media-library'); ?></h2>

                        <div class="postbox">

                            <div class="inside">

                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><label for="vergeml_lib_options[media_orderby]"><?php esc_html_e('Order media items by','verge-media-library'); ?></label></th>
                                        <td>
                                            <select name="vergeml_lib_options[media_orderby]" id="vergeml_lib_options_media_orderby">
                                                <option value="date" <?php selected( $vergeml_lib_options['media_orderby'], 'date' ); ?>><?php esc_html_e('Date','verge-media-library'); ?></option>
                                                <option value="title" <?php selected( $vergeml_lib_options['media_orderby'], 'title' ); ?>><?php esc_html_e('Title','verge-media-library'); ?></option>
                                                <option value="menuOrder" <?php selected( $vergeml_lib_options['media_orderby'], 'menuOrder' ); ?>><?php esc_html_e('Custom Order','verge-media-library'); ?></option>
                                            </select>
                                            <?php esc_html_e('For media library and media popups','verge-media-library'); ?>
                                            <p class="description"><?php esc_html_e( 'Allows changing media items order by drag and drop with Custom Order value.', 'verge-media-library' ); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="vergeml_lib_options[media_order]"><?php esc_html_e('Sort order','verge-media-library'); ?></label></th>
                                        <td>
                                            <select name="vergeml_lib_options[media_order]" id="vergeml_lib_options_media_order">
                                                <option value="ASC" <?php selected( $vergeml_lib_options['media_order'], 'ASC' ); ?>><?php esc_html_e('Ascending','verge-media-library'); ?></option>
                                                <option value="DESC" <?php selected( $vergeml_lib_options['media_order'], 'DESC' ); ?>><?php esc_html_e('Descending','verge-media-library'); ?></option>
                                            </select>
                                            <?php esc_html_e('For media library and media popups','verge-media-library'); ?>
                                        </td>
                                    </tr>

                                    <tr id="vergeml_lib_options_natural_sort">
                                        <th scope="row"><?php esc_html_e('Natural sort order','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Natural sort order','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[natural_sort]" type="hidden" value="0" /><input name="vergeml_lib_options[natural_sort]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['natural_sort'], true ); ?> /> <?php esc_html_e('Apply human-friendly sort order to Media Library and Galleries','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Example: [1, 2, 3, 10, 18, 22, abc-2, abc-11] instead of [1, 10, 18, 2, 22, 3, abc-11, abc-2]', 'verge-media-library' );  ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-lib-settings-order' ) ); ?>

                            </div>

                        </div>


                        <h2><?php esc_html_e('Grid Mode','verge-media-library'); ?></h2>

                        <div class="postbox">

                            <div class="inside">

                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Right sidebar width','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Right sidebar width','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[grid_sidebar_width]" type="number" min="200" step="10" value="<?php echo (int) $vergeml_lib_options['grid_sidebar_width']; ?>" /> <?php esc_html_e('Applies when the screen width is more than 900px','verge-media-library'); ?></label>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Ideal column width','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Ideal column width','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[ideal_column_width]" type="number" min="50" step="10" value="<?php echo (int) $vergeml_lib_options['ideal_column_width']; ?>" /> <?php esc_html_e('Set preferable size for thumbnails in the media library and media popups','verge-media-library'); ?></label>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Show caption','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Show caption','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[grid_show_caption]" type="hidden" value="0" /><input id="vergeml_lib_options_grid_show_caption" name="vergeml_lib_options[grid_show_caption]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['grid_show_caption'], true ); ?> /> <?php esc_html_e('Add text caption for media item thumbnails', 'verge-media-library'); ?></label>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr id="vergeml_lib_options_grid_caption_type">
                                        <th scope="row"><label for="vergeml_lib_options[grid_caption_type]"><?php esc_html_e('Caption type','verge-media-library'); ?></label></th>
                                        <td>
                                            <select name="vergeml_lib_options[grid_caption_type]">
                                                <option value="title" <?php selected( $vergeml_lib_options['grid_caption_type'], 'title' ); ?>><?php esc_html_e('Title','verge-media-library'); ?></option>
                                                <option value="filename" <?php selected( $vergeml_lib_options['grid_caption_type'], 'filename' ); ?>><?php esc_html_e('Filename','verge-media-library'); ?></option>
                                                <option value="caption" <?php selected( $vergeml_lib_options['grid_caption_type'], 'caption' ); ?>><?php esc_html_e('Caption','verge-media-library'); ?></option>
                                            </select>
                                        </td>
                                    </tr>

                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-lib-settings-grid-mode' ) ); ?>

                            </div>

                        </div>


                        <h2><?php esc_html_e('Media Shortcodes','verge-media-library'); ?></h2>

                        <div class="postbox">

                            <div class="inside">

                                <table class="form-table">

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Enhanced media shortcodes','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Enhanced media shortcodes','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_lib_options[enhance_media_shortcodes]" type="hidden" value="0" /><input name="vergeml_lib_options[enhance_media_shortcodes]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_lib_options['enhance_media_shortcodes'], true ); ?> /> <?php esc_html_e('Enhance WordPress media shortcodes to make them understand media taxonomies, upload date, and media items number limit','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Gallery example:', 'verge-media-library' );  ?> [gallery media_category="5" limit="10" monthnum="12" year="2015"]</p>
                                                <p class="description"><?php esc_html_e( 'Audio playlist example:', 'verge-media-library' ); ?> [playlist media_category="5" limit="10" monthnum="12" year="2015"]</p>
                                                <p class="description"><?php esc_html_e( 'Video playlist example:', 'verge-media-library' ); ?> [playlist type="video" media_category="5" limit="10" monthnum="12" year="2015"]</p>
                                                <p class="description"><?php
                                                printf(
                                                    '<strong style="color:red">%s!</strong> ',
                                                    __( 'Warning', 'verge-media-library' )
                                                );
                                                printf(
                                                    __( 'Incompatibility with other gallery plugins or themes possible! <a href="%s">Learn more</a>.', 'verge-media-library' ),
                                                    esc_url('https://wpuxsolutions.com/documents/enhanced-media-library/enhanced-gallery-possible-conflicts/')
                                                );
                                                echo ' ';
                                                printf(
                                                    __( 'Please check out your gallery front-end and back-end functionality once this option activated. If you find an issue please inform plugin authors at %s or %s.', 'verge-media-library' ),
                                                    '<a href="https://wordpress.org/support/plugin/enhanced-media-library">wordpress.org</a>',
                                                    '<a href="https://wpuxsolutions.com/support/create-new-ticket/">wpuxsolutions.com</a>'
                                                ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-lib-settings-media-shortcode' ) ); ?>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <?php
}



/**
 *  vergeml_print_taxonomies_options
 *
 *  @type     callback function
 *  @since    1.0
 *  @created  28/09/13
 */

function vergeml_print_taxonomies_options() {

    if ( ! current_user_can( 'manage_options' ) )
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'verge-media-library' ) );

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['media_settings'] )
            wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );
    }


    $vergeml_taxonomies = get_option( 'vergeml_taxonomies', array() );
    $title = __('Media Settings'); ?>


    <div id="vergeml-global-options-wrap" class="wrap eml-options">

        <h1><?php echo esc_html( $title ); ?></h1>

        <?php vergeml_print_media_settings_tabs( 'taxonomies' ); ?>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder">

                <div id="postbox-container-2" class="postbox-container">

                    <form id="vergeml-form-taxonomies" method="post" action="options.php">

                        <?php settings_fields( 'media-taxonomies' ); ?>

                        <div class="postbox">

                            <h3 class="hndle"><?php esc_html_e('Media Taxonomies','verge-media-library'); ?></h3>

                            <div class="inside">

                                <p><?php esc_html_e('Assign following taxonomies to Media Library:','verge-media-library'); ?></p>

                                <?php $html = '';

                                foreach ( get_taxonomies(array(),'object') as $taxonomy ) {

                                    if ( (in_array('attachment',$taxonomy->object_type) && count($taxonomy->object_type) == 1) || empty($taxonomy->object_type) ) {

                                        $assigned = (bool) $vergeml_taxonomies[$taxonomy->name]['assigned'];
                                        $eml_media = (bool) $vergeml_taxonomies[$taxonomy->name]['eml_media'];

                                        if ( $eml_media )
                                            $li_class = 'vergeml-taxonomy';
                                        else
                                            $li_class = 'wpuxss-non-eml-taxonomy';

                                        $html .= '<li class="' . $li_class . '" id="' . esc_attr($taxonomy->name) . '">';

                                        $html .= '<input name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][eml_media]" type="hidden" value="' . $eml_media . '" />';
                                        $html .= '<label><input class="vergeml-assigned" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][assigned]" type="checkbox" value="1" ' . checked( true, $assigned, false ) . ' title="' . __('Assign Taxonomy','verge-media-library') . '" />' . esc_html($taxonomy->label) . '</label>';
                                        $html .= '<a class="vergeml-button-edit" title="' . __('Edit Taxonomy','verge-media-library') . '" href="javascript:;">' . __('Edit','verge-media-library') . ' &darr;</a>';

                                        if ( $eml_media ) {

                                            $html .= '<a class="vergeml-button-remove" title="' . __('Delete Taxonomy','verge-media-library') . '" href="javascript:;">&ndash;</a>';

                                            $html .= '<div class="vergeml-taxonomy-edit" style="display:none;">';

                                            $html .= '<div class="vergeml-labels-edit">';
                                            $html .= '<h4>' . __('Labels','verge-media-library') . '</h4>';
                                            $html .= '<ul>';
                                            $html .= '<li><label>' . __('Singular','verge-media-library') . '</label><input type="text" class="vergeml-singular_name" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][singular_name]" value="' . esc_html($taxonomy->labels->singular_name) . '" /></li>';
                                            $html .= '<li><label>' . __('Plural','verge-media-library') . '</label><input type="text" class="vergeml-name" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][name]" value="' . esc_html($taxonomy->labels->name) . '" /></li>';
                                            $html .= '<li><label>' . __('Menu Name','verge-media-library') . '</label><input type="text" class="vergeml-menu_name" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][menu_name]" value="' . esc_html($taxonomy->labels->menu_name) . '" /></li>';
                                            $html .= '<li><label>' . __('All','verge-media-library') . '</label><input type="text" class="vergeml-all_items" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][all_items]" value="' . esc_html($taxonomy->labels->all_items) . '" /></li>';
                                            $html .= '<li><label>' . __('Edit','verge-media-library') . '</label><input type="text" class="vergeml-edit_item" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][edit_item]" value="' . esc_html($taxonomy->labels->edit_item) . '" /></li>';
                                            $html .= '<li><label>' . __('View','verge-media-library') . '</label><input type="text" class="vergeml-view_item" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][view_item]" value="' . esc_html($taxonomy->labels->view_item) . '" /></li>';
                                            $html .= '<li><label>' . __('Update','verge-media-library') . '</label><input type="text" class="vergeml-update_item" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][update_item]" value="' . esc_html($taxonomy->labels->update_item) . '" /></li>';
                                            $html .= '<li><label>' . __('Add New','verge-media-library') . '</label><input type="text" class="vergeml-add_new_item" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][add_new_item]" value="' . esc_html($taxonomy->labels->add_new_item) . '" /></li>';
                                            $html .= '<li><label>' . __('New','verge-media-library') . '</label><input type="text" class="vergeml-new_item_name" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][new_item_name]" value="' . esc_html($taxonomy->labels->new_item_name) . '" /></li>';
                                            $html .= '<li><label>' . __('Parent','verge-media-library') . '</label><input type="text" class="vergeml-parent_item" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][parent_item]" value="' . esc_html($taxonomy->labels->parent_item) . '" /></li>';
                                            $html .= '<li><label>' . __('Search','verge-media-library') . '</label><input type="text" class="vergeml-search_items" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][labels][search_items]" value="' . esc_html($taxonomy->labels->search_items) . '" /></li>';
                                            $html .= '</ul>';
                                            $html .= '</div>';

                                            $html .= '<div class="vergeml-settings-edit">';
                                            $html .= '<h4>' . __('Settings','verge-media-library') . '</h4>';
                                            $html .= '<ul>';
                                            $html .= '<li><label>' . __('Taxonomy Name','verge-media-library') . '</label><input type="text" class="vergeml-taxonomy-name" name="" value="' . esc_attr($taxonomy->name) . '" disabled="disabled" /></li>';
                                            $html .= '<li><label>' . __('Hierarchical','verge-media-library') . '</label><input type="checkbox" class="vergeml-hierarchical" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][hierarchical]" value="1" ' . checked( true, (bool) $taxonomy->hierarchical, false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Column for List View','verge-media-library') . '</label><input type="checkbox" class="vergeml-show_admin_column" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][show_admin_column]" value="1" ' . checked( true, (bool) $taxonomy->show_admin_column, false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Filter for List View','verge-media-library') . '</label><input type="checkbox" class="vergeml-admin_filter" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][admin_filter]" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['admin_filter'], false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Filter for Grid View / Media Popup','verge-media-library') . '</label><input type="checkbox" class="vergeml-media_uploader_filter" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][media_uploader_filter]" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['media_uploader_filter'], false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Edit in Media Popup','verge-media-library') . '</label><input type="checkbox" class="vergeml-media_popup_taxonomy_edit" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][media_popup_taxonomy_edit]" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['media_popup_taxonomy_edit'], false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Remember Terms Order (sort)','verge-media-library') . '</label><input type="checkbox" class="vergeml-sort" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][sort]" value="1" ' . checked( true, (bool) $taxonomy->sort, false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Show in REST','verge-media-library') . '</label><input type="checkbox" class="vergeml-show_in_rest" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][show_in_rest]" value="1" ' . checked( true, (bool) $taxonomy->show_in_rest, false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Rewrite Slug','verge-media-library') . '</label><input type="text" class="vergeml-slug" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][rewrite][slug]" value="' . esc_attr($vergeml_taxonomies[$taxonomy->name]['rewrite']['slug']) . '" /></li>';
                                            $html .= '<li><label>' . __('Slug with Front','verge-media-library') . '</label><input type="checkbox" class="vergeml-with_front" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][rewrite][with_front]" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['rewrite']['with_front'], false ) . ' /></li>';
                                            $html .= '</ul>';
                                            $html .= '</div>';

                                            $html .= '</div>';
                                        }
                                        else {

                                            $html .= '<div class="vergeml-taxonomy-edit" style="display:none;">';

                                            $html .= '<div class="vergeml-settings-edit">';
                                            $html .= '<h4>' . __('Settings','verge-media-library') . '</h4>';
                                            $html .= '<ul>';
                                            $html .= '<li><label>' . __('Filter for List View','verge-media-library') . '</label><input type="checkbox" class="vergeml-admin_filter" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][admin_filter]" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['admin_filter'], false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Filter for Grid View / Media Popup','verge-media-library') . '</label><input type="checkbox" class="vergeml-media_uploader_filter" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][media_uploader_filter]" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['media_uploader_filter'], false ) . ' /></li>';
                                            $html .= '<li><label>' . __('Edit in Media Popup','verge-media-library') . '</label><input type="checkbox" class="vergeml-media_popup_taxonomy_edit" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][media_popup_taxonomy_edit]" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['media_popup_taxonomy_edit'], false ) . ' /></li>';
                                            $html .= '</ul>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        }
                                        $html .= '</li>';
                                    }
                                }

                                $html .= '<li class="vergeml-clone" style="display:none">';
                                $html .= '<input name="" type="hidden" class="vergeml-eml_media" value="1" />';
                                $html .= '<input name="" type="hidden" class="vergeml-create_taxonomy" value="1" />';
                                $html .= '<label class="vergeml-taxonomy-label"><input class="vergeml-assigned" name="" type="checkbox" class="vergeml-assigned" value="1" checked="checked" title="' . __('Assign Taxonomy','verge-media-library') . '" />' . '<span>' . __('New Taxonomy','verge-media-library') . '</span></label>';

                                $html .= '<a class="vergeml-button-remove" title="' . __('Delete Taxonomy','verge-media-library') . '" href="javascript:;">&ndash;</a>';

                                $html .= '<div class="vergeml-taxonomy-edit">';

                                $html .= '<div class="vergeml-labels-edit">';
                                $html .= '<h4>' . __('Labels','verge-media-library') . '</h4>';
                                $html .= '<ul>';
                                $html .= '<li><label>' . __('Singular','verge-media-library') . '</label><input type="text" class="vergeml-singular_name" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Plural','verge-media-library') . '</label><input type="text" class="vergeml-name" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Menu Name','verge-media-library') . '</label><input type="text" class="vergeml-menu_name" name="" value="" /></li>';
                                $html .= '<li><label>' . __('All','verge-media-library') . '</label><input type="text" class="vergeml-all_items" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Edit','verge-media-library') . '</label><input type="text" class="vergeml-edit_item" name="" value="" /></li>';
                                $html .= '<li><label>' . __('View','verge-media-library') . '</label><input type="text" class="vergeml-view_item" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Update','verge-media-library') . '</label><input type="text" class="vergeml-update_item" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Add New','verge-media-library') . '</label><input type="text" class="vergeml-add_new_item" name="" value="" /></li>';
                                $html .= '<li><label>' . __('New','verge-media-library') . '</label><input type="text" class="vergeml-new_item_name" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Parent','verge-media-library') . '</label><input type="text" class="vergeml-parent_item" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Search','verge-media-library') . '</label><input type="text" class="vergeml-search_items" name="" value="" /></li>';
                                $html .= '</ul>';
                                $html .= '</div>';

                                $html .= '<div class="vergeml-settings-edit">';
                                $html .= '<h4>' . __('Settings','verge-media-library') . '</h4>';
                                $html .= '<ul>';
                                $html .= '<li><label>' . __('Taxonomy Name','verge-media-library') . '</label><input type="text" class="vergeml-taxonomy-name" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Hierarchical','verge-media-library') . '</label><input type="checkbox" class="vergeml-hierarchical" name="" value="1" checked="checked" /></li>';
                                $html .= '<li><label>' . __('Column for List View','verge-media-library') . '</label><input class="vergeml-show_admin_column" type="checkbox" name="" value="1" /></li>';
                                $html .= '<li><label>' . __('Filter for List View','verge-media-library') . '</label><input class="vergeml-admin_filter" type="checkbox"  name="" value="1" /></li>';
                                $html .= '<li><label>' . __('Filter for Grid View / Media Popup','verge-media-library') . '</label><input class="vergeml-media_uploader_filter" type="checkbox" name="" value="1" /></li>';
                                $html .= '<li><label>' . __('Edit in Media Popup','verge-media-library') . '</label><input class="vergeml-media_popup_taxonomy_edit" type="checkbox" name="" value="1" /></li>';
                                $html .= '<li><label>' . __('Remember Terms Order (sort)','verge-media-library') . '</label><input type="checkbox" class="vergeml-sort" name="" value="1" /></li>';
                                $html .= '<li><label>' . __('Show in REST','verge-media-library') . '</label><input type="checkbox" class="vergeml-show_in_rest" name="" value="1" /></li>';
                                $html .= '<li><label>' . __('Rewrite Slug','verge-media-library') . '</label><input type="text" class="vergeml-slug" name="" value="" /></li>';
                                $html .= '<li><label>' . __('Slug with Front','verge-media-library') . '</label><input type="checkbox" class="vergeml-with_front" name="" value="1" checked="checked" /></li>';
                                $html .= '</ul>';
                                $html .= '</div>';

                                $html .= '</div>';
                                $html .= '</li>'; ?>

                                <?php if ( ! empty( $html ) ) : ?>

                                    <ul class="vergeml-settings-list vergeml-media-taxonomy-list">
                                        <?php echo $html; ?>
                                    </ul>
                                    <div class="vergeml-button-container-right"><a class="add-new-h2 vergeml-button-create-taxonomy" href="javascript:;">+ <?php esc_html_e( 'Add New Taxonomy', 'verge-media-library' ); ?></a></div>
                                <?php endif; ?>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-tax-settings-media' ) ); ?>
                            </div>

                        </div>

                        <div class="postbox">

                            <h3 class="hndle"><?php esc_html_e('Non-Media Taxonomies','verge-media-library'); ?></h3>

                            <div class="inside">

                                <p><?php esc_html_e('Assign following taxonomies to Media Library:','verge-media-library'); ?></p>

                                <?php $unuse = array('revision','nav_menu_item','attachment');

                                foreach ( get_post_types(array(),'object') as $post_type ) {

                                    if ( ! in_array( $post_type->name, $unuse ) ) {

                                        $taxonomies = get_object_taxonomies($post_type->name,'object');
                                        if ( ! empty( $taxonomies ) ) {

                                            $html = '';

                                            foreach ( $taxonomies as $taxonomy ) {

                                                if ( $taxonomy->name == 'post_format' || 
                                                     $taxonomy->name == 'wp_theme'||
                                                     $taxonomy->name == 'wp_pattern_category'||
                                                     $taxonomy->name == 'wp_template_part_area' ) {
                                                    continue;
                                                }


                                                $html .= '<li class="wpuxss-non-eml-taxonomy" id="' . esc_attr($taxonomy->name) . '">';
                                                $html .= '<input name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][eml_media]" type="hidden" value="' . esc_attr($vergeml_taxonomies[$taxonomy->name]['eml_media']) . '" />';
                                                $html .= '<label><input class="vergeml-assigned" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][assigned]" type="checkbox" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['assigned'], false ) . ' title="' . __('Assign Taxonomy','verge-media-library') . '" />' . esc_html($taxonomy->label) . '</label>';
                                                $html .= '<a class="vergeml-button-edit" title="' . __('Edit Taxonomy','verge-media-library') . '" href="javascript:;">' . __('Edit','verge-media-library') . ' &darr;</a>';
                                                $html .= '<div class="vergeml-taxonomy-edit" style="display:none;">';

                                                $html .= '<h4>' . __('Settings','verge-media-library') . '</h4>';
                                                $html .= '<ul>';
                                                $html .= '<li><input type="checkbox" class="vergeml-admin_filter" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][admin_filter]" id="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-admin_filter" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['admin_filter'], false ) . ' /><label for="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-admin_filter">' . __('Filter for List View','verge-media-library') . '</label></li>';
                                                $html .= '<li><input type="checkbox" class="vergeml-media_uploader_filter" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][media_uploader_filter]" id="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-media_uploader_filter" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['media_uploader_filter'], false ) . ' /><label for="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-media_uploader_filter">' . __('Filter for Grid View / Media Popup','verge-media-library') . '</label></li>';
                                                $html .= '<li><input type="checkbox" class="vergeml-media_popup_taxonomy_edit" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][media_popup_taxonomy_edit]" id="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-media_popup_taxonomy_edit" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['media_popup_taxonomy_edit'], false ) . ' /><label for="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-media_popup_taxonomy_edit">' . __('Edit in Media Popup','verge-media-library') . '</label></li>';

                                                $class = defined( 'EML_IS_PRO' ) ? '' : ' class="disabled"';
                                                $class_name = defined( 'EML_IS_PRO' ) ? '' : ' disabled';
                                                $disabled = defined( 'EML_IS_PRO' ) ? '' : ' readonly="readonly"';
                                                $pro_message = defined( 'EML_IS_PRO' ) ? '' : ' <span class="premium disabled">/ Premium Feature</span>';
                                                $post_singular_name = strtolower ( $post_type->labels->singular_name );

                                                $html .= $pro_message;
                                                $html .= '<li' . $class . '><input type="checkbox" class="vergeml-taxonomy_auto_assign" name="vergeml_taxonomies[' . esc_attr($taxonomy->name) . '][taxonomy_auto_assign]" id="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-taxonomy_auto_assign" value="1" ' . checked( true, (bool) $vergeml_taxonomies[$taxonomy->name]['taxonomy_auto_assign'], false ) . $disabled . ' />';
                                                $html .= '<label for="vergeml_taxonomies-' . esc_attr($taxonomy->name) . '-taxonomy_auto_assign">' . sprintf(
                                                    __('Auto-assign media items to parent %s %s on upload','verge-media-library'),
                                                    esc_html($post_singular_name),
                                                    esc_html($taxonomy->label)
                                                ) . '</label>
                                                <a class="add-new-h2 eml-button-synchronize-terms' . $class_name . '" data-post-type="' . esc_attr($post_type->name) . '" data-taxonomy="' . esc_attr($taxonomy->name) . '" href="javascript:;">' . __( 'Synchronize Now', 'verge-media-library' ) . '</a><p class="description">';
                                                $html .= sprintf(
                                                    '<strong style="color:red">%s:</strong> ',
                                                    __('Warning','verge-media-library')
                                                );
                                                $html .= sprintf(
                                                    __('As a result of clicking "Synchronize Now" all media items attached to a %s will be assigned to %s of their parent %s. Currently assigned %s will not be saved. Media items that are not attached to any %s will not be affected.','verge-media-library'),
                                                    esc_html($post_singular_name),
                                                    esc_html($taxonomy->label),
                                                    esc_html($post_singular_name),
                                                    esc_html($taxonomy->label),
                                                    esc_html($post_singular_name)
                                                ) . '</p></li>';

                                                $html .= '</ul>';

                                                $html .= '</div>';
                                                $html .= '</li>';
                                            } ?>

                                            <?php if ( ! empty( $html ) ) : ?>

                                                <h4><?php echo esc_html($post_type->label); ?></h4>
                                                <ul class="vergeml-settings-list vergeml-non-media-taxonomy-list">
                                                    <?php echo $html; ?>
                                                </ul>

                                            <?php endif;
                                        }
                                    }
                                }

                                submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-tax-settings-non-media' ) ); ?>

                            </div>

                        </div>

                        <h2><?php esc_html_e('Options','verge-media-library'); ?></h2>

                        <?php $vergeml_tax_options = get_option( 'vergeml_tax_options' ); ?>

                        <div class="postbox">

                            <div class="inside">

                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Taxonomy archive pages','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Taxonomy archive pages','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_tax_options[tax_archives]" type="hidden" value="0" /><input name="vergeml_tax_options[tax_archives]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_tax_options['tax_archives'], true ); ?> /> <?php esc_html_e('Turn on media taxonomy archive pages on the front-end','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Re-save your permalink settings after this option change to make it work.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><?php esc_html_e('Assign all like hierarchical','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Assign all like hierarchical','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_tax_options[edit_all_as_hierarchical]" type="hidden" value="0" /><input name="vergeml_tax_options[edit_all_as_hierarchical]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_tax_options['edit_all_as_hierarchical'], true ); ?> /> <?php esc_html_e('Show non-hierarchical taxonomies like hierarchical in Grid View / Media Popup','verge-media-library'); ?></label>
                                            </fieldset>
                                        </td>
                                    </tr>

                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-tax-settings' ) ); ?>

                            </div>

                        </div>

                        <?php 
                            $class_name = defined( 'EML_IS_PRO' ) ? '' : ' disabled';
                            $class = defined( 'EML_IS_PRO' ) ? '' : ' class="disabled"';
                            $disabled = defined( 'EML_IS_PRO' ) ? '' : ' readonly="readonly"';
                            $pro_message = defined( 'EML_IS_PRO' ) ? '' : ' <span class="premium">/ Premium Feature</span>';
                        ?>

                        <h2<?php echo $class; ?>><?php esc_html_e('Bulk Edit','verge-media-library');  echo $pro_message; ?></h2>

                        <div class="postbox<?php echo $class_name; ?>">

                            <div class="inside">

                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Save Changes button','verge-media-library'); ?></th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php esc_html_e('Turn off \'Save Changes\' button','verge-media-library'); ?></span></legend>
                                                <label><input name="vergeml_tax_options[bulk_edit_save_button]" type="hidden" value="0"><input name="vergeml_tax_options[bulk_edit_save_button]" type="checkbox" value="1" <?php checked( true, (bool) $vergeml_tax_options['bulk_edit_save_button'], true ); echo $disabled; ?> /> <?php esc_html_e('Bulk changes are being made not immediately - by clicking \'Save Changes\' button','verge-media-library'); ?></label>
                                                <p class="description"><?php esc_html_e( 'Try this if you edit a lot of media items at once and feel uncomfortable with editing saved on the fly.', 'verge-media-library' ); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>

                                <?php submit_button( __( 'Save Changes' ), 'primary', 'submit', true, array( 'id' => 'eml-submit-tax-settings-bulk-edit' ) ); ?>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <?php
}



/**
 *  vergeml_print_mimetypes_options
 *
 *  @type     callback function
 *  @since    1.0
 *  @created  28/09/13
 */

function vergeml_print_mimetypes_options() {

    if ( ! current_user_can('manage_options' ) )
        wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );

    if ( is_multisite() ) {

        $vergeml_network_options = get_site_option( 'vergeml_network_options', array() );

        if ( ! current_user_can( 'manage_network_options' ) && ! (bool) $vergeml_network_options['media_settings'] )
            wp_die( __('You do not have sufficient permissions to access this page.','verge-media-library') );
    }


    $vergeml_mimes = get_option('vergeml_mimes');

    $title = __('Media Settings'); ?>

    <div id="vergeml-global-options-wrap" class="wrap eml-options">

        <h1>
            <?php echo esc_html( $title ); ?>
            <a class="add-new-h2 vergeml-button-create-mime" href="javascript:;">+ <?php esc_html_e('Add New MIME Type','verge-media-library'); ?></a>
        </h1>

        <?php
        $warning = sprintf( 
            /* translators: %s: html <strong> and <br> tags to emphaseize some points. */
            esc_html__( 'WordPress %1$scommon role restrictions%2$s apply to the allowed MIME Types %1$sto avoid security issues%2$s. Advanced role management is coming.%3$s If you experience an issue with uploading file types report it, please.', 'verge-media-library' ),
            '<strong>',
            '</strong>',
            '<br />'
        );
        $w_link = __( 'Report a filetype', 'verge-media-library' );
        printf(
            '<div class="notice notice-news eml-admin-notice dashicons-before">
                <p>%1$s</p>
                <a href="https://wpuxsolutions.com/support" target="_blank" class="button button-primary">%2$s</a>
            </div>',
            $warning,
            $w_link
        );
        ?>

        <?php vergeml_print_media_settings_tabs( 'mimetypes' ); ?>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder">

                <div id="postbox-container-2" class="postbox-container">

                    <form method="post" action="options.php" id="vergeml-form-mimetypes">

                        <?php settings_fields( 'mime-types' ); ?>

                        <?php vergeml_print_mimetypes_buttons(); ?>

                        <table class="vergeml-mime-type-list wp-list-table widefat" cellspacing="0">
                            <thead>
                            <tr>
                                <th scope="col" class="manage-column vergeml-column-extension"><?php esc_html_e('Extension','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-mime"><?php esc_html_e('MIME Type','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-singular"><?php esc_html_e('Singular Label','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-plural"><?php esc_html_e('Plural Label','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-filter"><?php esc_html_e('Add Filter','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-upload"><?php esc_html_e('Allow Upload','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-delete"></th>
                            </tr>
                            </thead>


                            <tbody>

                            <?php
                            $all_mimes = wp_get_mime_types();
                            ksort( $all_mimes, SORT_STRING ); ?>

                            <?php foreach ( $all_mimes as $type => $mime ) :

                                if ( isset( $vergeml_mimes[$type] ) ) :

                                    $label = '<code>'. str_replace( '|', '</code>, <code>', esc_html($type) ) .'</code>';

                                    $allowed = (bool) $vergeml_mimes[$type]['upload']; ?>

                                    <tr>
                                    <td id="<?php echo esc_attr($type); ?>"><?php echo $label; ?></td>
                                    <td><code><?php echo esc_html($mime); ?></code><input type="hidden" class="vergeml-mime" name="vergeml_mimes[<?php echo esc_attr($type); ?>][mime]" value="<?php echo esc_html($vergeml_mimes[$type]['mime']); ?>" /></td>
                                    <td><input type="text" name="vergeml_mimes[<?php echo esc_attr($type); ?>][singular]" value="<?php echo esc_html($vergeml_mimes[$type]['singular']); ?>" /></td>
                                    <td><input type="text" name="vergeml_mimes[<?php echo esc_attr($type); ?>][plural]" value="<?php echo esc_html($vergeml_mimes[$type]['plural']); ?>" /></td>
                                    <td class="checkbox_td"><input type="checkbox" name="vergeml_mimes[<?php echo esc_attr($type); ?>][filter]" title="<?php esc_html_e('Add Filter','verge-media-library'); ?>" value="1" <?php checked(true, (bool) $vergeml_mimes[$type]['filter']); ?> /></td>
                                    <td class="checkbox_td"><input type="checkbox" name="vergeml_mimes[<?php echo esc_attr($type); ?>][upload]" title="<?php esc_html_e('Allow Upload','verge-media-library'); ?>" value="1" <?php checked(true, $allowed); ?> /></td>
                                    <td><a class="vergeml-button-remove" title="<?php esc_html_e('Delete MIME Type','verge-media-library'); ?>" href="javascript:;">&ndash;</a></td>
                                    </tr>

                                <?php endif; ?>
                            <?php endforeach; ?>

                            <tr class="vergeml-clone" style="display:none;">
                                <td><input type="text" class="vergeml-type" placeholder="jpg|jpeg|jpe" /></td>
                                <td><input type="text" class="vergeml-mime" placeholder="image/jpeg" /></td>
                                <td><input type="text" class="vergeml-singular" placeholder="Image" /></td>
                                <td><input type="text" class="vergeml-plural" placeholder="Images" /></td>
                                <td class="checkbox_td"><input type="checkbox" class="vergeml-filter" title="<?php esc_html_e('Add Filter','verge-media-library'); ?>" value="1" /></td>
                                <td class="checkbox_td"><input type="checkbox" class="vergeml-upload" title="<?php esc_html_e('Allow Upload','verge-media-library'); ?>" value="1" /></td>
                                <td><a class="vergeml-button-remove" title="<?php esc_html_e('Delete MIME Type','verge-media-library'); ?>" href="javascript:;">&ndash;</a></td>
                            </tr>

                            </tbody>
                            <tfoot>
                            <tr>
                                <th scope="col" class="manage-column vergeml-column-extension"><?php esc_html_e('Extension','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-mime"><?php esc_html_e('MIME Type','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-singular"><?php esc_html_e('Singular Label','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-plural"><?php esc_html_e('Plural Label','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-filter"><?php esc_html_e('Add Filter','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-upload"><?php esc_html_e('Allow Upload','verge-media-library'); ?></th>
                                <th scope="col" class="manage-column vergeml-column-delete"></th>
                            </tr>
                            </tfoot>
                        </table>

                        <?php vergeml_print_mimetypes_buttons(); ?>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <?php
}



/**
 *  vergeml_print_mimetypes_buttons
 *
 *  @since    2.3.1
 *  @created  01/08/16
 */

function vergeml_print_mimetypes_buttons() { ?>

    <p class="submit">
        <?php submit_button( __( 'Save Changes' ), 'primary', 'eml-save-mime-types-settings', false, array( 'id' => 'eml-submit-settings-save-mime-types' ) ); ?>

        <input type="button" name="eml-restore-mime-types-settings" id="eml-restore-mime-types-settings" class="button" value="<?php esc_html_e('Restore WordPress Default MIME Types','verge-media-library'); ?>">
    </p>

    <?php
}



/**
 *  vergeml_print_credits
 *
 *  @since    1.0
 *  @created  28/09/13
 */

function vergeml_print_credits() { ?>

    <div class="postbox" id="wpuxss-credits">

        <h3 class="hndle">Enhanced Media Library <?php echo VERGEML_VERSION; ?></h3>

        <div class="inside">

            <h4><?php esc_html_e( 'Changelog', 'verge-media-library' ); ?></h4>
            <p><?php esc_html_e( 'What\'s new in', 'verge-media-library' ); ?> <a href="https://github.com/vergelabsnathan/verge-media-library/releases"><?php esc_html_e( 'version', 'verge-media-library' ); echo ' ' . VERGEML_VERSION; ?></a>.</p>

            <h4><?php esc_html_e( 'Support', 'verge-media-library' ); ?></h4>
            <p><?php esc_html_e( 'Report a problem on', 'verge-media-library' ); ?> <a href="https://github.com/vergelabsnathan/verge-media-library/issues">GitHub</a>.</p>

            <div class="author">
                <span><?php esc_html_e( 'Based on', 'verge-media-library' ); ?> <a href="https://wordpress.org/plugins/enhanced-media-library/">Enhanced Media Library</a> <?php esc_html_e( 'by', 'verge-media-library' ); ?> <a href="https://wpuxsolutions.com/">wpUXsolutions</a></span>
            </div>

        </div>

    </div>

    <?php
}



/**
 *  vergeml_maybe_new_notice
 *
 *  Asks the remote and records a notice to the database
 *
 *  Disabled in this fork. Upstream polls wpuxsolutions.com every twelve hours
 *  and prints whatever HTML comes back into the admin. A fork has no business
 *  calling the original author's server, and cannot vouch for markup served
 *  from it. The endpoint is also reported to be down, which left every admin
 *  request paying a fifteen second timeout twice a day.
 *
 *  The function is kept, unhooked, so the notice-dismissal and settings code
 *  paths that reference it still resolve. Remove it once the fork gains its
 *  own update channel.
 *
 *  @since    2.8.10
 *  @created  2024/03
 */

function vergeml_maybe_new_notice() {

    return;

    $notices = get_site_option( 'vergeml_notices', array() );
    $checked = isset( $notices['checked'] ) ? $notices['checked'] : false;
    $period  = 12 * HOUR_IN_SECONDS;


    if ( ! empty( $checked ) &&
         $period > ( time() - $checked ) ) {
        return;
    }


    $url = vergeml_get_notice_url();

    $response = wp_remote_get( 
        $url, 
        array(
            'timeout' => 15,
            'body' => array(
                'action' => 'get-notice'
            )
        )
    );

    $notices['checked'] = time();


    /* 
     * if an error - there is nothing to update in the database except the time
     * a new check after 12 hours brings new data
     */
    if ( is_wp_error( $response ) || ! is_array( $response ) ) {

        // update checked in the DB
        update_site_option( 'vergeml_notices', $notices );
        return;
    }


    // $headers = $response['headers']; 
    $notice    = json_decode( $response['body'], true );


    // no notice from remote - unset current
    if (    empty( $notice ) || 
            ! isset( $notice['id'] ) || 
            ! isset( $notice['message'] )
        ) {

        unset ( $notices['current'] );

        update_site_option( 'vergeml_notices', $notices );
        return;
    }


    // sanitize notice params
    $fields = array(
        'id',
        'type',
        'version'
    );

    foreach ( $fields as $field ) {
        if ( ! isset( $notice[$field] ) ) {
            $notice[$field] = '';
        }
        $notice[$field] = sanitize_text_field( $notice[$field] );
    }


    // admin screens to show a notice
    $screens = isset( $notice['screens'] ) && is_array( $notice['screens'] ) 
             ? $notice['screens'] 
             : array();

    if ( ! empty( $screens ) ) {

        $screens = array_map( 'sanitize_text_field', $screens );

        // v.2.8.10 options
        $options = array(
            'plugins-php'       => array(
                'plugins',
                'plugins-network'
            ),
            'eml-options'       => array(
                'settings_page_eml-settings',
                'settings_page_eml-settings-network'
            ),
            'eml-media-options' => array(
                'settings_page_media',
                'settings_page_media-library',
                'settings_page_media-taxonomies',
                'settings_page_mime-types'
            )
        );

        foreach( array_keys( $options ) as $option ) {
            $key = array_search( $option, $screens );
            if ( false !== $key ) {
                array_splice( $screens, $key, 1, $options[$option] );
            }
        }
    }


    // show to free, pro, multisite, all of them?
    $for     = isset( $notice['for'] ) && is_array( $notice['for'] ) 
             ? $notice['for'] 
             : array();

    if ( ! empty( $for ) ) {
        $for = array_map( 'sanitize_text_field', $for );
    }
    

    $notice['screens'] = $screens;
    $notice['for']     = $for;


    $notice['message'] = wp_kses(
        $notice['message'],
        array(
            'p'      => array(),
            'a'      => array(
                'href'   => array(),
                'title'  => array(),
                'class'  => array(),
                'target' => array()
            ),
            'br'     => array(),
            'em'     => array(),
            'strong' => array( 
                'class'  => array()
            )
        )
    );


    $current_id = $notice['id'];

    // update current notice from remote if exists
    if ( isset( $notices[$current_id] ) ) {

        $notices[$current_id]['message'] = $notice['message'];
        $notices[$current_id]['version'] = $notice['version'];
        $notices[$current_id]['screens'] = $notice['screens'];
        $notices[$current_id]['for']     = $notice['for'];
        $notices['current'] = $current_id;

        update_site_option( 'vergeml_notices', $notices );
        return;
    }


    // completely new notice from remote
    $notices[$current_id] = $notice;
    $notices['current'] = $current_id;


    update_site_option( 'vergeml_notices', $notices );
}



/**
 *  vergeml_admin_notice
 *
 *  Shows a notice
 * 
 *  @since    2.8.10
 *  @created  2024/04
 */

add_action( 'admin_notices', 'vergeml_admin_notice' );
add_action( 'network_admin_notices', 'vergeml_admin_notice' );

function vergeml_admin_notice() {

    global // $pagenow,
           $current_screen;


    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }


    $notices = get_site_option( 'vergeml_notices', array() );


    if ( empty( $notices ) ) {
        return;
    }


    if ( ! isset( $notices['current'] ) ) {
        return;
    }


    $notice_id = $notices['current'];


    $user_id = get_current_user_id();
    if ( get_user_meta( $user_id, "vergeml_{$notice_id}_notice_dismissed" ) ) {
        return;
    }


    $notice = $notices[$notice_id];


    if (    ! empty( $notice['version'] ) && 
            version_compare( VERGEML_VERSION, $notice['version'], '>=' ) 
        ) {
        return;
    }


    if (    ! empty( $notice['for'] ) ) {

        // a notice for free users only
        if ( in_array( 'free', $notice['for'] ) && defined( 'EML_IS_PRO' ) ) {
            return;
        }

        // a notice for pro users only
        if ( in_array( 'pro', $notice['for'] ) && ! defined( 'EML_IS_PRO' ) ) {
            return;
        }

        // a notice for multisite users only
        if ( in_array( 'multisite', $notice['for'] ) && ! is_multisite() ) {
            return;
        }
    }


    if (    ! isset( $notice['screens'] ) || 
            ! in_array( $current_screen->base, $notice['screens'] ) 
        ) {
        return;
    }


    printf(
        '<div class="notice notice-%2$s is-dismissible eml-admin-notice dashicons-before" id="%3$s">
            %1$s
        </div>',
        wp_kses(
            $notice['message'],
            array(
                'p'      => array(),
                'a'      => array(
                    'href'   => array(),
                    'title'  => array(),
                    'class'  => array(),
                    'target' => array()
                ),
                'br'     => array(),
                'em'     => array(),
                'strong' => array( 
                    'class'  => array()
                )
            )
        ),
        esc_attr( $notice['type'] ),
        esc_html( $notice_id )
    );
}



/**
 *  vergeml_admin_notice_dismiss
 *
 *  Associates a dismissed notice mark with a user
 * 
 *  @since    2.8.10
 *  @created  2024/04
 */

add_action( 'wp_ajax_vergeml-admin-notice-dismiss', 'vergeml_admin_notice_dismiss' );

function vergeml_admin_notice_dismiss() {

    if ( ! isset( $_POST['notice_id'] ) )
        wp_die();


    check_ajax_referer( 'eml-admin-notice-nonce', 'nonce' );


    if ( ! isset( $_POST['notice_id'] ) )
        wp_send_json_error();

    $notice_id = sanitize_text_field( wp_unslash( $_POST['notice_id'] ) );
    $user_id = get_current_user_id();

    update_user_meta( $user_id, "vergeml_{$notice_id}_notice_dismissed", true );


    wp_die();
}



/**
 *  vergeml_get_notice_url
 *
 *  @since    2.8.10
 *  @since    2.9.4    modified to /notices/
 *  @created  2024/04
 */

function vergeml_get_notice_url() {

    return 'https://wpuxsolutions.com/notices/enhanced-media-library/';
}
