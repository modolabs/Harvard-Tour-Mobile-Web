<?php
/**
 * @file
 * kurogo_tour.features.menu_links.inc
 */

/**
 * Implements hook_menu_default_menu_links().
 */
function kurogo_tour_menu_default_menu_links() {
  $menu_links = array();

  // Exported menu link: main-menu:admin/content
  $menu_links['main-menu:admin/content'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'admin/content',
    'router_path' => 'admin/content',
    'link_title' => 'All Content',
    'options' => array(
      'attributes' => array(
        'title' => '',
      ),
    ),
    'module' => 'menu',
    'hidden' => '0',
    'external' => '0',
    'has_children' => '0',
    'expanded' => '0',
    'weight' => '-49',
  );
  // Exported menu link: main-menu:node/add/lens-content-photo-text
  $menu_links['main-menu:node/add/lens-content-photo-text'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'node/add/lens-content-photo-text',
    'router_path' => 'node/add/lens-content-photo-text',
    'link_title' => 'Add Tab (Photo & Text)',
    'options' => array(
      'attributes' => array(
        'title' => '',
      ),
    ),
    'module' => 'menu',
    'hidden' => '0',
    'external' => '0',
    'has_children' => '0',
    'expanded' => '0',
    'weight' => '-47',
  );
  // Exported menu link: main-menu:node/add/lens-content-slideshow
  $menu_links['main-menu:node/add/lens-content-slideshow'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'node/add/lens-content-slideshow',
    'router_path' => 'node/add/lens-content-slideshow',
    'link_title' => 'Add Tab (Slideshow)',
    'options' => array(
      'attributes' => array(
        'title' => '',
      ),
    ),
    'module' => 'menu',
    'hidden' => '0',
    'external' => '0',
    'has_children' => '0',
    'expanded' => '0',
    'weight' => '-46',
  );
  // Exported menu link: main-menu:node/add/lens-content-video
  $menu_links['main-menu:node/add/lens-content-video'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'node/add/lens-content-video',
    'router_path' => 'node/add/lens-content-video',
    'link_title' => 'Add Tab (Video)',
    'options' => array(
      'attributes' => array(
        'title' => '',
      ),
    ),
    'module' => 'menu',
    'hidden' => '0',
    'external' => '0',
    'has_children' => '0',
    'expanded' => '0',
    'weight' => '-45',
  );
  // Exported menu link: main-menu:node/add/tour-stop
  $menu_links['main-menu:node/add/tour-stop'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'node/add/tour-stop',
    'router_path' => 'node/add/tour-stop',
    'link_title' => 'Add Stop',
    'options' => array(
      'attributes' => array(
        'title' => '',
      ),
    ),
    'module' => 'menu',
    'hidden' => '0',
    'external' => '0',
    'has_children' => '0',
    'expanded' => '0',
    'weight' => '-48',
  );
  // Translatables
  // Included for use with string extractors like potx.
  t('Add Stop');
  t('Add Tab (Photo & Text)');
  t('Add Tab (Slideshow)');
  t('Add Tab (Video)');
  t('All Content');


  return $menu_links;
}
