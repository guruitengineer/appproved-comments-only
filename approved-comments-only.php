<?php
/*
Plugin Name: Approved Comments Only
Plugin URI: https://wordpress.org/plugins/approved-comments-only/
Description: With this plugin you can restrict your users to view the unapproved comments in dashboard of multi-user site. Even you can restrict administrator and editor also. So, only the approved comments will be visible in the dashboard.
Version: 1.2
Author: Gurmeet Singh
Author URI: http://guruitengineer.in/
License: GPLv2+
*/
if (!defined('ABSPATH')) die();

class Approved_Comments_Only{

  function __construct() {
    add_action('admin_menu', array( $this, 'aco_add_menu' ));
    add_action('plugins_loaded', array( $this, 'aco_get_user_info' ));
    register_activation_hook( __FILE__, array( $this, 'aco_install' ) );
    register_deactivation_hook( __FILE__, array( $this, 'aco_uninstall' ) ); 
  }

  /*
  * Actions perform at loading of admin menu
  */
  function aco_add_menu() {

    add_menu_page( 'Approved Comments Only', 'Approved Comments Only', 'manage_options', 'aco_setting_page', 
      array(__CLASS__,'aco_setting_page'));
    add_action( 'admin_init', array($this,'aco_register_settings' ));
  }

  /*
  * Actions perform on loading of menu pages
  */
  function aco_setting_page()
  {
    require_once("includes/aco-custom-settings.php");
  }

  /*
  * Actions to get the user information and perform task as per the user's role.
  */
  public function aco_get_user_info()
  {
    $user = wp_get_current_user();
    $aco_user_level=(array)get_option('aco_user_level');

    if(in_array($user->roles[0],$aco_user_level))
    {
      add_filter('the_comments', array( $this, 'aco_filtered_comments' ));
      add_filter('comments_per_page', array( $this, 'aco_hide_default_pagination'));
      add_filter('manage_comments_nav', array( $this, 'aco_add_custom_pagination' ));
      add_filter( 'comment_status_links', array($this,'aco_hide_comment_status_links') );
      wp_enqueue_style( 'approved-comments-only', plugins_url().'/approved-comments-only/assets/css/approved-comments-only.css');
    }
  }

  /*
  * Actions to get filter comments.
  */
  function aco_filtered_comments($comments)
  {
    
    $published_comment=array();
    $current_user_id=(int)get_current_user_id();  
    $enable=get_option('aco_user_own_comments');
  
    if(is_admin())
    {
      foreach ($comments as $comment) {
      switch ($enable) {
        case '1':
        if($comment->comment_approved=='1' && $comment->user_id==$current_user_id)
          array_push($published_comment,$comment);
        break;  
        default:
        if($comment->comment_approved=='1')
          array_push($published_comment,$comment);
        break;
        }     
      } 
    }
    else
    {
      $published_comment=$comments;
    } 
    return $published_comment;
  }
  /*
  * Actions to hide the default pagination.
  */
  function aco_hide_default_pagination($comments_per_page)
  {
    return $comments_per_page;
  }
  /*
  * Actions to add custom pagination.
  */
  function aco_add_custom_pagination($view)
  {
    $published_comment_count=count(get_comments());

    // if ( empty( $this->_pagination_args ) ) {
    //   return;
    // }
    
    $total_items = $published_comment_count;
    global $wp_list_table;
    
    $comments_per_page=(int)get_user_option('edit_comments_per_page');
    if(empty($comments_per_page))
      $comments_per_page=20;
    $total_pages = ceil($total_items/$comments_per_page);

    $infinite_scroll = false;
    if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
      $infinite_scroll = $this->_pagination_args['infinite_scroll'];
    }

    if ( 'top' === $which && $total_pages > 1 ) {
      $this->screen->render_screen_reader_content( 'heading_pagination' );
    }

     $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

     $current = $wp_list_table->get_pagenum();
     $removable_query_args = wp_removable_query_args();

     $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

     $current_url = remove_query_arg( $removable_query_args, $current_url );
     $page_links = array();

     $total_pages_before = '<span class="paging-input">';
     $total_pages_after  = '</span></span>';

     $disable_first = $disable_last = $disable_prev = $disable_next = false;

    if ( $current == 1 ) {
      $disable_first = true;
      $disable_prev = true;
    }
    if ( $current == 2 ) {
      $disable_first = true;
    }
    if ( $current == $total_pages ) {
      $disable_last = true;
      $disable_next = true;
    }
    if ( $current == $total_pages - 1 ) {
      $disable_last = true;
    }

    if ( $disable_first ) {
      $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
    } else {
      $page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
        esc_url( remove_query_arg( 'paged', $current_url ) ),
        __( 'First page' ),
        '&laquo;'
      );
    }

    if ( $disable_prev ) {
      $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
    } else {
      $page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
        esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
        __( 'Previous page' ),
        '&lsaquo;'
      );
    }

    if ( 'bottom' === $which ) {
      $html_current_page  = $current;
      $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
    } else {
      $html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
        '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
        $current,
        strlen( $total_pages )
      );
    }
    $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
    $page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

    if ( $disable_next ) {
      $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
    } else {
      $page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
        esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
        __( 'Next page' ),
        '&rsaquo;'
      );
    }

    if ( $disable_last ) {
      $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
    } else {
      $page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
        esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
        __( 'Last page' ),
        '&raquo;'
      );
    }

    $pagination_links_class = 'pagination-links';
    if ( ! empty( $infinite_scroll ) ) {
      $pagination_links_class = ' hide-if-js';
    }
    $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

    if ( $total_pages ) {
      $page_class = $total_pages < 2 ? ' one-page' : '';
    } else {
      $page_class = ' no-pages';
    }
    $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

    echo $this->_pagination;
  }
  /*
  * Actions to hide comment status links All/Pending/Approved/Spam/Trash
  */
  function aco_hide_comment_status_links()
  {
    return $null;
  }

  /*
  * Register the settings
  */
  function aco_register_settings() {
      register_setting(
      'aco_options',  // settings section
      'aco_user_level' // setting name
    );
      register_setting(
      'aco_options',  // settings section
      'aco_user_own_comments' // setting name
    );
  }

  /*
  * Actions perform on activation of plugin
  */
  function aco_install() {



  }
  
  /*
  * Actions perform on de-activation of plugin
  */
  function aco_uninstall() {



  }
}
new Approved_Comments_Only();
?>