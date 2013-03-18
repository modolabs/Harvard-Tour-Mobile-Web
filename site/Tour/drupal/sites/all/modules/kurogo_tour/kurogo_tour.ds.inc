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
    'links' => array(
      'weight' => '0',
      'label' => 'hidden',
      'format' => 'default',
    ),
    'field_insideout_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_fastfacts_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_innovation_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
    'field_history_name' => array(
      'ft' => array(
        'styles' => 'field-inline-label',
      ),
    ),
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
    'field_innovation_desc' => array(
      'ft' => array(
        'styles' => 'field-inline-value',
      ),
    ),
    'field_history_desc' => array(
      'ft' => array(
        'styles' => 'field-inline-value',
      ),
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
  $ds_layout->layout = 'ds_1col';
  $ds_layout->settings = array(
    'hide_empty_regions' => 0,
    'regions' => array(
      'ds_content' => array(
        0 => 'group_info',
        1 => 'group_insideout',
        2 => 'group_innovation',
        3 => 'field_fastfacts_name',
        4 => 'group_fastfacts',
        5 => 'field_fastfacts_desc',
        6 => 'group_history',
        7 => 'field_history_name',
        8 => 'field_internal_notes',
        9 => 'field_history_desc',
        10 => 'field_innovation_name',
        11 => 'field_insideout_name',
        12 => 'field_innovation_desc',
        13 => 'field_insideout_desc',
        14 => 'field_info_name',
        15 => 'field_info_desc',
      ),
    ),
    'fields' => array(
      'group_info' => 'ds_content',
      'group_insideout' => 'ds_content',
      'group_innovation' => 'ds_content',
      'field_fastfacts_name' => 'ds_content',
      'group_fastfacts' => 'ds_content',
      'field_fastfacts_desc' => 'ds_content',
      'group_history' => 'ds_content',
      'field_history_name' => 'ds_content',
      'field_internal_notes' => 'ds_content',
      'field_history_desc' => 'ds_content',
      'field_innovation_name' => 'ds_content',
      'field_insideout_name' => 'ds_content',
      'field_innovation_desc' => 'ds_content',
      'field_insideout_desc' => 'ds_content',
      'field_info_name' => 'ds_content',
      'field_info_desc' => 'ds_content',
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
    'regions' => array(
      'ds_content' => array(
        0 => 'group_tourtabs',
        1 => 'field_stops',
        2 => 'field_internal_notes',
        3 => 'group_stops',
        4 => 'group_welcome',
        5 => 'group_finish',
        6 => 'group_help',
        7 => 'group_stop_detail_legend',
        8 => 'field_welcome',
        9 => 'field_welcome_lenses',
        10 => 'field_welcome_footer',
        11 => 'field_finish',
        12 => 'field_finish_links',
        13 => 'field_help',
        14 => 'field_finish_footer',
        15 => 'field_help_lenses',
        16 => 'field_stop_legend_lenses',
        17 => 'field_help_middle',
        18 => 'field_help_links',
        19 => 'field_help_middle_2',
        20 => 'field_help_links_2',
        21 => 'field_help_footer',
      ),
    ),
    'fields' => array(
      'group_tourtabs' => 'ds_content',
      'field_stops' => 'ds_content',
      'field_internal_notes' => 'ds_content',
      'group_stops' => 'ds_content',
      'group_welcome' => 'ds_content',
      'group_finish' => 'ds_content',
      'group_help' => 'ds_content',
      'group_stop_detail_legend' => 'ds_content',
      'field_welcome' => 'ds_content',
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
