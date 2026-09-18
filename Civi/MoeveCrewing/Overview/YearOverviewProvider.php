<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Overview;

use Civi\Api4\Contact;
use Civi\Api4\Event;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Participant;
use Civi\Api4\ParticipantStatusType;
use Civi\MoeveCrewing\Configuration\DefaultConfiguration;

/**
 * Builds the read-only data model for the crewing year overview.
 */
final class YearOverviewProvider {

  /**
   * @return array<int, int>
   */
  public function getAvailableYears(): array {
    $years = [];

    foreach (
      Event::get(FALSE)
        ->addSelect('start_date')
        ->addWhere('is_template', '=', FALSE)
        ->execute() as $event
    ) {
      $startDate = (string) ($event['start_date'] ?? '');
      if (preg_match('/^(\d{4})-/', $startDate, $matches)) {
        $years[(int) $matches[1]] = TRUE;
      }
    }

    if ($years === []) {
      $years[(int) date('Y')] = TRUE;
    }

    $result = array_map('intval', array_keys($years));
    sort($result, SORT_NUMERIC);
    return $result;
  }

  /**
   * @return array<string, mixed>
   */
  public function load(int $year): array {
    $roles = $this->getRoleMapping();
    $roleOptions = $this->getRoleOptions();
    $statuses = $this->getParticipantStatuses();
    $events = $this->getEvents($year, $roles);
    $participants = $this->getParticipants(
      array_column($events, 'id'),
      $statuses['byId'],
      $roleOptions
    );

    $roleRows = $this->buildRoleRows(
      $roles,
      $roleOptions,
      $events,
      $participants
    );
    $peopleRows = $this->buildPeopleRows(
      $events,
      $participants,
      $roleOptions
    );

    $attentionCount = 0;
    $coveredCount = 0;
    foreach ($roleRows as $roleRow) {
      foreach ($roleRow['cells'] as $cell) {
        if ($cell['state'] === 'covered') {
          $coveredCount++;
        }
        elseif (in_array($cell['state'], ['attention', 'gap'], TRUE)) {
          $attentionCount++;
        }
      }
    }

    return [
      'events' => $events,
      'roleRows' => $roleRows,
      'peopleRows' => $peopleRows,
      'statusLegend' => $statuses['legend'],
      'summary' => [
        'eventCount' => count($events),
        'roleCount' => count($roleRows),
        'peopleCount' => count($peopleRows),
        'coveredCount' => $coveredCount,
        'attentionCount' => $attentionCount,
      ],
    ];
  }

