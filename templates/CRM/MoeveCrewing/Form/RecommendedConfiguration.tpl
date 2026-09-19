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
    <div class="label">{$form.candidate_role_id.label}</div>
    <div class="content">
      {$form.candidate_role_id.html}
      <div class="description">
        Diese Rolle kennzeichnet eine Bewerbung vor der Zuordnung einer echten
        Bordfunktion. Sie erhält deshalb keine Freigabe- oder Mindestanzahlfelder.
      </div>
    </div>
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

  <h3>Veranstaltungsinformationen zuordnen</h3>
  <div class="help">
    <p>
      Diese Felder liefern später Reisebezeichnung, Strecke, Bordzeiten und
      Organisation für Detail- und Jahresübersichten. Datumsfelder müssen auch
      eine Uhrzeit speichern können.
    </p>
  </div>

  <div class="crm-section">
    <div class="label">{$form.event_info_group_id.label}</div>
    <div class="content">{$form.event_info_group_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.event_number_field_id.label}</div>
    <div class="content">{$form.event_number_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.departure_port_field_id.label}</div>
    <div class="content">{$form.departure_port_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.route_field_id.label}</div>
    <div class="content">{$form.route_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.arrival_port_field_id.label}</div>
    <div class="content">{$form.arrival_port_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.crew_on_board_field_id.label}</div>
    <div class="content">{$form.crew_on_board_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.crew_off_board_field_id.label}</div>
    <div class="content">{$form.crew_off_board_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.organizer_field_id.label}</div>
    <div class="content">{$form.organizer_field_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.comment_field_id.label}</div>
    <div class="content">{$form.comment_field_id.html}</div>
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

  <h3>Crewing-Rollen zuordnen</h3>
  <div class="help">
    <p>
      Aktivieren Sie ausschließlich die Rollen, die Möwe Crewing verwenden soll,
      und ordnen Sie jeder Rolle genau ein Freigabe- und ein Mindestanzahl-Feld
      aus der gewählten Veranstaltungsgruppe zu.
    </p>
    <p>
      Wenn Sie oben die Rollen-Optionsgruppe oder die Veranstaltungsgruppe ändern,
      speichern Sie zunächst die Basiszuordnung und laden Sie anschließend die Seite neu.
    </p>
  </div>

  <div class="crm-section">
    <div class="label">{$form.event_group_id.label}</div>
    <div class="content">{$form.event_group_id.html}</div>
    <div class="clear"></div>
  </div>

  {$form.role_source_option_group_id.html}
  {$form.role_source_event_group_id.html}

  {if $roleMappingIsValid}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      <p><strong>Rollenzuordnung gültig:</strong> Alle verwendeten Rollen besitzen kompatible Bedarfsfelder.</p>
    </div>
  {else}
    <div class="messages warning no-popup">
      <div class="icon warning-icon"></div>
      <p><strong>Rollenzuordnung noch nicht vollständig:</strong></p>
      <ul>
        {foreach from=$roleMappingErrors item=roleMappingError}
          <li>{$roleMappingError|escape}</li>
        {/foreach}
      </ul>
    </div>
  {/if}

  {if $roleRows}
    <table class="selector row-highlight">
      <thead>
        <tr>
          <th>Rolle</th>
          <th>Verwenden</th>
          <th>Freigabefeld</th>
          <th>Mindestanzahl-Feld</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$roleRows item=roleRow}
          {assign var=useElement value=$roleRow.useElement}
          {assign var=enabledElement value=$roleRow.enabledElement}
          {assign var=minimumElement value=$roleRow.minimumElement}
          <tr data-status-name="{$statusRow.name|escape}">
            <td>
              <strong>{$roleRow.label|escape}</strong><br>
              <small>{$roleRow.name|escape}</small>
            </td>
            <td>{$form.$useElement.html}</td>
            <td>{$form.$enabledElement.html}</td>
            <td>{$form.$minimumElement.html}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {/if}

  <h3>Teilnahmestatus für Crewing und Jahresübersicht</h3>
  <div class="help">
    <p>
      Grundlage ist die zentrale CiviCRM-Liste der Teilnahmestatus. Legen Sie
      hier ausdrücklich fest, welche Status die Sammelaktion für zugewiesene
      Crew bzw. für Absagen anbieten darf und welcher Status vorausgewählt ist.
      Nur die erstmalige Vorbelegung behandelt die CiviCRM-Klasse „Negative“
      als Absage; nach dem Speichern wird die Klasse nicht mehr zur Entscheidung
      verwendet.
    </p>
    <p>
      Die Personen-Jahresübersicht verwendet außerdem die hinterlegte Farbe.
      Alle Zuordnungen folgen dem technischen Statusnamen und bleiben daher bei
      Änderungen der sichtbaren Bezeichnung sowie bei unterschiedlichen
      Datenbank-IDs stabil.
    </p>
  </div>

  {if $statusWorkflowIsValid}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      <p><strong>Statusworkflow gültig:</strong> Die auswählbaren Status und beide Standardwerte sind eindeutig konfiguriert.</p>
    </div>
  {else}
    <div class="messages warning no-popup">
      <div class="icon warning-icon"></div>
      <p><strong>Statusworkflow verwendet vorläufig sichere Standardwerte:</strong></p>
      <ul>
        {foreach from=$statusWorkflowErrors item=statusWorkflowError}
          <li>{$statusWorkflowError|escape}</li>
        {/foreach}
      </ul>
    </div>
  {/if}

  {if $statusColorsAreValid}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      <p><strong>Statusfarben gültig:</strong> Für alle Teilnahmestatus ist eine Farbe hinterlegt.</p>
    </div>
  {else}
    <div class="messages warning no-popup">
      <div class="icon warning-icon"></div>
      <p><strong>Statusfarben werden auf sichere Standardwerte zurückgesetzt:</strong></p>
      <ul>
        {foreach from=$statusColorErrors item=statusColorError}
          <li>{$statusColorError|escape}</li>
        {/foreach}
      </ul>
    </div>
  {/if}

  {if $statusRows}
    <table class="selector row-highlight moeve-status-colors">
      <thead>
        <tr>
          <th>Teilnahmestatus</th>
          <th>Technischer Name</th>
          <th>Klasse</th>
          <th>Verfügbarkeit</th>
          <th>Für zugewiesene Crew</th>
          <th>Für Absagen</th>
          <th>Farbe</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$statusRows item=statusRow}
          {assign var=colorElement value=$statusRow.colorElement}
          {assign var=assignedElement value=$statusRow.assignedElement}
          {assign var=declinedElement value=$statusRow.declinedElement}
          <tr>
            <td><strong>{$statusRow.label|escape}</strong></td>
            <td><code>{$statusRow.name|escape}</code></td>
            <td>{$statusRow.class|escape}</td>
            <td>{if $statusRow.isActive}aktiv{else}inaktiv{/if}</td>
            <td class="moeve-workflow-assigned">{$form.$assignedElement.html}</td>
            <td class="moeve-workflow-declined">{$form.$declinedElement.html}</td>
            <td>{$form.$colorElement.html}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>

    <div class="crm-section">
      <div class="label">{$form.assigned_status_default.label}</div>
      <div class="content">
        {$form.assigned_status_default.html}
        <div class="description">
          Vorauswahl für „Alle Teilnahmestati der zugewiesenen Crew setzen“.
          Der Status muss oben in der Spalte „Für zugewiesene Crew“ markiert sein.
        </div>
      </div>
      <div class="clear"></div>
    </div>
    <div class="crm-section">
      <div class="label">{$form.declined_status_default.label}</div>
      <div class="content">
        {$form.declined_status_default.html}
        <div class="description">
          Vorauswahl für „Alle Teilnahmestati der abgesagten Bewerbungen setzen“.
          Der Status muss oben in der Spalte „Für Absagen“ markiert sein.
        </div>
      </div>
      <div class="clear"></div>
    </div>
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

{literal}
<style>
  .crm-moeve-crewing-setup-form-block .moeve-status-colors input[type="color"] {
    box-sizing: border-box;
    cursor: pointer;
    height: 2.25rem;
    padding: 0.15rem;
    width: 4.5rem;
  }
</style>
<script>
  CRM.$(function($) {
    $('.crm-moeve-crewing-setup-form-block .moeve-status-color')
      .attr('type', 'color');
  });
</script>
{/literal}
