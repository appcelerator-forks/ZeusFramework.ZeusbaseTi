<?php

class WPAPP {

  public $options;
  
  function __construct() {
    $this->query = new WPAPP_Query();
    $this->introspector = new WPAPP_Introspector();
    $this->response = new WPAPP_Response();
    add_action('template_redirect', array(&$this, 'template_redirect'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('update_option_wpapp_base', array(&$this, 'flush_rewrite_rules'));
    add_action('pre_update_option_wpapp_controllers', array(&$this, 'update_controllers'));

    $this->options = get_option('wpapp_options');
  }
  
  function template_redirect() {
    // Check to see if there's an appropriate API controller + method    
    $controller = strtolower($this->query->get_controller());
    $available_controllers = $this->get_controllers();
    $enabled_controllers = explode(',', get_option('wpapp_controllers', 'core'));
    $active_controllers = array_intersect($available_controllers, $enabled_controllers);
    
    if ($controller) {
      
      if (!in_array($controller, $active_controllers)) {
        $this->error("Unknown controller '$controller'.");
      }
      
      $controller_path = $this->controller_path($controller);
      if (file_exists($controller_path)) {
        require_once $controller_path;
      }
      $controller_class = $this->controller_class($controller);
      
      if (!class_exists($controller_class)) {
        $this->error("Unknown controller '$controller_class'.");
      }
      
      $this->controller = new $controller_class();
      $method = $this->query->get_method($controller);
      
      if ($method) {
        
        $this->response->setup();
        
        // Run action hooks for method
        do_action("wpapp-{$controller}-$method");
        
        // Error out if nothing is found
        if ($method == '404') {
          $this->error('Not found');
        }
        
        // Run the method
        $result = $this->controller->$method();
        
        // Handle the result
        $this->response->respond($result);
        
        // Done!
        exit;
      }
    }
  }
  
  function admin_menu() {
    add_options_page('WPAPP Settings', 'WPAPP', 'manage_options', 'wpapp', array(&$this, 'admin_options2'));
    $this->register_settings_and_fields();
  }

  ////////////////

  function admin_options2() {
    ?>

    <div class="wrap">
      <div id="icon-options-general" class="icon32"><br /></div>
      <h2>WPAPP Settings</h2>
    </div>

    <form method="post" action="options.php">
      <?php settings_fields('wpapp_options') ?>
      <?php do_settings_sections(__FILE__) ?>
      <input type="submit" class="button-primary" value="Save Changes">

    </form>


    <?php
  }

  function register_settings_and_fields() {
    register_setting('wpapp_options', 'wpapp_options');
    add_settings_section('wpapp_main_section','Main Setting',array($this, 'wpapp_main_section_cb'),__FILE__);
    add_settings_field('wpapp_api_key','API KEY:',array($this, 'wpapp_api_key_setting'),__FILE__,'wpapp_main_section');
    add_settings_field('wpapp_urbanairship_app_key','Urban Airship App Key:',array($this, 'wpapp_urbanairship_app_key_setting'),__FILE__,'wpapp_main_section');
    add_settings_field('wpapp_urbanairship_master_secret','Urban Airship Master Secret:',array($this, 'wpapp_urbanairship_master_secret_setting'),__FILE__,'wpapp_main_section');
    add_settings_field('wpapp_ddapns_access_key','DDAPNS Access Key:',array($this, 'wpapp_ddapns_access_key_setting'),__FILE__,'wpapp_main_section');
    add_settings_field('wpapp_ddapns_url','DDAPNS Url:',array($this, 'wpapp_ddapns_url_setting'),__FILE__,'wpapp_main_section');
  }

  function wpapp_main_section_cb() {
    
  }

  function wpapp_api_key_setting() {
    print "<input class='regular-text code' type='text' name='wpapp_options[wpapp_api_key]' value='{$this->options[wpapp_api_key]}' />";
  }

  function wpapp_urbanairship_app_key_setting() {
    print "<input class='regular-text code' type='text' name='wpapp_options[wpapp_urbanairship_app_key]' value='{$this->options[wpapp_urbanairship_app_key]}' />";
  }

  function wpapp_urbanairship_master_secret_setting() {
    print "<input class='regular-text code' type='text' name='wpapp_options[wpapp_urbanairship_master_secret]' value='{$this->options[wpapp_urbanairship_master_secret]}' />";
  }

  function wpapp_ddapns_access_key_setting() {
    print "<input class='regular-text code' type='text' name='wpapp_options[wpapp_ddapns_access_key]' value='{$this->options[wpapp_ddapns_access_key]}' />";
  }

  function wpapp_ddapns_url_setting() {
    print "<input class='regular-text code' type='text' name='wpapp_options[wpapp_ddapns_url]' value='{$this->options[wpapp_ddapns_url]}' /> (ends with slash)";
  }
  
  ////////////////

  function admin_options() {
    if (!current_user_can('manage_options'))  {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    
    $available_controllers = $this->get_controllers();
    $active_controllers = explode(',', get_option('wpapp_controllers', 'core'));
    
    if (count($active_controllers) == 1 && empty($active_controllers[0])) {
      $active_controllers = array();
    }
    
    if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {
      if ((!empty($_REQUEST['action']) || !empty($_REQUEST['action2'])) &&
          (!empty($_REQUEST['controller']) || !empty($_REQUEST['controllers']))) {
        if (!empty($_REQUEST['action'])) {
          $action = $_REQUEST['action'];
        } else {
          $action = $_REQUEST['action2'];
        }
        
        if (!empty($_REQUEST['controllers'])) {
          $controllers = $_REQUEST['controllers'];
        } else {
          $controllers = array($_REQUEST['controller']);
        }
        
        foreach ($controllers as $controller) {
          if (in_array($controller, $available_controllers)) {
            if ($action == 'activate' && !in_array($controller, $active_controllers)) {
              $active_controllers[] = $controller;
            } else if ($action == 'deactivate') {
              $index = array_search($controller, $active_controllers);
              if ($index !== false) {
                unset($active_controllers[$index]);
              }
            }
          }
        }
        $this->save_option('wpapp_controllers', implode(',', $active_controllers));
      }
      if (isset($_REQUEST['wpapp_base'])) {
        $this->save_option('wpapp_base', $_REQUEST['wpapp_base']);
      }
    }
    
    ?>
<div class="wrap">
  <div id="icon-options-general" class="icon32"><br /></div>
  <h2>WPAPP Settings</h2>
  <form action="options-general.php?page=wpapp" method="post">
    <?php wp_nonce_field('update-options'); ?>
    <h3>Controllers</h3>
    <?php $this->print_controller_actions(); ?>
    <table id="all-plugins-table" class="widefat">
      <thead>
        <tr>
          <th class="manage-column check-column" scope="col"><input type="checkbox" /></th>
          <th class="manage-column" scope="col">Controller</th>
          <th class="manage-column" scope="col">Description</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <th class="manage-column check-column" scope="col"><input type="checkbox" /></th>
          <th class="manage-column" scope="col">Controller</th>
          <th class="manage-column" scope="col">Description</th>
        </tr>
      </tfoot>
      <tbody class="plugins">
        <?php
        
        foreach ($available_controllers as $controller) {
          
          $error = false;
          $active = in_array($controller, $active_controllers);
          $info = $this->controller_info($controller);
          
          if (is_string($info)) {
            $active = false;
            $error = true;
            $info = array(
              'name' => $controller,
              'description' => "<p><strong>Error</strong>: $info</p>",
              'methods' => array(),
              'url' => null
            );
          }
          
          ?>
          <tr class="<?php echo ($active ? 'active' : 'inactive'); ?>">
            <th class="check-column" scope="row">
              <input type="checkbox" name="controllers[]" value="<?php echo $controller; ?>" />
            </th>
            <td class="plugin-title">
              <strong><?php echo $info['name']; ?></strong>
              <div class="row-actions-visible">
                <?php
                
                if ($active) {
                  echo '<a href="' . wp_nonce_url('options-general.php?page=wpapp&amp;action=deactivate&amp;controller=' . $controller, 'update-options') . '" title="' . __('Deactivate this controller') . '" class="edit">' . __('Deactivate') . '</a>';
                } else if (!$error) {
                  echo '<a href="' . wp_nonce_url('options-general.php?page=wpapp&amp;action=activate&amp;controller=' . $controller, 'update-options') . '" title="' . __('Activate this controller') . '" class="edit">' . __('Activate') . '</a>';
                }
                  
                if ($info['url']) {
                  echo ' | ';
                  echo '<a href="' . $info['url'] . '" target="_blank">Docs</a></div>';
                }
                
                ?>
            </td>
            <td class="desc">
              <p><?php echo $info['description']; ?></p>
              <p>
                <?php
                
                foreach($info['methods'] as $method) {
                  $url = $this->get_method_url($controller, $method, array('dev' => 1));
                  if ($active) {
                    echo "<code><a href=\"$url\">$method</a></code> ";
                  } else {
                    echo "<code>$method</code> ";
                  }
                }
                
                ?>
              </p>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <?php $this->print_controller_actions('action2'); ?>
    <h3>Address</h3>
    <p>Specify a base URL for WPAPP. For example, using <code>api</code> as your API base URL would enable the following <code><?php bloginfo('url'); ?>/api/get_recent_posts/</code>. If you assign a blank value the API will only be available by setting a <code>json</code> query variable.</p>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">API base</th>
        <td><code><?php bloginfo('url'); ?>/</code><input type="text" name="wpapp_base" value="<?php echo get_option('wpapp_base', 'api'); ?>" size="15" /></td>
      </tr>
    </table>
    <?php if (!get_option('permalink_structure', '')) { ?>
      <br />
      <p><strong>Note:</strong> User-friendly permalinks are not currently enabled. <a target="_blank" class="button" href="options-permalink.php">Change Permalinks</a>
    <?php } ?>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
  </form>
</div>
<?php
  }
  
  function print_controller_actions($name = 'action') {
    ?>
    <div class="tablenav">
      <div class="alignleft actions">
        <select name="<?php echo $name; ?>">
          <option selected="selected" value="-1">Bulk Actions</option>
          <option value="activate">Activate</option>
          <option value="deactivate">Deactivate</option>
        </select>
        <input type="submit" class="button-secondary action" id="doaction" name="doaction" value="Apply">
      </div>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
    <?php
  }
  
  function get_method_url($controller, $method, $options = '') {
    $url = get_bloginfo('url');
    $base = get_option('wpapp_base', 'api');
    $permalink_structure = get_option('permalink_structure', '');
    if (!empty($options) && is_array($options)) {
      $args = array();
      foreach ($options as $key => $value) {
        $args[] = urlencode($key) . '=' . urlencode($value);
      }
      $args = implode('&', $args);
    } else {
      $args = $options;
    }
    if ($controller != 'core') {
      $method = "$controller/$method";
    }
    if (!empty($base) && !empty($permalink_structure)) {
      if (!empty($args)) {
        $args = "?$args";
      }
      return "$url/$base/$method/$args";
    } else {
      return "$url?json=$method&$args";
    }
  }
  
  function save_option($id, $value) {
    $option_exists = (get_option($id, null) !== null);
    if ($option_exists) {
      update_option($id, $value);
    } else {
      add_option($id, $value);
    }
  }
  
  function get_controllers() {
    $controllers = array();
    $dir = wpapp_dir();
    $dh = opendir("$dir/controllers");
    while ($file = readdir($dh)) {
      if (preg_match('/(.+)\.php$/', $file, $matches)) {
        $controllers[] = $matches[1];
      }
    }
    $controllers = apply_filters('wpapp_controllers', $controllers);
    return array_map('strtolower', $controllers);
  }
  
  function controller_is_active($controller) {
    if (defined('WPAPP_CONTROLLERS')) {
      $default = WPAPP_CONTROLLERS;
    } else {
      $default = 'core';
    }
    $active_controllers = explode(',', get_option('wpapp_controllers', $default));
    return (in_array($controller, $active_controllers));
  }
  
  function update_controllers($controllers) {
    if (is_array($controllers)) {
      return implode(',', $controllers);
    } else {
      return $controllers;
    }
  }
  
  function controller_info($controller) {
    $path = $this->controller_path($controller);
    $class = $this->controller_class($controller);
    $response = array(
      'name' => $controller,
      'description' => '(No description available)',
      'methods' => array()
    );
    if (file_exists($path)) {
      $source = file_get_contents($path);
      if (preg_match('/^\s*Controller name:(.+)$/im', $source, $matches)) {
        $response['name'] = trim($matches[1]);
      }
      if (preg_match('/^\s*Controller description:(.+)$/im', $source, $matches)) {
        $response['description'] = trim($matches[1]);
      }
      if (preg_match('/^\s*Controller URI:(.+)$/im', $source, $matches)) {
        $response['docs'] = trim($matches[1]);
      }
      if (!class_exists($class)) {
        require_once($path);
      }
      $response['methods'] = get_class_methods($class);
      return $response;
    } else if (is_admin()) {
      return "Cannot find controller class '$class' (filtered path: $path).";
    } else {
      $this->error("Unknown controller '$controller'.");
    }
    return $response;
  }
  
  function controller_class($controller) {
    return "wpapp_{$controller}_controller";
  }
  
  function controller_path($controller) {
    $dir = wpapp_dir();
    $controller_class = $this->controller_class($controller);
    return apply_filters("{$controller_class}_path", "$dir/controllers/$controller.php");
  }
  
  function get_nonce_id($controller, $method) {
    $controller = strtolower($controller);
    $method = strtolower($method);
    return "wpapp-$controller-$method";
  }
  
  function flush_rewrite_rules() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
  }
  
  function error($message = 'Unknown error', $status = 'error') {
    $this->response->respond(array(
      'error' => $message
    ), $status);
  }
  
  function include_value($key) {
    return $this->response->is_value_included($key);
  }
  
}

?>