<?php
/**
 * Search_With_Term
 *
 * @package HAMWORKS\WP\Custom_Search
 */

namespace HAMWORKS\WP\Custom_Search;

use WP_Query;

/**
 * Class Search_With_Term
 */
class Search_With_Term {

	/**
	 * Taxonomies for search.
	 *
	 * @var string[]
	 */
	public $taxonomies = array();

	/**
	 * Constructor.
	 *
	 * @param string[] $taxonomies taxonomies for search.
	 */
	public function __construct( $taxonomies = array() ) {
		$this->taxonomies = $taxonomies;
		add_filter( 'posts_search', array( $this, 'posts_search' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby' ), 10, 2 );
	}

	/**
	 * Remove filters.
	 */
	public function __destruct() {
		remove_filter( 'posts_search', array( $this, 'posts_search' ) );
		remove_filter( 'posts_groupby', array( $this, 'posts_groupby' ) );
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

		$tt_where = implode(
			'OR',
			array_map(
				function ( $taxonomy ) {
					return " tt.taxonomy LIKE '{$taxonomy}' ";
				},
				$this->taxonomies
			)
		);

		$meta_where = " OR {$wpdb->posts}.ID IN (
			SELECT distinct r.object_id
			FROM {$wpdb->term_relationships} AS r
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON r.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
			WHERE ({$tt_where}) AND (t.name LIKE $1 OR t.slug LIKE $1 OR tt.description LIKE $1)
		)";

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