  /**
   * Build the operational planning view for each voyage.
   *
   * @return array<string, mixed>
   */
  public function loadVoyages(int $year, int $eventFilter = 0): array {
    $roles = $this->getRoleMapping();
    $roleOptions = $this->getRoleOptions();
    $statuses = $this->getParticipantStatuses();
    $allEvents = $this->getEvents($year, $roles);
    $participants = $this->getParticipants(
      array_column($allEvents, 'id'),
      $statuses['byId'],
      $roleOptions
    );

    $eventOptions = [];
    foreach ($allEvents as $event) {
      $label = (string) $event['title'];
      if ((string) $event['number'] !== '') {
        $label = (string) $event['number'] . ' – ' . $label;
      }
      if ((string) $event['dateLabel'] !== '') {
        $label .= ' · ' . (string) $event['dateLabel'];
      }
      $eventOptions[] = [
        'id' => (int) $event['id'],
        'label' => $label,
      ];
    }

    $participantsByEvent = [];
    foreach ($participants as $participant) {
      $participantsByEvent[(int) $participant['eventId']][] = $participant;
    }

    $roleByValue = [];
    foreach ($roles as $technicalName => $role) {
      $option = $roleOptions['byName'][$technicalName] ?? NULL;
      if ($option) {
        $roleByValue[(string) $option['value']] = [
          'name' => (string) $technicalName,
          'label' => (string) $role['label'],
        ];
      }
    }

    $voyages = [];
    $totalRequired = 0;
    $totalConfirmed = 0;
    $totalOpen = 0;
    $applicationIds = [];
    foreach ($allEvents as $event) {
      $eventId = (int) $event['id'];
      if ($eventFilter > 0 && $eventId !== $eventFilter) {
        continue;
      }
      $eventParticipants = $participantsByEvent[$eventId] ?? [];
      $demandRows = [];

      foreach ($roles as $technicalName => $role) {
        $option = $roleOptions['byName'][$technicalName] ?? NULL;
        if (!$option) {
          continue;
        }

        $record = $event['record'];
        $group = (string) $event['demandGroup'];
        $enabled = $this->isTruthy(
          $record[$group . '.' . $role['enabled_field']] ?? FALSE
        );
        if (!$enabled) {
          continue;
        }

        $minimumValue = $record[$group . '.' . $role['minimum_field']] ?? NULL;
        $required = max(1, (int) ($minimumValue ?? 1));
        $roleValue = (string) $option['value'];
        $confirmed = [];
        $provisional = [];
        $applications = [];

        foreach ($eventParticipants as $participant) {
          $statusClass = (string) $participant['status']['class'];
          if ($statusClass === 'Negative') {
            continue;
          }

          $isAssigned = in_array(
            $roleValue,
            $participant['roleValues'],
            TRUE
          );
          if ($isAssigned) {
            if ($statusClass === 'Positive') {
              $confirmed[] = $this->participantDisplay($participant);
            }
            else {
              $provisional[] = $this->participantDisplay($participant);
            }
          }
          elseif (
            $participant['isCandidate']
            && in_array(
              $roleValue,
              $participant['preferenceValues'],
              TRUE
            )
          ) {
            $applications[] = $this->participantDisplay($participant);
          }
        }

        $confirmedCount = count($confirmed);
        $provisionalCount = count($provisional);
        $applicationCount = count($applications);
        if ($confirmedCount >= $required) {
          $state = 'covered';
          $stateLabel = 'Bedarf gedeckt';
        }
        elseif ($provisionalCount > 0 || $applicationCount > 0) {
          $state = 'attention';
          $stateLabel = 'Entscheidung erforderlich';
        }
        else {
          $state = 'gap';
          $stateLabel = 'Offener Bedarf';
        }

        $open = max(0, $required - $confirmedCount);
        $totalRequired += $required;
        $totalConfirmed += $confirmedCount;
        $totalOpen += $open;
        $demandRows[] = [
          'name' => (string) $technicalName,
          'label' => (string) $role['label'],
          'required' => $required,
          'confirmedCount' => $confirmedCount,
          'provisionalCount' => $provisionalCount,
          'applicationCount' => $applicationCount,
          'openCount' => $open,
          'confirmed' => $confirmed,
          'provisional' => $provisional,
          'applications' => $applications,
          'state' => $state,
          'stateLabel' => $stateLabel,
        ];
      }

      $crew = [];
      $openApplications = [];
      foreach ($eventParticipants as $participant) {
        $assignedRoles = [];
        foreach ($participant['roleValues'] as $value) {
          if (isset($roleByValue[$value])) {
            $assignedRoles[] = $roleByValue[$value]['label'];
          }
        }
        if (
          $assignedRoles !== []
          && (string) $participant['status']['class'] !== 'Negative'
        ) {
          $crewMember = $this->participantDisplay($participant);
          $crewMember['roles'] = array_values(array_unique($assignedRoles));
          $crew[] = $crewMember;
        }

        if (
          $participant['isCandidate']
          && (string) $participant['status']['class'] !== 'Negative'
        ) {
          $preferences = [];
          foreach ($participant['preferenceValues'] as $value) {
            if (isset($roleByValue[$value])) {
              $preferences[] = $roleByValue[$value]['label'];
            }
          }
          $application = $this->participantDisplay($participant);
          $application['preferences'] = array_values(
            array_unique($preferences)
          );
          $openApplications[] = $application;
          $applicationIds[(int) $participant['id']] = TRUE;
        }
      }

      usort(
        $crew,
        static fn(array $left, array $right): int => strnatcasecmp(
          (string) $left['displayName'],
          (string) $right['displayName']
        )
      );
      usort(
        $openApplications,
        static fn(array $left, array $right): int => strnatcasecmp(
          (string) $left['displayName'],
          (string) $right['displayName']
        )
      );

      $voyages[] = [
        'event' => $event,
        'demands' => $demandRows,
        'crew' => $crew,
        'applications' => $openApplications,
        'summary' => [
          'crewCount' => count($crew),
          'requiredCount' => array_sum(array_column($demandRows, 'required')),
          'confirmedCount' => array_sum(
            array_column($demandRows, 'confirmedCount')
          ),
          'openCount' => array_sum(array_column($demandRows, 'openCount')),
          'applicationCount' => count($openApplications),
        ],
      ];
    }

    return [
      'voyageEvents' => $eventOptions,
      'voyages' => $voyages,
      'voyageSummary' => [
        'eventCount' => count($voyages),
        'requiredCount' => $totalRequired,
        'confirmedCount' => $totalConfirmed,
        'openCount' => $totalOpen,
        'applicationCount' => count($applicationIds),
      ],
      'selectedEventId' => $eventFilter,
    ];
  }

