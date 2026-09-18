<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Application;

use Civi\Api4\Event;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Participant;
use Civi\Api4\ParticipantStatusType;
use Civi\MoeveCrewing\Configuration\DefaultConfiguration;

/**
 * Loads and safely updates one crewing application.
 */
final class ApplicationManager {

  /**
   * @return array<string, mixed>
   */
  public function getFormData(int $participantId): array {
    if ($participantId < 1) {
      throw new \RuntimeException('Es wurde keine gültige Teilnahme angegeben.');
    }

    $roles = $this->getRoleMapping();
    $roleConfiguration = $this->getRoleConfiguration($roles);
    $statuses = $this->getStatuses();
    $participantGroup = $this->setting(
      'moeve_crewing_participant_group',
      DefaultConfiguration::PARTICIPANT_GROUP
    );
    $preferencesField = $this->setting(
      'moeve_crewing_preferences_field',
      DefaultConfiguration::PREFERENCES_FIELD
    );
    $preferencesKey = $participantGroup . '.' . $preferencesField;

    $participant = Participant::get(FALSE)
      ->addSelect(
        'id',
        'event_id',
        'contact_id',
        'contact_id.display_name',
        'status_id',
        'role_id',
        'register_date',
        $preferencesKey
      )
      ->addWhere('id', '=', $participantId)
      ->addWhere('is_test', '=', FALSE)
      ->execute()
      ->first();
    if (!$participant) {
      throw new \RuntimeException(
        'Die ausgewählte Teilnahme wurde nicht gefunden.'
      );
    }

    $eventId = (int) ($participant['event_id'] ?? 0);
    $event = Event::get(FALSE)
      ->addSelect('id', 'title', 'start_date', 'end_date')
      ->addWhere('id', '=', $eventId)
      ->execute()
      ->first();
    if (!$event) {
      throw new \RuntimeException(
        'Die Veranstaltung dieser Teilnahme wurde nicht gefunden.'
      );
    }

    $roleValues = $this->normalizeMultiValue(
      $participant['role_id'] ?? NULL
    );
    $preferenceValues = $this->normalizeMultiValue(
      $participant[$preferencesKey] ?? NULL
    );
    $assignedRoles = [];
    $currentChoices = [];
    foreach ($roleValues as $value) {
      if (isset($roleConfiguration['choiceByValue'][$value])) {
        $choice = $roleConfiguration['choiceByValue'][$value];
        $currentChoices[] = $choice;
        $assignedRoles[] = $roleConfiguration['choices'][$choice];
      }
    }

    $isCandidate = in_array(
      $roleConfiguration['candidateValue'],
      $roleValues,
      TRUE
    );
    if ($currentChoices !== []) {
      $currentRoleChoice = (string) reset($currentChoices);
    }
    elseif ($isCandidate) {
      $currentRoleChoice = 'candidate';
    }
    else {
      $currentRoleChoice = 'candidate';
    }

    $preferences = [];
    foreach ($preferenceValues as $value) {
      if (isset($roleConfiguration['labelByValue'][$value])) {
        $preferences[] = $roleConfiguration['labelByValue'][$value];
      }
    }

    $statusId = (int) ($participant['status_id'] ?? 0);
    $status = $statuses['byId'][$statusId] ?? [
      'id' => $statusId,
      'name' => 'Unknown',
      'label' => 'Unbekannt',
      'class' => '',
      'isActive' => FALSE,
      'color' => '#64748B',
    ];
    if (!isset($statuses['options'][$statusId])) {
      $statuses['options'][$statusId] = (string) $status['label'];
    }

    $contactId = (int) ($participant['contact_id'] ?? 0);
    $startDate = (string) ($event['start_date'] ?? '');
    $year = preg_match('/^(\d{4})-/', $startDate, $matches)
      ? (int) $matches[1]
      : (int) date('Y');

    return [
      'participantId' => $participantId,
      'contactId' => $contactId,
      'displayName' => (string) (
        $participant['contact_id.display_name']
        ?? sprintf('Kontakt %d', $contactId)
      ),
      'contactUrl' => \CRM_Utils_System::url(
        'civicrm/contact/view',
        'reset=1&cid=' . $contactId
      ),
      'eventId' => $eventId,
      'eventTitle' => (string) ($event['title'] ?? ''),
      'eventDateLabel' => $this->formatDateRange(
        $startDate,
        (string) ($event['end_date'] ?? '')
      ),
      'eventUrl' => \CRM_Utils_System::url(
        'civicrm/event/manage/settings',
        'reset=1&action=update&id=' . $eventId
      ),
      'registerDateLabel' => $this->formatDateTime(
        (string) ($participant['register_date'] ?? '')
      ),
      'status' => $status,
      'statusOptions' => $statuses['options'],
      'roleOptions' => $roleConfiguration['choices'],
      'currentRoleChoice' => $currentRoleChoice,
      'currentRoleValues' => $roleValues,
      'isCandidate' => $isCandidate,
      'preferences' => array_values(array_unique($preferences)),
      'assignedRoles' => array_values(array_unique($assignedRoles)),
      'multipleRoleWarning' => count($currentChoices) > 1,
      'year' => $year,
      'returnUrl' => $this->overviewUrl($year, $eventId),
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public function saveDecision(
    int $participantId,
    int $statusId,
    string $roleChoice
  ): array {
    $data = $this->getFormData($participantId);
    if (!isset($data['statusOptions'][$statusId])) {
      throw new \RuntimeException(
        'Der ausgewählte Teilnahmestatus ist nicht mehr verfügbar.'
      );
    }
    if (!isset($data['roleOptions'][$roleChoice])) {
      throw new \RuntimeException(
        'Die ausgewählte Crewing-Funktion ist nicht mehr verfügbar.'
      );
    }

    $roles = $this->getRoleMapping();
    $roleConfiguration = $this->getRoleConfiguration($roles);
    $controlledValues = array_merge(
      [$roleConfiguration['candidateValue']],
      array_map('strval', array_keys($roleConfiguration['choiceByValue']))
    );
    $newRoleValues = [];
    foreach ($data['currentRoleValues'] as $value) {
      if (!in_array($value, $controlledValues, TRUE)) {
        $newRoleValues[] = (string) $value;
      }
    }

    if ($roleChoice === 'candidate') {
      $newRoleValues[] = $roleConfiguration['candidateValue'];
    }
    else {
      $selectedValue = $roleConfiguration['valueByChoice'][$roleChoice] ?? '';
      if ($selectedValue === '') {
        throw new \RuntimeException(
          'Die ausgewählte Crewing-Funktion konnte nicht aufgelöst werden.'
        );
      }
      $newRoleValues[] = $selectedValue;
    }
    $newRoleValues = array_values(array_unique($newRoleValues, SORT_STRING));

    Participant::update(FALSE)
      ->addValue('status_id', $statusId)
      ->addValue('role_id', $newRoleValues)
      ->addWhere('id', '=', $participantId)
      ->execute();

    \Civi::log()->info(
      'Möwe Crewing application {participantId} updated: status {statusId}, role {roleChoice}',
      [
        'participantId' => $participantId,
        'statusId' => $statusId,
        'roleChoice' => $roleChoice,
      ]
    );

    return $this->getFormData($participantId);
  }

  /**
   * @param array<string, array<string, string>> $roles
   * @return array<string, mixed>
   */
  private function getRoleConfiguration(array $roles): array {
    $groupName = $this->setting(
      'moeve_crewing_role_option_group',
      DefaultConfiguration::ROLE_OPTION_GROUP
    );
    $candidateName = $this->setting(
      'moeve_crewing_candidate_role',
      DefaultConfiguration::CANDIDATE_ROLE
    );

    $group = OptionGroup::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', $groupName)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if (!$group) {
      throw new \RuntimeException(
        'Die konfigurierte Crewing-Rollengruppe wurde nicht gefunden.'
      );
    }

    $byName = [];
    foreach (
      OptionValue::get(FALSE)
        ->addSelect('name', 'label', 'value', 'weight')
        ->addWhere('option_group_id', '=', (int) $group['id'])
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('weight', 'ASC')
        ->execute() as $option
    ) {
      $byName[(string) $option['name']] = [
        'name' => (string) $option['name'],
        'label' => (string) ($option['label'] ?? $option['name']),
        'value' => (string) $option['value'],
      ];
    }
    if (!isset($byName[$candidateName])) {
      throw new \RuntimeException(
        'Die konfigurierte Rolle für potentielle Crewmitglieder wurde nicht gefunden.'
      );
    }

    $choices = [
      'candidate' => 'Bewerbung offen – potentielles Crewmitglied',
    ];
    $choiceByValue = [];
    $valueByChoice = [];
    $labelByValue = [];
    foreach ($roles as $technicalName => $role) {
      $option = $byName[$technicalName] ?? NULL;
      if (!$option) {
        continue;
      }
      $choice = 'role:' . $technicalName;
      $value = (string) $option['value'];
      $label = (string) $role['label'];
      $choices[$choice] = $label;
      $choiceByValue[$value] = $choice;
      $valueByChoice[$choice] = $value;
      $labelByValue[$value] = $label;
    }
    if (count($choices) < 2) {
      throw new \RuntimeException(
        'Es sind keine zuweisbaren Crewing-Funktionen konfiguriert.'
      );
    }

    return [
      'candidateValue' => (string) $byName[$candidateName]['value'],
      'choices' => $choices,
      'choiceByValue' => $choiceByValue,
      'valueByChoice' => $valueByChoice,
      'labelByValue' => $labelByValue,
    ];
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private function getRoleMapping(): array {
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
        'Die gespeicherte Crewing-Rollenzuordnung enthält ungültiges JSON.',
        0,
        $exception
      );
    }
    if (!is_array($roles) || $roles === []) {
      throw new \RuntimeException(
        'Es sind keine Crewing-Funktionen konfiguriert.'
      );
    }
    return $roles;
  }

