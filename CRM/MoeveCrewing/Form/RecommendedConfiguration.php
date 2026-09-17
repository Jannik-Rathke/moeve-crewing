<?php
declare(strict_types = 1);

use Civi\MoeveCrewing\Setup\RecommendedConfigurationInstaller;
use CRM_MoeveCrewing_ExtensionUtil as E;

/**
 * Runs or repairs the recommended Möwe Crewing configuration.
 */
class CRM_MoeveCrewing_Form_RecommendedConfiguration extends CRM_Core_Form {

  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    if (!CRM_Core_Permission::check('administer CiviCRM')) {
      throw new CRM_Core_Exception(E::ts('Sie dürfen diese Einrichtung nicht ausführen.'));
    }

    CRM_Utils_System::setTitle(E::ts('Möwe Crewing – Einrichtung'));

    $this->assign('resultMessages', []);
    $this->assign('createdCount', 0);
    $this->assign('existingCount', 0);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Konfiguration anlegen oder reparieren'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
  }

  public function postProcess(): void {
    try {
      $messages = RecommendedConfigurationInstaller::install();
      $createdCount = count(array_filter(
        $messages,
        static fn(string $message): bool => str_starts_with($message, 'erstellt:')
      ));
      $existingCount = count(array_filter(
        $messages,
        static fn(string $message): bool => str_starts_with($message, 'vorhanden:')
      ));

      $this->assign('resultMessages', $messages);
      $this->assign('createdCount', $createdCount);
      $this->assign('existingCount', $existingCount);

      CRM_Core_Session::setStatus(
        E::ts('Einrichtung abgeschlossen: %1 neu erstellt, %2 bereits vorhanden.', [
          1 => $createdCount,
          2 => $existingCount,
        ]),
        E::ts('Möwe Crewing'),
        'success'
      );
    }
    catch (\Throwable $exception) {
      Civi::log()->error('Möwe Crewing setup failed: {message}', [
        'message' => $exception->getMessage(),
        'exception' => $exception,
      ]);

      CRM_Core_Session::setStatus(
        E::ts('Die Einrichtung ist fehlgeschlagen: %1', [
          1 => $exception->getMessage(),
        ]),
        E::ts('Möwe Crewing'),
        'error'
      );
    }

    parent::postProcess();
  }

}
