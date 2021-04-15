<?php
/**
 * Test for Search_With_Term
 *
 * @package HAMWORKS\WP\Custom_Search\Test
 */

namespace HAMWORKS\WP\Custom_Search\Test;

use HAMWORKS\WP\Custom_Search\Search_With_Term;

/**
 * Class Search_With_Term_Test
 */
class Search_With_Term_Test extends \WP_UnitTestCase {

	/**
	 * WP_Query.
	 *
	 * @var \WP_Query
	 */
	protected $q;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$cat = $this->factory()->category->create( array( 'name' => 'opqrstuvwxyz' ) );
		$this->factory()->post->create_many( 25 );

		$this->factory()->post->create_many(
			25,
			array(
				'post_category' => array( $cat ),
			)
		);

		$this->q = new \WP_Query();
	}

	/**
	 * Search helper.
	 *
	 * @param string $terms term.
	 *
	 * @return int[]|\WP_Post[]
	 */
	private function get_search_results( string $terms ) {
		$args = http_build_query(
			array(
				's'        => $terms,
				'nopaging' => true,
			)
		);
		return $this->q->query( $args );
	}


	/**
	 * Test.
	 */
	public function test_search() {
		new Search_With_Term( array( 'category' ) );
		$posts = $this->get_search_results( 'opqrstuvwxyz' );
		$this->assertEquals( 25, count( $posts ) );
	}

}
