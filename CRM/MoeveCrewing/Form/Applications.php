<?php

declare(strict_types = 1);

use Civi\MoeveCrewing\Application\ApplicationManager;
use Civi\MoeveCrewing\Overview\YearOverviewProvider;
use CRM_MoeveCrewing_ExtensionUtil as E;

/**
 * Filtered bulk workspace for crewing applications.
 */
class CRM_MoeveCrewing_Form_Applications extends CRM_Core_Form {

  private ApplicationManager $manager;

  private YearOverviewProvider $provider;

  /**
   * @var array<string, mixed>
   */
  private array $data = [];

  /**
   * @var array<string, mixed>
   */
  private array $bulkConfiguration = [];

  /**
   * @var array<int, int>
   */
  private array $availableYears = [];

  private int $selectedYear;

  private int $selectedEventId;

  private int $selectedStatusId;

  private bool $canManage = FALSE;

  private string $pageError = '';

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    parent::preProcess();

    if (!$this->canAccess()) {
      throw new CRM_Core_Exception(
        E::ts('Sie dürfen Möwe Crewing nicht aufrufen.')
      );
    }

    $this->canManage = $this->canManage();
    $this->provider = new YearOverviewProvider();
    $this->manager = new ApplicationManager();
    $this->availableYears = $this->provider->getAvailableYears();

    $requestedYear = $this->requestInteger('filter_year');
    if ($requestedYear < 1) {
      $requestedYear = $this->requestInteger('year');
    }
    $this->selectedYear = $requestedYear > 0
      ? $requestedYear
      : $this->defaultYear($this->availableYears);
    if (!in_array($this->selectedYear, $this->availableYears, TRUE)) {
      $this->availableYears[] = $this->selectedYear;
      sort($this->availableYears, SORT_NUMERIC);
    }

    $this->selectedEventId = max(
      0,
      $this->requestInteger('filter_event_id')
        ?: $this->requestInteger('event_id')
    );
    $this->selectedStatusId = max(
      0,
      $this->requestInteger('filter_status_id')
        ?: $this->requestInteger('status_id')
    );

