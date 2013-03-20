<?php
/**
 * @file
 * kurogo_tour.ds.inc
 */

/**
 * Implements hook_ds_field_settings_info().
 */
function kurogo_tour_ds_field_settings_info() {
  $export = array();

  $ds_fieldsetting = new stdClass();
  $ds_fieldsetting->api_version = 1;
  $ds_fieldsetting->id = 'node|lens_descriptions|default';
  $ds_fieldsetting->entity_type = 'node';
  $ds_fieldsetting->bundle = 'lens_descriptions';
  $ds_fieldsetting->view_mode = 'default';
  $ds_fieldsetting->settings = array(
    'field_insideout_desc' => array(
      'ft' => array(
        'styles' => 'field-inline-value',
      ),
    ),
    'field_fastfacts_desc' => array(
      'ft' => array(
        'styles' => 'field-inline-value',
      ),
    ),
    'field_fastfacts_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_history_desc' => array(
      'ft' => array(
        'styles' => 'field-inline-value',
      ),
    ),
    'field_history_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_innovation_desc' => array(
      'ft' => array(
        'styles' => 'field-inline-value',
      ),
    ),
    'field_innovation_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_insideout_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_info_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_info_desc' => array(
      'ft' => array(
        'styles' => 'field-inline-value',
      ),
    ),
  );
  $export['node|lens_descriptions|default'] = $ds_fieldsetting;

  $ds_fieldsetting = new stdClass();
  $ds_fieldsetting->api_version = 1;
  $ds_fieldsetting->id = 'node|tour_stop|default';
  $ds_fieldsetting->entity_type = 'node';
  $ds_fieldsetting->bundle = 'tour_stop';
  $ds_fieldsetting->view_mode = 'default';
  $ds_fieldsetting->settings = array(
    'title' => array(
      'weight' => '0',
      'label' => 'hidden',
      'format' => 'default',
    ),
  );
  $export['node|tour_stop|default'] = $ds_fieldsetting;

  $ds_fieldsetting = new stdClass();
  $ds_fieldsetting->api_version = 1;
  $ds_fieldsetting->id = 'node|tour_stop|teaser';
  $ds_fieldsetting->entity_type = 'node';
  $ds_fieldsetting->bundle = 'tour_stop';
  $ds_fieldsetting->view_mode = 'teaser';
  $ds_fieldsetting->settings = array(
    'title' => array(
      'weight' => '0',
      'label' => 'hidden',
      'format' => 'default',
      'formatter_settings' => array(
        'link' => '1',
        'wrapper' => 'h2',
        'class' => '',
      ),
    ),
    'field_approach_thumbnail' => array(
      'ft' => array(
        'func' => 'theme_ds_field_expert',
        'ow' => TRUE,
        'ow-el' => 'div',
        'ow-cl' => 'field-name-field-approach-thumbnail',
        'fis' => TRUE,
        'fis-el' => 'div',
        'fis-cl' => 'field-items',
        'fi' => TRUE,
        'fi-el' => 'center',
        'fi-cl' => 'field-item',
      ),
    ),
    'field_location' => array(
      'ft' => array(
        'func' => 'theme_ds_field_expert',
        'ow' => TRUE,
        'ow-el' => 'div',
        'ow-cl' => 'field-name-field-location',
        'fis' => TRUE,
        'fis-el' => 'div',
        'fis-cl' => 'field-items',
        'fi' => TRUE,
        'fi-el' => 'center',
        'fi-cl' => 'field-item',
      ),
    ),
  );
  $export['node|tour_stop|teaser'] = $ds_fieldsetting;

  $ds_fieldsetting = new stdClass();
  $ds_fieldsetting->api_version = 1;
  $ds_fieldsetting->id = 'node|tour|default';
  $ds_fieldsetting->entity_type = 'node';
  $ds_fieldsetting->bundle = 'tour';
  $ds_fieldsetting->view_mode = 'default';
  $ds_fieldsetting->settings = array(
    'title' => array(
      'weight' => '0',
      'label' => 'hidden',
      'format' => 'default',
    ),
  );
  $export['node|tour|default'] = $ds_fieldsetting;

  return $export;
}

/**
 * Implements hook_ds_layout_settings_info().
 */
