<div id="moeve-crewing-applications" class="crm-container moeve-crewing-applications">
  <nav class="moeve-tabs" aria-label="Möwe-Crewing-Bereiche">
    {foreach from=$tabs item=tab}
      <a
        href="{$tab.url|escape}"
        class="moeve-tab{if $tab.active} is-active{/if}"
        {if $tab.active}aria-current="page"{/if}
      >{$tab.label|escape}</a>
    {/foreach}
  </nav>

  <section class="crm-block crm-content-block moeve-application-controls">
    <div class="moeve-filter-row">
      <label>
        <strong>{$form.filter_year.label}</strong>
        {$form.filter_year.html}
      </label>
      <label>
        <strong>{$form.filter_event_id.label}</strong>
        {$form.filter_event_id.html}
      </label>
      <label>
        <strong>{$form.filter_status_id.label}</strong>
        {$form.filter_status_id.html}
      </label>
      <div class="moeve-filter-actions">
        <button
          type="button"
          class="crm-button"
          data-moeve-action="filter"
        >Filtern</button>
        <a href="{$resetUrl|escape}" class="crm-button">Zurücksetzen</a>
      </div>
    </div>
  </section>

  <input
    type="hidden"
    name="moeve_bulk_choices"
    value="{$initialChoicesJson|escape}"
  >
  <input
    type="hidden"
    name="moeve_bulk_action"
    value="filter"
  >

  {if $pageError}
    <div class="messages error no-popup">
      <div class="icon error-icon"></div>
      <p>{$pageError|escape}</p>
    </div>
  {else}
    {if $canManage && $statusWorkflowErrors}
      <div class="messages warning no-popup">
        <div class="icon warning-icon"></div>
        <p><strong>Die gespeicherte Statusverwendung ist nicht mehr vollständig.</strong></p>
        <ul>
          {foreach from=$statusWorkflowErrors item=statusWorkflowError}
            <li>{$statusWorkflowError|escape}</li>
          {/foreach}
        </ul>
        <p>Bis zur Korrektur in „Möwe Crewing – Einrichtung“ werden sichere Ersatzwerte verwendet.</p>
      </div>
    {/if}
    <section class="moeve-summary" aria-label="Zusammenfassung Bewerbungen">
      <div class="moeve-summary-card">
        <span class="moeve-summary-value">{$applicationSummary.resultCount|escape}</span>
        <span class="moeve-summary-label">Treffer</span>
      </div>
      <div class="moeve-summary-card is-pending">
        <span class="moeve-summary-value">{$applicationSummary.openCount|escape}</span>
        <span class="moeve-summary-label">Noch zuzuordnen</span>
      </div>
      <div class="moeve-summary-card is-covered">
        <span class="moeve-summary-value">{$applicationSummary.assignedCount|escape}</span>
        <span class="moeve-summary-label">Funktion zugewiesen</span>
      </div>
      <div class="moeve-summary-card is-negative">
        <span class="moeve-summary-value">{$applicationSummary.negativeCount|escape}</span>
        <span class="moeve-summary-label">Negativer Status</span>
      </div>
    </section>

    <section class="crm-block crm-content-block moeve-application-section">
      <div class="moeve-section-heading">
        <div>
          <h2>Bewerbungen und Crew-Zuordnungen</h2>
          {if $canManage}
            <p>
              Klicken Sie auf eine Wunschfunktion oder auf „Abgesagt“. Grau
              zeigt eine noch nicht gespeicherte Zuweisung, Grün eine
              gespeicherte Funktion und Rot eine Absage.
            </p>
          {else}
            <p>
              Die Liste enthält potentielle Crewmitglieder und bereits einer
              Crewing-Funktion zugeordnete Teilnahmen.
            </p>
          {/if}
        </div>
      </div>

      {if $applicationRows}
        <div class="moeve-application-table-wrap" tabindex="0">
          <table class="selector row-highlight moeve-application-table">
            <thead>
              <tr>
                <th>Eingegangen</th>
                <th>Person</th>
                <th>Törn</th>
                <th>Wunschfunktionen</th>
                <th>Teilnahmestatus</th>
                <th>Zugewiesene Funktion</th>
                <th>Aktionen</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$applicationRows item=application}
                <tr
                  class="moeve-application-row"
                  data-participant-id="{$application.id|escape}"
                  data-initial-choice="{$application.initialChoice|escape}"
                  data-initial-label="{$application.initialChoiceLabel|escape}"
                >
                  <td class="moeve-nowrap">
                    {if $application.registerDateLabel}
                      {$application.registerDateLabel|escape}
                    {else}
                      <span class="moeve-empty-value">–</span>
                    {/if}
                  </td>
                  <td>
                    <a href="{$application.contactUrl|escape}" class="moeve-person-link">
                      {$application.displayName|escape}
                    </a>
                    {if $application.isCandidate && !$application.isDeclined}
                      <span class="moeve-badge is-candidate">Bewerbung</span>
                    {elseif $application.isDeclined}
                      <span class="moeve-badge is-declined">Abgesagt</span>
                    {/if}
                  </td>
                  <td>
                    <a href="{$application.eventUrl|escape}">
                      {$application.eventTitle|escape}
                    </a>
                    {if $application.eventDateLabel}
                      <small>{$application.eventDateLabel|escape}</small>
                    {/if}
                  </td>
                  <td>
                    <div class="moeve-badge-list moeve-choice-list">
                      {if $application.preferenceChoices}
                        {foreach from=$application.preferenceChoices item=preference}
                          {if $canManage}
                            <button
                              type="button"
                              class="moeve-choice is-preference"
                              data-choice="{$preference.choice|escape}"
                              data-label="{$preference.label|escape}"
                              aria-pressed="false"
                            >{$preference.label|escape}</button>
                          {else}
                            <span class="moeve-badge is-preference">
                              {$preference.label|escape}
                            </span>
                          {/if}
                        {/foreach}
                      {elseif !$canManage}
                        <span class="moeve-empty-value">Keine angegeben</span>
                      {/if}
                      {if $canManage}
                        <button
                          type="button"
                          class="moeve-choice is-declined"
                          data-choice="declined"
                          data-label="Abgesagt"
                          aria-pressed="false"
                        >Abgesagt</button>
                      {/if}
                    </div>
                  </td>
                  <td>
                    <span class="moeve-status-label">
                      <i
                        class="moeve-status-dot"
                        style="--moeve-status-color: {$application.status.color|escape};"
                      ></i>
                      {$application.status.label|escape}
                    </span>
                  </td>
                  <td class="moeve-assignment-cell">
                    <div class="moeve-assignment-target" aria-live="polite">
                      {if $application.isDeclined}
                        <span class="moeve-assignment is-declined">Abgesagt</span>
                      {elseif $application.assignedRoleChoices}
                        {foreach from=$application.assignedRoleChoices item=assignedRole}
                          <span class="moeve-assignment is-saved">
                            {$assignedRole.label|escape}
                          </span>
                        {/foreach}
                      {else}
                        <span class="moeve-empty-value">Noch nicht zugeordnet</span>
                      {/if}
                    </div>
                  </td>
                  <td>
                    <div class="moeve-action-list">
                      {if $canManage}
                        <a href="{$application.decisionUrl|escape}" class="crm-button">
                          Einzeln bearbeiten
                        </a>
                      {/if}
                      <a href="{$application.participantUrl|escape}" class="crm-button">
                        Details
                      </a>
                      <a href="{$application.contactUrl|escape}" class="crm-button">
                        Kontakt
                      </a>
                    </div>
                  </td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>

        {if $canManage}
          <section class="moeve-bulk-actions" aria-label="Sammelaktionen">
            <div class="moeve-bulk-intro">
              <strong>Nur die {$visibleCount|escape} aktuell angezeigten Zeilen werden bearbeitet.</strong>
              <span id="moeve-staged-count">Keine ungespeicherten Änderungen.</span>
            </div>

            <div class="moeve-bulk-action is-save">
              <div>
                <strong>1. Zuweisungen übernehmen</strong>
                <small>Speichert Crewfunktionen und rote Absagen.</small>
              </div>
              <button
                type="button"
                class="crm-button crm-button-type-submit"
                data-moeve-action="save_assignments"
              >Alle Zuweisungen speichern</button>
            </div>

            <div class="moeve-bulk-action">
              <div>
                <strong>2. Status der zugewiesenen Crew setzen</strong>
                <small>Speichert zuerst die sichtbaren Zuweisungen und setzt dann den Status aller angezeigten Zeilen mit Crewfunktion.</small>
              </div>
              <div class="moeve-bulk-controls">
                {$form.assigned_status_id.html}
                <button
                  type="button"
                  class="crm-button"
                  data-moeve-action="apply_assigned_status"
                >Alle zugewiesenen auf Status setzen</button>
              </div>
            </div>

            <div class="moeve-bulk-action is-declined">
              <div>
                <strong>3. Status der Absagen setzen</strong>
                <small>Speichert zuerst die sichtbaren Zuweisungen und setzt dann den Status aller angezeigten, rot als „Abgesagt“ markierten Zeilen.</small>
              </div>
              <div class="moeve-bulk-controls">
                {$form.declined_status_id.html}
                <button
                  type="button"
                  class="crm-button moeve-button-danger"
                  data-moeve-action="apply_declined_status"
                >Alle abgesagten auf Status setzen</button>
              </div>
            </div>
          </section>
        {/if}
      {else}
        <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          <p>Für die gewählten Filter wurden keine Crewing-Bewerbungen gefunden.</p>
        </div>
      {/if}
    </section>
  {/if}

  <div class="moeve-submit-proxy" hidden>
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

