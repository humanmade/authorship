<?php
/**
 * REST API controller for fetching users for authorship and creating guest authors.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Users_Controller;

/**
 * Core class used to manage users via the REST API.
 *
 * This API grants wider read-only access to users, but with less data exposed for each user. This
 * allows Authorship to grant lower level users permission to browse users and create guest authors
 * without exposing all the information that the `wp/v2/users` endpoint does.
 *
 * This controller extends core's Users controller to take advantage of a lot of inheritance.
 */
class Users_Controller extends WP_REST_Users_Controller {

	const _NAMESPACE = 'authorship/v1';
	const BASE = 'users';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->namespace = self::_NAMESPACE;
		$this->rest_base = self::BASE;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Permissions check for getting all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'attribute_post_type', $request->get_param( 'post_type' ) ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to list users.', 'authorship' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		if (
			$request->get_param( 'slug' ) ||
			$request->get_param( 'who' ) ||
			$request->get_param( 'roles' )
		) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to filter users by this parameter.', 'authorship' ),
				[
					'status' => WP_Http::BAD_REQUEST,
				]
			);
		}

		if ( 'edit' === $request->get_param( 'context' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to list users in the context of editing.', 'authorship' ),
				[
					'status' => WP_Http::BAD_REQUEST,
				]
			);
		}

		return true;
	}

	/**
	 * Retrieves all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		add_filter( 'rest_user_query', [ $this, 'filter_rest_user_query' ], 10, 2 );

		$items = parent::get_items( $request );

		remove_filter( 'rest_user_query', [ $this, 'filter_rest_user_query' ] );

		return $items;
	}

	/**
	 * Filters WP_User_Query arguments when querying users via the REST API.
	 *
	 * @param mixed[]          $prepared_args Array of arguments for WP_User_Query.
	 * @param \WP_REST_Request $request       The current request.
	 * @return mixed[] Array of arguments for WP_User_Query.
	 */
	function filter_rest_user_query( array $prepared_args, \WP_REST_Request $request ) : array {
		unset( $prepared_args['has_published_posts'] );

		return $prepared_args;
	}

	/**
	 * Checks if a given request has access to create users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'create_guest_authors' ) ) {
			return new WP_Error(
				'rest_cannot_create_user',
				__( 'Sorry, you are not allowed to create guest authors.', 'authorship' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		if ( $request->get_param( 'email' ) && ! current_user_can( 'create_users' ) ) {
			return new WP_Error(
				'rest_cannot_create_user_with_email',
				__( 'Sorry, you are not allowed to create guest authors with an email address.', 'authorship' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		if (
			$request->get_param( 'roles' ) ||
			$request->get_param( 'password' )
		) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to provide this parameter when creating a guest author.', 'authorship' ),
				[
					'status' => WP_Http::FORBIDDEN,
				]
			);
		}

		return true;
	}

	/**
	 * Creates a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$username = sanitize_title( sanitize_user( $request->get_param( 'name' ), true ) );
		$username = preg_replace( '/[^a-z0-9]/', '', $username );

		$request->set_param( 'username', $username );

		/**
		 * Filters the validated user registration details.
		 *
		 * @param array $result {
		 *     The array of user name, email, and the error messages.
		 *
		 *     @type string   $user_name     Sanitized and unique username.
		 *     @type string   $orig_username Original username.
		 *     @type string   $user_email    User email address.
		 *     @type WP_Error $errors        WP_Error object containing any errors found.
		 * }
		 */
		add_filter( 'wpmu_validate_user_signup', function( array $result ) : array {
			/** @var WP_Error $errors */
			$errors = $result['errors'];
			$errors->remove( 'user_email' );

			return $result;
		} );

		return parent::create_item( $request );
	}

	/**
	 * Prepares a single user for creation or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return object User object.
	 */
	protected function prepare_item_for_database( $request ) {
		$request->set_param( 'password', wp_generate_password( 24 ) );

		if ( empty( $request->get_param( 'email' ) ) || ! current_user_can( 'create_users' ) ) {
			$request->set_param( 'email', '' );
		}

		$request->set_param( 'roles', [ GUEST_ROLE ] );

		return parent::prepare_item_for_database( $request );
	}

	/**
	 * Retrieves the user's schema, conforming to JSON Schema.
	 *
	 * @return mixed[] Item schema data.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['email']['required'] = false;
		$schema['properties']['username']['required'] = false;
		$schema['properties']['password']['required'] = false;
		$schema['properties']['password']['readonly'] = true;

		$schema['properties']['name']['required'] = true;

		unset(
			$schema['properties']['capabilities'],
			$schema['properties']['description'],
			$schema['properties']['extra_capabilities'],
			$schema['properties']['url']
		);

		$this->schema = $schema;

		return $schema;
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @return mixed[] Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['orderby']['enum'] = [
			'id',
			'include',
			'name',
		];

		$query_params['post_type'] = [
			'description' => __( 'Post type name.', 'authorship' ),
			'type'        => 'string',
			'enum'        => get_post_types( [
				'show_in_rest' => true,
			] ),
			'required'    => true,
		];

		unset(
			$query_params['context'],
			$query_params['slug'],
			$query_params['who'],
			$query_params['roles']
		);

		/**
		 * Filters REST API collection parameters for the authorship users controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_User_Query parameter.
		 *
		 * Mimics the rest_user_collection_params from core's endpoint.
		 *
		 * @since 0.2.8
		 *
		 * @param array $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'authorship_rest_user_collection_params', $query_params );
	}
}
