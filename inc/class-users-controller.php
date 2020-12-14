<?php
/**
 * REST API controller for fetching users for authorship and greating guest authors.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use WP_Error;
use WP_Http;
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
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the user.' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Permissions check for getting all users.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has read access, otherwise WP_Error object.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'list_users' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to list users.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( $request->get_param( 'roles' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to filter users by role.' ),
				array( 'status' => WP_Http::FORBIDDEN )
			);
		}

		if ( 'edit' === $request->get_param( 'context' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit users.' ),
				array( 'status' => WP_Http::FORBIDDEN )
			);
		}

		return true;
	}

	/**
	 * Checks if a given request has access to read a user.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has read access for the item, otherwise \WP_Error object.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'list_users' ) ) {
			return new WP_Error(
				'rest_user_cannot_view',
				__( 'Sorry, you are not allowed to list users.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( 'edit' === $request->get_param( 'context' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit users.' ),
				array( 'status' => WP_Http::FORBIDDEN )
			);
		}

		$user = $this->get_user( $request['id'] );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		return true;
	}

	/**
	 * Checks if a given request has access create users.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access to create items, \WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'create_users' ) ) {
			return new WP_Error(
				'rest_cannot_create_user',
				__( 'Sorry, you are not allowed to create new users.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Prepares a single user for creation or update.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return object User object.
	 */
	protected function prepare_item_for_database( $request ) {
		$request->set_param( 'password', 'password' );
		$request->set_param( 'roles', [ ROLE ] );

		return parent::prepare_item_for_database( $request );
	}

	/**
	 * Retrieves the user's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		unset(
			$schema['properties']['capabilities'],
			$schema['properties']['extra_capabilities'],
			$schema['properties']['password'],
			$schema['properties']['roles']
		);

		return $schema;
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @return mixed[] Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['orderby'] = array(
			'default'     => 'name',
			'description' => __( 'Sort collection by object attribute.' ),
			'enum'        => array(
				'id',
				'name',
			),
			'type'        => 'string',
		);

		$query_params['search']['required'] = true;

		unset(
			$query_params['context'],
			$query_params['include'],
			$query_params['slug'],
			$query_params['who'],
			$query_params['roles'],
			$query_params['exclude']
		);

		return $query_params;
	}
}
