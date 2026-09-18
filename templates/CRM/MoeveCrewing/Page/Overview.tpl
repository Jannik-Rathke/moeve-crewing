<div id="moeve-crewing-overview" class="crm-container moeve-crewing-overview">
  <nav class="moeve-tabs" aria-label="Möwe-Crewing-Bereiche">
    {foreach from=$tabs item=tab}
      <a
        href="{$tab.url|escape}"
        class="moeve-tab{if $tab.active} is-active{/if}"
        {if $tab.active}aria-current="page"{/if}
      >{$tab.label|escape}</a>
    {/foreach}
  </nav>

  {if $activeView eq 'applications'}
    <section class="crm-block crm-content-block moeve-application-controls">
      <form method="get" action="{crmURL p='civicrm/moeve-crewing'}">
        <input type="hidden" name="reset" value="1">
        <input type="hidden" name="view" value="applications">

        <label for="moeve-application-year"><strong>Jahr</strong></label>
        <select id="moeve-application-year" name="year">
          {foreach from=$availableYears item=year}
            <option value="{$year|escape}"{if $year eq $selectedYear} selected{/if}>
              {$year|escape}
            </option>
          {/foreach}
        </select>

        <label for="moeve-application-event"><strong>Törn</strong></label>
        <select id="moeve-application-event" name="event_id">
          <option value="0">Alle Törns</option>
          {foreach from=$applicationEvents item=eventOption}
            <option
              value="{$eventOption.id|escape}"
              {if $eventOption.id eq $selectedEventId} selected{/if}
            >{$eventOption.label|escape}</option>
          {/foreach}
        </select>

        <label for="moeve-application-status"><strong>Status</strong></label>
        <select id="moeve-application-status" name="status_id">
          <option value="0">Alle Status</option>
          {foreach from=$applicationStatuses item=statusOption}
            <option
              value="{$statusOption.id|escape}"
              {if $statusOption.id eq $selectedStatusId} selected{/if}
            >{$statusOption.label|escape}{if !$statusOption.isActive} (inaktiv){/if}</option>
          {/foreach}
        </select>

        <button type="submit" class="crm-button">Filtern</button>
        <a href="{$applicationResetUrl|escape}" class="crm-button">Zurücksetzen</a>
      </form>
    </section>

    {if $pageError}
      <div class="messages error no-popup">
        <div class="icon error-icon"></div>
        <p>{$pageError|escape}</p>
      </div>
    {else}
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
            <p>
              Die Liste enthält potentielle Crewmitglieder und bereits einer
              Crewing-Funktion zugeordnete Teilnahmen.
            </p>
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
                  <tr>
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
                      {if $application.isCandidate}
                        <span class="moeve-badge is-candidate">Bewerbung</span>
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
                      {if $application.preferences}
                        <div class="moeve-badge-list">
                          {foreach from=$application.preferences item=preference}
                            <span class="moeve-badge is-preference">{$preference|escape}</span>
                          {/foreach}
                        </div>
                      {else}
                        <span class="moeve-empty-value">Keine angegeben</span>
                      {/if}
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
                    <td>
                      {if $application.assignedRoles}
                        <div class="moeve-badge-list">
                          {foreach from=$application.assignedRoles item=assignedRole}
                            <span class="moeve-badge is-assigned">{$assignedRole|escape}</span>
                          {/foreach}
                        </div>
                      {else}
                        <span class="moeve-empty-value">Noch nicht zugeordnet</span>
                      {/if}
                    </td>
                    <td>
                      <div class="moeve-action-list">
                        <a href="{$application.participantUrl|escape}" class="crm-button">
                          Teilnahme
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
        {else}
          <div class="messages status no-popup">
            <div class="icon inform-icon"></div>
            <p>Für die gewählten Filter wurden keine Crewing-Bewerbungen gefunden.</p>
          </div>
        {/if}
      </section>
    {/if}
  {elseif $activeView neq 'year'}
    <section class="crm-block crm-content-block moeve-placeholder">
      <h2>{$placeholder.title|escape}</h2>
      <p>{$placeholder.text|escape}</p>
      <p class="description">
        Der Reiter ist bereits vorbereitet und wird in einer der nächsten
        Ausbaustufen mit Funktionen gefüllt.
      </p>
    </section>
  {else}
    <section class="crm-block crm-content-block moeve-year-controls">
      <form method="get" action="{crmURL p='civicrm/moeve-crewing'}">
        <input type="hidden" name="reset" value="1">
        <input type="hidden" name="view" value="year">
        <label for="moeve-year"><strong>Jahr</strong></label>
        <select id="moeve-year" name="year">
          {foreach from=$availableYears item=year}
            <option value="{$year|escape}"{if $year eq $selectedYear} selected{/if}>
              {$year|escape}
            </option>
          {/foreach}
        </select>
        <button type="submit" class="crm-button">Anzeigen</button>
        <button
          type="button"
          id="moeve-toggle-labels"
          class="crm-button"
          data-hide-label="Törnnamen ausblenden"
          data-show-label="Törnnamen einblenden"
        >Törnnamen ausblenden</button>
      </form>
    </section>

    {if $pageError}
      <div class="messages error no-popup">
        <div class="icon error-icon"></div>
        <p>{$pageError|escape}</p>
      </div>
    {else}
      <section class="moeve-summary" aria-label="Zusammenfassung">
        <div class="moeve-summary-card">
          <span class="moeve-summary-value">{$summary.eventCount|escape}</span>
          <span class="moeve-summary-label">Törns</span>
        </div>
        <div class="moeve-summary-card">
          <span class="moeve-summary-value">{$summary.roleCount|escape}</span>
          <span class="moeve-summary-label">Funktionen</span>
        </div>
        <div class="moeve-summary-card">
          <span class="moeve-summary-value">{$summary.peopleCount|escape}</span>
          <span class="moeve-summary-label">Personen</span>
        </div>
        <div class="moeve-summary-card is-covered">
          <span class="moeve-summary-value">{$summary.coveredCount|escape}</span>
          <span class="moeve-summary-label">Bedarfe gedeckt</span>
        </div>
        <div class="moeve-summary-card is-attention">
          <span class="moeve-summary-value">{$summary.attentionCount|escape}</span>
          <span class="moeve-summary-label">Bedarfe offen</span>
        </div>
      </section>

      <section class="crm-block crm-content-block moeve-matrix-section">
        <div class="moeve-section-heading">
          <div>
            <h2>Jahresübersicht nach Funktion</h2>
            <p>
              Jede Spalte ist ein Törn. Ein Klick auf ein Kästchen öffnet die
              zugehörige Veranstaltung.
            </p>
          </div>
          <div class="moeve-legend" aria-label="Legende Funktionsübersicht">
            <span><i class="moeve-swatch is-covered"></i> Bedarf gedeckt</span>
            <span><i class="moeve-swatch is-attention"></i> Bewerbung/Vormerkung</span>
            <span><i class="moeve-swatch is-gap"></i> Bedarf offen</span>
            <span><i class="moeve-swatch is-inactive"></i> Nicht benötigt</span>
          </div>
        </div>

        {if $events}
          <div class="moeve-matrix-scroll" tabindex="0">
            <table class="moeve-matrix moeve-role-matrix">
              <thead>
                <tr>
                  <th class="moeve-row-label">Funktion</th>
                  {foreach from=$events item=event}
                    <th class="moeve-voyage-header" title="{$event.tooltip|escape}">
                      <a href="{$event.url|escape}" class="moeve-voyage-label">
                        {$event.title|escape}
                      </a>
                    </th>
                  {/foreach}
                </tr>
              </thead>
              <tbody>
                {foreach from=$roleRows item=roleRow}
                  <tr>
                    <th scope="row" class="moeve-row-label">
                      <span>{$roleRow.label|escape}</span>
                    </th>
                    {foreach from=$roleRow.cells item=cell}
                      <td>
                        <a
                          href="{$cell.url|escape}"
                          class="moeve-matrix-cell is-{$cell.state|escape}"
                          title="{$cell.title|escape}"
                          aria-label="{$cell.title|escape}"
                        ></a>
                      </td>
                    {/foreach}
                  </tr>
                {/foreach}
              </tbody>
            </table>
          </div>
        {else}
          <div class="messages status no-popup">
            <div class="icon inform-icon"></div>
            <p>Für {$selectedYear|escape} wurden keine Törns gefunden.</p>
          </div>
        {/if}
      </section>

      <section class="crm-block crm-content-block moeve-matrix-section">
        <div class="moeve-section-heading">
          <div>
            <h2>Jahresübersicht nach Person</h2>
            <p>
              Aufgeführt werden alle Personen mit einer Bewerbung oder Teilnahme
              in diesem Jahr. Leere Felder bedeuten: keine Bewerbung.
            </p>
          </div>
        </div>

        {if $statusLegend}
          <div class="moeve-legend moeve-status-legend" aria-label="Teilnahmestatusfarben">
            {foreach from=$statusLegend item=status}
              <span>
                <i class="moeve-swatch" style="--moeve-swatch-color: {$status.color|escape};"></i>
                {$status.label|escape}
              </span>
            {/foreach}
          </div>
        {/if}

        {if $events && $peopleRows}
          <div class="moeve-matrix-scroll" tabindex="0">
            <table class="moeve-matrix moeve-people-matrix">
              <thead>
                <tr>
                  <th class="moeve-row-label">Person</th>
                  {foreach from=$events item=event}
                    <th class="moeve-voyage-header" title="{$event.tooltip|escape}">
                      <a href="{$event.url|escape}" class="moeve-voyage-label">
                        {$event.title|escape}
                      </a>
                    </th>
                  {/foreach}
                </tr>
              </thead>
              <tbody>
                {foreach from=$peopleRows item=person}
                  <tr>
                    <th scope="row" class="moeve-row-label">
                      <a href="{$person.contactUrl|escape}">{$person.displayName|escape}</a>
                    </th>
                    {foreach from=$person.cells item=cell}
                      <td>
                        <a
                          href="{$cell.url|escape}"
                          class="moeve-matrix-cell moeve-person-cell{if !$cell.hasParticipation} is-empty{/if}"
                          {if $cell.hasParticipation}style="--moeve-cell-color: {$cell.color|escape};"{/if}
                          title="{$cell.title|escape}"
                          aria-label="{$cell.title|escape}"
                        ></a>
                      </td>
                    {/foreach}
                  </tr>
                {/foreach}
              </tbody>
            </table>
          </div>
        {elseif $events}
          <div class="messages status no-popup">
            <div class="icon inform-icon"></div>
            <p>Für {$selectedYear|escape} gibt es noch keine Bewerbungen oder Teilnahmen.</p>
          </div>
        {/if}
      </section>
    {/if}
  {/if}
