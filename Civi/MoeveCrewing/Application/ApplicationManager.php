<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Application;

use Civi\Api4\Event;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Participant;
use Civi\Api4\ParticipantStatusType;
use Civi\MoeveCrewing\Configuration\DefaultConfiguration;
use Civi\MoeveCrewing\Configuration\ParticipantStatusWorkflow;

/**
 * Loads and safely updates one crewing application.
 */
final class ApplicationManager {

  private const DECLINED_SETTING = 'moeve_crewing_declined_participants';

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

    $declined = $this->getDeclinedParticipantIds();
    unset($declined[$participantId]);
    $this->saveDeclinedParticipantIds($declined);

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
   * Options and defaults used by the filtered bulk workspace.
   *
   * @return array<string, mixed>
   */
  public function getBulkConfiguration(): array {
    $roles = $this->getRoleMapping();
    $roleConfiguration = $this->getRoleConfiguration($roles);
    $statuses = $this->getStatuses();
    $workflow = ParticipantStatusWorkflow::resolve($statuses['byId']);
    $assignedNames = array_fill_keys(
      $workflow['assigned']['allowed'],
      TRUE
    );
    $declinedNames = array_fill_keys(
      $workflow['declined']['allowed'],
      TRUE
    );

    $assignedStatusOptions = [];
    $declinedStatusOptions = [];
    foreach ($statuses['byId'] as $id => $status) {
      $label = (string) $status['label'];
      if (!$status['isActive']) {
        $label .= ' (inaktiv)';
      }
      if (isset($assignedNames[(string) $status['name']])) {
        $assignedStatusOptions[(int) $id] = $label;
      }
      if (isset($declinedNames[(string) $status['name']])) {
        $declinedStatusOptions[(int) $id] = $label;
      }
    }

    $assignedDefault = (int) (
      $statuses['byName'][$workflow['assigned']['default']]['id']
      ?? array_key_first($assignedStatusOptions)
      ?? 0
    );
    $declinedDefault = (int) (
      $statuses['byName'][$workflow['declined']['default']]['id']
      ?? array_key_first($declinedStatusOptions)
      ?? 0
    );

    return [
      'roleOptions' => $roleConfiguration['choices'],
      'assignedStatusOptions' => $assignedStatusOptions,
      'declinedStatusOptions' => $declinedStatusOptions,
      'assignedStatusDefault' => $assignedDefault,
      'declinedStatusDefault' => $declinedDefault,
      'statusWorkflowErrors' => $workflow['errors'],
    ];
  }

