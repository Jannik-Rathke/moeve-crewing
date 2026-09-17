<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Configuration;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;

/**
 * Resolves and validates administrator-selected CiviCRM configuration.
 */
final class ExistingConfigurationManager {

  /**
   * @return array{
   *   roleOptionGroups: array<string, string>,
   *   individualGroups: array<string, string>,
   *   participantGroups: array<string, string>,
   *   eventGroups: array<string, string>,
   *   individualFields: array<string, string>,
   *   participantFields: array<string, string>,
   *   defaults: array<string, string>,
   *   statusErrors: array<int, string>
   * }
   */
  public static function getFormData(): array {
    $optionGroups = [];
    foreach (
      OptionGroup::get(FALSE)
        ->addSelect('id', 'name', 'title')
        ->addWhere('is_active', '=', TRUE)
        ->execute() as $optionGroup
    ) {
      $optionGroups[(string) $optionGroup['id']] = self::recordLabel(
        (string) ($optionGroup['title'] ?? $optionGroup['name']),
        (string) $optionGroup['name']
      );
    }
    natcasesort($optionGroups);

    $groupRecords = [];
    $groups = [
      'Individual' => [],
      'Participant' => [],
      'Event' => [],
    ];

    foreach (
      CustomGroup::get(FALSE)
        ->addSelect('id', 'name', 'title', 'extends')
        ->addWhere('is_active', '=', TRUE)
        ->addWhere('extends', 'IN', array_keys($groups))
        ->execute() as $group
    ) {
      $id = (string) $group['id'];
      $extends = (string) $group['extends'];
      $groupRecords[$id] = $group;
      $groups[$extends][$id] = self::recordLabel(
        (string) ($group['title'] ?? $group['name']),
        (string) $group['name']
      );
    }

    foreach ($groups as &$groupOptions) {
      natcasesort($groupOptions);
    }
    unset($groupOptions);

    $fields = [
      'Individual' => [],
      'Participant' => [],
    ];

    foreach (
      CustomField::get(FALSE)
        ->addSelect(
          'id',
          'name',
          'label',
          'custom_group_id',
          'data_type',
          'html_type',
          'option_group_id',
          'serialize'
        )
        ->addWhere('is_active', '=', TRUE)
        ->execute() as $field
    ) {
      $groupId = (string) $field['custom_group_id'];
      $group = $groupRecords[$groupId] ?? NULL;
      if (!$group) {
        continue;
      }

      $extends = (string) $group['extends'];
      if (!isset($fields[$extends])) {
        continue;
      }

      if (
        (string) $field['data_type'] !== 'Int'
        || (string) $field['html_type'] !== 'CheckBox'
        || (int) ($field['serialize'] ?? 0) !== 1
        || empty($field['option_group_id'])
      ) {
        continue;
      }

      $fields[$extends][(string) $field['id']] = sprintf(
        '%s — %s.%s',
        (string) ($field['label'] ?? $field['name']),
        (string) $group['name'],
        (string) $field['name']
      );
    }

    foreach ($fields as &$fieldOptions) {
      natcasesort($fieldOptions);
    }
    unset($fieldOptions);

    $defaults = self::getCurrentIds();

    return [
      'roleOptionGroups' => $optionGroups,
      'individualGroups' => $groups['Individual'],
      'participantGroups' => $groups['Participant'],
      'eventGroups' => $groups['Event'],
      'individualFields' => $fields['Individual'],
      'participantFields' => $fields['Participant'],
      'defaults' => $defaults,
      'statusErrors' => self::getStatusErrors($defaults),
    ];
  }

  /**
   * @param array<string, mixed> $values
   * @return array<int, string>
   */
  public static function save(array $values): array {
    $configuration = self::validateIds($values);

    foreach ($configuration['settings'] as $name => $value) {
      \Civi::settings()->set($name, $value);
    }

    return $configuration['messages'];
  }

  /**
   * @param array<string, string> $ids
   * @return array<int, string>
   */
  private static function getStatusErrors(array $ids): array {
    try {
      self::validateIds($ids);
      return [];
    }
    catch (\Throwable $exception) {
      return [$exception->getMessage()];
    }
  }