{literal}
<style>
  .moeve-crewing-applications {
    --moeve-border: #cbd5e1;
    --moeve-blue: #036b8f;
    --moeve-navy: #102f55;
    --moeve-green: #16a34a;
    --moeve-red: #dc2626;
    color: #273653;
  }

  .moeve-tabs {
    border-bottom: 1px solid var(--moeve-border);
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-bottom: 1.25rem;
    padding: 0 1rem;
  }

  .moeve-tab {
    color: var(--moeve-blue);
    font-weight: 700;
    padding: .75rem 1rem;
    text-decoration: none;
  }

  .moeve-tab.is-active {
    background: var(--moeve-blue);
    color: #fff;
  }

  .moeve-application-controls,
  .moeve-application-section {
    border: 1px solid var(--moeve-border);
    border-radius: .45rem;
    margin: 0 0 1rem;
    padding: 1rem;
  }

  .moeve-filter-row,
  .moeve-filter-actions,
  .moeve-bulk-controls {
    align-items: flex-end;
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
  }

  .moeve-filter-row label {
    display: grid;
    gap: .25rem;
  }

  .moeve-filter-row select {
    min-width: 12rem;
  }

  .moeve-summary {
    display: grid;
    gap: .75rem;
    grid-template-columns: repeat(4, minmax(10rem, 1fr));
    margin: 0 0 1rem;
  }

  .moeve-summary-card {
    background: #f8fafc;
    border: 1px solid var(--moeve-border);
    border-radius: .45rem;
    display: grid;
    gap: .15rem;
    padding: 1rem;
  }

  .moeve-summary-card.is-covered {
    border-left: .35rem solid var(--moeve-green);
  }

  .moeve-summary-card.is-pending {
    border-left: .35rem solid #f59e0b;
  }

  .moeve-summary-card.is-negative {
    border-left: .35rem solid var(--moeve-red);
  }

  .moeve-summary-value {
    color: var(--moeve-navy);
    font-size: 1.55rem;
    font-weight: 800;
  }

  .moeve-section-heading h2 {
    margin: 0 0 .2rem;
  }

  .moeve-section-heading p {
    margin: 0 0 .75rem;
  }

  .moeve-application-table-wrap {
    border: 1px solid var(--moeve-border);
    max-height: 70vh;
    overflow: auto;
  }

  .moeve-application-table {
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
    min-width: 82rem;
    width: 100%;
  }

  .moeve-application-table th,
  .moeve-application-table td {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: .65rem;
    text-align: left;
    vertical-align: top;
  }

  .moeve-application-table thead th {
    background: #f8fafc;
    position: sticky;
    top: 0;
    z-index: 2;
  }

  .moeve-application-table small,
  .moeve-bulk-action small {
    color: #64748b;
    display: block;
    margin-top: .2rem;
  }

  .moeve-person-link {
    display: block;
    font-weight: 700;
    margin-bottom: .3rem;
  }

  .moeve-badge-list,
  .moeve-action-list {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
  }

  .moeve-badge,
  .moeve-choice,
  .moeve-assignment {
    border: 1px solid transparent;
    border-radius: 999px;
    display: inline-block;
    font-size: .78rem;
    line-height: 1.2;
    padding: .2rem .48rem;
  }

  .moeve-choice {
    cursor: pointer;
    font-family: inherit;
  }

  .moeve-badge.is-candidate {
    background: #fff7ed;
    border-color: #fdba74;
    color: #9a3412;
  }

  .moeve-badge.is-preference,
  .moeve-choice.is-preference {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1e40af;
  }

  .moeve-choice.is-preference:hover,
  .moeve-choice.is-preference.is-selected {
    background: #dbeafe;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
  }

  .moeve-badge.is-declined,
  .moeve-choice.is-declined,
  .moeve-assignment.is-declined {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #b91c1c;
  }

  .moeve-choice.is-declined:hover,
  .moeve-choice.is-declined.is-selected {
    background: #fee2e2;
    border-color: var(--moeve-red);
    box-shadow: 0 0 0 2px rgba(220, 38, 38, .12);
  }

  .moeve-assignment-target {
    min-width: 10rem;
  }

  .moeve-assignment.is-saved {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
  }

  .moeve-assignment.is-staged {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #334155;
  }

  .moeve-assignment.is-declined.is-staged {
    border-style: dashed;
  }

  .moeve-status-label {
    align-items: center;
    display: inline-flex;
    gap: .4rem;
  }

  .moeve-status-dot {
    background: var(--moeve-status-color, #64748b);
    border: 1px solid rgba(15, 23, 42, .2);
    border-radius: 50%;
    display: inline-block;
    height: .8rem;
    width: .8rem;
  }

  .moeve-empty-value {
    color: #64748b;
    font-style: italic;
  }

  .moeve-nowrap {
    white-space: nowrap;
  }

  .moeve-bulk-actions {
    background: #f8fafc;
    border: 1px solid var(--moeve-border);
    border-radius: .45rem;
    display: grid;
    gap: .75rem;
    margin-top: 1rem;
    padding: 1rem;
  }

  .moeve-bulk-intro {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem 1rem;
    justify-content: space-between;
  }

  .moeve-bulk-action {
    align-items: center;
    background: #fff;
    border: 1px solid #dbe3ed;
    border-left: .3rem solid #64748b;
    border-radius: .35rem;
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    justify-content: space-between;
    padding: .75rem;
  }

  .moeve-bulk-action.is-save {
    border-left-color: var(--moeve-green);
  }

  .moeve-bulk-action.is-declined {
    border-left-color: var(--moeve-red);
  }

  .moeve-button-danger {
    background: #b91c1c !important;
    border-color: #991b1b !important;
    color: #fff !important;
  }

  @media (max-width: 900px) {
    .moeve-summary {
      grid-template-columns: repeat(2, minmax(9rem, 1fr));
    }

    .moeve-bulk-action {
      align-items: stretch;
      flex-direction: column;
    }
  }
</style>

<script>
  CRM.$(function($) {
    var $workspace = $('#moeve-crewing-applications');
    var $choicesInput = $workspace.find('[name="moeve_bulk_choices"]');
    var $actionInput = $workspace.find('[name="moeve_bulk_action"]');
    var choices = {};
    var dirty = false;

    try {
      choices = JSON.parse($choicesInput.val() || '{}');
    }
    catch (error) {
      choices = {};
    }

    function renderRow($row) {
      var participantId = String($row.data('participant-id'));
      var initialChoice = String($row.data('initial-choice') || '');
      var initialLabel = String($row.data('initial-label') || '');
      var choice = String(choices[participantId] || '');
      var $target = $row.find('.moeve-assignment-target');
      var $button = $row.find('.moeve-choice').filter(function() {
        return String($(this).data('choice')) === choice;
      }).first();
      var label = $button.length
        ? String($button.data('label'))
        : (choice === initialChoice ? initialLabel : '');

      $row.find('.moeve-choice')
        .removeClass('is-selected')
        .attr('aria-pressed', 'false');
      if ($button.length) {
        $button.addClass('is-selected').attr('aria-pressed', 'true');
      }

      if (!choice || !label) {
        $target.empty().append(
          $('<span>').addClass('moeve-empty-value').text('Noch nicht zugeordnet')
        );
        return;
      }

      var staged = choice !== initialChoice;
      var $assignment = $('<span>')
        .addClass('moeve-assignment')
        .toggleClass('is-declined', choice === 'declined')
        .toggleClass('is-saved', !staged && choice !== 'declined')
        .toggleClass('is-staged', staged)
        .text(label);
      $target.empty().append($assignment);
    }

    function updateDirtyState() {
      var changed = 0;
      $workspace.find('.moeve-application-row').each(function() {
        var $row = $(this);
        var participantId = String($row.data('participant-id'));
        var initialChoice = String($row.data('initial-choice') || '');
        if (String(choices[participantId] || '') !== initialChoice) {
          changed++;
        }
      });
      dirty = changed > 0;
      $('#moeve-staged-count').text(changed
        ? changed + (changed === 1
          ? ' ungespeicherte Änderung.'
          : ' ungespeicherte Änderungen.')
        : 'Keine ungespeicherten Änderungen.');
    }

    $workspace.find('.moeve-application-row').each(function() {
      var $row = $(this);
      var participantId = String($row.data('participant-id'));
      if (
        !Object.prototype.hasOwnProperty.call(choices, participantId)
        || typeof choices[participantId] !== 'string'
      ) {
        choices[participantId] = String(
          $row.data('initial-choice') || ''
        );
      }
      renderRow($row);
    });
    $choicesInput.val(JSON.stringify(choices));
    updateDirtyState();

    $workspace.on('click', '.moeve-choice', function() {
      var $button = $(this);
      var $row = $button.closest('.moeve-application-row');
      var participantId = String($row.data('participant-id'));
      choices[participantId] = String($button.data('choice'));
      $choicesInput.val(JSON.stringify(choices));
      renderRow($row);
      updateDirtyState();
    });

    $workspace.on('click', '[data-moeve-action]', function(event) {
      event.preventDefault();
      var action = String($(this).data('moeve-action') || '');
      $actionInput.val(action);
      $choicesInput.val(JSON.stringify(choices));

      if (action === 'filter' && dirty) {
        if (!window.confirm(
          'Ungespeicherte Zuweisungen gehen beim Filtern verloren. Trotzdem filtern?'
        )) {
          return;
        }
      }

      var selectedCount = Object.keys(choices).filter(function(id) {
        return String(choices[id] || '') !== '';
      }).length;
      if (action !== 'filter' && selectedCount === 0) {
        window.alert('Es ist noch keine Zuweisung ausgewählt.');
        return;
      }

      if (action === 'apply_assigned_status') {
        var assignedCount = Object.keys(choices).filter(function(id) {
          return String(choices[id] || '').indexOf('role:') === 0;
        }).length;
        if (assignedCount === 0) {
          window.alert(
            'In der angezeigten Liste ist keine Crewing-Funktion zugewiesen.'
          );
          return;
        }
        if (!window.confirm(
          'Teilnahmestatus für ' + assignedCount
          + ' angezeigte Bewerbungen mit Crewfunktion ändern?'
        )) {
          return;
        }
      }
      else if (action === 'apply_declined_status') {
        var declinedCount = Object.keys(choices).filter(function(id) {
          return choices[id] === 'declined';
        }).length;
        if (declinedCount === 0) {
          window.alert(
            'In der angezeigten Liste ist keine Bewerbung als abgesagt markiert.'
          );
          return;
        }
        if (!window.confirm(
          'Teilnahmestatus für ' + declinedCount
          + ' angezeigte abgesagte Bewerbungen ändern?'
        )) {
          return;
        }
      }

      var $submitProxy = $workspace
        .find('.moeve-submit-proxy .crm-form-submit')
        .first();
      if (!$submitProxy.length) {
        window.alert('Das Formular konnte nicht abgesendet werden.');
        return;
      }

      dirty = false;
      var submitProxy = $submitProxy.get(0);
      if (
        submitProxy.form
        && typeof submitProxy.form.requestSubmit === 'function'
      ) {
        submitProxy.form.requestSubmit(submitProxy);
      }
      else {
        $submitProxy.trigger('click');
      }
    });

    window.addEventListener('beforeunload', function(event) {
      if (!dirty) {
        return;
      }
      event.preventDefault();
      event.returnValue = '';
    });
  });
</script>
{/literal}
