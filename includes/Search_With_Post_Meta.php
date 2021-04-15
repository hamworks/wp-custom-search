<?php
/**
 * Search_With_Post_Meta
 *
 * @package HAMWORKS\WP\Custom_Search
 */

namespace HAMWORKS\WP\Custom_Search;

use WP_Query;

/**
 * Class Search_With_Post_Meta
 */
class Search_With_Post_Meta {

	/**
	 * Alias for postmeta.
	 *
	 * @var string
	 */
	private $post_meta_alias = 'pm_alias';

	/**
	 * Include custom field with these keys only.
	 *
	 * @var string[]
	 */
	public $meta_keys = array();

	/**
	 * Constructor.
	 *
	 * @param string[] $meta_keys meta key for search.
	 */
	public function __construct( $meta_keys = array() ) {
		$this->meta_keys = $meta_keys;
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_search', array( $this, 'posts_search' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby' ), 10, 2 );
	}

	/**
	 * Remove filters.
	 */
	public function __destruct() {
		remove_filter( 'posts_join', array( $this, 'posts_join' ) );
		remove_filter( 'posts_search', array( $this, 'posts_search' ) );
		remove_filter( 'posts_groupby', array( $this, 'posts_groupby' ) );
	}

	/**
	 * Filters the JOIN clause of the query.
	 *
	 * @param string   $join  The JOIN clause of the query.
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 *
	 * @return string
	 */
	public function posts_join( string $join, WP_Query $query ):string {
		global $wpdb;

		if ( ! $query->is_search() ) {
			return $join;
		}

		// Otherwise, join wp_postmeta table.
		$join .= "INNER JOIN {$wpdb->postmeta} as {$this->post_meta_alias} ON ( {$wpdb->posts}.ID = {$this->post_meta_alias}.post_id )";

		return $join;
	}

	/**
	 *
	 * Filters the search SQL that is used in the WHERE clause of WP_Query.
	 *
	 * @param string   $search Search SQL for WHERE clause.
	 * @param WP_Query $query  The current WP_Query object.
	 *
	 * @return string
	 */
	public function posts_search( string $search, WP_Query $query ):string {
		global $wpdb;

		if ( ! $query->is_search() ) {
			return $search;
		}

		$meta_where = '';
		if ( is_array( $this->meta_keys ) ) {
			foreach ( $this->meta_keys as $meta_key ) {
				$meta_where .= " OR ({$this->post_meta_alias}.meta_key = '{$meta_key}' AND {$this->post_meta_alias}.meta_value LIKE $1)";
			}
		}

		return preg_replace(
			"/\(\s*{$wpdb->posts}\.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			"({$wpdb->posts}.post_title LIKE $1)" . $meta_where,
			$search
		);
	}

	/**
	 * Filters the GROUP BY clause of the query.
	 *
	 * @param string   $groupby The GROUP BY clause of the query.
	 * @param WP_Query $query   The WP_Query instance (passed by reference).
	 *
	 * @return string
	 */
	public function posts_groupby( string $groupby, WP_Query $query ):string {
		global $wpdb;

		if ( ! $query->is_search() ) {
			return $groupby;
		}
		// Otherwise, let's add a group by clause.
		if ( empty( $groupby ) ) {
			$groupby = "{$wpdb->posts}.ID";
		}

		return $groupby;
	}

}
