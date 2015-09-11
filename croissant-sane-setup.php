<?php

/*
Plugin Name: Croissant Sane Setup
Plugin URI: https://bitbucket.org/ShortlistMedia/croissant-sane-setup
Description: Set sane defaults on an initial WordPress Install
Version: 0.1.0
Author: Jon Sherrard & Gareth Foote
Author URI: http://twitter.com/jshez
License: GPL2
*/

class SaneSetup  {

  function __construct() {
    add_action( 'admin_init', array( $this, 'decide_to_run' ) );
  }

  function decide_to_run() {
    if ($this->check_for_run_option()) {
      $this->show_notice('Sane Setup has configured WordPress. <a href="'.admin_url().'?sane_setup_action=deactivate_sane_setup">Deactivate the Sane Setup plugin to remove this message</a>', 'updated');
      if ($this->get_sane_setup_action()  == 'deactivate_sane_setup') {
        $this->deactivate_self();
      }

    } else {
      $this->show_notice('Sane Setup has not been run yet. <a href="'.admin_url().'?sane_setup_action=run_sane_setup">Run Sane Setup now</a>', 'error');

      if ($this->get_sane_setup_action()  == 'run_sane_setup') {
        $this->do_setup();
      }

    }
  }

  function get_sane_setup_action() {
    if (isset($_GET['sane_setup_action'])) {
      return $_GET['sane_setup_action'];
    } else {
      return false;
    }
  }

  function check_for_run_option () {
    if ( get_option( 'sane_setup', '0' ) == '1' ) {
      return true;
    } else {
      return false;
    }
  }

  function deactivate_self() {
    deactivate_plugins( plugin_basename( __FILE__ ) );
  }

  function do_setup() {
    $this->create_a_menu();
    $this->delete_hello_world();
    $this->delete_sample_page();
    $this->create_home_page();
    $this->set_front_page();
    $this->sensible_category();
    $this->set_start_of_week();
    $this->update_permalinks();
    $this->disable_emojis();
    $this->create_list();
    update_option( 'sane_setup', '1' );
  }

  function create_a_menu() {
    wp_create_nav_menu('main');
  }

  function disable_emojis() {
    update_option( 'use_smilies', 0 );
  }

  function update_permalinks() {
    update_option( 'selection','custom' );
    update_option( 'permalink_structure','/%category%/%postname%' );
  }

  function set_start_of_week() {
     update_option( 'start_of_week', 1 );
  }

  function sensible_category() {
    // Get rid of 'Uncategorised' category and replace with 'Blog' as default
      wp_update_term(1, 'category', array(
        'name' => 'News',
        'slug' => 'news',
        'description' => 'News'
      ));
  }

  function create_home_page() {
    // Create a 'Home' page if it doesn't exist, and 'run once' a whole bunch of settings
    if(get_page_by_title('Home')) {
    }
    else {
      global $wpdb;
    // First Page
      $first_page = get_site_option( 'first_page');
      $first_post_guid = get_option('home') . '/?page_id=1';
      $wpdb->insert( $wpdb->posts, array(
       'post_content' => '',
       'post_excerpt' => '',
       'post_title' => __( 'Home' ),
       /* translators: Default page slug */
       'post_name' => __( 'home' ),
       'guid' => $first_post_guid,
       'post_type' => 'page',
       'to_ping' => '',
       'pinged' => '',
       'comment_status' => 'closed',
       'post_content_filtered' => ''
       ));

      $wpdb->insert( $wpdb->postmeta, array( 'post_id' => 1, 'meta_key' => '_wp_page_template', 'meta_value' => 'default' ) );

    }
  }

  function create_list() {

    // Create a default 'List' if one doesn't exist using the sensible_category from above.
    $lists = get_posts(array('post_type' => 'list'));
    if(count($lists) >= 1) {
      return;
    }
    // If list not registered post type then return.
    $types = get_post_types();
    if(!in_array('list', $types)){
      $this->show_notice('Sane setup could not create a List because Custom Post Type of List is not registered. Is the Croissant theme activated yet?', 'updated error');
      return;
    }

    global $wpdb;
    $first_list_guid = get_option('home') . '/?post_type=list&p='.time();
    $wpdb->insert( $wpdb->posts, array(
      'post_content' => '',
      'post_excerpt' => '',
      'post_title' => __( 'Most Recent' ),
      /* translators: Default page slug */
      'post_name' => __( 'most-recent' ),
      'guid' => $first_list_guid,
      'post_type' => 'list',
      'to_ping' => '',
      'pinged' => '',
      'comment_status' => 'closed',
      'post_content_filtered' => ''
    ));

    $list = get_posts(array('post_type' => 'list'));
    $id = $list[0]->ID;

    $wpdb->insert( $wpdb->postmeta, array(
      'post_id' => $id,
      'meta_key' => 'limit',
      'meta_value' => 50,
    ));
    $wpdb->insert( $wpdb->postmeta, array(
      'post_id' => $id,
      'meta_key' => '_limit',
      'meta_value' => 'list_limit',
    ));

  }

  function set_front_page() {
    // Setup Theme to use a static front page
    $about = get_page_by_title( 'Home' );
    update_option( 'page_on_front', $about->ID );
    update_option( 'show_on_front', 'page' );
  }

  function delete_sample_page() {
    // Find and delete the WP default 'Sample Page'
    $default_page = get_page_by_title( 'Sample Page' );
    if ($default_page) {
      wp_delete_post( $default_page->ID );
    }
  }

  function delete_hello_world() {
    // Find and delete the WP default post 'Hello world!'
    $post = get_page_by_path('hello-world',OBJECT,'post');
    if ($post){
      wp_delete_post($post->ID,true);
    }
  }

  function show_notice( $text, $class = 'updated' ) {
    add_action( 'admin_notices', function() use ( $text, $class ) {
        echo '<div class="'.$class.'"><p>'.$text.'</p></div>';
      }, 1 );
  }


}

new SaneSetup();
?>