  /**
   * Build the read-only application workspace.
   *
   * @return array<string, mixed>
   */
  public function loadApplications(
    int $year,
    int $eventFilter = 0,
    int $statusFilter = 0
  ): array {
    $roles = $this->getRoleMapping();
    $roleOptions = $this->getRoleOptions();
    $statuses = $this->getParticipantStatuses();
    $events = $this->getEvents($year, $roles);
    $participants = $this->getParticipants(
      array_column($events, 'id'),
      $statuses['byId'],
      $roleOptions
    );

    $eventsById = [];
    $eventOptions = [];
    foreach ($events as $event) {
      $eventId = (int) $event['id'];
      $eventsById[$eventId] = $event;
      $optionLabel = (string) $event['title'];
      if ((string) $event['number'] !== '') {
        $optionLabel = (string) $event['number'] . ' – ' . $optionLabel;
      }
      if ((string) $event['dateLabel'] !== '') {
        $optionLabel .= ' · ' . (string) $event['dateLabel'];
      }
      $eventOptions[] = [
        'id' => $eventId,
        'label' => $optionLabel,
      ];
    }

    $configuredRoleValues = [];
    foreach ($roles as $technicalName => $role) {
      $option = $roleOptions['byName'][$technicalName] ?? NULL;
      if ($option) {
        $configuredRoleValues[(string) $option['value']] =
          (string) $role['label'];
      }
    }

    $rows = [];
    $openCount = 0;
    $assignedCount = 0;
    $negativeCount = 0;
    foreach ($participants as $participant) {
      $eventId = (int) $participant['eventId'];
      $statusId = (int) $participant['status']['id'];
      if ($eventFilter > 0 && $eventId !== $eventFilter) {
        continue;
      }
      if ($statusFilter > 0 && $statusId !== $statusFilter) {
        continue;
      }

      $event = $eventsById[$eventId] ?? NULL;
      if (!$event) {
        continue;
      }

      $preferenceLabels = [];
      foreach ($participant['preferenceValues'] as $value) {
        if (isset($configuredRoleValues[$value])) {
          $preferenceLabels[] = $configuredRoleValues[$value];
        }
      }
      $preferenceLabels = array_values(array_unique($preferenceLabels));

      $assignedRoleLabels = [];
      foreach ($participant['roleValues'] as $value) {
        if (isset($configuredRoleValues[$value])) {
          $assignedRoleLabels[] = $configuredRoleValues[$value];
        }
      }
      $assignedRoleLabels = array_values(array_unique($assignedRoleLabels));

      if (
        !$participant['isCandidate']
        && $preferenceLabels === []
        && $assignedRoleLabels === []
      ) {
        continue;
      }

      $statusClass = (string) $participant['status']['class'];
      if ($participant['isCandidate'] && $statusClass !== 'Negative') {
        $openCount++;
      }
      if ($assignedRoleLabels !== []) {
        $assignedCount++;
      }
      if ($statusClass === 'Negative') {
        $negativeCount++;
      }

      $contactId = (int) $participant['contactId'];
      $participantId = (int) $participant['id'];
      $rows[] = [
        'id' => $participantId,
        'eventId' => $eventId,
        'eventTitle' => (string) $event['title'],
        'eventDateLabel' => (string) $event['dateLabel'],
        'eventUrl' => (string) $event['url'],
        'contactId' => $contactId,
        'displayName' => (string) $participant['displayName'],
        'contactUrl' => \CRM_Utils_System::url(
          'civicrm/contact/view',
          'reset=1&cid=' . $contactId
        ),
        'participantUrl' => \CRM_Utils_System::url(
          'civicrm/contact/view/participant',
          http_build_query([
            'reset' => 1,
            'action' => 'update',
            'id' => $participantId,
            'cid' => $contactId,
            'context' => 'participant',
          ], '', '&', PHP_QUERY_RFC3986)
        ),
        'decisionUrl' => \CRM_Utils_System::url(
          'civicrm/moeve-crewing/application',
          'reset=1&id=' . $participantId
        ),
        'status' => $participant['status'],
        'isCandidate' => (bool) $participant['isCandidate'],
        'preferences' => $preferenceLabels,
        'assignedRoles' => $assignedRoleLabels,
        'registerDate' => (string) $participant['registerDate'],
        'registerDateLabel' => $this->formatDateTime(
          (string) $participant['registerDate']
        ),
      ];
    }

    usort(
      $rows,
      static function (array $left, array $right): int {
        $dateComparison = strcmp(
          (string) $right['registerDate'],
          (string) $left['registerDate']
        );
        if ($dateComparison !== 0) {
          return $dateComparison;
        }
        return strnatcasecmp(
          (string) $left['displayName'],
          (string) $right['displayName']
        );
      }
    );

    return [
      'applicationEvents' => $eventOptions,
      'applicationStatuses' => array_values($statuses['byId']),
      'applicationRows' => $rows,
      'applicationSummary' => [
        'resultCount' => count($rows),
        'openCount' => $openCount,
        'assignedCount' => $assignedCount,
        'negativeCount' => $negativeCount,
      ],
      'selectedEventId' => $eventFilter,
      'selectedStatusId' => $statusFilter,
    ];
  }