  /**
   * Persist all staged role choices in the filtered result set.
   *
   * The pseudo-choice "declined" is deliberately not a CiviCRM participant
   * role. It restores the candidate role and stores an extension-owned marker
   * until another crew role is selected.
   *
   * @param array<int, int> $participantIds
   * @param array<int, string> $choices
   * @return array<string, int>
   */
  public function saveBulkAssignments(
    array $participantIds,
    array $choices
  ): array {
    $participantIds = $this->normalizeParticipantIds($participantIds);
    if ($participantIds === []) {
      throw new \RuntimeException(
        'Die gefilterte Liste enthält keine bearbeitbaren Teilnahmen.'
      );
    }

    $roles = $this->getRoleMapping();
    $roleConfiguration = $this->getRoleConfiguration($roles);
    $declined = $this->getDeclinedParticipantIds();
    $currentRoleValues = [];
    foreach (
      Participant::get(FALSE)
        ->addSelect('id', 'role_id')
        ->addWhere('id', 'IN', $participantIds)
        ->addWhere('is_test', '=', FALSE)
        ->execute() as $participant
    ) {
      $currentRoleValues[(int) $participant['id']] =
        $this->normalizeMultiValue($participant['role_id'] ?? NULL);
    }
    $updatedCount = 0;
    $assignedCount = 0;
    $declinedCount = 0;

    $transaction = new \CRM_Core_Transaction();
    try {
      foreach ($participantIds as $participantId) {
        $choice = trim((string) ($choices[$participantId] ?? ''));
        if ($choice === '') {
          continue;
        }

        if (!isset($currentRoleValues[$participantId])) {
          throw new \RuntimeException(sprintf(
            'Teilnahme %d wurde nicht gefunden.',
            $participantId
          ));
        }
        $roleChoice = $choice === 'declined' ? 'candidate' : $choice;
        if (!isset($roleConfiguration['choices'][$roleChoice])) {
          throw new \RuntimeException(sprintf(
            'Die ausgewählte Funktion für Teilnahme %d ist nicht verfügbar.',
            $participantId
          ));
        }

        $currentChoices = [];
        foreach ($currentRoleValues[$participantId] as $currentValue) {
          if (isset($roleConfiguration['choiceByValue'][$currentValue])) {
            $currentChoices[] = (string) (
              $roleConfiguration['choiceByValue'][$currentValue]
            );
          }
        }
        $isUnchanged = $choice === 'declined'
          ? isset($declined[$participantId])
          : !isset($declined[$participantId])
            && in_array($choice, $currentChoices, TRUE);
        if ($isUnchanged) {
          continue;
        }

        $newRoleValues = $this->roleValuesForChoice(
          $currentRoleValues[$participantId],
          $roleChoice,
          $roleConfiguration
        );
        Participant::update(FALSE)
          ->addValue('role_id', $newRoleValues)
          ->addWhere('id', '=', $participantId)
          ->execute();

        if ($choice === 'declined') {
          $declined[$participantId] = gmdate('c');
          $declinedCount++;
        }
        else {
          unset($declined[$participantId]);
          $assignedCount++;
        }
        $updatedCount++;
      }

      $this->saveDeclinedParticipantIds($declined);
      $transaction->commit();
    }
    catch (\Throwable $exception) {
      $transaction->rollback();
      throw $exception;
    }

    \Civi::log()->info(
      'Möwe Crewing bulk assignments saved: {updated} updated, {assigned} assigned, {declined} declined',
      [
        'updated' => $updatedCount,
        'assigned' => $assignedCount,
        'declined' => $declinedCount,
      ]
    );

    return [
      'updatedCount' => $updatedCount,
      'assignedCount' => $assignedCount,
      'declinedCount' => $declinedCount,
    ];
  }

