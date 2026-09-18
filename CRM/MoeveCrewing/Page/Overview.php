<?php
declare(strict_types = 1);

use Civi\MoeveCrewing\Overview\YearOverviewProvider;
use CRM_MoeveCrewing_ExtensionUtil as E;

/**
 * Read-only shell for the Möwe Crewing planning views.
 */
class CRM_MoeveCrewing_Page_Overview extends CRM_Core_Page {

  /**
   * @throws \CRM_Core_Exception
   */
  public function run(): void {
    if (!CRM_Core_Permission::check('administer CiviCRM')) {
      throw new CRM_Core_Exception(
        E::ts('Sie dürfen die Crewing-Übersicht nicht aufrufen.')
      );
    }

    $allowedViews = ['cockpit', 'voyages', 'applications', 'year'];
    $activeView = (string) CRM_Utils_Request::retrieve(
      'view',
      'String',
      $this,
      FALSE,
      'year'
    );
    if (!in_array($activeView, $allowedViews, TRUE)) {
      $activeView = 'year';
    }

    $provider = new YearOverviewProvider();
    $availableYears = $provider->getAvailableYears();
    $requestedYear = (int) CRM_Utils_Request::retrieve(
      'year',
      'Positive',
      $this,
      FALSE,
      0
    );
    $selectedYear = $requestedYear > 0
      ? $requestedYear
      : $this->defaultYear($availableYears);
    if (!in_array($selectedYear, $availableYears, TRUE)) {
      $availableYears[] = $selectedYear;
      sort($availableYears, SORT_NUMERIC);
    }
    $selectedEventId = max(0, (int) CRM_Utils_Request::retrieve(
      'event_id',
      'Integer',
      $this,
      FALSE,
      0
    ));
    $selectedStatusId = max(0, (int) CRM_Utils_Request::retrieve(
      'status_id',
      'Integer',
      $this,
      FALSE,
      0
    ));

    $tabs = [];
    foreach ([
      'cockpit' => E::ts('Cockpit'),
      'voyages' => E::ts('Törnplanung'),
      'applications' => E::ts('Bewerbungen'),
      'year' => E::ts('Jahresübersicht'),
    ] as $key => $label) {
      $tabs[] = [
        'key' => $key,
        'label' => $label,
        'active' => $key === $activeView,
        'url' => CRM_Utils_System::url(
          'civicrm/moeve-crewing',
          http_build_query([
            'reset' => 1,
            'view' => $key,
            'year' => $selectedYear,
          ], '', '&', PHP_QUERY_RFC3986)
        ),
      ];
    }

    $titles = [
      'cockpit' => E::ts('Möwe Crewing – Cockpit'),
      'voyages' => E::ts('Möwe Crewing – Törnplanung'),
      'applications' => E::ts('Möwe Crewing – Bewerbungen'),
      'year' => E::ts('Möwe Crewing – Jahresübersicht'),
    ];
    CRM_Utils_System::setTitle($titles[$activeView]);

    $this->assign('activeView', $activeView);
    $this->assign('tabs', $tabs);
    $this->assign('selectedYear', $selectedYear);
    $this->assign('availableYears', $availableYears);
    $this->assign('pageError', '');
    $this->assign('events', []);
    $this->assign('roleRows', []);
    $this->assign('peopleRows', []);
    $this->assign('statusLegend', []);
    $this->assign('cockpitVoyages', []);
    $this->assign('cockpitNeeds', []);
    $this->assign('cockpitApplications', []);
    $this->assign('cockpitUrls', []);
    $this->assign('cockpitSummary', [
      'upcomingEventCount' => 0,
      'requiredCount' => 0,
      'confirmedCount' => 0,
      'openCount' => 0,
      'applicationCount' => 0,
      'attentionEventCount' => 0,
      'missingInfoEventCount' => 0,
    ]);
    $this->assign('voyageEvents', []);
    $this->assign('voyages', []);
    $this->assign('voyageSummary', [
      'eventCount' => 0,
      'requiredCount' => 0,
      'confirmedCount' => 0,
      'openCount' => 0,
      'applicationCount' => 0,
    ]);
    $this->assign('applicationEvents', []);
    $this->assign('applicationStatuses', []);
    $this->assign('applicationRows', []);
    $this->assign('applicationSummary', [
      'resultCount' => 0,
      'openCount' => 0,
      'assignedCount' => 0,
      'negativeCount' => 0,
    ]);
    $this->assign('selectedEventId', $selectedEventId);
    $this->assign('selectedStatusId', $selectedStatusId);
    $this->assign(
      'voyageResetUrl',
      CRM_Utils_System::url(
        'civicrm/moeve-crewing',
        http_build_query([
          'reset' => 1,
          'view' => 'voyages',
          'year' => $selectedYear,
        ], '', '&', PHP_QUERY_RFC3986)
      )
    );
    $this->assign(
      'applicationResetUrl',
      CRM_Utils_System::url(
        'civicrm/moeve-crewing',
        http_build_query([
          'reset' => 1,
          'view' => 'applications',
          'year' => $selectedYear,
        ], '', '&', PHP_QUERY_RFC3986)
      )
    );
    $this->assign('summary', [
      'eventCount' => 0,
      'roleCount' => 0,
      'peopleCount' => 0,
      'coveredCount' => 0,
      'attentionCount' => 0,
    ]);
    if ($activeView === 'cockpit') {
      try {
        $data = $provider->loadCockpit($selectedYear);
        foreach ($data as $name => $value) {
          $this->assign($name, $value);
        }
      }
      catch (\Throwable $exception) {
        Civi::log()->error('Möwe Crewing cockpit failed: {message}', [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]);
        $this->assign('pageError', E::ts(
          'Das Crewing-Cockpit konnte nicht geladen werden: %1',
          [1 => $exception->getMessage()]
        ));
      }
    }
    elseif ($activeView === 'year') {
      try {
        $data = $provider->load($selectedYear);
        foreach ($data as $name => $value) {
          $this->assign($name, $value);
        }
      }
      catch (\Throwable $exception) {
        Civi::log()->error('Möwe Crewing year overview failed: {message}', [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]);
        $this->assign('pageError', E::ts(
          'Die Jahresübersicht konnte nicht geladen werden: %1',
          [1 => $exception->getMessage()]
        ));
      }
    }
    elseif ($activeView === 'voyages') {
      try {
        $data = $provider->loadVoyages($selectedYear, $selectedEventId);
        foreach ($data as $name => $value) {
          $this->assign($name, $value);
        }
      }
      catch (\Throwable $exception) {
        Civi::log()->error('Möwe Crewing voyage planning failed: {message}', [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]);
        $this->assign('pageError', E::ts(
          'Die Törnplanung konnte nicht geladen werden: %1',
          [1 => $exception->getMessage()]
        ));
      }
    }
    elseif ($activeView === 'applications') {
      try {
        $data = $provider->loadApplications(
          $selectedYear,
          $selectedEventId,
          $selectedStatusId
        );
        foreach ($data as $name => $value) {
          $this->assign($name, $value);
        }
      }
      catch (\Throwable $exception) {
        Civi::log()->error('Möwe Crewing applications failed: {message}', [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]);
        $this->assign('pageError', E::ts(
          'Die Bewerbungsübersicht konnte nicht geladen werden: %1',
          [1 => $exception->getMessage()]
        ));
      }
    }

    parent::run();
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

}