  /**
   * @return array<string, array{
   *   label: string,
   *   enabled_field: string,
   *   minimum_field: string
   * }>
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
        'Für die Jahresübersicht sind keine Crewing-Rollen konfiguriert.'
      );
    }

    return $roles;
  }

  /**
   * @return array{
   *   byName: array<string, array<string, mixed>>,
   *   byValue: array<string, array<string, mixed>>,
   *   candidateValue: string
   * }
   */
  private function getRoleOptions(): array {
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
      throw new \RuntimeException(sprintf(
        'Die konfigurierte Rollen-Optionsgruppe „%s“ wurde nicht gefunden.',
        $groupName
      ));
    }

    $byName = [];
    $byValue = [];
    foreach (
      OptionValue::get(FALSE)
        ->addSelect('id', 'name', 'label', 'value', 'weight')
        ->addWhere('option_group_id', '=', (int) $group['id'])
        ->addWhere('is_active', '=', TRUE)
        ->execute() as $option
    ) {
      $record = [
        'id' => (int) $option['id'],
        'name' => (string) $option['name'],
        'label' => (string) ($option['label'] ?? $option['name']),
        'value' => (string) $option['value'],
        'weight' => (int) ($option['weight'] ?? 0),
      ];
      $byName[$record['name']] = $record;
      $byValue[$record['value']] = $record;
    }

    if (!isset($byName[$candidateName])) {
      throw new \RuntimeException(sprintf(
        'Die konfigurierte Bewerberrolle „%s“ wurde nicht gefunden.',
        $candidateName
      ));
    }