    try {
      $this->data = $this->provider->loadApplications(
        $this->selectedYear,
        $this->selectedEventId,
        $this->selectedStatusId
      );
      $this->bulkConfiguration = $this->manager->getBulkConfiguration();
    }
    catch (\Throwable $exception) {
      Civi::log()->error(
        'Möwe Crewing bulk applications failed to load: {message}',
        [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]
      );
      $this->pageError = E::ts(
        'Die Bewerbungsübersicht konnte nicht geladen werden: %1',
        [1 => $exception->getMessage()]
      );
      $this->data = $this->emptyData();
      $this->bulkConfiguration = [
        'assignedStatusOptions' => [],
        'declinedStatusOptions' => [],
        'assignedStatusDefault' => 0,
        'declinedStatusDefault' => 0,
        'statusWorkflowErrors' => [],
      ];
    }
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('Möwe Crewing – Bewerbungen'));

    $yearOptions = [];
    foreach ($this->availableYears as $year) {
      $yearOptions[(int) $year] = (string) $year;
    }
    $eventOptions = [0 => E::ts('Alle Törns')];
    foreach ($this->data['applicationEvents'] as $event) {
      $eventOptions[(int) $event['id']] = (string) $event['label'];
    }
    $statusOptions = [0 => E::ts('Alle Status')];
    foreach ($this->data['applicationStatuses'] as $status) {
      $label = (string) $status['label'];
      if (empty($status['isActive'])) {
        $label .= E::ts(' (inaktiv)');
      }
      $statusOptions[(int) $status['id']] = $label;
    }

    $this->add(
      'select',
      'filter_year',
      E::ts('Jahr'),
      $yearOptions,
      TRUE,
      ['class' => 'crm-select2']
    );
    $this->add(
      'select',
      'filter_event_id',
      E::ts('Törn'),
      $eventOptions,
      FALSE,
      ['class' => 'crm-select2 huge']
    );
    $this->add(
      'select',
      'filter_status_id',
      E::ts('Status'),
      $statusOptions,
      FALSE,
      ['class' => 'crm-select2 huge']
    );
    if ($this->canManage && $this->data['applicationRows'] !== []) {
      $this->add(
        'select',
        'assigned_status_id',
        E::ts('Status für zugewiesene Funktionen'),
        $this->bulkConfiguration['assignedStatusOptions'],
        TRUE,
        ['class' => 'crm-select2 huge']
      );
      $this->add(
        'select',
        'declined_status_id',
        E::ts('Status für abgesagte Bewerbungen'),
        $this->bulkConfiguration['declinedStatusOptions'],
        TRUE,
        ['class' => 'crm-select2 huge']
      );
    }

    // QuickForm controllers only accept their standard "submit" action.
    // Visible workflow buttons set bulk_action and activate this one proxy.
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Aktion ausführen'),
        'isDefault' => TRUE,
      ],
    ]);

    $initialChoices = [];
    foreach ($this->data['applicationRows'] as $row) {
      $initialChoices[(string) $row['id']] = (string) $row['initialChoice'];
    }
    $initialChoicesJson = json_encode(
      $initialChoices,
      JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    );
    $this->setDefaults([
      'filter_year' => $this->selectedYear,
      'filter_event_id' => $this->selectedEventId,
      'filter_status_id' => $this->selectedStatusId,
      'assigned_status_id' => (int) (
        $this->bulkConfiguration['assignedStatusDefault'] ?? 0
      ),
      'declined_status_id' => (int) (
        $this->bulkConfiguration['declinedStatusDefault'] ?? 0
      ),
    ]);

    foreach ($this->data as $name => $value) {
      $this->assign($name, $value);
    }
    $this->assign('tabs', $this->tabs());
    $this->assign('canManage', $this->canManage);
    $this->assign('pageError', $this->pageError);
    $this->assign('visibleCount', count($this->data['applicationRows']));
    $this->assign('initialChoicesJson', $initialChoicesJson);
    $this->assign(
      'statusWorkflowErrors',
      $this->bulkConfiguration['statusWorkflowErrors'] ?? []
    );
    $this->assign('resetUrl', $this->applicationsUrl($this->selectedYear));

    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->getSubmittedValues();
    $action = trim($this->postedString('moeve_bulk_action', 'filter'));
    if ($action === '') {
      $action = 'filter';
    }

    $year = max(1, (int) ($values['filter_year'] ?? $this->selectedYear));
    $eventId = max(0, (int) ($values['filter_event_id'] ?? 0));
    $statusId = max(0, (int) ($values['filter_status_id'] ?? 0));
    $returnUrl = $this->applicationsUrl($year, $eventId, $statusId);

    if ($action === 'filter') {
      CRM_Utils_System::redirect($returnUrl);
    }

    if (!$this->canManage()) {
      throw new CRM_Core_Exception(
        E::ts('Sie dürfen Crewing-Bewerbungen nicht bearbeiten.')
      );
    }

    try {
      $visibleData = $this->provider->loadApplications(
        $year,
        $eventId,
        $statusId
      );
      $visibleRows = $visibleData['applicationRows'];
      $visibleIds = array_map(
        static fn(array $row): int => (int) $row['id'],
        $visibleRows
      );
      $choices = $this->validatedChoices(
        $this->postedString('moeve_bulk_choices', '{}'),
        $visibleRows
      );

      if ($action === 'save_assignments') {
        $result = $this->manager->saveBulkAssignments(
          $visibleIds,
          $choices
        );
        $message = E::ts(
          '%1 angezeigte Zuweisungen wurden gespeichert (%2 Crewfunktionen, %3 abgesagt).',
          [
            1 => $result['updatedCount'],
            2 => $result['assignedCount'],
            3 => $result['declinedCount'],
          ]
        );
      }
      elseif ($action === 'apply_assigned_status') {
        $result = $this->manager->applyBulkStatus(
          $visibleIds,
          $choices,
          (int) ($values['assigned_status_id'] ?? 0),
          'assigned'
        );
        $message = E::ts(
          'Der Teilnahmestatus wurde für %1 angezeigte, zugewiesene Bewerbungen geändert.',
          [1 => $result['statusCount']]
        );
      }
      elseif ($action === 'apply_declined_status') {
        $result = $this->manager->applyBulkStatus(
          $visibleIds,
          $choices,
          (int) ($values['declined_status_id'] ?? 0),
          'declined'
        );
        $message = E::ts(
          'Der Teilnahmestatus wurde für %1 angezeigte, abgesagte Bewerbungen geändert.',
          [1 => $result['statusCount']]
        );
      }
      else {
        throw new \RuntimeException('Die Sammelaktion wurde nicht erkannt.');
      }

      CRM_Core_Session::setStatus(
        $message,
        E::ts('Möwe Crewing'),
        'success'
      );
    }
    catch (\Throwable $exception) {
      Civi::log()->error(
        'Möwe Crewing bulk application update failed: {message}',
        [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]
      );
      CRM_Core_Session::setStatus(
        E::ts('Die Sammelaktion ist fehlgeschlagen: %1', [
          1 => $exception->getMessage(),
        ]),
        E::ts('Möwe Crewing'),
        'error'
      );
    }

    CRM_Utils_System::redirect($returnUrl);
  }

  private function postedString(string $name, string $default = ''): string {
    $value = $_POST[$name] ?? $default;
    return is_string($value) ? $value : $default;
  }

  /**
   * @param array<int, array<string, mixed>> $visibleRows
   * @return array<int, string>
   */
  private function validatedChoices(
    string $json,
    array $visibleRows
  ): array {
    try {
      $decoded = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException(
        'Die ausgewählten Zuweisungen konnten nicht gelesen werden.',
        0,
        $exception
      );
    }
    if (!is_array($decoded)) {
      throw new \RuntimeException(
        'Die ausgewählten Zuweisungen haben ein ungültiges Format.'
      );
    }

    $availableChoices = array_fill_keys(
      array_keys($this->manager->getBulkConfiguration()['roleOptions']),
      TRUE
    );
    unset($availableChoices['candidate']);
    $availableChoices['declined'] = TRUE;
    $availableChoices[''] = TRUE;

    $choices = [];
    foreach ($visibleRows as $row) {
      $participantId = (int) $row['id'];
      $choice = trim((string) (
        $decoded[(string) $participantId]
        ?? $decoded[$participantId]
        ?? $row['initialChoice']
        ?? ''
      ));
      if (!isset($availableChoices[$choice])) {
        throw new \RuntimeException(sprintf(
          'Die Auswahl für Teilnahme %d ist nicht mehr verfügbar.',
          $participantId
        ));
      }
      $choices[$participantId] = $choice;
    }
    return $choices;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function tabs(): array {
    $tabs = [];
    foreach ([
      'cockpit' => E::ts('Cockpit'),
      'voyages' => E::ts('Törnplanung'),
      'applications' => E::ts('Bewerbungen'),
      'year' => E::ts('Jahresübersicht'),
    ] as $view => $label) {
      $tabs[] = [
        'label' => $label,
        'active' => $view === 'applications',
        'url' => $view === 'applications'
          ? $this->applicationsUrl($this->selectedYear)
          : CRM_Utils_System::url(
            'civicrm/moeve-crewing',
            http_build_query([
              'reset' => 1,
              'view' => $view,
              'year' => $this->selectedYear,
            ], '', '&', PHP_QUERY_RFC3986)
          ),
      ];
    }
    return $tabs;
  }

  private function applicationsUrl(
    int $year,
    int $eventId = 0,
    int $statusId = 0
  ): string {
    $parameters = [
      'reset' => 1,
      'year' => $year,
    ];
    if ($eventId > 0) {
      $parameters['event_id'] = $eventId;
    }
    if ($statusId > 0) {
      $parameters['status_id'] = $statusId;
    }
    return CRM_Utils_System::url(
      'civicrm/moeve-crewing/applications',
      http_build_query($parameters, '', '&', PHP_QUERY_RFC3986)
    );
  }

  /**
   * @param array<int, int> $availableYears
   */
  private function defaultYear(array $availableYears): int {
    $currentYear = (int) date('Y');
    foreach ($availableYears as $year) {
      if ($year >= $currentYear) {
        return $year;
      }
    }
    return $availableYears !== []
      ? (int) max($availableYears)
      : $currentYear;
  }

  private function requestInteger(string $name): int {
    return max(0, (int) CRM_Utils_Request::retrieve(
      $name,
      'Integer',
      $this,
      FALSE,
      0
    ));
  }

  private function canAccess(): bool {
    return CRM_Core_Permission::check('access Moeve Crewing')
      || CRM_Core_Permission::check('administer CiviCRM');
  }

  private function canManage(): bool {
    return CRM_Core_Permission::check('manage Moeve Crewing')
      || CRM_Core_Permission::check('administer CiviCRM');
  }

  /**
   * @return array<string, mixed>
   */
  private function emptyData(): array {
    return [
      'applicationEvents' => [],
      'applicationStatuses' => [],
      'applicationRows' => [],
      'applicationSummary' => [
        'resultCount' => 0,
        'openCount' => 0,
        'assignedCount' => 0,
        'negativeCount' => 0,
      ],
      'selectedEventId' => $this->selectedEventId,
      'selectedStatusId' => $this->selectedStatusId,
    ];
  }

}
