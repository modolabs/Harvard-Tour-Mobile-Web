<?php
/**
 * @file
 * kurogo_tour.features.user_role.inc
 */

/**
 * Implements hook_user_default_roles().
 */
function kurogo_tour_user_default_roles() {
  $roles = array();

  // Exported role: administrator.
  $roles['administrator'] = array(
    'name' => 'administrator',
    'weight' => '2',
  );

  // Exported role: content administrator.
  $roles['content administrator'] = array(
    'name' => 'content administrator',
    'weight' => '4',
  );

  // Exported role: content editor.
  $roles['content editor'] = array(
    'name' => 'content editor',
    'weight' => '3',
  );

  return $roles;
}