  /**
   * @return array{
   *   byId: array<int, array<string, mixed>>,
   *   options: array<int, string>
   * }
   */
  private function getStatuses(): array {
    $configuredColors = [];
    $json = trim(
      (string) \Civi::settings()->get(
        'moeve_crewing_participant_status_colors'
      )
    );
    if ($json !== '') {
      try {
        $decoded = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
          $configuredColors = $decoded;
        }
      }
      catch (\JsonException) {
        $configuredColors = [];
      }
    }

    $byId = [];
    $options = [];
    foreach (
      ParticipantStatusType::get(FALSE)
        ->addSelect('id', 'name', 'label', 'class', 'is_active', 'weight')
        ->addOrderBy('weight', 'ASC')
        ->execute() as $status
    ) {
      $id = (int) $status['id'];
      $name = (string) $status['name'];
      $class = (string) ($status['class'] ?? '');
      $color = $configuredColors[$name] ?? NULL;
      if (!is_string($color) || !preg_match('/^#[0-9A-Fa-f]{6}$/D', $color)) {
        $color = DefaultConfiguration::participantStatusColor($name, $class);
      }
      $isActive = !empty($status['is_active']);
      $label = (string) ($status['label'] ?? $name);
      $byId[$id] = [
        'id' => $id,
        'name' => $name,
        'label' => $label,
        'class' => $class,
        'isActive' => $isActive,
        'color' => strtoupper($color),
      ];
      $options[$id] = $label . ($isActive ? '' : ' (inaktiv)');
    }
    return [
      'byId' => $byId,
      'options' => $options,
    ];
  }

  /**
   * @return array<int, string>
   */
  private function normalizeMultiValue(mixed $value): array {
    if ($value === NULL || $value === '' || $value === FALSE) {
      return [];
    }
    if (is_array($value)) {
      $normalized = [];
      foreach ($value as $item) {
        foreach ($this->normalizeMultiValue($item) as $part) {
          $normalized[] = (string) $part;
        }
      }
      return array_values(array_unique($normalized, SORT_STRING));
    }
    if (is_int($value) || is_float($value)) {
      return [(string) $value];
    }

    $stringValue = trim((string) $value);
    if ($stringValue === '') {
      return [];
    }
    $parts = preg_split('/[\x01,;|]+/', trim($stringValue, "\x01,;| "));
    $normalized = [];
    foreach ($parts ?: [] as $part) {
      $part = trim($part);
      if ($part !== '') {
        $normalized[] = $part;
      }
    }
    return array_values(array_unique($normalized, SORT_STRING));
  }

  private function formatDateRange(string $start, string $end): string {
    try {
      $startDate = new \DateTimeImmutable($start);
      $endDate = $end !== '' ? new \DateTimeImmutable($end) : NULL;
    }
    catch (\Throwable) {
      return '';
    }
    if (!$endDate || $startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
      return $startDate->format('d.m.Y');
    }
    return sprintf(
      '%s–%s',
      $startDate->format('d.m.'),
      $endDate->format('d.m.Y')
    );
  }

  private function formatDateTime(string $value): string {
    if ($value === '') {
      return '';
    }
    try {
      return (new \DateTimeImmutable($value))->format('d.m.Y H:i');
    }
    catch (\Throwable) {
      return '';
    }
  }

  private function overviewUrl(int $year, int $eventId): string {
    return \CRM_Utils_System::url(
      'civicrm/moeve-crewing',
      http_build_query([
        'reset' => 1,
        'view' => 'applications',
        'year' => $year,
        'event_id' => $eventId,
      ], '', '&', PHP_QUERY_RFC3986)
    );
  }

  private function setting(string $name, string $fallback): string {
    $value = trim((string) \Civi::settings()->get($name));
    return $value !== '' ? $value : $fallback;
  }

}
