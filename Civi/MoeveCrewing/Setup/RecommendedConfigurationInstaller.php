<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Setup;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\MoeveCrewing\Configuration\DefaultConfiguration;

final class RecommendedConfigurationInstaller {

  /**
   * Create missing recommended configuration without changing existing items.
   *
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
    $candidateRoleName = self::setting(
      'moeve_crewing_candidate_role',
      DefaultConfiguration::CANDIDATE_ROLE
    );
    $candidateRoleLabel = isset($roles[$candidateRoleName]['label'])
      ? (string) $roles[$candidateRoleName]['label']
      : 'potentielles Crewmitglied';
    unset($roles[$candidateRoleName]);
    $roleOptions = $roles;
    $roleOptions[$candidateRoleName] = [
      'label' => $candidateRoleLabel,
    ];
    self::ensureRoleOptions($optionGroupId, $roleOptions, $messages);

    $individualGroup = self::ensureCustomGroup(
      self::setting('moeve_crewing_individual_group', DefaultConfiguration::INDIVIDUAL_GROUP),
      'Crewing – Fähigkeiten',
      'Individual',
      $messages
    );
    self::ensureCustomField(
      $individualGroup,
      self::setting('moeve_crewing_capabilities_field', DefaultConfiguration::CAPABILITIES_FIELD),
      'Meine Fähigkeiten',
      'Int',
      'CheckBox',
      TRUE,
      $messages,
      $optionGroupId,
      1,
      10
    );

    $participantGroup = self::ensureCustomGroup(
      self::setting('moeve_crewing_participant_group', DefaultConfiguration::PARTICIPANT_GROUP),
      'Crewing – gewünschte Funktionen',
      'Participant',
      $messages
    );
    self::ensureCustomField(
      $participantGroup,
      self::setting('moeve_crewing_preferences_field', DefaultConfiguration::PREFERENCES_FIELD),
      'Gewünschte Funktionen an Bord',
      'Int',
      'CheckBox',
      TRUE,
      $messages,
      $optionGroupId,
      1,
      10
    );

    $eventInfoGroup = self::ensureCustomGroup(
      self::setting(
        'moeve_crewing_event_info_group',
        DefaultConfiguration::EVENT_INFO_GROUP
      ),
      'Veranstaltungsinfo',
      'Event',
      $messages
    );

    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_event_number_field',
        DefaultConfiguration::EVENT_NUMBER_FIELD
      ),
      'Veranstaltungsnummer',
      'String',
      'Text',
      TRUE,
      $messages,
      NULL,
      0,
      10,
      ['help_pre' => 'Kann automatisch generiert werden']
    );
    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_departure_port_field',
        DefaultConfiguration::DEPARTURE_PORT_FIELD
      ),
      'Hafen von',
      'String',
      'Text',
      TRUE,
      $messages,
      NULL,
      0,
      20
    );
    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_route_field',
        DefaultConfiguration::ROUTE_FIELD
      ),
      'Route',
      'String',
      'Text',
      TRUE,
      $messages,
      NULL,
      0,
      30
    );
    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_arrival_port_field',
        DefaultConfiguration::ARRIVAL_PORT_FIELD
      ),
      'Hafen bis',
      'String',
      'Text',
      TRUE,
      $messages,
      NULL,
      0,
      40
    );
    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_crew_on_board_field',
        DefaultConfiguration::CREW_ON_BOARD_FIELD
      ),
      'Stamm an Bord',
      'Date',
      'Select Date',
      TRUE,
      $messages,
      NULL,
      0,
      50,
      [
        'date_format' => 'dd.mm.yy',
        'time_format' => 2,
      ],
      ['time_format' => 2]
    );
    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_crew_off_board_field',
        DefaultConfiguration::CREW_OFF_BOARD_FIELD
      ),
      'Stamm von Bord',
      'Date',
      'Select Date',
      TRUE,
      $messages,
      NULL,
      0,
      60,
      [
        'date_format' => 'dd.mm.yy',
        'time_format' => 2,
      ],
      ['time_format' => 2]
    );
    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_organizer_field',
        DefaultConfiguration::ORGANIZER_FIELD
      ),
      'Organisator',
      'ContactReference',
      'Autocomplete-Select',
      TRUE,
      $messages,
      NULL,
      0,
      70
    );
    self::ensureCustomField(
      $eventInfoGroup,
      self::setting(
        'moeve_crewing_comment_field',
        DefaultConfiguration::COMMENT_FIELD
      ),
      'Kommentar',
      'Memo',
      'TextArea',
      FALSE,
      $messages,
      NULL,
      0,
      80,
      [
        'note_rows' => 4,
        'note_columns' => 60,
      ]
    );

    $eventGroup = self::ensureCustomGroup(
      self::setting('moeve_crewing_event_group', DefaultConfiguration::EVENT_GROUP),
      'Crewing – Besetzungsbedarf',
      'Event',
      $messages
    );

    $weight = 10;
    foreach ($roles as $role) {
      self::ensureCustomField(
        $eventGroup,
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
        $eventGroup,
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
   * @return array<string, array{label: string, enabled_field: string, minimum_field: string}>
   */
  private static function getRoleMapping(): array {
    $json = trim((string) \Civi::settings()->get('moeve_crewing_role_mapping'));
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
      throw new \RuntimeException('The configured role mapping must contain at least one role.');
    }

