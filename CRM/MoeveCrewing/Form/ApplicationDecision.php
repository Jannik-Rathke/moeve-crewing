<?php

declare(strict_types = 1);

use Civi\MoeveCrewing\Application\ApplicationManager;
use CRM_MoeveCrewing_ExtensionUtil as E;

/**
 * Safely changes status and crewing role for one application.
 */
class CRM_MoeveCrewing_Form_ApplicationDecision extends CRM_Core_Form {

  private int $participantId;

  /**
   * @var array<string, mixed>
   */
  private array $application = [];

  private ApplicationManager $manager;

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    parent::preProcess();

    if (
      !CRM_Core_Permission::check('manage Moeve Crewing')
      && !CRM_Core_Permission::check('administer CiviCRM')
    ) {
      throw new CRM_Core_Exception(
        E::ts('Sie dürfen Crewing-Bewerbungen nicht bearbeiten.')
      );
    }

    $this->participantId = (int) CRM_Utils_Request::retrieve(
      'id',
      'Positive',
      $this,
      TRUE
    );
    $this->manager = new ApplicationManager();

    try {
      $this->application = $this->manager->getFormData(
        $this->participantId
      );
    }
    catch (\Throwable $exception) {
      Civi::log()->error(
        'Möwe Crewing application form failed: {message}',
        [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]
      );
      throw new CRM_Core_Exception($exception->getMessage());
    }
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('Crewing-Bewerbung bearbeiten'));

    $this->add(
      'select',
      'status_id',
      E::ts('Teilnahmestatus'),
      $this->application['statusOptions'],
      TRUE,
      ['class' => 'crm-select2 huge']
    );
    $this->add(
      'select',
      'role_choice',
      E::ts('Crewing-Funktion'),
      $this->application['roleOptions'],
      TRUE,
      ['class' => 'crm-select2 huge']
    );
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Entscheidung speichern'),
        'isDefault' => TRUE,
      ],
    ]);

    $this->setDefaults([
      'status_id' => (int) $this->application['status']['id'],
      'role_choice' => (string) $this->application['currentRoleChoice'],
    ]);
    $this->assign('application', $this->application);
    $this->assign('returnUrl', $this->application['returnUrl']);

    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->exportValues();

    try {
      $updated = $this->manager->saveDecision(
        $this->participantId,
        (int) $values['status_id'],
        (string) $values['role_choice']
      );

      CRM_Core_Session::setStatus(
        E::ts('Status und Crewing-Funktion für %1 wurden gespeichert.', [
          1 => (string) $updated['displayName'],
        ]),
        E::ts('Möwe Crewing'),
        'success'
      );
      CRM_Utils_System::redirect((string) $updated['returnUrl']);
    }
    catch (\Throwable $exception) {
      Civi::log()->error(
        'Möwe Crewing application update failed: {message}',
        [
          'message' => $exception->getMessage(),
          'exception' => $exception,
        ]
      );
      CRM_Core_Session::setStatus(
        E::ts('Die Entscheidung konnte nicht gespeichert werden: %1', [
          1 => $exception->getMessage(),
        ]),
        E::ts('Möwe Crewing'),
        'error'
      );
    }

    parent::postProcess();
  }

}
