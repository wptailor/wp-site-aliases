<?php

/**
 * Site Aliases Cache
 *
 * @package Plugins/Site/Aliases/Cache
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds any site aliases from the given ids to the cache that do not already
 * exist in cache.
 *
 * @since 1.0.0
 * @access private
 *
 * @see update_site_cache()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $ids               ID list.
 * @param bool  $update_meta_cache Whether to update site alias cache. Default true.
 */
function _prime_site_alias_caches( $ids = array(), $update_meta_cache = true ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $ids, 'blog-aliases' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_aliases = $wpdb->get_results( sprintf( "SELECT * FROM {$wpdb->blog_aliases} WHERE id IN (%s)", join( ",", array_map( 'intval', $non_cached_ids ) ) ) );

		update_site_alias_cache( $fresh_aliases, $update_meta_cache );
	}
}

/**
 * Updates site aliases in cache.
 *
 * @since 1.0.0
 *
 * @param array $aliases           Array of site alias objects.
 * @param bool  $update_meta_cache Whether to update site alias cache. Default true.
 */
function update_site_alias_cache( $aliases = array(), $update_meta_cache = true ) {

	// Bail if no aliases
	if ( empty( $aliases ) ) {
		return;
	}

	// Loop through aliases & add them to cache group
	foreach ( $aliases as $alias ) {
		wp_cache_add( $alias->id, $alias, 'blog-aliases' );
	}

	// Maybe update site alias meta cache
	if ( true === $update_meta_cache ) {
		update_site_aliasmeta_cache( wp_list_pluck( $aliases, 'id' ) );
	}
}

/**
 * Clean the site alias cache
 *
 * @since 1.0.0
 *
 * @param int|WP_Site_Alias $alias Alias ID or alias object to remove from the cache
 */
function clean_blog_alias_cache( $alias ) {
	global $_wp_suspend_cache_invalidation;

	// Bail if cache invalidation is suspended
	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	// Get alias, and bail if not found
	$alias = WP_Site_Alias::get_instance( $alias );
	if ( empty( $alias ) || is_wp_error( $alias ) ) {
		return;
	}

	// Delete alias from cache groups
	wp_cache_delete( $alias->id , 'blog-aliases'    );
	wp_cache_delete( $alias->id , 'blog_alias_meta' );

	/**
	 * Fires immediately after a site alias has been removed from the object cache.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $alias_id Alias ID.
	 * @param WP_Site $alias    Alias object.
	 */
	do_action( 'clean_site_alias_cache', $alias->id, $alias );

	wp_cache_set( 'last_changed', microtime(), 'blog-aliases' );
}
