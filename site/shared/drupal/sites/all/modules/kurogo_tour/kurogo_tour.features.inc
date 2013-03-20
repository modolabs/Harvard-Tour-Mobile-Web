<?php
/**
 * @file
 * kurogo_tour.features.inc
 */

/**
 * Implements hook_ctools_plugin_api().
 */
function kurogo_tour_ctools_plugin_api() {
  list($module, $api) = func_get_args();
  if ($module == "ds" && $api == "ds") {
    return array("version" => "1");
  }
  list($module, $api) = func_get_args();
  if ($module == "field_group" && $api == "field_group") {
    return array("version" => "1");
  }
  list($module, $api) = func_get_args();
  if ($module == "services" && $api == "services") {
    return array("version" => "3");
  }
  list($module, $api) = func_get_args();
  if ($module == "strongarm" && $api == "strongarm") {
    return array("version" => "1");
  }
  return array("version" => "3.0");
}

/**
 * Implements hook_node_info().
 */
function kurogo_tour_node_info() {
  $items = array(
    'lens_content_photo_text' => array(
      'name' => t('Tab Content - Photo & Text'),
      'base' => 'node_content',
      'description' => t('Tab content containing text and an optional photo.'),
      'has_title' => '1',
      'title_label' => t('Title (internal use only)'),
      'help' => '',
    ),
    'lens_content_slideshow' => array(
      'name' => t('Tab Content - Slideshow'),
      'base' => 'node_content',
      'description' => t('Tab content containing a slideshow of photos.'),
      'has_title' => '1',
      'title_label' => t('Title (internal use only)'),
      'help' => '',
    ),
    'lens_content_video' => array(
      'name' => t('Tab Content - Video'),
      'base' => 'node_content',
      'description' => t('Tab content containing a video and caption.'),
      'has_title' => '1',
      'title_label' => t('Title (internal use only)'),
      'help' => '',
    ),
    'lens_descriptions' => array(
      'name' => t('Tab Descriptions'),
      'base' => 'node_content',
      'description' => t('Tab names and descriptions used by the welcome screen and help page.'),
      'has_title' => '1',
      'title_label' => t('Title (internal)'),
      'help' => '',
    ),
    'tour' => array(
      'name' => t('Tour'),
      'base' => 'node_content',
      'description' => t('A tour of a location with ordered stops'),
      'has_title' => '1',
      'title_label' => t('Title (internal)'),
      'help' => '',
    ),
    'tour_stop' => array(
      'name' => t('Tour Stop'),
      'base' => 'node_content',
      'description' => t('A stop on a tour with a geographic location'),
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  return $items;
}
