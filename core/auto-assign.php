<?php

if ( ! defined( 'ABSPATH' ) )
    exit;


/**
 *  Auto-assign on upload.
 *
 *  When a file is uploaded from inside a post, it can inherit that post's
 *  terms. Upload three photos while editing a post filed under "Projects" and
 *  the photos are filed under "Projects" too, without anyone tagging them by
 *  hand afterwards.
 *
 *  This only applies to a taxonomy registered for BOTH attachments and the
 *  parent's post type, because a parent cannot hold terms of a taxonomy it does
 *  not have. In practice that means the taxonomies you assign to media on the
 *  Non-Media Taxonomies part of the settings screen: Categories, Tags, or your
 *  own.
 *
 *  Uploading from Media > Library assigns nothing, because there is no parent to
 *  inherit from. That is the intended behaviour, not an oversight.
 *
 *  @since    2.10
 */


/**
 *  vergeml_auto_assign_taxonomies
 *
 *  The taxonomies with auto-assign switched on that both the attachment and the
 *  given post type actually have.
 */

function vergeml_auto_assign_taxonomies( $parent_post_type ) {

    $settings = get_option( 'vergeml_taxonomies', array() );

    if ( ! is_array( $settings ) || empty( $settings ) )
        return array();

    $shared = array_intersect(
        get_object_taxonomies( 'attachment', 'names' ),
        get_object_taxonomies( $parent_post_type, 'names' )
    );

    $enabled = array();

    foreach ( $shared as $taxonomy ) {
        if ( ! empty( $settings[ $taxonomy ]['taxonomy_auto_assign'] ) )
            $enabled[] = $taxonomy;
    }

    return $enabled;
}


/**
 *  vergeml_auto_assign_parent_terms
 *
 *  Copies the parent's terms onto a freshly uploaded attachment.
 *
 *  Terms are appended rather than set, so nothing a person has already put on
 *  the item is thrown away. On a new upload there is nothing to preserve, but
 *  the hook is public and this keeps it safe to call twice.
 */

add_action( 'add_attachment', 'vergeml_auto_assign_parent_terms' );

function vergeml_auto_assign_parent_terms( $attachment_id ) {

    $attachment = get_post( $attachment_id );

    if ( ! $attachment || empty( $attachment->post_parent ) )
        return;

    $parent = get_post( $attachment->post_parent );

    if ( ! $parent )
        return;

    $taxonomies = vergeml_auto_assign_taxonomies( $parent->post_type );

    if ( empty( $taxonomies ) )
        return;

    foreach ( $taxonomies as $taxonomy ) {

        $terms = wp_get_object_terms( $parent->ID, $taxonomy, array( 'fields' => 'ids' ) );

        if ( is_wp_error( $terms ) || empty( $terms ) )
            continue;

        $terms = array_map( 'intval', $terms );

        /**
         *  Filter the terms about to be inherited from the parent post.
         *
         *  Return an empty array to skip this taxonomy for this upload.
         *
         *  @since 2.10
         *
         *  @param int[]   $terms         Term ids taken from the parent.
         *  @param int     $attachment_id The attachment receiving them.
         *  @param WP_Post $parent        The post the file was uploaded to.
         *  @param string  $taxonomy      The taxonomy being copied.
         */
        $terms = apply_filters( 'vergeml_auto_assign_terms', $terms, $attachment_id, $parent, $taxonomy );

        if ( empty( $terms ) )
            continue;

        wp_set_object_terms( $attachment_id, $terms, $taxonomy, true );
    }
}