    return [
      'byName' => $byName,
      'byValue' => $byValue,
      'candidateValue' => (string) $byName[$candidateName]['value'],
    ];
  }

  /**
   * @return array{
   *   byId: array<int, array<string, mixed>>,
   *   legend: array<int, array<string, mixed>>
   * }
   */
  private function getParticipantStatuses(): array {
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
    $legend = [];
    foreach (
      ParticipantStatusType::get(FALSE)
        ->addSelect('id', 'name', 'label', 'class', 'is_active', 'weight')
        ->addOrderBy('weight', 'ASC')
        ->execute() as $status
    ) {
      $name = (string) $status['name'];
      $class = (string) ($status['class'] ?? '');
      $color = $configuredColors[$name] ?? NULL;
      if (!is_string($color) || !preg_match('/^#[0-9A-Fa-f]{6}$/D', $color)) {
        $color = DefaultConfiguration::participantStatusColor($name, $class);
      }

      $record = [
        'id' => (int) $status['id'],
        'name' => $name,
        'label' => (string) ($status['label'] ?? $name),
        'class' => $class,
        'isActive' => !empty($status['is_active']),
        'color' => strtoupper($color),
      ];
      $byId[$record['id']] = $record;
      if ($record['isActive']) {
        $legend[] = $record;
      }
    }

    return [
      'byId' => $byId,
      'legend' => $legend,
    ];
  }

  /**
   * @param array<string, array<string, string>> $roles
   * @return array<int, array<string, mixed>>
   */
  private function getEvents(int $year, array $roles): array {
    $infoGroup = $this->setting(
      'moeve_crewing_event_info_group',
      DefaultConfiguration::EVENT_INFO_GROUP
    );
    $demandGroup = $this->setting(
      'moeve_crewing_event_group',
      DefaultConfiguration::EVENT_GROUP
    );
    $infoFields = [
      'number' => $this->setting(
        'moeve_crewing_event_number_field',
        DefaultConfiguration::EVENT_NUMBER_FIELD
      ),
      'departure' => $this->setting(
        'moeve_crewing_departure_port_field',
        DefaultConfiguration::DEPARTURE_PORT_FIELD
      ),
      'route' => $this->setting(
        'moeve_crewing_route_field',
        DefaultConfiguration::ROUTE_FIELD
      ),
      'arrival' => $this->setting(
        'moeve_crewing_arrival_port_field',
        DefaultConfiguration::ARRIVAL_PORT_FIELD
      ),
      'crewOnBoard' => $this->setting(
        'moeve_crewing_crew_on_board_field',
        DefaultConfiguration::CREW_ON_BOARD_FIELD
      ),
      'crewOffBoard' => $this->setting(
        'moeve_crewing_crew_off_board_field',
        DefaultConfiguration::CREW_OFF_BOARD_FIELD
      ),
      'organizer' => $this->setting(
        'moeve_crewing_organizer_field',
        DefaultConfiguration::ORGANIZER_FIELD
      ),
      'comment' => $this->setting(
        'moeve_crewing_comment_field',
        DefaultConfiguration::COMMENT_FIELD
      ),
    ];

    $select = [
      'id',
      'title',
      'start_date',
      'end_date',
      'is_active',
    ];
    foreach ($infoFields as $fieldName) {
      $select[] = $infoGroup . '.' . $fieldName;
    }
    foreach ($roles as $role) {
      $select[] = $demandGroup . '.' . $role['enabled_field'];
      $select[] = $demandGroup . '.' . $role['minimum_field'];
    }

    $records = Event::get(FALSE)
      ->addSelect(...array_values(array_unique($select)))
      ->addWhere('start_date', '>=', sprintf('%04d-01-01 00:00:00', $year))
      ->addWhere('start_date', '<', sprintf('%04d-01-01 00:00:00', $year + 1))
      ->addWhere('is_template', '=', FALSE)
      ->addOrderBy('start_date', 'ASC')
      ->execute();

    $organizerKey = $infoGroup . '.' . $infoFields['organizer'];
    $organizerIds = [];
    foreach ($records as $record) {
      $organizerId = $this->referenceId($record[$organizerKey] ?? NULL);
      if ($organizerId > 0) {
        $organizerIds[$organizerId] = TRUE;
      }
    }

    $organizerNames = [];
    if ($organizerIds !== []) {
      foreach (
        Contact::get(FALSE)
          ->addSelect('id', 'display_name')
          ->addWhere('id', 'IN', array_map('intval', array_keys($organizerIds)))
          ->execute() as $contact
      ) {
        $organizerNames[(int) $contact['id']] = (string) (
          $contact['display_name'] ?? ''
        );
      }
    }

    $events = [];
    foreach ($records as $record) {
      $eventId = (int) $record['id'];
      $title = trim((string) ($record['title'] ?? ''));
      $number = trim((string) (
        $record[$infoGroup . '.' . $infoFields['number']] ?? ''
      ));
      $departure = trim((string) (
        $record[$infoGroup . '.' . $infoFields['departure']] ?? ''
      ));
      $route = trim((string) (
        $record[$infoGroup . '.' . $infoFields['route']] ?? ''
      ));
      $arrival = trim((string) (
        $record[$infoGroup . '.' . $infoFields['arrival']] ?? ''
      ));
      $crewOnBoard = (string) (
        $record[$infoGroup . '.' . $infoFields['crewOnBoard']] ?? ''
      );
      $crewOffBoard = (string) (
        $record[$infoGroup . '.' . $infoFields['crewOffBoard']] ?? ''
      );
      $organizerId = $this->referenceId(
        $record[$organizerKey] ?? NULL
      );
      $comment = trim((string) (
        $record[$infoGroup . '.' . $infoFields['comment']] ?? ''
      ));

      $routeParts = array_values(array_filter(
        [$departure, $route, $arrival],
        static fn(string $value): bool => $value !== ''
      ));
      $dateLabel = $this->formatDateRange(
        (string) ($record['start_date'] ?? ''),
        (string) ($record['end_date'] ?? '')
      );
      $dateTimeLabel = $this->formatDateTimeRange(
        (string) ($record['start_date'] ?? ''),
        (string) ($record['end_date'] ?? '')
      );
      $routeLabel = implode(' – ', $routeParts);
      $tooltipParts = array_values(array_filter(
        [$number, $title, $dateLabel, $routeLabel],
        static fn(string $value): bool => $value !== ''
      ));

      $events[] = [
        'id' => $eventId,
        'title' => $title !== '' ? $title : sprintf('Veranstaltung %d', $eventId),
        'number' => $number,
        'dateLabel' => $dateLabel,
        'dateTimeLabel' => $dateTimeLabel,
        'startDate' => (string) ($record['start_date'] ?? ''),
        'endDate' => (string) ($record['end_date'] ?? ''),
        'departure' => $departure,
        'route' => $route,
        'arrival' => $arrival,
        'routeLabel' => $routeLabel,
        'crewOnBoard' => $crewOnBoard,
        'crewOnBoardLabel' => $this->formatDateTime($crewOnBoard),
        'crewOffBoard' => $crewOffBoard,
        'crewOffBoardLabel' => $this->formatDateTime($crewOffBoard),
        'organizerId' => $organizerId,
        'organizerName' => $organizerNames[$organizerId] ?? '',
        'organizerUrl' => $organizerId > 0
          ? \CRM_Utils_System::url(
            'civicrm/contact/view',
            'reset=1&cid=' . $organizerId
          )
          : '',
        'comment' => $comment,
        'tooltip' => implode(' · ', $tooltipParts),
        'isActive' => !empty($record['is_active']),
        'url' => $this->eventUrl($eventId),
        'record' => $record,
        'demandGroup' => $demandGroup,
      ];
    }

    return $events;
  }

  /**
   * @param array<int, int> $eventIds
   * @param array<int, array<string, mixed>> $statuses
   * @param array<string, mixed> $roleOptions
   * @return array<int, array<string, mixed>>
   */
  private function getParticipants(
    array $eventIds,
    array $statuses,
    array $roleOptions
  ): array {
    if ($eventIds === []) {
      return [];
    }

    $participantGroup = $this->setting(
      'moeve_crewing_participant_group',
      DefaultConfiguration::PARTICIPANT_GROUP
    );
    $preferencesField = $this->setting(
      'moeve_crewing_preferences_field',
      DefaultConfiguration::PREFERENCES_FIELD
    );
    $preferencesKey = $participantGroup . '.' . $preferencesField;

    $records = Participant::get(FALSE)
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
      ->addWhere('event_id', 'IN', $eventIds)
      ->addWhere('is_test', '=', FALSE)
      ->execute();

    $participants = [];
    foreach ($records as $record) {
      $statusId = (int) ($record['status_id'] ?? 0);
      $status = $statuses[$statusId] ?? [
        'id' => $statusId,
        'name' => 'Unknown',
        'label' => 'Unbekannt',
        'class' => '',
        'isActive' => FALSE,
        'color' => '#64748B',
      ];
      $roleValues = $this->normalizeMultiValue($record['role_id'] ?? NULL);
      $preferenceValues = $this->normalizeMultiValue(
        $record[$preferencesKey] ?? NULL
      );
      $roleLabels = [];
      foreach ($roleValues as $roleValue) {
        if (isset($roleOptions['byValue'][$roleValue])) {
          $roleLabels[] = (string) $roleOptions['byValue'][$roleValue]['label'];
        }
      }

      $contactId = (int) ($record['contact_id'] ?? 0);
      $participants[] = [
        'id' => (int) $record['id'],
        'eventId' => (int) $record['event_id'],
        'contactId' => $contactId,
        'displayName' => (string) (
          $record['contact_id.display_name']
          ?? sprintf('Kontakt %d', $contactId)
        ),
        'status' => $status,
        'roleValues' => $roleValues,
        'roleLabels' => array_values(array_unique($roleLabels)),
        'preferenceValues' => $preferenceValues,
        'registerDate' => (string) ($record['register_date'] ?? ''),
        'isCandidate' => in_array(
          (string) $roleOptions['candidateValue'],
          $roleValues,
          TRUE
        ),
      ];
    }

    return $participants;
  }

  /**
   * @param array<string, array<string, string>> $roles
   * @param array<string, mixed> $roleOptions
   * @param array<int, array<string, mixed>> $events
   * @param array<int, array<string, mixed>> $participants
   * @return array<int, array<string, mixed>>
   */
  private function buildRoleRows(
    array $roles,
    array $roleOptions,
    array $events,
    array $participants
  ): array {
    $participantsByEvent = [];
    foreach ($participants as $participant) {
      $participantsByEvent[$participant['eventId']][] = $participant;
    }

    $rows = [];
    foreach ($roles as $technicalName => $role) {
      $option = $roleOptions['byName'][$technicalName] ?? NULL;
      if (!$option) {
        continue;
      }
      $roleValue = (string) $option['value'];
      $cells = [];

      foreach ($events as $event) {
        $record = $event['record'];
        $group = (string) $event['demandGroup'];
        $enabled = $this->isTruthy(
          $record[$group . '.' . $role['enabled_field']] ?? FALSE
        );
        $minimumValue = $record[$group . '.' . $role['minimum_field']] ?? NULL;
        $required = $enabled ? max(1, (int) ($minimumValue ?? 1)) : 0;

        $confirmed = [];
        $provisional = [];
        $applications = [];
        foreach ($participantsByEvent[$event['id']] ?? [] as $participant) {
          if ((string) $participant['status']['class'] === 'Negative') {
            continue;
          }

          if (in_array($roleValue, $participant['roleValues'], TRUE)) {
            if ((string) $participant['status']['class'] === 'Positive') {
              $confirmed[$participant['id']] = TRUE;
            }
            else {
              $provisional[$participant['id']] = TRUE;
            }
          }

          if (
            $participant['isCandidate']
            && in_array($roleValue, $participant['preferenceValues'], TRUE)
          ) {
            $applications[$participant['id']] = TRUE;
          }
        }

        $confirmedCount = count($confirmed);
        $provisionalCount = count($provisional);
        $applicationCount = count($applications);
        if (!$enabled) {
          $state = 'inactive';
          $stateLabel = 'Nicht benötigt';
        }
        elseif ($confirmedCount >= $required) {
          $state = 'covered';
          $stateLabel = 'Bedarf gedeckt';
        }
        elseif ($provisionalCount > 0 || $applicationCount > 0) {
          $state = 'attention';
          $stateLabel = 'Bewerbungen oder Vormerkungen vorhanden';
        }
        else {
          $state = 'gap';
          $stateLabel = 'Offener Bedarf';
        }

        $cells[] = [
          'state' => $state,
          'stateLabel' => $stateLabel,
          'required' => $required,
          'confirmed' => $confirmedCount,
          'provisional' => $provisionalCount,
          'applications' => $applicationCount,
          'url' => $event['url'],
          'title' => sprintf(
            '%s – %s: %d/%d bestätigt, %d vorgemerkt, %d Bewerbungen',
            $event['title'],
            $role['label'],
            $confirmedCount,
            $required,
            $provisionalCount,
            $applicationCount
          ),
        ];
      }

      $rows[] = [
        'name' => (string) $technicalName,
        'label' => (string) $role['label'],
        'cells' => $cells,
      ];
    }

    return $rows;
  }

  /**
   * @param array<int, array<string, mixed>> $events
   * @param array<int, array<string, mixed>> $participants
   * @param array<string, mixed> $roleOptions
   * @return array<int, array<string, mixed>>
   */
  private function buildPeopleRows(
    array $events,
    array $participants,
    array $roleOptions
  ): array {
    $latestByContactAndEvent = [];
    $people = [];
    foreach ($participants as $participant) {
      $contactId = (int) $participant['contactId'];
      $eventId = (int) $participant['eventId'];
      if ($contactId < 1) {
        continue;
      }

      $people[$contactId] = (string) $participant['displayName'];
      $existing = $latestByContactAndEvent[$contactId][$eventId] ?? NULL;
      if (!$existing || (int) $participant['id'] > (int) $existing['id']) {
        $latestByContactAndEvent[$contactId][$eventId] = $participant;
      }
    }

    natcasesort($people);
    $rows = [];
    foreach ($people as $contactId => $displayName) {
      $cells = [];
      foreach ($events as $event) {
        $participant = $latestByContactAndEvent[$contactId][$event['id']] ?? NULL;
        if (!$participant) {
          $cells[] = [
            'hasParticipation' => FALSE,
            'color' => '',
            'url' => $event['url'],
            'title' => sprintf(
              '%s – keine Bewerbung oder Teilnahme',
              $event['title']
            ),
          ];
          continue;
        }

        $roleLabels = $participant['roleLabels'];
        if ($participant['isCandidate'] && $participant['preferenceValues'] !== []) {
          $preferences = [];
          foreach ($participant['preferenceValues'] as $value) {
            if (isset($roleOptions['byValue'][$value])) {
              $preferences[] = (string) $roleOptions['byValue'][$value]['label'];
            }
          }
          if ($preferences !== []) {
            $roleLabels[] = 'Wunsch: ' . implode(', ', array_unique($preferences));
          }
        }

        $details = $roleLabels !== []
          ? implode(', ', array_unique($roleLabels))
          : 'keine Funktion';
        $cells[] = [
          'hasParticipation' => TRUE,
          'color' => (string) $participant['status']['color'],
          'url' => $event['url'],
          'title' => sprintf(
            '%s – %s: %s (%s)',
            $event['title'],
            $displayName,
            (string) $participant['status']['label'],
            $details
          ),
        ];
      }

      $rows[] = [
        'contactId' => (int) $contactId,
        'displayName' => $displayName,
        'contactUrl' => \CRM_Utils_System::url(
          'civicrm/contact/view',
          'reset=1&cid=' . (int) $contactId
        ),
        'cells' => $cells,
      ];
    }

    return $rows;
  }

  /**
   * @param array<string, mixed> $participant
   * @return array<string, mixed>
   */
  private function participantDisplay(array $participant): array {
    $participantId = (int) $participant['id'];
    $contactId = (int) $participant['contactId'];

    return [
      'id' => $participantId,
      'contactId' => $contactId,
      'displayName' => (string) $participant['displayName'],
      'status' => $participant['status'],
      'registerDateLabel' => $this->formatDateTime(
        (string) $participant['registerDate']
      ),
      'contactUrl' => \CRM_Utils_System::url(
        'civicrm/contact/view',
        'reset=1&cid=' . $contactId
      ),
      'participantUrl' => \CRM_Utils_System::url(
        'civicrm/contact/view/participant',
        http_build_query([
          'reset' => 1,
          'action' => 'update',
          'id' => $participantId,
          'cid' => $contactId,
          'context' => 'participant',
        ], '', '&', PHP_QUERY_RFC3986)
      ),
      'decisionUrl' => \CRM_Utils_System::url(
        'civicrm/moeve-crewing/application',
        'reset=1&id=' . $participantId
      ),
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

  private function isTruthy(mixed $value): bool {
    if (is_bool($value)) {
      return $value;
    }
    return in_array(
      strtolower(trim((string) $value)),
      ['1', 'true', 'yes', 'on'],
      TRUE
    );
  }

  private function formatDateRange(string $start, string $end): string {
    try {
      $startDate = new \DateTimeImmutable($start);
      $endDate = $end !== '' ? new \DateTimeImmutable($end) : NULL;
    }
    catch (\Throwable) {
      return '';
    }

    if (!$endDate) {
      return $startDate->format('d.m.Y');
    }
    if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
      return $startDate->format('d.m.Y');
    }
    return sprintf(
      '%s–%s',
      $startDate->format('d.m.'),
      $endDate->format('d.m.Y')
    );
  }

  private function formatDateTimeRange(string $start, string $end): string {
    try {
      $startDate = new \DateTimeImmutable($start);
      $endDate = $end !== '' ? new \DateTimeImmutable($end) : NULL;
    }
    catch (\Throwable) {
      return '';
    }

    if (!$endDate) {
      return $startDate->format('d.m.Y H:i');
    }
    if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
      return sprintf(
        '%s %s–%s',
        $startDate->format('d.m.Y'),
        $startDate->format('H:i'),
        $endDate->format('H:i')
      );
    }
    return sprintf(
      '%s – %s',
      $startDate->format('d.m.Y H:i'),
      $endDate->format('d.m.Y H:i')
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

  private function referenceId(mixed $value): int {
    if (is_array($value)) {
      foreach ($value as $part) {
        $referenceId = $this->referenceId($part);
        if ($referenceId > 0) {
          return $referenceId;
        }
      }
      return 0;
    }

    if (is_object($value)) {
      return $this->referenceId((array) $value);
    }

    return is_numeric($value) ? max(0, (int) $value) : 0;
  }

  private function eventUrl(int $eventId): string {
    return \CRM_Utils_System::url(
      'civicrm/event/manage/settings',
      'reset=1&action=update&id=' . $eventId
    );
  }

  private function setting(string $name, string $fallback): string {
    $value = trim((string) \Civi::settings()->get($name));
    return $value !== '' ? $value : $fallback;
  }

}
