<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Setup;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\MoeveCrewing\Configuration\DefaultConfiguration;

/**
 * Creates the recommended configuration without modifying existing records.
 */
final class RecommendedConfigurationInstaller {

  /**
   * @return array<int, string>
   */
  public static function install(): array {
    $messages = [];
    $roles = self::getRoleMapping();

    $optionGroupName = self::setting(
      'moeve_crewing_role_option_group',
      DefaultConfiguration::ROLE_OPTION_GROUP
    );

    $optionGroup = OptionGroup::get(FALSE)
      ->addSelect('id', 'name')
      ->addWhere('name', '=', $optionGroupName)
      ->execute()
      ->first();

    if (!$optionGroup) {
      throw new \RuntimeException(sprintf(
        'Required role option group "%s" was not found.',
        $optionGroupName
      ));
    }

    $optionGroupId = (int) $optionGroup['id'];
    self::ensureRoleOptions($optionGroupId, $roles, $messages);

    $individualGroupId = self::ensureCustomGroup(
      self::setting(
        'moeve_crewing_individual_group',
        DefaultConfiguration::INDIVIDUAL_GROUP
      ),
      'Crewing – Fähigkeiten',
      'Individual',
      $messages
    );

    self::ensureCustomField(
      $individualGroupId,
      self::setting(
        'moeve_crewing_capabilities_field',
        DefaultConfiguration::CAPABILITIES_FIELD
      ),
      'Meine Fähigkeiten',
      'Int',
      'CheckBox',
      TRUE,
      $messages,
      $optionGroupId,
      1,
      10
    );

    $participantGroupId = self::ensureCustomGroup(
      self::setting(
        'moeve_crewing_participant_group',
        DefaultConfiguration::PARTICIPANT_GROUP
      ),
      'Crewing – gewünschte Funktionen',
      'Participant',
      $messages
    );

    self::ensureCustomField(
      $participantGroupId,
      self::setting(
        'moeve_crewing_preferences_field',
        DefaultConfiguration::PREFERENCES_FIELD
      ),
      'Gewünschte Funktionen an Bord',
      'Int',
      'CheckBox',
      TRUE,
      $messages,
      $optionGroupId,
      1,
      10
    );

    $eventGroupId = self::ensureCustomGroup(
      self::setting(
        'moeve_crewing_event_group',
        DefaultConfiguration::EVENT_GROUP
      ),
      'Crewing – Besetzungsbedarf',
      'Event',
      $messages
    );

    $weight = 10;

    foreach ($roles as $role) {
      self::ensureCustomField(
        $eventGroupId,
        $role['enabled_field'],
        $role['label'] . ' freischalten',
        'Boolean',
        'Toggle',
        FALSE,
        $messages,
        NULL,
        0,
        $weight++
      );

      self::ensureCustomField(
        $eventGroupId,
        $role['minimum_field'],
        'Mindestanzahl ' . $role['label'],
        'Int',
        'Text',
        FALSE,
        $messages,
        NULL,
        0,
        $weight++
      );
    }

    return $messages;
  }

  /**
   * @return array<string, array{
   *   label: string,
   *   enabled_field: string,
   *   minimum_field: string
   * }>
   */
  private static function getRoleMapping(): array {
    $json = trim(
      (string) \Civi::settings()->get('moeve_crewing_role_mapping')
    );

    if ($json === '') {
      return DefaultConfiguration::roles();
    }

    try {
      $roles = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException(
        'The configured role mapping contains invalid JSON.',
        0,
        $exception
      );
    }

    if (!is_array($roles) || $roles === []) {
      throw new \RuntimeException(
        'The configured role mapping must contain at least one role.'
      );
    }

    foreach ($roles as $name => $role) {
      if (!is_string($name) || !is_array($role)) {
        throw new \RuntimeException(
          'Every role mapping needs a technical role name.'
        );
      }

      foreach (
        ['label', 'enabled_field', 'minimum_field'] as $requiredKey
      ) {
        if (
          !isset($role[$requiredKey])
          || !is_string($role[$requiredKey])
          || $role[$requiredKey] === ''
        ) {
          throw new \RuntimeException(sprintf(
            'Role "%s" is missing the value "%s".',
            $name,
            $requiredKey
          ));
        }
      }
    }

    return $roles;
  }

