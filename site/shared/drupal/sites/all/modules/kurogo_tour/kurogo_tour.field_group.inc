<?php
/**
 * @file
 * kurogo_tour.field_group.inc
 */

/**
 * Implements hook_field_group_info().
 */
function kurogo_tour_field_group_info() {
  $export = array();

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_approach|node|tour_stop|form';
  $field_group->group_name = 'group_approach';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Approach Photo',
    'weight' => '4',
    'children' => array(
      0 => 'field_approach_photo',
      1 => 'field_approach_thumbnail',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Approach Photo',
      'instance_settings' => array(
        'required_fields' => 1,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_approach|node|tour_stop|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_details|node|tour_stop|default';
  $field_group->group_name = 'group_details';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_panes';
  $field_group->data = array(
    'label' => 'Details',
    'weight' => '16',
    'children' => array(
      0 => 'field_approach_photo',
      1 => 'field_approach_thumbnail',
      2 => 'field_building',
      3 => 'field_location',
      4 => 'field_subtitle',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Details',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_details|node|tour_stop|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_fastfacts|node|lens_descriptions|default';
  $field_group->group_name = 'group_fastfacts';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'lens_descriptions';
  $field_group->mode = 'default';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type B',
    'weight' => '3',
    'children' => array(
      0 => 'field_fastfacts_desc',
      1 => 'field_fastfacts_name',
    ),
    'format_type' => 'div',
    'format_settings' => array(
      'label' => 'Tab Type B',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
        'effect' => 'none',
        'speed' => 'fast',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_fastfacts|node|lens_descriptions|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_fastfacts|node|tour_stop|default';
  $field_group->group_name = 'group_fastfacts';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_panes';
  $field_group->data = array(
    'label' => 'Tab Type B',
    'weight' => '19',
    'children' => array(
      0 => 'field_fastfacts',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Tab Type B',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_fastfacts|node|tour_stop|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_fastfacts|node|tour_stop|form';
  $field_group->group_name = 'group_fastfacts';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type B Content',
    'weight' => '7',
    'children' => array(
      0 => 'field_fastfacts',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Tab Type B Content',
      'instance_settings' => array(
        'required_fields' => 1,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_fastfacts|node|tour_stop|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_finish|node|tour|default';
  $field_group->group_name = 'group_finish';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Finish Page',
    'weight' => '8',
    'children' => array(
      0 => 'field_finish',
      1 => 'field_finish_footer',
      2 => 'field_finish_links',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'formatter' => 'closed',
      'instance_settings' => array(
        'classes' => '',
      ),
    ),
  );
  $export['group_finish|node|tour|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_finish|node|tour|form';
  $field_group->group_name = 'group_finish';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'form';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Finish Page',
    'weight' => '5',
    'children' => array(
      0 => 'field_finish',
      1 => 'field_finish_links',
      2 => 'field_finish_footer',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Finish Page',
      'instance_settings' => array(
        'required_fields' => 0,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_finish|node|tour|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_help|node|tour|default';
  $field_group->group_name = 'group_help';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Help Page',
    'weight' => '10',
    'children' => array(
      0 => 'field_help',
      1 => 'field_help_footer',
      2 => 'field_help_lenses',
      3 => 'field_help_links',
      4 => 'field_help_links_2',
      5 => 'field_help_middle',
      6 => 'field_help_middle_2',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'formatter' => 'closed',
      'instance_settings' => array(
        'classes' => '',
      ),
    ),
  );
  $export['group_help|node|tour|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_help|node|tour|form';
  $field_group->group_name = 'group_help';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'form';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Help Page',
    'weight' => '6',
    'children' => array(
      0 => 'field_help',
      1 => 'field_help_footer',
      2 => 'field_help_middle',
      3 => 'field_help_lenses',
      4 => 'field_help_links',
      5 => 'field_help_links_2',
      6 => 'field_help_middle_2',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Help Page',
      'instance_settings' => array(
        'required_fields' => 0,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_help|node|tour|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_history|node|lens_descriptions|default';
  $field_group->group_name = 'group_history';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'lens_descriptions';
  $field_group->mode = 'default';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type D',
    'weight' => '4',
    'children' => array(
      0 => 'field_history_desc',
      1 => 'field_history_name',
    ),
    'format_type' => 'div',
    'format_settings' => array(
      'label' => 'Tab Type D',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
        'effect' => 'none',
        'speed' => 'fast',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_history|node|lens_descriptions|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_history|node|tour_stop|default';
  $field_group->group_name = 'group_history';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_panes';
  $field_group->data = array(
    'label' => 'Tab Type D',
    'weight' => '21',
    'children' => array(
      0 => 'field_history',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Tab Type D',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_history|node|tour_stop|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_history|node|tour_stop|form';
  $field_group->group_name = 'group_history';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type D Content',
    'weight' => '9',
    'children' => array(
      0 => 'field_history',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Tab Type D Content',
      'instance_settings' => array(
        'required_fields' => 1,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_history|node|tour_stop|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_info|node|lens_descriptions|default';
  $field_group->group_name = 'group_info';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'lens_descriptions';
  $field_group->mode = 'default';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Info',
    'weight' => '0',
    'children' => array(
      0 => 'field_info_name',
      1 => 'field_info_desc',
    ),
    'format_type' => 'div',
    'format_settings' => array(
      'label' => 'Info',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
        'effect' => 'none',
        'speed' => 'fast',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_info|node|lens_descriptions|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_info|node|tour_stop|default';
  $field_group->group_name = 'group_info';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_panes';
  $field_group->data = array(
    'label' => 'Info Tab',
    'weight' => '17',
    'children' => array(
      0 => 'field_photo',
      1 => 'field_text',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Info Tab',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_info|node|tour_stop|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_info|node|tour_stop|form';
  $field_group->group_name = 'group_info';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Info Pane',
    'weight' => '5',
    'children' => array(
      0 => 'field_photo',
      1 => 'field_text',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Info Pane',
      'instance_settings' => array(
        'required_fields' => 1,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_info|node|tour_stop|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_innovation|node|lens_descriptions|default';
  $field_group->group_name = 'group_innovation';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'lens_descriptions';
  $field_group->mode = 'default';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type C',
    'weight' => '2',
    'children' => array(
      0 => 'field_innovation_desc',
      1 => 'field_innovation_name',
    ),
    'format_type' => 'div',
    'format_settings' => array(
      'label' => 'Tab Type C',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
        'effect' => 'none',
        'speed' => 'fast',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_innovation|node|lens_descriptions|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_innovation|node|tour_stop|default';
  $field_group->group_name = 'group_innovation';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_panes';
  $field_group->data = array(
    'label' => 'Tab Type C',
    'weight' => '20',
    'children' => array(
      0 => 'field_innovation',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Tab Type C',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_innovation|node|tour_stop|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_innovation|node|tour_stop|form';
  $field_group->group_name = 'group_innovation';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type C Content',
    'weight' => '8',
    'children' => array(
      0 => 'field_innovation',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Tab Type C Content',
      'instance_settings' => array(
        'required_fields' => 1,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_innovation|node|tour_stop|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_insideout|node|lens_descriptions|default';
  $field_group->group_name = 'group_insideout';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'lens_descriptions';
  $field_group->mode = 'default';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type A',
    'weight' => '1',
    'children' => array(
      0 => 'field_insideout_desc',
      1 => 'field_insideout_name',
    ),
    'format_type' => 'div',
    'format_settings' => array(
      'label' => 'Tab Type A',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
        'effect' => 'none',
        'speed' => 'fast',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_insideout|node|lens_descriptions|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_insideout|node|tour_stop|default';
  $field_group->group_name = 'group_insideout';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_panes';
  $field_group->data = array(
    'label' => 'Tab Type A',
    'weight' => '18',
    'children' => array(
      0 => 'field_insideout',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Tab Type A',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_insideout|node|tour_stop|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_insideout|node|tour_stop|form';
  $field_group->group_name = 'group_insideout';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tab Type A Content',
    'weight' => '6',
    'children' => array(
      0 => 'field_insideout',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Tab Type A Content',
      'instance_settings' => array(
        'required_fields' => 1,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_insideout|node|tour_stop|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_lens_names|node|tour|form';
  $field_group->group_name = 'group_lens_names';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'form';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Stop Detail Legend',
    'weight' => '7',
    'children' => array(
      0 => 'field_stop_legend_lenses',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Stop Detail Legend',
      'instance_settings' => array(
        'required_fields' => 0,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_lens_names|node|tour|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_panes|node|tour_stop|default';
  $field_group->group_name = 'group_panes';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour_stop';
  $field_group->mode = 'default';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tabs',
    'weight' => '0',
    'children' => array(
      0 => 'group_fastfacts',
      1 => 'group_history',
      2 => 'group_info',
      3 => 'group_innovation',
      4 => 'group_insideout',
      5 => 'group_details',
    ),
    'format_type' => 'htabs',
    'format_settings' => array(
      'formatter' => '',
      'instance_settings' => array(
        'classes' => '',
      ),
    ),
  );
  $export['group_panes|node|tour_stop|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_stop_detail_legend|node|tour|default';
  $field_group->group_name = 'group_stop_detail_legend';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Stop Detail Legend',
    'weight' => '11',
    'children' => array(
      0 => 'field_stop_legend_lenses',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'formatter' => 'closed',
      'instance_settings' => array(
        'classes' => '',
      ),
    ),
  );
  $export['group_stop_detail_legend|node|tour|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_stops|node|tour|default';
  $field_group->group_name = 'group_stops';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Tour Stops',
    'weight' => '6',
    'children' => array(
      0 => 'field_stops',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Tour Stops',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_stops|node|tour|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_stops|node|tour|form';
  $field_group->group_name = 'group_stops';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'form';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Tour Stops',
    'weight' => '3',
    'children' => array(
      0 => 'field_stops',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Tour Stops',
      'instance_settings' => array(
        'required_fields' => 0,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $export['group_stops|node|tour|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_tourtabs|node|tour|default';
  $field_group->group_name = 'group_tourtabs';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'default';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tabs',
    'weight' => '1',
    'children' => array(
      0 => 'group_finish',
      1 => 'group_help',
      2 => 'group_stop_detail_legend',
      3 => 'group_stops',
      4 => 'group_welcome',
    ),
    'format_type' => 'htabs',
    'format_settings' => array(
      'formatter' => '',
      'instance_settings' => array(
        'classes' => '',
      ),
    ),
  );
  $export['group_tourtabs|node|tour|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_tourtabs|node|tour|form';
  $field_group->group_name = 'group_tourtabs';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Tabs',
    'weight' => '1',
    'children' => array(
      0 => 'group_welcome',
      1 => 'group_stops',
      2 => 'group_finish',
      3 => 'group_help',
      4 => 'group_lens_names',
    ),
    'format_type' => 'htabs',
    'format_settings' => array(
      'formatter' => '',
      'instance_settings' => array(
        'classes' => '',
      ),
    ),
  );
  $export['group_tourtabs|node|tour|form'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_welcome|node|tour|default';
  $field_group->group_name = 'group_welcome';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'default';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Welcome Page',
    'weight' => '7',
    'children' => array(
      0 => 'field_welcome',
      1 => 'field_welcome_footer',
      2 => 'field_welcome_lenses',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Welcome Page',
      'instance_settings' => array(
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_welcome|node|tour|default'] = $field_group;

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_welcome|node|tour|form';
  $field_group->group_name = 'group_welcome';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'tour';
  $field_group->mode = 'form';
  $field_group->parent_name = 'group_tourtabs';
  $field_group->data = array(
    'label' => 'Welcome Page',
    'weight' => '4',
    'children' => array(
      0 => 'field_welcome',
      1 => 'field_welcome_footer',
      2 => 'field_welcome_lenses',
    ),
    'format_type' => 'htab',
    'format_settings' => array(
      'label' => 'Welcome Page',
      'instance_settings' => array(
        'required_fields' => 0,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'closed',
    ),
  );
  $export['group_welcome|node|tour|form'] = $field_group;

  return $export;
}
