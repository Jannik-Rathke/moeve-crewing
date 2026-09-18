<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Configuration;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\ParticipantStatusType;

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
   *   candidateRoles: array<string, string>,
   *   eventTextFields: array<string, string>,
   *   eventDateTimeFields: array<string, string>,
   *   eventContactFields: array<string, string>,
   *   eventMemoFields: array<string, string>,
   *   roleEnabledFields: array<string, string>,
   *   roleMinimumFields: array<string, string>,
   *   roleRows: array<int, array<string, string>>,
   *   statusRows: array<int, array<string, mixed>>,
   *   defaults: array<string, mixed>,
   *   statusErrors: array<int, string>,
   *   roleMappingErrors: array<int, string>,
   *   statusColorErrors: array<int, string>
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
    $eventInfoFields = [
      'text' => [],
      'dateTime' => [],
      'contact' => [],
      'memo' => [],
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
          'serialize',
          'time_format'
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
      if ($extends === 'Event') {
        $fieldId = (string) $field['id'];
        $dataType = (string) $field['data_type'];
        $htmlType = (string) $field['html_type'];
        $option = sprintf(
          '%s — %s.%s',
          (string) ($field['label'] ?? $field['name']),
          (string) $group['name'],
          (string) $field['name']
        );

        if ($dataType === 'String' && $htmlType === 'Text') {
          $eventInfoFields['text'][$fieldId] = $option;
        }
        elseif (
          $dataType === 'Date'
          && $htmlType === 'Select Date'
          && (int) ($field['time_format'] ?? 0) > 0
        ) {
          $eventInfoFields['dateTime'][$fieldId] = $option;
        }
        elseif (
          $dataType === 'ContactReference'
          && $htmlType === 'Autocomplete-Select'
        ) {
          $eventInfoFields['contact'][$fieldId] = $option;
        }
        elseif ($dataType === 'Memo' && $htmlType === 'TextArea') {
          $eventInfoFields['memo'][$fieldId] = $option;
        }
        continue;
      }

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
    foreach ($eventInfoFields as &$fieldOptions) {
      natcasesort($fieldOptions);
    }
    unset($fieldOptions);

    $defaults = self::getCurrentIds();
    $roleFormData = self::getRoleFormData($defaults);
    $statusFormData = self::getParticipantStatusFormData();
    $defaults = array_merge(
      $defaults,
      $roleFormData['defaults'],
      $statusFormData['defaults']
    );

    $candidateRoles = [];
    $optionGroupId = (string) ($defaults['role_option_group_id'] ?? '');
    if (ctype_digit($optionGroupId) && (int) $optionGroupId > 0) {
      foreach (self::getRoleOptions((int) $optionGroupId) as $option) {
        $candidateRoles[(string) $option['id']] = self::recordLabel(
          (string) ($option['label'] ?? $option['name']),
          (string) $option['name']
        );
      }
    }

    return [
      'roleOptionGroups' => $optionGroups,
      'candidateRoles' => $candidateRoles,
      'individualGroups' => $groups['Individual'],
      'participantGroups' => $groups['Participant'],
      'eventGroups' => $groups['Event'],
      'individualFields' => $fields['Individual'],
      'participantFields' => $fields['Participant'],
      'eventTextFields' => $eventInfoFields['text'],
      'eventDateTimeFields' => $eventInfoFields['dateTime'],
      'eventContactFields' => $eventInfoFields['contact'],
      'eventMemoFields' => $eventInfoFields['memo'],
      'roleEnabledFields' => $roleFormData['enabledFields'],
      'roleMinimumFields' => $roleFormData['minimumFields'],
      'roleRows' => $roleFormData['rows'],
      'statusRows' => $statusFormData['rows'],
      'defaults' => $defaults,
      'statusErrors' => self::getStatusErrors($defaults),
      'roleMappingErrors' => $roleFormData['errors'],
      'statusColorErrors' => $statusFormData['errors'],
    ];
  }

  /**
   * @param array<string, mixed> $values
   * @return array<int, string>
   */
  public static function save(array $values): array {
    $configuration = self::validateIds($values);
    $statusColors = self::validateParticipantStatusColors($values);
    $roleMapping = NULL;

    if (self::roleSourceMatches($values, $configuration['ids'])) {
      $roleMapping = self::validateRoleMapping(
        $values,
        $configuration['ids']['role_option_group_id'],
        $configuration['ids']['event_group_id'],
        $configuration['ids']['candidate_role_id']
      );
    }

    foreach ($configuration['settings'] as $name => $value) {
      \Civi::settings()->set($name, $value);
    }
    \Civi::settings()->set(
      'moeve_crewing_participant_status_colors',
      $statusColors['json']
    );
    $configuration['messages'][] = sprintf(
      'gespeichert: Farben für %d Teilnahmestatus',
      $statusColors['count']
    );

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
      'candidate_role_id' => 'Rolle für potentielle Crewmitglieder',
      'individual_group_id' => 'Feldgruppe für Personen',
      'capabilities_field_id' => 'Fähigkeiten-Feld',
      'participant_group_id' => 'Feldgruppe für Teilnahmen',
      'preferences_field_id' => 'Wunschfunktionen-Feld',
      'event_info_group_id' => 'Feldgruppe für Veranstaltungsinformationen',
      'event_number_field_id' => 'Veranstaltungsnummer-Feld',
      'departure_port_field_id' => 'Abfahrtshafen-Feld',
      'route_field_id' => 'Routen-Feld',
      'arrival_port_field_id' => 'Ankunftshafen-Feld',
      'crew_on_board_field_id' => 'Stamm-an-Bord-Feld',
      'crew_off_board_field_id' => 'Stamm-von-Bord-Feld',
      'organizer_field_id' => 'Organisator-Feld',
      'comment_field_id' => 'Kommentar-Feld',
      'event_group_id' => 'Feldgruppe für den Besetzungsbedarf',
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

    $candidateRole = OptionValue::get(FALSE)
      ->addSelect('id', 'name', 'label', 'option_group_id')
      ->addWhere('id', '=', $ids['candidate_role_id'])
      ->addWhere('option_group_id', '=', (int) $optionGroup['id'])
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if (!$candidateRole) {
      throw new \RuntimeException(
        'Die Rolle für potentielle Crewmitglieder gehört nicht zur ausgewählten Rollengruppe oder ist inaktiv.'
      );
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
    $eventInfoGroup = self::requireGroup(
      $ids['event_info_group_id'],
      'Event',
      'Veranstaltungsinformationen'
    );
    $eventGroup = self::requireGroup(
      $ids['event_group_id'],
      'Event',
      'den Besetzungsbedarf'
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

    $eventNumberField = self::requireEventInfoField(
      $ids['event_number_field_id'],
      (int) $eventInfoGroup['id'],
      'String',
      'Text',
      'Veranstaltungsnummer-Feld'
    );
    $departurePortField = self::requireEventInfoField(
      $ids['departure_port_field_id'],
      (int) $eventInfoGroup['id'],
      'String',
      'Text',
      'Abfahrtshafen-Feld'
    );
    $routeField = self::requireEventInfoField(
      $ids['route_field_id'],
      (int) $eventInfoGroup['id'],
      'String',
      'Text',
      'Routen-Feld'
    );
    $arrivalPortField = self::requireEventInfoField(
      $ids['arrival_port_field_id'],
      (int) $eventInfoGroup['id'],
      'String',
      'Text',
      'Ankunftshafen-Feld'
    );
    $crewOnBoardField = self::requireEventInfoField(
      $ids['crew_on_board_field_id'],
      (int) $eventInfoGroup['id'],
      'Date',
      'Select Date',
      'Stamm-an-Bord-Feld',
      TRUE
    );
    $crewOffBoardField = self::requireEventInfoField(
      $ids['crew_off_board_field_id'],
      (int) $eventInfoGroup['id'],
      'Date',
      'Select Date',
      'Stamm-von-Bord-Feld',
      TRUE
    );
    $organizerField = self::requireEventInfoField(
      $ids['organizer_field_id'],
      (int) $eventInfoGroup['id'],
      'ContactReference',
      'Autocomplete-Select',
      'Organisator-Feld'
    );
    $commentField = self::requireEventInfoField(
      $ids['comment_field_id'],
      (int) $eventInfoGroup['id'],
      'Memo',
      'TextArea',
      'Kommentar-Feld'
    );

    $eventInfoFieldIds = [
      $ids['event_number_field_id'],
      $ids['departure_port_field_id'],
      $ids['route_field_id'],
      $ids['arrival_port_field_id'],
      $ids['crew_on_board_field_id'],
      $ids['crew_off_board_field_id'],
      $ids['organizer_field_id'],
      $ids['comment_field_id'],
    ];
    if (count(array_unique($eventInfoFieldIds)) !== count($eventInfoFieldIds)) {
      throw new \RuntimeException(
        'Jedes Veranstaltungsinformationsfeld darf nur einmal zugeordnet werden.'
      );
    }

    return [
      'settings' => [
        'moeve_crewing_role_option_group' => (string) $optionGroup['name'],
        'moeve_crewing_candidate_role' => (string) $candidateRole['name'],
        'moeve_crewing_individual_group' => (string) $individualGroup['name'],
        'moeve_crewing_capabilities_field' => (string) $capabilitiesField['name'],
        'moeve_crewing_participant_group' => (string) $participantGroup['name'],
        'moeve_crewing_preferences_field' => (string) $preferencesField['name'],
        'moeve_crewing_event_info_group' => (string) $eventInfoGroup['name'],
        'moeve_crewing_event_number_field' => (string) $eventNumberField['name'],
        'moeve_crewing_departure_port_field' => (string) $departurePortField['name'],
        'moeve_crewing_route_field' => (string) $routeField['name'],
        'moeve_crewing_arrival_port_field' => (string) $arrivalPortField['name'],
        'moeve_crewing_crew_on_board_field' => (string) $crewOnBoardField['name'],
        'moeve_crewing_crew_off_board_field' => (string) $crewOffBoardField['name'],
        'moeve_crewing_organizer_field' => (string) $organizerField['name'],
        'moeve_crewing_comment_field' => (string) $commentField['name'],
        'moeve_crewing_event_group' => (string) $eventGroup['name'],
      ],
      'messages' => [
        sprintf('gespeichert: Rollengruppe %s', (string) $optionGroup['name']),
        sprintf('gespeichert: Rolle für potentielle Crewmitglieder %s', (string) $candidateRole['name']),
        sprintf('gespeichert: Personen-Feldgruppe %s', (string) $individualGroup['name']),
        sprintf('gespeichert: Fähigkeiten-Feld %s', (string) $capabilitiesField['name']),
        sprintf('gespeichert: Teilnahme-Feldgruppe %s', (string) $participantGroup['name']),
        sprintf('gespeichert: Wunschfunktionen-Feld %s', (string) $preferencesField['name']),
        sprintf('gespeichert: Veranstaltungsinfo-Feldgruppe %s', (string) $eventInfoGroup['name']),
        sprintf('gespeichert: Veranstaltungsnummer-Feld %s', (string) $eventNumberField['name']),
        sprintf('gespeichert: Abfahrtshafen-Feld %s', (string) $departurePortField['name']),
        sprintf('gespeichert: Routen-Feld %s', (string) $routeField['name']),
        sprintf('gespeichert: Ankunftshafen-Feld %s', (string) $arrivalPortField['name']),
        sprintf('gespeichert: Stamm-an-Bord-Feld %s', (string) $crewOnBoardField['name']),
        sprintf('gespeichert: Stamm-von-Bord-Feld %s', (string) $crewOffBoardField['name']),
        sprintf('gespeichert: Organisator-Feld %s', (string) $organizerField['name']),
        sprintf('gespeichert: Kommentar-Feld %s', (string) $commentField['name']),
        sprintf('gespeichert: Besetzungsbedarf-Feldgruppe %s', (string) $eventGroup['name']),
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
   * @return array<string, mixed>
   */
  private static function requireEventInfoField(
    int $id,
    int $groupId,
    string $dataType,
    string $htmlType,
    string $label,
    bool $requireTime = FALSE
  ): array {
    $field = CustomField::get(FALSE)
      ->addSelect(
        'id',
        'name',
        'custom_group_id',
        'data_type',
        'html_type',
        'time_format'
      )
      ->addWhere('id', '=', $id)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    if (!$field) {
      throw new \RuntimeException(sprintf(
        '%s wurde nicht gefunden oder ist inaktiv.',
        $label
      ));
    }

    if (
      (int) $field['custom_group_id'] !== $groupId
      || (string) $field['data_type'] !== $dataType
      || (string) $field['html_type'] !== $htmlType
      || ($requireTime && (int) ($field['time_format'] ?? 0) < 1)
    ) {
      throw new \RuntimeException(sprintf(
        '%s gehört nicht zur ausgewählten Veranstaltungsinfo-Gruppe oder besitzt keinen kompatiblen Feldtyp.',
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
    $candidateRoleId = (string) ($baseIds['candidate_role_id'] ?? '');
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
      if ($optionId === $candidateRoleId) {
        unset($unmatchedRoles[$optionName]);
        continue;
      }
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
    int $eventGroupId,
    int $candidateRoleId
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
      if ((int) $option['id'] === $candidateRoleId) {
        continue;
      }
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
   * @return array{
   *   rows: array<int, array<string, mixed>>,
   *   defaults: array<string, string>,
   *   errors: array<int, string>
   * }
   */
  private static function getParticipantStatusFormData(): array {
    $configured = [];
    $errors = [];
    $json = trim(
      (string) \Civi::settings()->get(
        'moeve_crewing_participant_status_colors'
      )
    );

    if ($json !== '') {
      try {
        $decoded = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
          throw new \RuntimeException(
            'Die gespeicherten Statusfarben besitzen kein gültiges Format.'
          );
        }
        $configured = $decoded;
      }
      catch (\JsonException $exception) {
        $errors[] = 'Die gespeicherten Statusfarben enthalten ungültiges JSON.';
      }
      catch (\RuntimeException $exception) {
        $errors[] = $exception->getMessage();
      }
    }

    $rows = [];
    $defaults = [];
    foreach (
      ParticipantStatusType::get(FALSE)
        ->addSelect('id', 'name', 'label', 'class', 'is_active', 'weight')
        ->addOrderBy('weight', 'ASC')
        ->execute() as $status
    ) {
      $name = (string) $status['name'];
      $class = (string) ($status['class'] ?? '');
      $element = 'status_color_' . (string) $status['id'];
      $color = $configured[$name] ?? NULL;
      if (!is_string($color) || !preg_match('/^#[0-9A-Fa-f]{6}$/D', $color)) {
        if ($color !== NULL) {
          $errors[] = sprintf(
            'Für den Teilnahmestatus „%s“ ist keine gültige Farbe gespeichert.',
            (string) ($status['label'] ?? $name)
          );
        }
        $color = DefaultConfiguration::participantStatusColor($name, $class);
      }

      $defaults[$element] = strtoupper($color);
      $rows[] = [
        'name' => $name,
        'label' => (string) ($status['label'] ?? $name),
        'class' => $class,
        'isActive' => !empty($status['is_active']),
        'colorElement' => $element,
      ];
    }

    if ($rows === []) {
      $errors[] = 'Es wurden keine Teilnahmestatus gefunden.';
    }

    return [
      'rows' => $rows,
      'defaults' => $defaults,
      'errors' => array_values(array_unique($errors)),
    ];
  }

  /**
   * @param array<string, mixed> $values
   * @return array{json: string, count: int}
   */
  private static function validateParticipantStatusColors(
    array $values
  ): array {
    $colors = [];
    foreach (
      ParticipantStatusType::get(FALSE)
        ->addSelect('id', 'name', 'label')
        ->execute() as $status
    ) {
      $element = 'status_color_' . (string) $status['id'];
      $color = strtoupper(trim((string) ($values[$element] ?? '')));
      if (!preg_match('/^#[0-9A-F]{6}$/D', $color)) {
        throw new \RuntimeException(sprintf(
          'Bitte wählen Sie für den Teilnahmestatus „%s“ eine gültige Farbe aus.',
          (string) ($status['label'] ?? $status['name'])
        ));
      }
      $colors[(string) $status['name']] = $color;
    }

    if ($colors === []) {
      throw new \RuntimeException(
        'Es wurden keine Teilnahmestatus für die Farbzuordnung gefunden.'
      );
    }

    try {
      $json = json_encode(
        $colors,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
      );
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException(
        'Die Statusfarben konnten nicht gespeichert werden.',
        0,
        $exception
      );
    }

    return [
      'json' => $json,
      'count' => count($colors),
    ];
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
    $eventInfoGroupName = self::setting(
      'moeve_crewing_event_info_group',
      DefaultConfiguration::EVENT_INFO_GROUP
    );

    $optionGroupId = self::optionGroupId($optionGroupName);
    $individualGroupId = self::customGroupId($individualGroupName);
    $participantGroupId = self::customGroupId($participantGroupName);
    $eventGroupId = self::customGroupId($eventGroupName);
    $eventInfoGroupId = self::customGroupId($eventInfoGroupName);

    return [
      'role_option_group_id' => $optionGroupId,
      'candidate_role_id' => self::optionValueId(
        $optionGroupId,
        self::setting(
          'moeve_crewing_candidate_role',
          DefaultConfiguration::CANDIDATE_ROLE
        )
      ),
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
      'event_info_group_id' => $eventInfoGroupId,
      'event_number_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_event_number_field',
          DefaultConfiguration::EVENT_NUMBER_FIELD
        )
      ),
      'departure_port_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_departure_port_field',
          DefaultConfiguration::DEPARTURE_PORT_FIELD
        )
      ),
      'route_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_route_field',
          DefaultConfiguration::ROUTE_FIELD
        )
      ),
      'arrival_port_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_arrival_port_field',
          DefaultConfiguration::ARRIVAL_PORT_FIELD
        )
      ),
      'crew_on_board_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_crew_on_board_field',
          DefaultConfiguration::CREW_ON_BOARD_FIELD
        )
      ),
      'crew_off_board_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_crew_off_board_field',
          DefaultConfiguration::CREW_OFF_BOARD_FIELD
        )
      ),
      'organizer_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_organizer_field',
          DefaultConfiguration::ORGANIZER_FIELD
        )
      ),
      'comment_field_id' => self::customFieldId(
        $eventInfoGroupId,
        self::setting(
          'moeve_crewing_comment_field',
          DefaultConfiguration::COMMENT_FIELD
        )
      ),
      'event_group_id' => $eventGroupId,
    ];
  }

  private static function optionValueId(
    string $optionGroupId,
    string $name
  ): string {
    if ($optionGroupId === '') {
      return '';
    }

    $record = OptionValue::get(FALSE)
      ->addSelect('id')
      ->addWhere('option_group_id', '=', (int) $optionGroupId)
      ->addWhere('name', '=', $name)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    return $record ? (string) $record['id'] : '';
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