  /**
   * Save the current staging and update one status for the selected group.
   *
   * @param array<int, int> $participantIds
   * @param array<int, string> $choices
   * @return array<string, int>
   */
  public function applyBulkStatus(
    array $participantIds,
    array $choices,
    int $statusId,
    string $target
  ): array {
    if (!in_array($target, ['assigned', 'declined'], TRUE)) {
      throw new \RuntimeException('Die Sammelaktion ist ungültig.');
    }

    $statuses = $this->getStatuses();
    $status = $statuses['byId'][$statusId] ?? NULL;
    if (!$status) {
      throw new \RuntimeException(
        'Der ausgewählte Teilnahmestatus ist nicht mehr verfügbar.'
      );
    }
    $workflow = ParticipantStatusWorkflow::resolve($statuses['byId']);
    if (!in_array(
      (string) $status['name'],
      $workflow[$target]['allowed'],
      TRUE
    )) {
      throw new \RuntimeException(
        $target === 'assigned'
          ? 'Dieser Teilnahmestatus ist nicht für zugewiesene Crew freigegeben.'
          : 'Dieser Teilnahmestatus ist nicht für abgesagte Bewerbungen freigegeben.'
      );
    }

    $participantIds = $this->normalizeParticipantIds($participantIds);
    $targetIds = [];
    foreach ($participantIds as $participantId) {
      $choice = trim((string) ($choices[$participantId] ?? ''));
      if (
        ($target === 'assigned' && str_starts_with($choice, 'role:'))
        || ($target === 'declined' && $choice === 'declined')
      ) {
        $targetIds[] = $participantId;
      }
    }
    if ($targetIds === []) {
      throw new \RuntimeException(
        $target === 'assigned'
          ? 'In der angezeigten Liste ist keine Crewing-Funktion zugewiesen.'
          : 'In der angezeigten Liste ist keine Bewerbung als abgesagt markiert.'
      );
    }

    $assignmentResult = $this->saveBulkAssignments(
      $participantIds,
      $choices
    );

    Participant::update(FALSE)
      ->addValue('status_id', $statusId)
      ->addWhere('id', 'IN', $targetIds)
      ->execute();

    \Civi::log()->info(
      'Möwe Crewing bulk status updated: {count} participants to status {statusId} ({target})',
      [
        'count' => count($targetIds),
        'statusId' => $statusId,
        'target' => $target,
      ]
    );

    return $assignmentResult + ['statusCount' => count($targetIds)];
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
    $byName = [];
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
      $byName[$name] = $byId[$id];
      $options[$id] = $label . ($isActive ? '' : ' (inaktiv)');
    }
    return [
      'byId' => $byId,
      'byName' => $byName,
      'options' => $options,
    ];
  }

  /**
   * @param array<int, string> $currentRoleValues
   * @param array<string, mixed> $roleConfiguration
   * @return array<int, string>
   */
  private function roleValuesForChoice(
    array $currentRoleValues,
    string $roleChoice,
    array $roleConfiguration
  ): array {
    $controlledValues = array_merge(
      [$roleConfiguration['candidateValue']],
      array_map('strval', array_keys($roleConfiguration['choiceByValue']))
    );
    $newRoleValues = [];
    foreach ($currentRoleValues as $value) {
      if (!in_array((string) $value, $controlledValues, TRUE)) {
        $newRoleValues[] = (string) $value;
      }
    }

    if ($roleChoice === 'candidate') {
      $newRoleValues[] = (string) $roleConfiguration['candidateValue'];
    }
    else {
      $selectedValue = (string) (
        $roleConfiguration['valueByChoice'][$roleChoice] ?? ''
      );
      if ($selectedValue === '') {
        throw new \RuntimeException(
          'Die ausgewählte Crewing-Funktion konnte nicht aufgelöst werden.'
        );
      }
      $newRoleValues[] = $selectedValue;
    }

    return array_values(array_unique($newRoleValues, SORT_STRING));
  }

  /**
   * @param array<int, int|string> $participantIds
   * @return array<int, int>
   */
  private function normalizeParticipantIds(array $participantIds): array {
    $normalized = [];
    foreach ($participantIds as $participantId) {
      $participantId = (int) $participantId;
      if ($participantId > 0) {
        $normalized[$participantId] = $participantId;
      }
    }
    if (count($normalized) > 500) {
      throw new \RuntimeException(
        'Pro Sammelaktion können höchstens 500 Teilnahmen bearbeitet werden.'
      );
    }
    return array_values($normalized);
  }

  /**
   * @return array<int, string>
   */
  private function getDeclinedParticipantIds(): array {
    $json = trim((string) \Civi::settings()->get(self::DECLINED_SETTING));
    if ($json === '') {
      return [];
    }
    try {
      $decoded = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      return [];
    }
    if (!is_array($decoded)) {
      return [];
    }

    $normalized = [];
    foreach ($decoded as $participantId => $timestamp) {
      $participantId = (int) $participantId;
      if ($participantId > 0) {
        $normalized[$participantId] = is_string($timestamp)
          ? $timestamp
          : '';
      }
    }
    return $normalized;
  }

  /**
   * @param array<int, string> $participantIds
   */
  private function saveDeclinedParticipantIds(array $participantIds): void {
    ksort($participantIds, SORT_NUMERIC);
    \Civi::settings()->set(
      self::DECLINED_SETTING,
      $participantIds === []
        ? ''
        : json_encode(
          $participantIds,
          JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        )
    );
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
      'civicrm/moeve-crewing/applications',
      http_build_query([
        'reset' => 1,
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
