<?php
class DAM_Users_Controller extends WP_REST_Controller
{

  // Here initialize our namespace and resource name.
  public function __construct()
  {
    $this->namespace     = '/dam/v1';
    $this->resource_name = 'users';
  }

  // Register our routes.
  public function register_routes()
  {
    register_rest_route($this->namespace, '/' . $this->resource_name, array(
      // Here we register the readable endpoint for collections.
      array(
        'methods'   => 'GET',
        'callback'  => array($this, 'get_items'),
        'permission_callback' => array($this, 'get_items_permissions_check'),
      ),
      // Register our schema callback.
      'schema' => array($this, 'get_item_schema'),
    ));
    register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
      // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
      array(
        'methods'   => 'GET',
        'callback'  => array($this, 'get_item'),
        'permission_callback' => array($this, 'get_item_permissions_check'),
      ),
      // Register our schema callback.
      'schema' => array($this, 'get_item_schema'),
    ));
  }

  /**
   * Check permissions for the users.
   *
   * @param WP_REST_Request $request Current request.
   */
  public function get_items_permissions_check($request)
  {
    if (!current_user_can('read')) {
      return new WP_Error('rest_forbidden', esc_html__('Você não pode ver o recurso do usuário.'), array('status' => $this->authorization_status_code()));
    }
    return true;
  }

  /**
   * Grabs the five most recent users and outputs them as a rest response.
   *
   * @param WP_REST_Request $request Current request.
   */
  public function get_items($request)
  {
    $args = array(
      'post_per_page' => -1,
      'date_query'  => array(
        'after'     => $request['after'], // Usuários após a data de registro
        'before'    => $request['before'], // Usuários antes da data de registro
        'inclusive' => false,
      ),
    );
    $users = get_users($args);

    $data = array();

    if (empty($users)) {
      return rest_ensure_response($data);
    }

    foreach ($users as $user) {
      $response = $this->prepare_item_for_response($user, $request);
      $data[] = $this->prepare_response_for_collection($response);
    }

    // Return all of our comment response data.
    return rest_ensure_response($data);
  }

  /**
   * Check permissions for the users.
   *
   * @param WP_REST_Request $request Current request.
   */
  public function get_item_permissions_check($request)
  {
    if (!current_user_can('read')) {
      return new WP_Error('rest_forbidden', esc_html__('Você não pode ver o recurso do usuário.'), array('status' => $this->authorization_status_code()));
    }
    return true;
  }

  /**
   * Grabs the five most recent users and outputs them as a rest response.
   *
   * @param WP_REST_Request $request Current request.
   */
  public function get_item($request)
  {
    $id = (int) $request['id'];
    $user = get_user_by('ID', $id);

    if (empty($user)) {
      return rest_ensure_response(array());
    }

    $response = $this->prepare_item_for_response($user, $request);

    // Return all of our user response data.
    return $response;
  }

  /**
   * Matches the user data to the schema we want.
   *
   * @param WP_Post $post The comment object whose response is being prepared.
   */
  public function prepare_item_for_response($post, $request)
  {
    $post_data = array();

    $schema = $this->get_item_schema($request);

    // We are also renaming the fields to more understandable names.
    if (isset($schema['properties']['id'])) {
      $post_data['id'] = (int) $post->ID;
    }

    $post_data['display_name'] = $post->data->display_name;
    $post_data['email'] = $post->data->user_email;
    $post_data['user_login'] = $post->data->user_login;
    $post_data['roles'] = $post->roles;
    $post_data['registered_date'] = $post->data->user_registered;

    return rest_ensure_response($post_data);
  }


  /**
   * Prepare a response for inserting into a collection of responses.
   *
   * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
   *
   * @param WP_REST_Response $response Response object.
   * @return array Response data, ready for insertion into collection data.
   */
  public function prepare_response_for_collection($response)
  {
    if (!($response instanceof WP_REST_Response)) {
      return $response;
    }

    $data = (array) $response->get_data();
    $server = rest_get_server();

    if (method_exists($server, 'get_compact_response_links')) {
      $links = call_user_func(array($server, 'get_compact_response_links'), $response);
    } else {
      $links = call_user_func(array($server, 'get_response_links'), $response);
    }

    if (!empty($links)) {
      $data['_links'] = $links;
    }

    return $data;
  }

  /**
   * Get our sample schema for a user.
   *
   * @return array The sample schema for a user
   */
  public function get_item_schema()
  {
    if ($this->schema) {
      // Since WordPress 5.3, the schema can be cached in the $schema property.
      return $this->schema;
    }

    $this->schema = array(
      // This tells the spec of JSON Schema we are using which is draft 4.
      '$schema'              => 'http://json-schema.org/draft-04/schema#',
      // The title property marks the identity of the resource.
      'title'                => 'post',
      'type'                 => 'object',
      // In JSON Schema you can specify object properties in the properties attribute.
      'properties'           => array(
        'id' => array(
          'description'  => esc_html__('Identificador único para o objeto.', 'my-textdomain'),
          'type'         => 'integer',
          'context'      => array('view', 'edit', 'embed'),
          'readonly'     => true,
        ),
        'content' => array(
          'description'  => esc_html__('Conteúdo do objeto.', 'my-textdomain'),
          'type'         => 'string',
        ),
      ),
    );

    return $this->schema;
  }

  // Sets up the proper HTTP status code for authorization.
  public function authorization_status_code()
  {

    $status = 401;

    if (is_user_logged_in()) {
      $status = 403;
    }

    return $status;
  }
}

// Function to register our new routes from the controller.
function user_created_my_rest_routes()
{
  $controller = new DAM_Users_Controller();
  $controller->register_routes();
}

add_action('rest_api_init', 'user_created_my_rest_routes');