  /**
   * @param array<string, mixed> $values
   * @return array{
   *   settings: array<string, string>,
   *   messages: array<int, string>
   * }
   */
  private static function validateIds(array $values): array {
    $required = [
      'role_option_group_id' => 'Rollengruppe',
      'individual_group_id' => 'Feldgruppe für Personen',
      'capabilities_field_id' => 'Fähigkeiten-Feld',
      'participant_group_id' => 'Feldgruppe für Teilnahmen',
      'preferences_field_id' => 'Wunschfunktionen-Feld',
      'event_group_id' => 'Feldgruppe für Veranstaltungen',
    ];

    $ids = [];
    foreach ($required as $key => $label) {
      $value = (string) ($values[$key] ?? '');
      if ($value === '' || !ctype_digit($value) || (int) $value < 1) {
        throw new \RuntimeException(sprintf(
          'Bitte wählen Sie eine gültige %s aus.',
          $label
        ));
      }
      $ids[$key] = (int) $value;
    }

    $optionGroup = OptionGroup::get(FALSE)
      ->addSelect('id', 'name', 'title')
      ->addWhere('id', '=', $ids['role_option_group_id'])
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if (!$optionGroup) {
      throw new \RuntimeException('Die ausgewählte Rollengruppe wurde nicht gefunden oder ist inaktiv.');
    }

    $individualGroup = self::requireGroup(
      $ids['individual_group_id'],
      'Individual',
      'Personen'
    );
    $participantGroup = self::requireGroup(
      $ids['participant_group_id'],
      'Participant',
      'Teilnahmen'
    );
    $eventGroup = self::requireGroup(
      $ids['event_group_id'],
      'Event',
      'Veranstaltungen'
    );

    $capabilitiesField = self::requireRoleField(
      $ids['capabilities_field_id'],
      (int) $individualGroup['id'],
      (int) $optionGroup['id'],
      'Fähigkeiten-Feld'
    );
    $preferencesField = self::requireRoleField(
      $ids['preferences_field_id'],
      (int) $participantGroup['id'],
      (int) $optionGroup['id'],
      'Wunschfunktionen-Feld'
    );

    return [
      'settings' => [
        'moeve_crewing_role_option_group' => (string) $optionGroup['name'],
        'moeve_crewing_individual_group' => (string) $individualGroup['name'],
        'moeve_crewing_capabilities_field' => (string) $capabilitiesField['name'],
        'moeve_crewing_participant_group' => (string) $participantGroup['name'],
        'moeve_crewing_preferences_field' => (string) $preferencesField['name'],
        'moeve_crewing_event_group' => (string) $eventGroup['name'],
      ],
      'messages' => [
        sprintf('gespeichert: Rollengruppe %s', (string) $optionGroup['name']),
        sprintf('gespeichert: Personen-Feldgruppe %s', (string) $individualGroup['name']),
        sprintf('gespeichert: Fähigkeiten-Feld %s', (string) $capabilitiesField['name']),
        sprintf('gespeichert: Teilnahme-Feldgruppe %s', (string) $participantGroup['name']),
        sprintf('gespeichert: Wunschfunktionen-Feld %s', (string) $preferencesField['name']),
        sprintf('gespeichert: Veranstaltungs-Feldgruppe %s', (string) $eventGroup['name']),
      ],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private static function requireGroup(
    int $id,
    string $extends,
    string $label
  ): array {
    $group = CustomGroup::get(FALSE)
      ->addSelect('id', 'name', 'title', 'extends')
      ->addWhere('id', '=', $id)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    if (!$group || (string) $group['extends'] !== $extends) {
      throw new \RuntimeException(sprintf(
        'Die ausgewählte Feldgruppe für %s ist nicht kompatibel.',
        $label
      ));
    }

    return $group;
  }

  /**
   * @return array<string, mixed>
   */
  private static function requireRoleField(
    int $id,
    int $groupId,
    int $optionGroupId,
    string $label
  ): array {
    $field = CustomField::get(FALSE)
      ->addSelect(
        'id',
        'name',
        'custom_group_id',
        'data_type',
        'html_type',
        'option_group_id',
        'serialize'
      )
      ->addWhere('id', '=', $id)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    if (!$field) {
      throw new \RuntimeException(sprintf('%s wurde nicht gefunden oder ist inaktiv.', $label));
    }

    if (
      (int) $field['custom_group_id'] !== $groupId
      || (string) $field['data_type'] !== 'Int'
      || (string) $field['html_type'] !== 'CheckBox'
      || (int) ($field['serialize'] ?? 0) !== 1
      || (int) ($field['option_group_id'] ?? 0) !== $optionGroupId
    ) {
      throw new \RuntimeException(sprintf(
        '%s gehört nicht zur ausgewählten Feldgruppe oder verwendet nicht dieselbe Rollengruppe.',
        $label
      ));
    }

    return $field;
  }

  /**
   * @return array<string, string>
   */
  private static function getCurrentIds(): array {
    $optionGroupName = self::setting(
      'moeve_crewing_role_option_group',
      DefaultConfiguration::ROLE_OPTION_GROUP
    );
    $individualGroupName = self::setting(
      'moeve_crewing_individual_group',
      DefaultConfiguration::INDIVIDUAL_GROUP
    );
    $participantGroupName = self::setting(
      'moeve_crewing_participant_group',
      DefaultConfiguration::PARTICIPANT_GROUP
    );
    $eventGroupName = self::setting(
      'moeve_crewing_event_group',
      DefaultConfiguration::EVENT_GROUP
    );

    $optionGroupId = self::optionGroupId($optionGroupName);
    $individualGroupId = self::customGroupId($individualGroupName);
    $participantGroupId = self::customGroupId($participantGroupName);
    $eventGroupId = self::customGroupId($eventGroupName);

    return [
      'role_option_group_id' => $optionGroupId,
      'individual_group_id' => $individualGroupId,
      'capabilities_field_id' => self::customFieldId(
        $individualGroupId,
        self::setting(
          'moeve_crewing_capabilities_field',
          DefaultConfiguration::CAPABILITIES_FIELD
        )
      ),
      'participant_group_id' => $participantGroupId,
      'preferences_field_id' => self::customFieldId(
        $participantGroupId,
        self::setting(
          'moeve_crewing_preferences_field',
          DefaultConfiguration::PREFERENCES_FIELD
        )
      ),
      'event_group_id' => $eventGroupId,
    ];
  }

  private static function optionGroupId(string $name): string {
    $record = OptionGroup::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', $name)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    return $record ? (string) $record['id'] : '';
  }

  private static function customGroupId(string $name): string {
    $record = CustomGroup::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', $name)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    return $record ? (string) $record['id'] : '';
  }

  private static function customFieldId(string $groupId, string $name): string {
    if ($groupId === '') {
      return '';
    }

    $record = CustomField::get(FALSE)
      ->addSelect('id')
      ->addWhere('custom_group_id', '=', (int) $groupId)
      ->addWhere('name', '=', $name)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    return $record ? (string) $record['id'] : '';
  }

  private static function setting(string $name, string $fallback): string {
    $value = trim((string) \Civi::settings()->get($name));
    return $value !== '' ? $value : $fallback;
  }

  private static function recordLabel(string $title, string $name): string {
    return sprintf('%s (%s)', $title, $name);
  }

}
