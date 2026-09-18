<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'moeve_crewing.civix.php';
// phpcs:enable

use CRM_MoeveCrewing_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function moeve_crewing_civicrm_config(\CRM_Core_Config $config): void {
  _moeve_crewing_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function moeve_crewing_civicrm_install(): void {
  _moeve_crewing_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function moeve_crewing_civicrm_enable(): void {
  _moeve_crewing_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @param array<int|string, mixed> $menu
 */
function moeve_crewing_civicrm_navigationMenu(array &$menu): void {
  _moeve_crewing_civix_insert_navigation_menu($menu, 'Events', [
    'label' => E::ts('Möwe Crewing'),
    'name' => 'moeve_crewing_overview',
    'url' => 'civicrm/moeve-crewing?reset=1&view=year',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  _moeve_crewing_civix_insert_navigation_menu($menu, 'Administer', [
    'label' => E::ts('Möwe Crewing einrichten'),
    'name' => 'moeve_crewing_setup',
    'url' => 'civicrm/admin/moeve-crewing/setup?reset=1',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  _moeve_crewing_civix_navigationMenu($menu);
}
