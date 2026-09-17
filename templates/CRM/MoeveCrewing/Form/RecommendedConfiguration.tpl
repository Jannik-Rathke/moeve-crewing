<div class="crm-block crm-form-block crm-moeve-crewing-setup-form-block">
  {if $mappingIsValid}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      <p><strong>Basiszuordnung gültig:</strong> Alle ausgewählten Gruppen und Felder sind vorhanden und kompatibel.</p>
    </div>
  {else}
    <div class="messages warning no-popup">
      <div class="icon warning-icon"></div>
      <p><strong>Basiszuordnung noch nicht vollständig:</strong></p>
      <ul>
        {foreach from=$mappingStatusErrors item=statusError}
          <li>{$statusError|escape}</li>
        {/foreach}
      </ul>
    </div>
  {/if}

  <h3>Vorhandene CiviCRM-Strukturen verwenden</h3>
  <div class="help">
    <p>
      Ordnen Sie hier bereits vorhandene Optionsgruppen, Feldgruppen und Auswahlfelder zu.
      Es werden ausschließlich kompatible Mehrfachauswahlfelder angeboten.
    </p>
    <p>
      Beim anschließenden Anlegen oder Reparieren ergänzt Möwe Crewing nur noch fehlende
      Crewing-Rollen und veranstaltungsbezogene Bedarfsfelder. Bestehende Einträge werden
      weder überschrieben noch gelöscht.
    </p>
  </div>

  <div class="crm-section">
    <div class="label">{$form.role_option_group_id.label}</div>
    <div class="content">{$form.role_option_group_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.individual_group_id.label}</div>
    <div class="content">{$form.individual_group_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.capabilities_field_id.label}</div>
    <div class="content">{$form.capabilities_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.participant_group_id.label}</div>
    <div class="content">{$form.participant_group_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.preferences_field_id.label}</div>
    <div class="content">{$form.preferences_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.event_group_id.label}</div>
    <div class="content">{$form.event_group_id.html}</div>
    <div class="clear"></div>
  </div>

  {if $configurationMessages}
    <details class="crm-accordion-wrapper crm-accordion_title-accordion">
      <summary>Gespeicherte Zuordnung anzeigen</summary>
      <ul>
        {foreach from=$configurationMessages item=configurationMessage}
          <li>{$configurationMessage|escape}</li>
        {/foreach}
      </ul>
    </details>
  {/if}

  <h3>Empfohlene Möwe-Crewing-Konfiguration</h3>
  <div class="help">
    <p>
      Diese Aktion legt die empfohlenen Crewing-Rollen, Feldgruppen und
      benutzerdefinierten Felder an oder ergänzt fehlende Bestandteile in der
      oben gespeicherten Zuordnung.
    </p>
    <p>
      Bereits vorhandene Einträge werden nicht überschrieben oder gelöscht.
      Abweichende, nicht kompatible Einträge werden als Fehler gemeldet.
    </p>
  </div>

  <div class="crm-section">
    <div class="label">{$form.setup_action.label}</div>
    <div class="content">{$form.setup_action.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  {if $resultMessages}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      <p>
        <strong>Einrichtung abgeschlossen:</strong>
        {$createdCount|escape} neu erstellt,
        {$existingCount|escape} bereits vorhanden.
      </p>
    </div>

    <details class="crm-accordion-wrapper crm-accordion_title-accordion">
      <summary>Ausführliches Ergebnis anzeigen</summary>
      <ul>
        {foreach from=$resultMessages item=resultMessage}
          <li>{$resultMessage|escape}</li>
        {/foreach}
      </ul>
    </details>
  {/if}
</div>