  /**
   * @param array<string, array{
   *   label: string,
   *   enabled_field: string,
   *   minimum_field: string
   * }> $roles
   * @param array<int, string> $messages
   */
  private static function ensureRoleOptions(
    int $optionGroupId,
    array $roles,
    array &$messages
  ): void {
    $existing = OptionValue::get(FALSE)
      ->addSelect('id', 'name', 'value', 'weight')
      ->addWhere('option_group_id', '=', $optionGroupId)
      ->execute();

    $names = [];
    $usedValues = [];
    $maxWeight = 0;

    foreach ($existing as $option) {
      $names[(string) $option['name']] = TRUE;

      $value = (string) $option['value'];
      if (ctype_digit($value)) {
        $usedValues[(int) $value] = TRUE;
      }

      $maxWeight = max(
        $maxWeight,
        (int) ($option['weight'] ?? 0)
      );
    }

    $nextValue = 1;

    foreach ($roles as $name => $role) {
      if (isset($names[$name])) {
        $messages[] = sprintf('vorhanden: Rolle %s', $name);
        continue;
      }

      while (isset($usedValues[$nextValue])) {
        $nextValue++;
      }

      OptionValue::create(FALSE)
        ->addValue('option_group_id', $optionGroupId)
        ->addValue('name', $name)
        ->addValue('label', $role['label'])
        ->addValue('value', (string) $nextValue)
        ->addValue('weight', ++$maxWeight)
        ->addValue('is_active', TRUE)
        ->addValue('is_default', FALSE)
        ->execute();

      $usedValues[$nextValue] = TRUE;
      $messages[] = sprintf('erstellt: Rolle %s', $name);
      $nextValue++;
    }
  }

  /**
   * @param array<int, string> $messages
   */
  private static function ensureCustomGroup(
    string $name,
    string $title,
    string $extends,
    array &$messages
  ): int {
    $existing = CustomGroup::get(FALSE)
      ->addSelect('id', 'name', 'extends')
      ->addWhere('name', '=', $name)
      ->execute()
      ->first();

    if ($existing) {
      if ((string) $existing['extends'] !== $extends) {
        throw new \RuntimeException(sprintf(
          'Custom group "%s" extends "%s" instead of "%s".',
          $name,
          (string) $existing['extends'],
          $extends
        ));
      }

      $messages[] = sprintf(
        'vorhanden: Feldgruppe %s',
        $name
      );

      return (int) $existing['id'];
    }

    $created = CustomGroup::create(FALSE)
      ->addValue('name', $name)
      ->addValue('title', $title)
      ->addValue('extends', $extends)
      ->addValue('style', 'Inline')
      ->addValue('is_multiple', FALSE)
      ->addValue('is_active', TRUE)
      ->execute()
      ->first();

    if (!$created) {
      throw new \RuntimeException(sprintf(
        'Could not create custom group "%s".',
        $name
      ));
    }

    $messages[] = sprintf(
      'erstellt: Feldgruppe %s',
      $name
    );

    return (int) $created['id'];
  }

  /**
   * @param array<int, string> $messages
   */
  private static function ensureCustomField(
    int $customGroupId,
    string $name,
    string $label,
    string $dataType,
    string $htmlType,
    bool $isSearchable,
    array &$messages,
    ?int $optionGroupId = NULL,
    int $serialize = 0,
    int $weight = 1
  ): void {
    $existing = CustomField::get(FALSE)
      ->addSelect(
        'id',
        'name',
        'data_type',
        'html_type',
        'option_group_id',
        'serialize'
      )
      ->addWhere('custom_group_id', '=', $customGroupId)
      ->addWhere('name', '=', $name)
      ->execute()
      ->first();

    if ($existing) {
      $existingOptionGroupId = isset($existing['option_group_id'])
        ? (int) $existing['option_group_id']
        : NULL;

      if (
        (string) $existing['data_type'] !== $dataType
        || (string) $existing['html_type'] !== $htmlType
        || $existingOptionGroupId !== $optionGroupId
        || (int) ($existing['serialize'] ?? 0) !== $serialize
      ) {
        throw new \RuntimeException(sprintf(
          'Custom field "%s" has an incompatible configuration.',
          $name
        ));
      }

      $messages[] = sprintf(
        'vorhanden: Feld %s',
        $name
      );

      return;
    }

    $create = CustomField::create(FALSE)
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('name', $name)
      ->addValue('label', $label)
      ->addValue('data_type', $dataType)
      ->addValue('html_type', $htmlType)
      ->addValue('is_required', FALSE)
      ->addValue('is_searchable', $isSearchable)
      ->addValue('is_active', TRUE)
      ->addValue('serialize', $serialize)
      ->addValue('weight', $weight);

    if ($optionGroupId !== NULL) {
      $create->addValue(
        'option_group_id',
        $optionGroupId
      );
    }

    $create->execute();

    $messages[] = sprintf(
      'erstellt: Feld %s',
      $name
    );
  }

  private static function setting(
    string $name,
    string $fallback
  ): string {
    $value = trim(
      (string) \Civi::settings()->get($name)
    );

    return $value !== '' ? $value : $fallback;
  }

}