</div>

{literal}
<style>
  .moeve-crewing-overview {
    --moeve-border: #cbd5e1;
    --moeve-covered: #16a34a;
    --moeve-attention: #f59e0b;
    --moeve-gap: #dc2626;
    --moeve-inactive: #d1d5db;
    color: #253858;
  }

  .moeve-tabs {
    border-bottom: 1px solid var(--moeve-border);
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin: 0 0 1rem;
  }

  .moeve-tab {
    border-radius: .4rem .4rem 0 0;
    color: #075985;
    display: inline-block;
    font-weight: 700;
    padding: .7rem 1rem;
    text-decoration: none;
  }

  .moeve-tab:hover,
  .moeve-tab:focus {
    background: #e0f2fe;
  }

  .moeve-tab.is-active {
    background: #075985;
    color: #fff;
  }

  .moeve-placeholder,
  .moeve-application-controls,
  .moeve-application-section,
  .moeve-year-controls,
  .moeve-matrix-section {
    border-radius: .45rem;
    margin-bottom: 1rem;
    padding: 1rem;
  }

  .moeve-application-controls form,
  .moeve-year-controls form {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
  }

  .moeve-application-controls select {
    max-width: 24rem;
    min-width: 10rem;
  }

  .moeve-year-controls select {
    min-width: 7rem;
  }

  .moeve-summary {
    display: grid;
    gap: .75rem;
    grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr));
    margin: 0 0 1rem;
  }

  .moeve-summary-card {
    background: #f8fafc;
    border: 1px solid var(--moeve-border);
    border-radius: .45rem;
    display: flex;
    flex-direction: column;
    padding: .8rem 1rem;
  }

  .moeve-summary-card.is-covered {
    border-left: .35rem solid var(--moeve-covered);
  }

  .moeve-summary-card.is-attention {
    border-left: .35rem solid var(--moeve-gap);
  }

  .moeve-summary-card.is-pending {
    border-left: .35rem solid var(--moeve-attention);
  }

  .moeve-summary-card.is-negative {
    border-left: .35rem solid var(--moeve-gap);
  }

  .moeve-summary-value {
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.1;
  }

  .moeve-summary-label {
    color: #475569;
    font-size: .85rem;
    margin-top: .25rem;
  }

  .moeve-section-heading {
    align-items: flex-start;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: space-between;
  }

  .moeve-section-heading h2 {
    margin: 0 0 .25rem;
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
    min-width: 78rem;
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

  .moeve-application-table small {
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

  .moeve-badge {
    border: 1px solid transparent;
    border-radius: 999px;
    display: inline-block;
    font-size: .78rem;
    line-height: 1.2;
    padding: .2rem .45rem;
  }

  .moeve-badge.is-candidate {
    background: #fff7ed;
    border-color: #fdba74;
    color: #9a3412;
  }

  .moeve-badge.is-preference {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1e40af;
  }

  .moeve-badge.is-assigned {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
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

  .moeve-legend {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem 1rem;
    margin: .25rem 0 .85rem;
  }

  .moeve-legend span {
    align-items: center;
    display: inline-flex;
    font-size: .85rem;
    gap: .35rem;
  }

  .moeve-swatch {
    background: var(--moeve-swatch-color, #64748b);
    border: 1px solid rgba(15, 23, 42, .18);
    border-radius: .2rem;
    display: inline-block;
    height: 1rem;
    width: 1rem;
  }

  .moeve-swatch.is-covered { background: var(--moeve-covered); }
  .moeve-swatch.is-attention { background: var(--moeve-attention); }
  .moeve-swatch.is-gap { background: var(--moeve-gap); }
  .moeve-swatch.is-inactive { background: var(--moeve-inactive); }

  .moeve-matrix-scroll {
    border: 1px solid var(--moeve-border);
    max-height: 70vh;
    overflow: auto;
    position: relative;
  }

  .moeve-matrix {
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
    width: max-content;
  }

  .moeve-matrix th,
  .moeve-matrix td {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
    box-sizing: border-box;
    height: 2.25rem;
    padding: .2rem;
    text-align: center;
  }

  .moeve-matrix thead th {
    background: #f8fafc;
    position: sticky;
    top: 0;
    z-index: 3;
  }

  .moeve-matrix .moeve-row-label {
    left: 0;
    min-width: 13rem;
    padding: .45rem .7rem;
    position: sticky;
    text-align: left;
    width: 13rem;
    z-index: 2;
  }

  .moeve-matrix thead .moeve-row-label {
    z-index: 5;
  }

  .moeve-voyage-header {
    height: 10rem !important;
    min-width: 2.35rem;
    vertical-align: bottom;
    width: 2.35rem;
  }

  .moeve-voyage-label {
    color: #075985;
    display: inline-block;
    font-size: .78rem;
    font-weight: 700;
    line-height: 1;
    max-height: 9rem;
    overflow: hidden;
    text-decoration: none;
    text-overflow: ellipsis;
    transform: rotate(180deg);
    white-space: nowrap;
    writing-mode: vertical-rl;
  }

  .moeve-matrix td {
    min-width: 2.35rem;
    width: 2.35rem;
  }

  .moeve-matrix-cell {
    border: 1px solid rgba(15, 23, 42, .16);
    border-radius: .25rem;
    box-sizing: border-box;
    display: block;
    height: 1.7rem;
    margin: auto;
    outline-offset: 1px;
    width: 1.7rem;
  }

  .moeve-matrix-cell:hover,
  .moeve-matrix-cell:focus {
    box-shadow: 0 0 0 2px #0284c7;
  }

  .moeve-matrix-cell.is-covered { background: var(--moeve-covered); }
  .moeve-matrix-cell.is-attention { background: var(--moeve-attention); }
  .moeve-matrix-cell.is-gap { background: var(--moeve-gap); }
  .moeve-matrix-cell.is-inactive { background: var(--moeve-inactive); }
  .moeve-person-cell { background: var(--moeve-cell-color); }
  .moeve-person-cell.is-empty { background: #fff; border-style: dashed; }

  .moeve-hide-column-labels .moeve-voyage-header {
    height: 2.5rem !important;
  }

  .moeve-hide-column-labels .moeve-voyage-label {
    display: none;
  }

  @media (max-width: 700px) {
    .moeve-matrix .moeve-row-label {
      min-width: 10rem;
      width: 10rem;
    }
  }
</style>
<script>
  CRM.$(function($) {
    var $overview = $('#moeve-crewing-overview');
    var $button = $('#moeve-toggle-labels');
    var storageKey = 'moeveCrewingHideColumnLabels';

    function setLabelsHidden(hidden) {
      $overview.toggleClass('moeve-hide-column-labels', hidden);
      $button.text(hidden
        ? $button.data('show-label')
        : $button.data('hide-label'));
      $button.attr('aria-pressed', hidden ? 'true' : 'false');
    }

    var hidden = false;
    try {
      hidden = window.localStorage.getItem(storageKey) === '1';
    }
    catch (error) {
      hidden = false;
    }
    setLabelsHidden(hidden);

    $button.on('click', function() {
      hidden = !$overview.hasClass('moeve-hide-column-labels');
      setLabelsHidden(hidden);
      try {
        window.localStorage.setItem(storageKey, hidden ? '1' : '0');
      }
      catch (error) {
        // The preference is optional when local storage is unavailable.
      }
    });
  });
</script>
{/literal}
