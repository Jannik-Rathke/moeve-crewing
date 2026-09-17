<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Configuration;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

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
   *   roleEnabledFields: array<string, string>,
   *   roleMinimumFields: array<string, string>,
   *   roleRows: array<int, array<string, string>>,
   *   defaults: array<string, mixed>,
   *   statusErrors: array<int, string>,
   *   roleMappingErrors: array<int, string>
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
    $roleFormData = self::getRoleFormData($defaults);
    $defaults = array_merge($defaults, $roleFormData['defaults']);

    return [
      'roleOptionGroups' => $optionGroups,
      'individualGroups' => $groups['Individual'],
      'participantGroups' => $groups['Participant'],
      'eventGroups' => $groups['Event'],
      'individualFields' => $fields['Individual'],
      'participantFields' => $fields['Participant'],
      'roleEnabledFields' => $roleFormData['enabledFields'],
      'roleMinimumFields' => $roleFormData['minimumFields'],
      'roleRows' => $roleFormData['rows'],
      'defaults' => $defaults,
      'statusErrors' => self::getStatusErrors($defaults),
      'roleMappingErrors' => $roleFormData['errors'],
    ];
  }

  /**
   * @param array<string, mixed> $values
   * @return array<int, string>
   */
  public static function save(array $values): array {
    $configuration = self::validateIds($values);
    $roleMapping = NULL;

    if (self::roleSourceMatches($values, $configuration['ids'])) {
      $roleMapping = self::validateRoleMapping(
        $values,
        $configuration['ids']['role_option_group_id'],
        $configuration['ids']['event_group_id']
      );
    }

    foreach ($configuration['settings'] as $name => $value) {
      \Civi::settings()->set($name, $value);
    }

    if ($roleMapping !== NULL) {
      \Civi::settings()->set(
        'moeve_crewing_role_mapping',
        $roleMapping['json']
      );
      $configuration['messages'][] = sprintf(
        'gespeichert: Rollenzuordnung mit %d Rollen',
        $roleMapping['count']
      );
    }
    else {
      $configuration['messages'][] =
        'Hinweis: Die Basiszuordnung wurde geändert. Laden Sie die Seite neu, bevor Sie die Rollen zuordnen.';
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
   *   messages: array<int, string>,
   *   ids: array<string, int>
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
      'ids' => $ids,
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
   * @param array<string, string> $baseIds
   * @return array{
   *   rows: array<int, array<string, string>>,
   *   enabledFields: array<string, string>,
   *   minimumFields: array<string, string>,
   *   defaults: array<string, mixed>,
   *   errors: array<int, string>
   * }
   */
  private static function getRoleFormData(array $baseIds): array {
    $optionGroupId = (string) ($baseIds['role_option_group_id'] ?? '');
    $eventGroupId = (string) ($baseIds['event_group_id'] ?? '');
    $defaults = [
      'role_source_option_group_id' => $optionGroupId,
      'role_source_event_group_id' => $eventGroupId,
    ];

    if (
      !ctype_digit($optionGroupId)
      || (int) $optionGroupId < 1
      || !ctype_digit($eventGroupId)
      || (int) $eventGroupId < 1
    ) {
      return [
        'rows' => [],
        'enabledFields' => [],
        'minimumFields' => [],
        'defaults' => $defaults,
        'errors' => [
          'Speichern Sie zuerst eine gültige Basiszuordnung, um die Rollen zuzuordnen.',
        ],
      ];
    }

    $roleOptions = self::getRoleOptions((int) $optionGroupId);
    $eventFields = self::getEventRoleFields((int) $eventGroupId);
    $enabledFieldOptions = self::fieldOptions($eventFields['enabled']);
    $minimumFieldOptions = self::fieldOptions($eventFields['minimum']);
    $enabledFieldsByName = self::fieldsByName($eventFields['enabled']);
    $minimumFieldsByName = self::fieldsByName($eventFields['minimum']);
    $errors = [];

    try {
      $mapping = self::getCurrentRoleMapping();
    }
    catch (\Throwable $exception) {
      $mapping = [];
      $errors[] = $exception->getMessage();
    }

    $hasOverride = trim(
      (string) \Civi::settings()->get('moeve_crewing_role_mapping')
    ) !== '';
    $unmatchedRoles = array_fill_keys(array_keys($mapping), TRUE);
    $rows = [];
    $selectedCount = 0;

    foreach ($roleOptions as $option) {
      $optionId = (string) $option['id'];
      $optionName = (string) $option['name'];
      $configuredRole = $mapping[$optionName] ?? NULL;
      $useElement = 'role_use_' . $optionId;
      $enabledElement = 'role_enabled_field_' . $optionId;
      $minimumElement = 'role_minimum_field_' . $optionId;

      $defaults[$useElement] = $configuredRole !== NULL ? 1 : 0;
      $defaults[$enabledElement] = '';
      $defaults[$minimumElement] = '';

      if ($configuredRole !== NULL) {
        $selectedCount++;
        unset($unmatchedRoles[$optionName]);

        $enabledName = (string) $configuredRole['enabled_field'];
        $minimumName = (string) $configuredRole['minimum_field'];
        $defaults[$enabledElement] = isset($enabledFieldsByName[$enabledName])
          ? (string) $enabledFieldsByName[$enabledName]['id']
          : '';
        $defaults[$minimumElement] = isset($minimumFieldsByName[$minimumName])
          ? (string) $minimumFieldsByName[$minimumName]['id']
          : '';

        if ($defaults[$enabledElement] === '') {
          $errors[] = sprintf(
            'Für die Rolle „%s“ fehlt ein kompatibles Freigabefeld.',
            (string) ($option['label'] ?? $optionName)
          );
        }
        if ($defaults[$minimumElement] === '') {
          $errors[] = sprintf(
            'Für die Rolle „%s“ fehlt ein kompatibles Mindestanzahl-Feld.',
            (string) ($option['label'] ?? $optionName)
          );
        }
      }

      $rows[] = [
        'name' => $optionName,
        'label' => (string) ($option['label'] ?? $optionName),
        'useElement' => $useElement,
        'enabledElement' => $enabledElement,
        'minimumElement' => $minimumElement,
      ];
    }

    if ($roleOptions === []) {
      $errors[] = 'Die ausgewählte Optionsgruppe enthält keine aktiven Rollen.';
    }
    elseif ($selectedCount === 0) {
      $errors[] = 'Wählen Sie mindestens eine Rolle für Möwe Crewing aus.';
    }

    if ($hasOverride) {
      foreach (array_keys($unmatchedRoles) as $unmatchedRole) {
        $errors[] = sprintf(
          'Die konfigurierte Rolle „%s“ ist in der ausgewählten Optionsgruppe nicht vorhanden.',
          $unmatchedRole
        );
      }
    }

    return [
      'rows' => $rows,
      'enabledFields' => $enabledFieldOptions,
      'minimumFields' => $minimumFieldOptions,
      'defaults' => $defaults,
      'errors' => array_values(array_unique($errors)),
    ];
  }

  /**
   * @param array<string, mixed> $values
   * @param array<string, int> $ids
   */
  private static function roleSourceMatches(array $values, array $ids): bool {
    return (string) ($values['role_source_option_group_id'] ?? '')
        === (string) $ids['role_option_group_id']
      && (string) ($values['role_source_event_group_id'] ?? '')
        === (string) $ids['event_group_id'];
  }

  /**
   * @param array<string, mixed> $values
   * @return array{json: string, count: int}
   */
  private static function validateRoleMapping(
    array $values,
    int $optionGroupId,
    int $eventGroupId
  ): array {
    $roleOptions = self::getRoleOptions($optionGroupId);
    $eventFields = self::getEventRoleFields($eventGroupId);
    $enabledFields = $eventFields['enabled'];
    $minimumFields = $eventFields['minimum'];
    $usedEnabledFields = [];
    $usedMinimumFields = [];
    $mapping = [];

    foreach ($roleOptions as $option) {
      $optionId = (string) $option['id'];
      if (empty($values['role_use_' . $optionId])) {
        continue;
      }

      $enabledFieldId = self::submittedId(
        $values,
        'role_enabled_field_' . $optionId,
        sprintf('Freigabefeld für „%s“', (string) $option['label'])
      );
      $minimumFieldId = self::submittedId(
        $values,
        'role_minimum_field_' . $optionId,
        sprintf('Mindestanzahl-Feld für „%s“', (string) $option['label'])
      );

      if (!isset($enabledFields[$enabledFieldId])) {
        throw new \RuntimeException(sprintf(
          'Das Freigabefeld für „%s“ ist nicht kompatibel.',
          (string) $option['label']
        ));
      }
      if (!isset($minimumFields[$minimumFieldId])) {
        throw new \RuntimeException(sprintf(
          'Das Mindestanzahl-Feld für „%s“ ist nicht kompatibel.',
          (string) $option['label']
        ));
      }
      if (isset($usedEnabledFields[$enabledFieldId])) {
        throw new \RuntimeException(sprintf(
          'Das Freigabefeld für „%s“ wurde bereits einer anderen Rolle zugeordnet.',
          (string) $option['label']
        ));
      }
      if (isset($usedMinimumFields[$minimumFieldId])) {
        throw new \RuntimeException(sprintf(
          'Das Mindestanzahl-Feld für „%s“ wurde bereits einer anderen Rolle zugeordnet.',
          (string) $option['label']
        ));
      }

      $usedEnabledFields[$enabledFieldId] = TRUE;
      $usedMinimumFields[$minimumFieldId] = TRUE;
      $mapping[(string) $option['name']] = [
        'label' => (string) $option['label'],
        'enabled_field' => (string) $enabledFields[$enabledFieldId]['name'],
        'minimum_field' => (string) $minimumFields[$minimumFieldId]['name'],
      ];
    }

    if ($mapping === []) {
      throw new \RuntimeException(
        'Wählen Sie mindestens eine Rolle für Möwe Crewing aus.'
      );
    }

    try {
      $json = json_encode(
        $mapping,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
      );
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException(
        'Die Rollenzuordnung konnte nicht gespeichert werden.',
        0,
        $exception
      );
    }

    return [
      'json' => $json,
      'count' => count($mapping),
    ];
  }

  /**
   * @param array<string, mixed> $values
   */
  private static function submittedId(
    array $values,
    string $key,
    string $label
  ): int {
    $value = (string) ($values[$key] ?? '');
    if ($value === '' || !ctype_digit($value) || (int) $value < 1) {
      throw new \RuntimeException(sprintf(
        'Bitte wählen Sie ein gültiges %s aus.',
        $label
      ));
    }

    return (int) $value;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private static function getRoleOptions(int $optionGroupId): array {
    $options = [];
    foreach (
      OptionValue::get(FALSE)
        ->addSelect('id', 'name', 'label', 'weight')
        ->addWhere('option_group_id', '=', $optionGroupId)
        ->addWhere('is_active', '=', TRUE)
        ->execute() as $option
    ) {
      $options[] = $option;
    }

    usort(
      $options,
      static function (array $left, array $right): int {
        $weightComparison = (int) ($left['weight'] ?? 0)
          <=> (int) ($right['weight'] ?? 0);
        if ($weightComparison !== 0) {
          return $weightComparison;
        }
        return strnatcasecmp(
          (string) ($left['label'] ?? $left['name']),
          (string) ($right['label'] ?? $right['name'])
        );
      }
    );

    return $options;
  }

  /**
   * @return array{
   *   enabled: array<int, array<string, mixed>>,
   *   minimum: array<int, array<string, mixed>>
   * }
   */
  private static function getEventRoleFields(int $eventGroupId): array {
    $fields = [
      'enabled' => [],
      'minimum' => [],
    ];

    foreach (
      CustomField::get(FALSE)
        ->addSelect('id', 'name', 'label', 'data_type', 'html_type')
        ->addWhere('custom_group_id', '=', $eventGroupId)
        ->addWhere('is_active', '=', TRUE)
        ->execute() as $field
    ) {
      $fieldId = (int) $field['id'];
      if (
        (string) $field['data_type'] === 'Boolean'
        && (string) $field['html_type'] === 'Toggle'
      ) {
        $fields['enabled'][$fieldId] = $field;
      }
      elseif (
        (string) $field['data_type'] === 'Int'
        && (string) $field['html_type'] === 'Text'
      ) {
        $fields['minimum'][$fieldId] = $field;
      }
    }

    return $fields;
  }

  /**
   * @param array<int, array<string, mixed>> $fields
   * @return array<string, string>
   */
  private static function fieldOptions(array $fields): array {
    $options = [];
    foreach ($fields as $id => $field) {
      $options[(string) $id] = sprintf(
        '%s (%s)',
        (string) ($field['label'] ?? $field['name']),
        (string) $field['name']
      );
    }
    natcasesort($options);
    return $options;
  }

  /**
   * @param array<int, array<string, mixed>> $fields
   * @return array<string, array<string, mixed>>
   */
  private static function fieldsByName(array $fields): array {
    $byName = [];
    foreach ($fields as $field) {
      $byName[(string) $field['name']] = $field;
    }
    return $byName;
  }

  /**
   * @return array<string, array{
   *   label: string,
   *   enabled_field: string,
   *   minimum_field: string
   * }>
   */
  private static function getCurrentRoleMapping(): array {
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
        'Die gespeicherte Rollenzuordnung enthält ungültiges JSON.',
        0,
        $exception
      );
    }

    if (!is_array($roles) || $roles === []) {
      throw new \RuntimeException(
        'Die gespeicherte Rollenzuordnung muss mindestens eine Rolle enthalten.'
      );
    }

    foreach ($roles as $name => $role) {
      if (!is_string($name) || !is_array($role)) {
        throw new \RuntimeException(
          'Jede Rollenzuordnung benötigt einen technischen Rollennamen.'
        );
      }

      foreach (['label', 'enabled_field', 'minimum_field'] as $requiredKey) {
        if (
          !isset($role[$requiredKey])
          || !is_string($role[$requiredKey])
          || trim($role[$requiredKey]) === ''
        ) {
          throw new \RuntimeException(sprintf(
            'Der Rolle „%s“ fehlt der Wert „%s“.',
            $name,
            $requiredKey
          ));
        }
      }
    }

    return $roles;
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
