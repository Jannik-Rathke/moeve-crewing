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

  {if $activeView neq 'year'}
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
  .moeve-year-controls,
  .moeve-matrix-section {
    border-radius: .45rem;
    margin-bottom: 1rem;
    padding: 1rem;
  }

  .moeve-year-controls form {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
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