function kurogo_tour_ds_layout_settings_info() {
  $export = array();

  $ds_layout = new stdClass();
  $ds_layout->api_version = 1;
  $ds_layout->id = 'node|lens_content_photo_text|default';
  $ds_layout->entity_type = 'node';
  $ds_layout->bundle = 'lens_content_photo_text';
  $ds_layout->view_mode = 'default';
  $ds_layout->layout = 'ds_1col';
  $ds_layout->settings = array(
    'hide_empty_regions' => 0,
    'regions' => array(
      'ds_content' => array(
        0 => 'field_photo',
        1 => 'field_text',
        2 => 'field_internal_notes',
      ),
    ),
    'fields' => array(
      'field_photo' => 'ds_content',
      'field_text' => 'ds_content',
      'field_internal_notes' => 'ds_content',
    ),
    'classes' => array(),
  );
  $export['node|lens_content_photo_text|default'] = $ds_layout;

  $ds_layout = new stdClass();
  $ds_layout->api_version = 1;
  $ds_layout->id = 'node|lens_content_slideshow|default';
  $ds_layout->entity_type = 'node';
  $ds_layout->bundle = 'lens_content_slideshow';
  $ds_layout->view_mode = 'default';
  $ds_layout->layout = 'ds_1col';
  $ds_layout->settings = array(
    'hide_empty_regions' => 0,
    'regions' => array(
      'ds_content' => array(
        0 => 'field_photos',
        1 => 'field_internal_notes',
      ),
    ),
    'fields' => array(
      'field_photos' => 'ds_content',
      'field_internal_notes' => 'ds_content',
    ),
    'classes' => array(),
  );
  $export['node|lens_content_slideshow|default'] = $ds_layout;

  $ds_layout = new stdClass();
  $ds_layout->api_version = 1;
  $ds_layout->id = 'node|lens_content_video|default';
  $ds_layout->entity_type = 'node';
  $ds_layout->bundle = 'lens_content_video';
  $ds_layout->view_mode = 'default';
  $ds_layout->layout = 'ds_1col';
  $ds_layout->settings = array(
    'hide_empty_regions' => 0,
    'regions' => array(
      'ds_content' => array(
        0 => 'field_mpeg4',
        1 => 'field_youtube',
        2 => 'field_caption',
        3 => 'field_text',
      ),
    ),
    'fields' => array(
      'field_mpeg4' => 'ds_content',
      'field_youtube' => 'ds_content',
      'field_caption' => 'ds_content',
      'field_text' => 'ds_content',
    ),
    'classes' => array(),
  );
  $export['node|lens_content_video|default'] = $ds_layout;

  $ds_layout = new stdClass();
  $ds_layout->api_version = 1;
  $ds_layout->id = 'node|lens_descriptions|default';
  $ds_layout->entity_type = 'node';
  $ds_layout->bundle = 'lens_descriptions';
  $ds_layout->view_mode = 'default';
  $ds_layout->layout = 'ds_3col_stacked_fluid';
  $ds_layout->settings = array(
    'hide_empty_regions' => 1,
    'hide_sidebars' => 0,
    'regions' => array(
      'left' => array(
        0 => 'field_info_name',
        1 => 'field_insideout_name',
        2 => 'field_fastfacts_name',
        3 => 'field_innovation_name',
        4 => 'field_history_name',
      ),
      'middle' => array(
        0 => 'field_info_desc',
        1 => 'field_insideout_desc',
        2 => 'field_fastfacts_desc',
        3 => 'field_innovation_desc',
        4 => 'field_history_desc',
      ),
      'footer' => array(
        0 => 'field_internal_notes',
      ),
    ),
    'fields' => array(
      'field_info_name' => 'left',
      'field_insideout_name' => 'left',
      'field_fastfacts_name' => 'left',
      'field_innovation_name' => 'left',
      'field_history_name' => 'left',
      'field_info_desc' => 'middle',
      'field_insideout_desc' => 'middle',
      'field_fastfacts_desc' => 'middle',
      'field_innovation_desc' => 'middle',
      'field_history_desc' => 'middle',
      'field_internal_notes' => 'footer',
    ),
    'classes' => array(),
  );
  $export['node|lens_descriptions|default'] = $ds_layout;

  $ds_layout = new stdClass();
  $ds_layout->api_version = 1;
  $ds_layout->id = 'node|tour_stop|default';
  $ds_layout->entity_type = 'node';
  $ds_layout->bundle = 'tour_stop';
  $ds_layout->view_mode = 'default';
  $ds_layout->layout = 'ds_1col';
  $ds_layout->settings = array(
    'hide_empty_regions' => 0,
    'regions' => array(
      'ds_content' => array(
        0 => 'group_panes',
        1 => 'field_internal_notes',
        2 => 'field_subtitle',
        3 => 'field_location',
        4 => 'field_building',
        5 => 'field_approach_photo',
        6 => 'field_history',
        7 => 'field_approach_thumbnail',
        8 => 'field_innovation',
        9 => 'field_photo',
        10 => 'field_text',
        11 => 'field_insideout',
        12 => 'field_fastfacts',
        13 => 'group_details',
        14 => 'group_info',
        15 => 'group_insideout',
        16 => 'group_fastfacts',
        17 => 'group_innovation',
        18 => 'group_history',
      ),
    ),
    'fields' => array(
      'group_panes' => 'ds_content',
      'field_internal_notes' => 'ds_content',
      'field_subtitle' => 'ds_content',
      'field_location' => 'ds_content',
      'field_building' => 'ds_content',
      'field_approach_photo' => 'ds_content',
      'field_history' => 'ds_content',
      'field_approach_thumbnail' => 'ds_content',
      'field_innovation' => 'ds_content',
      'field_photo' => 'ds_content',
      'field_text' => 'ds_content',
      'field_insideout' => 'ds_content',
      'field_fastfacts' => 'ds_content',
      'group_details' => 'ds_content',
      'group_info' => 'ds_content',
      'group_insideout' => 'ds_content',
      'group_fastfacts' => 'ds_content',
      'group_innovation' => 'ds_content',
      'group_history' => 'ds_content',
    ),
    'classes' => array(),
  );
  $export['node|tour_stop|default'] = $ds_layout;

  $ds_layout = new stdClass();
  $ds_layout->api_version = 1;
  $ds_layout->id = 'node|tour_stop|teaser';
  $ds_layout->entity_type = 'node';
  $ds_layout->bundle = 'tour_stop';
  $ds_layout->view_mode = 'teaser';
  $ds_layout->layout = 'ds_3col_stacked_fluid';
  $ds_layout->settings = array(
    'hide_empty_regions' => 1,
    'regions' => array(
      'middle' => array(
        0 => 'title',
        1 => 'field_subtitle',
      ),
      'left' => array(
        0 => 'field_approach_thumbnail',
      ),
      'right' => array(
        0 => 'field_location',
      ),
    ),
    'fields' => array(
      'title' => 'middle',
      'field_approach_thumbnail' => 'left',
      'field_subtitle' => 'middle',
      'field_location' => 'right',
    ),
    'classes' => array(),
  );
  $export['node|tour_stop|teaser'] = $ds_layout;

  $ds_layout = new stdClass();
  $ds_layout->api_version = 1;
  $ds_layout->id = 'node|tour|default';
  $ds_layout->entity_type = 'node';
  $ds_layout->bundle = 'tour';
  $ds_layout->view_mode = 'default';
  $ds_layout->layout = 'ds_1col';
  $ds_layout->settings = array(
    'hide_empty_regions' => 0,
    'hide_sidebars' => 0,
    'regions' => array(
      'ds_content' => array(
        0 => 'title',
        1 => 'field_stops',
        2 => 'group_tourtabs',
        3 => 'field_internal_notes',
        4 => 'group_stops',
        5 => 'group_welcome',
        6 => 'group_finish',
        7 => 'group_help',
        8 => 'field_welcome',
        9 => 'group_stop_detail_legend',
        10 => 'field_welcome_lenses',
        11 => 'field_welcome_footer',
        12 => 'field_finish',
        13 => 'field_finish_links',
        14 => 'field_help',
        15 => 'field_finish_footer',
        16 => 'field_help_lenses',
        17 => 'field_stop_legend_lenses',
        18 => 'field_help_middle',
        19 => 'field_help_links',
        20 => 'field_help_middle_2',
        21 => 'field_help_links_2',
        22 => 'field_help_footer',
      ),
    ),
    'fields' => array(
      'title' => 'ds_content',
      'field_stops' => 'ds_content',
      'group_tourtabs' => 'ds_content',
      'field_internal_notes' => 'ds_content',
      'group_stops' => 'ds_content',
      'group_welcome' => 'ds_content',
      'group_finish' => 'ds_content',
      'group_help' => 'ds_content',
      'field_welcome' => 'ds_content',
      'group_stop_detail_legend' => 'ds_content',
      'field_welcome_lenses' => 'ds_content',
      'field_welcome_footer' => 'ds_content',
      'field_finish' => 'ds_content',
      'field_finish_links' => 'ds_content',
      'field_help' => 'ds_content',
      'field_finish_footer' => 'ds_content',
      'field_help_lenses' => 'ds_content',
      'field_stop_legend_lenses' => 'ds_content',
      'field_help_middle' => 'ds_content',
      'field_help_links' => 'ds_content',
      'field_help_middle_2' => 'ds_content',
      'field_help_links_2' => 'ds_content',
      'field_help_footer' => 'ds_content',
    ),
    'classes' => array(),
  );
  $export['node|tour|default'] = $ds_layout;

  return $export;
}