    foreach ($roles as $name => $role) {
      if (!is_string($name) || !is_array($role)) {
        throw new \RuntimeException('Every role mapping needs a technical role name.');
      }
      foreach (['label', 'enabled_field', 'minimum_field'] as $requiredKey) {
        if (!isset($role[$requiredKey]) || !is_string($role[$requiredKey]) || $role[$requiredKey] === '') {
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
   * @param array<string, array{label: string}> $roles
   * @param array<int, string> $messages
   */
  private static function ensureRoleOptions(int $optionGroupId, array $roles, array &$messages): void {
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
      $maxWeight = max($maxWeight, (int) ($option['weight'] ?? 0));
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
          'Custom group "%s" exists but extends "%s" instead of "%s".',
          $name,
          (string) $existing['extends'],
          $extends
        ));
      }
      $messages[] = sprintf('vorhanden: Feldgruppe %s', $name);
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
      throw new \RuntimeException(sprintf('Could not create custom group "%s".', $name));
    }

    $messages[] = sprintf('erstellt: Feldgruppe %s', $name);
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
    int $weight = 1,
    array $extraValues = [],
    array $compatibilityValues = []
  ): void {
    $select = [
      'id',
      'name',
      'data_type',
      'html_type',
      'option_group_id',
      'serialize',
    ];
    foreach (array_keys($compatibilityValues) as $fieldName) {
      if (!in_array($fieldName, $select, TRUE)) {
        $select[] = $fieldName;
      }
    }

    $existing = CustomField::get(FALSE)
      ->addSelect(...$select)
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
          'Custom field "%s" exists with incompatible type %s/%s.',
          $name,
          (string) $existing['data_type'],
          (string) $existing['html_type']
        ));
      }
      foreach ($compatibilityValues as $fieldName => $expectedValue) {
        if (($existing[$fieldName] ?? NULL) != $expectedValue) {
          throw new \RuntimeException(sprintf(
            'Custom field "%s" has an incompatible value for "%s".',
            $name,
            $fieldName
          ));
        }
      }
      $messages[] = sprintf('vorhanden: Feld %s', $name);
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
      $create->addValue('option_group_id', $optionGroupId);
    }
    foreach ($extraValues as $fieldName => $value) {
      $create->addValue($fieldName, $value);
    }

    $create->execute();
    $messages[] = sprintf('erstellt: Feld %s', $name);
  }

  private static function setting(string $name, string $fallback): string {
    $value = trim((string) \Civi::settings()->get($name));
    return $value !== '' ? $value : $fallback;
  }

}
