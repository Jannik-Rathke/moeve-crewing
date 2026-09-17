<?php
declare(strict_types = 1);

use Civi\MoeveCrewing\Configuration\ExistingConfigurationManager;
use Civi\MoeveCrewing\Setup\RecommendedConfigurationInstaller;
use CRM_MoeveCrewing_ExtensionUtil as E;

/**
 * Configures, installs, or repairs the Möwe Crewing configuration.
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

    $formData = ExistingConfigurationManager::getFormData();

    $this->assign('resultMessages', []);
    $this->assign('configurationMessages', []);
    $this->assign('createdCount', 0);
    $this->assign('existingCount', 0);
    $this->assign('mappingIsValid', $formData['statusErrors'] === []);
    $this->assign('mappingStatusErrors', $formData['statusErrors']);
    $this->assign('roleRows', $formData['roleRows']);
    $this->assign(
      'roleMappingIsValid',
      $formData['roleMappingErrors'] === []
    );
    $this->assign('roleMappingErrors', $formData['roleMappingErrors']);

    $emptyOption = ['' => E::ts('- bitte auswählen -')];

    $this->add(
      'select',
      'role_option_group_id',
      E::ts('Optionsgruppe der Crewing-Rollen'),
      $emptyOption + $formData['roleOptionGroups'],
      FALSE,
      ['class' => 'crm-select2 huge']
    );
    $this->add(
      'select',
      'individual_group_id',
      E::ts('Feldgruppe für Personen'),
      $emptyOption + $formData['individualGroups'],
      FALSE,
      ['class' => 'crm-select2 huge']
    );
    $this->add(
      'select',
      'capabilities_field_id',
      E::ts('Feld „Meine Fähigkeiten“'),
      $emptyOption + $formData['individualFields'],
      FALSE,
      ['class' => 'crm-select2 huge']
    );
    $this->add(
      'select',
      'participant_group_id',
      E::ts('Feldgruppe für Teilnahmen'),
      $emptyOption + $formData['participantGroups'],
      FALSE,
      ['class' => 'crm-select2 huge']
    );
    $this->add(
      'select',
      'preferences_field_id',
      E::ts('Feld „Gewünschte Funktionen“'),
      $emptyOption + $formData['participantFields'],
      FALSE,
      ['class' => 'crm-select2 huge']
    );
    $this->add(
      'select',
      'event_group_id',
      E::ts('Feldgruppe für Veranstaltungen'),
      $emptyOption + $formData['eventGroups'],
      FALSE,
      ['class' => 'crm-select2 huge']
    );

    $this->add('hidden', 'role_source_option_group_id');
    $this->add('hidden', 'role_source_event_group_id');

    foreach ($formData['roleRows'] as $roleRow) {
      $this->add(
        'advcheckbox',
        $roleRow['useElement'],
        NULL,
        E::ts('verwenden')
      );
      $this->add(
        'select',
        $roleRow['enabledElement'],
        NULL,
        $emptyOption + $formData['roleEnabledFields'],
        FALSE,
        ['class' => 'crm-select2 huge']
      );
      $this->add(
        'select',
        $roleRow['minimumElement'],
        NULL,
        $emptyOption + $formData['roleMinimumFields'],
        FALSE,
        ['class' => 'crm-select2 huge']
      );
    }

    $this->addRadio(
      'setup_action',
      E::ts('Auszuführende Aktion'),
      [
        'save_existing' => E::ts('Ausgewählte Basis- und Rollenzuordnung speichern'),
        'install_recommended' => E::ts('Empfohlene Konfiguration anlegen oder reparieren'),
      ],
      [],
      '<br>',
      TRUE
    );

    $defaults = $formData['defaults'];
    $defaults['setup_action'] = 'save_existing';
    $this->setDefaults($defaults);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Ausgewählte Aktion ausführen'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->getSubmittedValues();
    $saveExisting = ($values['setup_action'] ?? '') === 'save_existing';

    try {
      if ($saveExisting) {
        $messages = ExistingConfigurationManager::save($values);
        $this->assign('configurationMessages', $messages);
        $this->assign('mappingIsValid', TRUE);
        $this->assign('mappingStatusErrors', []);

        CRM_Core_Session::setStatus(
          E::ts('Die Möwe-Crewing-Konfiguration wurde erfolgreich gespeichert.'),
          E::ts('Möwe Crewing'),
          'success'
        );
      }
      else {
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
        $this->assign('mappingIsValid', TRUE);
        $this->assign('mappingStatusErrors', []);

        CRM_Core_Session::setStatus(
          E::ts('Einrichtung abgeschlossen: %1 neu erstellt, %2 bereits vorhanden.', [
            1 => $createdCount,
            2 => $existingCount,
          ]),
          E::ts('Möwe Crewing'),
          'success'
        );
      }
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
