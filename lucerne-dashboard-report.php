<?php
/**
 * Plugin Name: Lucerne Dashboard Reporting
 * Plugin URI: http://lucernepartners.com
 * Description: This plugin shows the overall report of all its users
 * Version: 1.0.0
 * Author: Mikko de Castro
 * Author URI: http://mikkodc.com
 * License: GPL2
 */

function ldr_admin_page() {
  global $ldr_settings;
  $ldr_settings = add_menu_page(__('Lucerne Dashboard Reporting', 'ldr'), __('Site Report', 'ldr'), 'manage_options', 'ldr-reporting', 'ldr_render_admin', 'dashicons-chart-bar', '5');
}
add_action('admin_menu', 'ldr_admin_page');


function ldr_render_admin() {
  ?>
  <div class="wrap">
    <h2><?php _e('Lucerne Dashboard Reporting', 'ldr');?></h2>
    <div class="col-md-4">
    <?php

    $subscribers = get_users($args);

    echo '<h4 class="page-header">Select User:</h4>';
    echo '<ul class="select-users">';
    foreach ($subscribers as $user) {
      echo '<li><a data-id="'. $user->ID .'">' .$user->first_name.' '. $user->last_name. ' - ['.$user->user_email . ']</a></li>';
    }
    echo '</ul>';

    global $wpdb;
    $clientsViews = $wpdb->get_results(
      "
      SELECT *
      FROM wp_reporting
      WHERE visited_at != '0000-00-00 00:00:00'
      "
    );

    $arrangeUsers = array();
    foreach($clientsViews as $clients) {
      $user = $clients->user_id;

      $arrangeUsers[$clients->user_id][] = array (
        'visited_at' => $clients->visited_at,
        'post_id' => $clients->post_id
      );
    }
    ?>
      <div class="reporting">
        <h4 class="page-header">Last 5 Login</h4>
        <ul>
          <?php fb_list_authors(); ?>
        </ul>
      </div>
      <div class="reporting">
        <h4 class="page-header">Last 5 Articles Viewed</h4>
        <?php articlesReport(post_views_count, View); ?>
      </div>
      <div class="reporting">
        <h4 class="page-header">Last 5 Articles Visited</h4>
        <?php articlesReport(post_visits_count, Visit); ?>
      </div>
    </div>
    <div class="col-md-8">
      <h3 class="page-header">View Report by user</h3>
      <div id="ajax-container">
        <p>Click a user on the left side to see their reports.</p>
      </div>
    </div>
  </div>
  <script type="text/javascript">
    var pluginDir = "<?php echo plugin_dir_url(__FILE__) ?>";
  </script>

  <?php
}

function ldr_load_scripts($hook) {
  global $ldr_settings;

  if ($hook != $ldr_settings)
    return;

  wp_enqueue_script('ldr-ajax', plugin_dir_url(__FILE__) . 'js/ldr-ajax.js', array('jquery'));
  wp_enqueue_style( 'bootstrap-css', get_stylesheet_directory_uri() .'/library/src/bootstrap-css/css/bootstrap.min.css' );
  wp_enqueue_style( 'ldr-css', plugin_dir_url(__FILE__) . 'css/style.css' );

}
add_action('admin_enqueue_scripts', 'ldr_load_scripts');

function ldr_view_report() {
  $client_id = $_GET['clientID'];

  global $wpdb;

  $clientsVisits = $wpdb->get_results(
    "
    SELECT *
    FROM wp_reporting
    WHERE visited_at != '0000-00-00 00:00:00'
    AND user_id = $client_id
    "
  );

  $user_info = get_userdata($client_id);
  $first_name = $user_info->first_name; ?>

  <h4>Last 5 Visited Articles by <?php echo $first_name; ?></h4>


  <?php
  if(!$clientsVisits) {
    echo "<i>Client has no articles visited yet.</i>";
  } else {
    $visitArray = array();
    foreach($clientsVisits as $visit) {
      $visitArray[] = array(
        'visited_at' => $visit->visited_at,
        'post_id' => $visit->post_id
      );
    }

    array_multisort($visitArray);
    $sortedVisited = val_sort($visitArray, 'visited_at');
    ?>
    <ul>
    <?php
    foreach($sortedVisited as $visitSorted) {
      $post_viewed = get_post($visitSorted['post_id']);
      echo "<li>". $post_viewed->post_title ."</li>";
    }
    ?>
    </ul>
  <?php }

  $clientsViews = $wpdb->get_results(
    "
    SELECT *
    FROM wp_reporting
    WHERE viewed_at != '0000-00-00 00:00:00'
    AND user_id = $client_id
    "
  ); ?>

  <h4>Last 5 Viewed Articles by <?php echo $first_name; ?></h4>
  <?php if(!$clientsViews) {
    echo "<i>Client has no articles viewed yet.</i>";
  } else { ?>
    <ul>

    <?php
    $viewArray = array();
    foreach($clientsViews as $view) {
      $viewArray[] = array(
        'viewed_at' => $view->viewed_at,
        'post_id' => $view->post_id
      );
    }
    array_multisort($viewArray);
    $sortedView = val_sort($viewArray, 'viewed_at');
    ?>
    <ul>
    <?php
    foreach($sortedView as $viewSorted) {
      $post_visited = get_post($viewSorted['post_id']);
      echo "<li>". $post_visited->post_title ."</li>";
    }
    ?>
    </ul>
  <?php } ?>

  <h4>Last 5 Login Dates by <?php echo $first_name; ?></h4>

  <?php $clientLogin = $wpdb->get_results(
    "
    SELECT *
    FROM wp_loginlog
    WHERE user_id = $client_id
    "
  );
  if(!$clientLogin) {
    echo "<i>Client has not logged in yet.</i>";
  } else { ?>
      <ul>
      <?php $explodedVal = explode(",", $clientLogin[0]->login_log);
      array_multisort($explodedVal);
      $sortedLogin = val_sort($explodedVal, 'login_log');
      foreach ($sortedLogin as $viewLogin) {
        $formattedLogin = date('F j, Y - H:i:sa', strtotime($viewLogin));
        echo "<li>". $formattedLogin . "</li>";
      }
      ?>
      </ul>
  <?php }
  die();
}
add_action('wp_ajax_view_report', 'ldr_view_report');

?>
