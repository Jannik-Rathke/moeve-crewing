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

  {if $activeView eq 'cockpit'}
    <section class="crm-block crm-content-block moeve-cockpit-controls">
      <form method="get" action="{crmURL p='civicrm/moeve-crewing'}">
        <input type="hidden" name="reset" value="1">
        <input type="hidden" name="view" value="cockpit">
        <label for="moeve-cockpit-year"><strong>Planungsjahr</strong></label>
        <select id="moeve-cockpit-year" name="year">
          {foreach from=$availableYears item=year}
            <option value="{$year|escape}"{if $year eq $selectedYear} selected{/if}>
              {$year|escape}
            </option>
          {/foreach}
        </select>
        <button type="submit" class="crm-button">Anzeigen</button>
      </form>
    </section>

    {if $pageError}
      <div class="messages error no-popup">
        <div class="icon error-icon"></div>
        <p>{$pageError|escape}</p>
      </div>
    {else}
      <section class="moeve-summary" aria-label="Zusammenfassung Cockpit">
        <div class="moeve-summary-card">
          <span class="moeve-summary-value">{$cockpitSummary.upcomingEventCount|escape}</span>
          <span class="moeve-summary-label">Kommende Törns</span>
        </div>
        <div class="moeve-summary-card">
          <span class="moeve-summary-value">{$cockpitSummary.requiredCount|escape}</span>
          <span class="moeve-summary-label">Soll-Plätze</span>
        </div>
        <div class="moeve-summary-card is-covered">
          <span class="moeve-summary-value">{$cockpitSummary.confirmedCount|escape}</span>
          <span class="moeve-summary-label">Bestätigt</span>
        </div>
        <div class="moeve-summary-card is-attention">
          <span class="moeve-summary-value">{$cockpitSummary.openCount|escape}</span>
          <span class="moeve-summary-label">Offene Plätze</span>
        </div>
        <div class="moeve-summary-card is-pending">
          <span class="moeve-summary-value">{$cockpitSummary.applicationCount|escape}</span>
          <span class="moeve-summary-label">Bewerbungen</span>
        </div>
      </section>

      <section class="moeve-cockpit-alerts" aria-label="Offene Aufgaben">
        <a href="{$cockpitUrls.voyages|escape}" class="moeve-task-card{if !$cockpitSummary.attentionEventCount} is-done{/if}">
          <span class="moeve-task-number">{$cockpitSummary.attentionEventCount|escape}</span>
          <span>
            <strong>Törns mit offenem Crewbedarf</strong>
            <small>Besetzung prüfen und vervollständigen</small>
          </span>
        </a>
        <a href="{$cockpitUrls.applications|escape}" class="moeve-task-card{if !$cockpitSummary.applicationCount} is-done{/if}">
          <span class="moeve-task-number">{$cockpitSummary.applicationCount|escape}</span>
          <span>
            <strong>Unbearbeitete Bewerbungen</strong>
            <small>Funktion und Teilnahmestatus festlegen</small>
          </span>
        </a>
        <a href="{$cockpitUrls.voyages|escape}" class="moeve-task-card{if !$cockpitSummary.missingInfoEventCount} is-done{/if}">
          <span class="moeve-task-number">{$cockpitSummary.missingInfoEventCount|escape}</span>
          <span>
            <strong>Törns mit fehlenden Angaben</strong>
            <small>Strecke, Bordzeiten oder Organisation ergänzen</small>
          </span>
        </a>
      </section>

      <div class="moeve-cockpit-layout">
        <section class="crm-block crm-content-block moeve-cockpit-section moeve-next-voyages">
          <div class="moeve-section-heading">
            <div>
              <h2>Nächste Törns</h2>
              <p>Die nächsten laufenden oder bevorstehenden Veranstaltungen.</p>
            </div>
            <a href="{$cockpitUrls.voyages|escape}" class="crm-button">Alle Törns planen</a>
          </div>

          {if $cockpitVoyages}
            <div class="moeve-cockpit-voyage-list">
              {foreach from=$cockpitVoyages item=voyage}
                <article class="moeve-cockpit-voyage">
                  <div class="moeve-cockpit-voyage-main">
                    <div class="moeve-cockpit-voyage-kicker">
                      {if $voyage.timingLabel}
                        <span>{$voyage.timingLabel|escape}</span>
                      {/if}
                      {if $voyage.event.number}
                        <span>{$voyage.event.number|escape}</span>
                      {/if}
                    </div>
                    <h3>
                      <a href="{$voyage.voyageUrl|escape}">{$voyage.event.title|escape}</a>
                    </h3>
                    <p>
                      {$voyage.event.dateTimeLabel|escape}
                      {if $voyage.event.routeLabel}
                        <span aria-hidden="true"> · </span>{$voyage.event.routeLabel|escape}
                      {/if}
                    </p>
                    <div class="moeve-progress" style="--moeve-progress: {$voyage.completionPercent|escape}%;">
                      <span></span>
                    </div>
                    <div class="moeve-cockpit-voyage-counts">
                      <span><strong>{$voyage.summary.confirmedCount|escape}/{$voyage.summary.requiredCount|escape}</strong> bestätigt</span>
                      <span><strong>{$voyage.summary.openCount|escape}</strong> offen</span>
                      <span><strong>{$voyage.summary.applicationCount|escape}</strong> Bewerbungen</span>
                    </div>
                    {if $voyage.missingInformation}
                      <div class="moeve-missing-info">
                        Fehlende Angaben: {$voyage.missingInformationLabel|escape}
                      </div>
                    {/if}
                  </div>
                  <div class="moeve-cockpit-voyage-actions">
                    <span class="moeve-state-label is-{$voyage.state|escape}">
                      {$voyage.stateLabel|escape}
                    </span>
                    {if $canManage}
                      <a href="{$voyage.voyageUrl|escape}" class="crm-button">Planen</a>
                    {/if}
                    {if $voyage.summary.applicationCount}
                      <a href="{$voyage.applicationsUrl|escape}" class="crm-button">Bewerbungen</a>
                    {/if}
                  </div>
                </article>
              {/foreach}
            </div>
          {else}
            <div class="messages status no-popup">
              <div class="icon inform-icon"></div>
              <p>Für {$selectedYear|escape} gibt es keine kommenden Törns.</p>
            </div>
          {/if}
        </section>

        <section class="crm-block crm-content-block moeve-cockpit-section moeve-cockpit-applications">
          <div class="moeve-section-heading">
            <div>
              <h2>Neue Bewerbungen</h2>
              <p>Die zuletzt eingegangenen, noch nicht zugeordneten Bewerbungen.</p>
            </div>
            <a href="{$cockpitUrls.applications|escape}" class="crm-button">Alle Bewerbungen</a>
          </div>

          {if $cockpitApplications}
            <ul class="moeve-cockpit-application-list">
              {foreach from=$cockpitApplications item=application}
                <li>
                  <div>
                    <a href="{$application.contactUrl|escape}" class="moeve-person-link">
                      {$application.displayName|escape}
                    </a>
                    <small>
                      {$application.eventTitle|escape}
                      {if $application.eventDateLabel} · {$application.eventDateLabel|escape}{/if}
                    </small>
                    {if $application.preferences}
                      <div class="moeve-badge-list">
                        {foreach from=$application.preferences item=preference}
                          <span class="moeve-badge is-preference">{$preference|escape}</span>
                        {/foreach}
                      </div>
                    {else}
                      <span class="moeve-empty-value">Keine Wunschfunktion</span>
                    {/if}
                  </div>
                  {if $canManage}
                    <a href="{$application.decisionUrl|escape}" class="crm-button">Entscheiden</a>
                  {/if}
                </li>
              {/foreach}
            </ul>
          {else}
            <div class="moeve-cockpit-empty is-success">
              <strong>Alles bearbeitet</strong>
              <span>Aktuell warten keine Bewerbungen auf eine Zuordnung.</span>
            </div>
          {/if}
        </section>
      </div>

      <section class="crm-block crm-content-block moeve-cockpit-section">
        <div class="moeve-section-heading">
          <div>
            <h2>Dringendste offene Funktionen</h2>
            <p>Nach dem Startdatum des Törns priorisiert.</p>
          </div>
          <a href="{$cockpitUrls.voyages|escape}" class="crm-button">Zur Törnplanung</a>
        </div>

        {if $cockpitNeeds}
          <div class="moeve-cockpit-needs-wrap" tabindex="0">
            <table class="selector row-highlight moeve-cockpit-needs">
              <thead>
                <tr>
                  <th>Törn</th>
                  <th>Funktion</th>
                  <th>Soll / bestätigt</th>
                  <th>Vorgemerkt</th>
                  <th>Bewerbungen</th>
                  <th>Offen</th>
                  <th>Aktion</th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$cockpitNeeds item=need}
                  <tr>
                    <td>
                      <a href="{$need.voyageUrl|escape}">{$need.eventTitle|escape}</a>
                      <small>
                        {if $need.eventNumber}{$need.eventNumber|escape} · {/if}{$need.eventDateLabel|escape}
                      </small>
                    </td>
                    <th scope="row">{$need.roleLabel|escape}</th>
                    <td>{$need.requiredCount|escape} / {$need.confirmedCount|escape}</td>
                    <td>{$need.provisionalCount|escape}</td>
                    <td>{$need.applicationCount|escape}</td>
                    <td>
                      <span class="moeve-state-label is-{$need.state|escape}">
                        {$need.openCount|escape} offen
                      </span>
                    </td>
                    <td>
                      {if $need.applicationCount}
                        <a href="{$need.applicationsUrl|escape}" class="crm-button">Bewerbungen</a>
                      {elseif $canManage}
                        <a href="{$need.voyageUrl|escape}" class="crm-button">Planen</a>
                      {/if}
                    </td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          </div>
        {else}
          <div class="moeve-cockpit-empty is-success">
            <strong>Alle kommenden Bedarfe sind gedeckt</strong>
            <span>Für die ausgewählten Törns fehlen derzeit keine bestätigten Crewmitglieder.</span>
          </div>
        {/if}
      </section>

      <section class="moeve-cockpit-shortcuts" aria-label="Schnellzugriffe">
        <a href="{$cockpitUrls.voyages|escape}"><strong>Törnplanung</strong><span>Besetzung je Törn bearbeiten</span></a>
        <a href="{$cockpitUrls.applications|escape}"><strong>Bewerbungen</strong><span>Teilnahmen prüfen und zuordnen</span></a>
        <a href="{$cockpitUrls.year|escape}"><strong>Jahresübersicht</strong><span>Bedarfe und Personen vergleichen</span></a>
        <a href="{$cockpitUrls.setup|escape}"><strong>Einrichtung</strong><span>Felder, Rollen und Farben konfigurieren</span></a>
      </section>
    {/if}
  {elseif $activeView eq 'voyages'}
    <section class="crm-block crm-content-block moeve-voyage-controls">
      <form method="get" action="{crmURL p='civicrm/moeve-crewing'}">
        <input type="hidden" name="reset" value="1">
        <input type="hidden" name="view" value="voyages">

        <label for="moeve-voyage-year"><strong>Jahr</strong></label>
        <select id="moeve-voyage-year" name="year">
          {foreach from=$availableYears item=year}
            <option value="{$year|escape}"{if $year eq $selectedYear} selected{/if}>
              {$year|escape}
            </option>
          {/foreach}
        </select>

        <label for="moeve-voyage-event"><strong>Törn</strong></label>
        <select id="moeve-voyage-event" name="event_id">
          <option value="0">Alle Törns</option>
          {foreach from=$voyageEvents item=eventOption}
            <option
              value="{$eventOption.id|escape}"
              {if $eventOption.id eq $selectedEventId} selected{/if}
            >{$eventOption.label|escape}</option>
          {/foreach}
        </select>

        <button type="submit" class="crm-button">Filtern</button>
        <a href="{$voyageResetUrl|escape}" class="crm-button">Zurücksetzen</a>
      </form>
    </section>

    {if $pageError}
      <div class="messages error no-popup">
        <div class="icon error-icon"></div>
        <p>{$pageError|escape}</p>
      </div>
    {else}
      <section class="moeve-summary" aria-label="Zusammenfassung Törnplanung">
        <div class="moeve-summary-card">
          <span class="moeve-summary-value">{$voyageSummary.eventCount|escape}</span>
          <span class="moeve-summary-label">Törns</span>
        </div>
        <div class="moeve-summary-card">
          <span class="moeve-summary-value">{$voyageSummary.requiredCount|escape}</span>
          <span class="moeve-summary-label">Soll-Plätze</span>
        </div>
        <div class="moeve-summary-card is-covered">
          <span class="moeve-summary-value">{$voyageSummary.confirmedCount|escape}</span>
          <span class="moeve-summary-label">Bestätigt</span>
        </div>
        <div class="moeve-summary-card is-attention">
          <span class="moeve-summary-value">{$voyageSummary.openCount|escape}</span>
          <span class="moeve-summary-label">Noch offen</span>
        </div>
        <div class="moeve-summary-card is-pending">
          <span class="moeve-summary-value">{$voyageSummary.applicationCount|escape}</span>
          <span class="moeve-summary-label">Bewerbungen</span>
        </div>
      </section>

      {if $voyages}
        <div class="moeve-voyage-list">
          {foreach from=$voyages item=voyage}
            <article class="crm-block crm-content-block moeve-voyage-card">
              <header class="moeve-voyage-card-header">
                <div>
                  {if $voyage.event.number}
                    <div class="moeve-voyage-number">{$voyage.event.number|escape}</div>
                  {/if}
                  <h2>
                    <a href="{$voyage.event.url|escape}">{$voyage.event.title|escape}</a>
                  </h2>
                  {if $voyage.event.dateTimeLabel}
                    <p>{$voyage.event.dateTimeLabel|escape}</p>
                  {/if}
                </div>
                <div class="moeve-voyage-head-summary">
                  <span class="moeve-count-pill is-covered">
                    {$voyage.summary.confirmedCount|escape}/{$voyage.summary.requiredCount|escape} bestätigt
                  </span>
                  {if $voyage.summary.openCount}
                    <span class="moeve-count-pill is-gap">
                      {$voyage.summary.openCount|escape} offen
                    </span>
                  {/if}
                  {if $voyage.summary.applicationCount}
                    <span class="moeve-count-pill is-attention">
                      {$voyage.summary.applicationCount|escape} Bewerbungen
                    </span>
                  {/if}
                  {if $canManage}
                    <a href="{$voyage.event.url|escape}" class="crm-button">Törn bearbeiten</a>
                  {/if}
                </div>
              </header>

              <dl class="moeve-voyage-meta">
                <div>
                  <dt>Strecke</dt>
                  <dd>
                    {if $voyage.event.routeLabel}
                      {$voyage.event.routeLabel|escape}
                    {else}
                      <span class="moeve-empty-value">Nicht hinterlegt</span>
                    {/if}
                  </dd>
                </div>
                <div>
                  <dt>Stamm an Bord</dt>
                  <dd>
                    {if $voyage.event.crewOnBoardLabel}
                      {$voyage.event.crewOnBoardLabel|escape}
                    {else}
                      <span class="moeve-empty-value">Nicht hinterlegt</span>
                    {/if}
                  </dd>
                </div>
                <div>
                  <dt>Stamm von Bord</dt>
                  <dd>
                    {if $voyage.event.crewOffBoardLabel}
                      {$voyage.event.crewOffBoardLabel|escape}
                    {else}
                      <span class="moeve-empty-value">Nicht hinterlegt</span>
                    {/if}
                  </dd>
                </div>
                <div>
                  <dt>Organisation</dt>
                  <dd>
                    {if $voyage.event.organizerName}
                      <a href="{$voyage.event.organizerUrl|escape}">
                        {$voyage.event.organizerName|escape}
                      </a>
                    {else}
                      <span class="moeve-empty-value">Nicht hinterlegt</span>
                    {/if}
                  </dd>
                </div>
              </dl>

              {if $voyage.event.comment}
                <div class="moeve-voyage-comment">
                  <strong>Kommentar</strong>
                  <p>{$voyage.event.comment|escape|nl2br}</p>
                </div>
              {/if}

              <section class="moeve-demand-section">
                <h3>Besetzungsbedarf</h3>
                {if $voyage.demands}
                  <div class="moeve-demand-table-wrap" tabindex="0">
                    <table class="selector row-highlight moeve-demand-table">
                      <thead>
                        <tr>
                          <th>Funktion</th>
                          <th>Soll / bestätigt</th>
                          <th>Bestätigt</th>
                          <th>Vorgemerkt</th>
                          <th>Bewerbungen</th>
                          <th>Zustand</th>
                        </tr>
                      </thead>
                      <tbody>
                        {foreach from=$voyage.demands item=demand}
                          <tr>
                            <th scope="row">{$demand.label|escape}</th>
                            <td class="moeve-nowrap">
                              <strong>{$demand.required|escape} / {$demand.confirmedCount|escape}</strong>
                            </td>
                            <td>
                              {if $demand.confirmed}
                                <div class="moeve-person-chips">
                                  {foreach from=$demand.confirmed item=person}
                                    <a href="{if $canManage}{$person.decisionUrl|escape}{else}{$person.contactUrl|escape}{/if}" class="moeve-person-chip">
                                      <i
                                        class="moeve-status-dot"
                                        style="--moeve-status-color: {$person.status.color|escape};"
                                      ></i>
                                      {$person.displayName|escape}
                                    </a>
                                  {/foreach}
                                </div>
                              {else}
                                <span class="moeve-empty-value">–</span>
                              {/if}
                            </td>
                            <td>
                              {if $demand.provisional}
                                <div class="moeve-person-chips">
                                  {foreach from=$demand.provisional item=person}
                                    <a href="{if $canManage}{$person.decisionUrl|escape}{else}{$person.contactUrl|escape}{/if}" class="moeve-person-chip">
                                      <i
                                        class="moeve-status-dot"
                                        style="--moeve-status-color: {$person.status.color|escape};"
                                      ></i>
                                      {$person.displayName|escape}
                                    </a>
                                  {/foreach}
                                </div>
                              {else}
                                <span class="moeve-empty-value">–</span>
                              {/if}
                            </td>
                            <td>
                              {if $demand.applications}
                                <div class="moeve-person-chips">
                                  {foreach from=$demand.applications item=person}
                                    <a href="{if $canManage}{$person.decisionUrl|escape}{else}{$person.contactUrl|escape}{/if}" class="moeve-person-chip is-application">
                                      <i
                                        class="moeve-status-dot"
                                        style="--moeve-status-color: {$person.status.color|escape};"
                                      ></i>
                                      {$person.displayName|escape}
                                    </a>
                                  {/foreach}
                                </div>
                              {else}
                                <span class="moeve-empty-value">–</span>
                              {/if}
                            </td>
                            <td>
                              <span class="moeve-state-label is-{$demand.state|escape}">
                                {$demand.stateLabel|escape}
                              </span>
                            </td>
                          </tr>
                        {/foreach}
                      </tbody>
                    </table>
                  </div>
                {else}
                  <div class="messages status no-popup">
                    <div class="icon inform-icon"></div>
                    <p>Für diesen Törn sind keine Crewing-Funktionen freigeschaltet.</p>
                  </div>
                {/if}
              </section>

              <div class="moeve-voyage-people-grid">
                <section>
                  <h3>Eingeplante Crew <span>{$voyage.summary.crewCount|escape}</span></h3>
                  {if $voyage.crew}
                    <ul class="moeve-person-list">
                      {foreach from=$voyage.crew item=member}
                        <li>
                          <div>
                            <a href="{$member.contactUrl|escape}" class="moeve-person-link">
                              {$member.displayName|escape}
                            </a>
                            <div class="moeve-badge-list">
                              {foreach from=$member.roles item=roleLabel}
                                <span class="moeve-badge is-assigned">{$roleLabel|escape}</span>
                              {/foreach}
                            </div>
                          </div>
                          <div class="moeve-person-list-actions">
                            <span class="moeve-status-label">
                              <i
                                class="moeve-status-dot"
                                style="--moeve-status-color: {$member.status.color|escape};"
                              ></i>
                              {$member.status.label|escape}
                            </span>
                            {if $canManage}
                              <a href="{$member.decisionUrl|escape}" class="crm-button">Bearbeiten</a>
                            {/if}
                          </div>
                        </li>
                      {/foreach}
                    </ul>
                  {else}
                    <p class="moeve-empty-value">Noch niemand eingeplant.</p>
                  {/if}
                </section>

                <section>
                  <h3>Offene Bewerbungen <span>{$voyage.summary.applicationCount|escape}</span></h3>
                  {if $voyage.applications}
                    <ul class="moeve-person-list">
                      {foreach from=$voyage.applications item=application}
                        <li>
                          <div>
                            <a href="{$application.contactUrl|escape}" class="moeve-person-link">
                              {$application.displayName|escape}
                            </a>
                            {if $application.preferences}
                              <div class="moeve-badge-list">
                                {foreach from=$application.preferences item=preference}
                                  <span class="moeve-badge is-preference">{$preference|escape}</span>
                                {/foreach}
                              </div>
                            {else}
                              <span class="moeve-empty-value">Keine Wunschfunktion angegeben</span>
                            {/if}
                          </div>
                          <div class="moeve-person-list-actions">
                            <span class="moeve-status-label">
                              <i
                                class="moeve-status-dot"
                                style="--moeve-status-color: {$application.status.color|escape};"
                              ></i>
                              {$application.status.label|escape}
                            </span>
                            {if $canManage}
                              <a href="{$application.decisionUrl|escape}" class="crm-button">Entscheiden</a>
                            {/if}
                          </div>
                        </li>
                      {/foreach}
                    </ul>
                  {else}
                    <p class="moeve-empty-value">Keine offenen Bewerbungen.</p>
                  {/if}
                </section>
              </div>
            </article>
          {/foreach}
        </div>
      {else}
        <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          <p>Für die gewählten Filter wurden keine Törns gefunden.</p>
        </div>
      {/if}
    {/if}
  {elseif $activeView eq 'applications'}
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
                        <a href="{$application.decisionUrl|escape}" class="crm-button">
                          Entscheiden
                        </a>
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
        {else}
          <div class="messages status no-popup">
            <div class="icon inform-icon"></div>
            <p>Für die gewählten Filter wurden keine Crewing-Bewerbungen gefunden.</p>
          </div>
        {/if}
      </section>
    {/if}
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

  .moeve-cockpit-controls,
  .moeve-cockpit-section,
  .moeve-voyage-controls,
  .moeve-voyage-card,
  .moeve-application-controls,
  .moeve-application-section,
  .moeve-year-controls,
  .moeve-matrix-section {
    border-radius: .45rem;
    margin-bottom: 1rem;
    padding: 1rem;
  }

  .moeve-cockpit-controls form,
  .moeve-voyage-controls form,
  .moeve-application-controls form,
  .moeve-year-controls form {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
  }

  .moeve-cockpit-controls select,
  .moeve-voyage-controls select,
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

  .moeve-cockpit-alerts {
    display: grid;
    gap: .75rem;
    grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
    margin: 0 0 1rem;
  }

  .moeve-task-card {
    align-items: center;
    background: #fff7ed;
    border: 1px solid #fdba74;
    border-radius: .45rem;
    color: #7c2d12;
    display: flex;
    gap: .75rem;
    padding: .8rem;
    text-decoration: none;
  }

  .moeve-task-card:hover,
  .moeve-task-card:focus {
    box-shadow: 0 0 0 2px #0284c7;
  }

  .moeve-task-card.is-done {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
  }

  .moeve-task-number {
    align-items: center;
    background: rgba(255, 255, 255, .8);
    border: 1px solid currentColor;
    border-radius: 50%;
    display: inline-flex;
    flex: 0 0 2.35rem;
    font-size: 1.15rem;
    font-weight: 800;
    height: 2.35rem;
    justify-content: center;
  }

  .moeve-task-card strong,
  .moeve-task-card small {
    display: block;
  }

  .moeve-task-card small {
    margin-top: .15rem;
  }

  .moeve-cockpit-layout {
    align-items: start;
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(0, 2fr) minmax(19rem, 1fr);
  }

  .moeve-cockpit-section {
    border: 1px solid var(--moeve-border);
  }

  .moeve-cockpit-voyage-list {
    display: grid;
    gap: .65rem;
  }

  .moeve-cockpit-voyage {
    align-items: flex-start;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .4rem;
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    padding: .8rem;
  }

  .moeve-cockpit-voyage-main {
    min-width: 0;
    width: 100%;
  }

  .moeve-cockpit-voyage h3 {
    margin: .2rem 0;
  }

  .moeve-cockpit-voyage p {
    color: #475569;
    margin: 0 0 .55rem;
  }

  .moeve-cockpit-voyage-kicker {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }

  .moeve-cockpit-voyage-kicker span {
    background: #e0f2fe;
    border-radius: 999px;
    color: #075985;
    font-size: .72rem;
    font-weight: 700;
    padding: .15rem .4rem;
  }

  .moeve-progress {
    background: #e2e8f0;
    border-radius: 999px;
    height: .42rem;
    margin: .45rem 0;
    overflow: hidden;
  }

  .moeve-progress span {
    background: var(--moeve-covered);
    display: block;
    height: 100%;
    width: var(--moeve-progress, 0%);
  }

  .moeve-cockpit-voyage-counts {
    display: flex;
    flex-wrap: wrap;
    font-size: .78rem;
    gap: .35rem .8rem;
  }

  .moeve-missing-info {
    color: #9a3412;
    font-size: .76rem;
    margin-top: .45rem;
  }

  .moeve-cockpit-voyage-actions {
    align-items: flex-end;
    display: flex;
    flex: 0 0 auto;
    flex-direction: column;
    gap: .4rem;
  }

  .moeve-cockpit-application-list {
    display: grid;
    gap: .55rem;
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .moeve-cockpit-application-list > li {
    align-items: flex-start;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .4rem;
    display: flex;
    gap: .6rem;
    justify-content: space-between;
    padding: .65rem;
  }

  .moeve-cockpit-application-list small {
    color: #64748b;
    display: block;
    margin: -.15rem 0 .35rem;
  }

  .moeve-cockpit-empty {
    border: 1px solid #cbd5e1;
    border-radius: .4rem;
    display: flex;
    flex-direction: column;
    padding: .85rem;
  }

  .moeve-cockpit-empty.is-success {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
  }

  .moeve-cockpit-empty span {
    margin-top: .2rem;
  }

  .moeve-cockpit-needs-wrap {
    border: 1px solid var(--moeve-border);
    overflow-x: auto;
  }

  .moeve-cockpit-needs {
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
    min-width: 65rem;
    width: 100%;
  }

  .moeve-cockpit-needs th,
  .moeve-cockpit-needs td {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: .6rem;
    text-align: left;
    vertical-align: middle;
  }

  .moeve-cockpit-needs thead th {
    background: #f8fafc;
  }

  .moeve-cockpit-needs small {
    color: #64748b;
    display: block;
    margin-top: .15rem;
  }

  .moeve-cockpit-shortcuts {
    display: grid;
    gap: .75rem;
    grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
    margin: 0 0 1rem;
  }

  .moeve-cockpit-shortcuts a {
    background: #f8fafc;
    border: 1px solid var(--moeve-border);
    border-radius: .45rem;
    color: #075985;
    display: flex;
    flex-direction: column;
    padding: .8rem;
    text-decoration: none;
  }

  .moeve-cockpit-shortcuts a:hover,
  .moeve-cockpit-shortcuts a:focus {
    background: #e0f2fe;
    border-color: #38bdf8;
  }

  .moeve-cockpit-shortcuts span {
    color: #475569;
    font-size: .8rem;
    margin-top: .2rem;
  }

  .moeve-voyage-list {
    display: grid;
    gap: 1rem;
  }

  .moeve-voyage-card {
    border: 1px solid var(--moeve-border);
    margin-bottom: 0;
  }

  .moeve-voyage-card-header {
    align-items: flex-start;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: space-between;
    margin: -1rem -1rem 1rem;
    padding: 1rem;
  }

  .moeve-voyage-card-header h2 {
    margin: .15rem 0 .2rem;
  }

  .moeve-voyage-card-header p {
    color: #475569;
    margin: 0;
  }

  .moeve-voyage-number {
    color: #075985;
    font-size: .8rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .moeve-voyage-head-summary {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    justify-content: flex-end;
  }

  .moeve-count-pill,
  .moeve-state-label {
    border: 1px solid transparent;
    border-radius: 999px;
    display: inline-block;
    font-size: .78rem;
    font-weight: 700;
    line-height: 1.2;
    padding: .3rem .55rem;
    white-space: nowrap;
  }

  .moeve-count-pill.is-covered,
  .moeve-state-label.is-covered {
    background: #dcfce7;
    border-color: #86efac;
    color: #166534;
  }

  .moeve-count-pill.is-attention,
  .moeve-state-label.is-attention {
    background: #ffedd5;
    border-color: #fdba74;
    color: #9a3412;
  }

  .moeve-count-pill.is-gap,
  .moeve-state-label.is-gap {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #991b1b;
  }

  .moeve-voyage-meta {
    display: grid;
    gap: .75rem;
    grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
    margin: 0 0 1rem;
  }

  .moeve-voyage-meta > div {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: .4rem;
    padding: .65rem .75rem;
  }

  .moeve-voyage-meta dt {
    color: #64748b;
    font-size: .75rem;
    font-weight: 700;
    margin-bottom: .2rem;
    text-transform: uppercase;
  }

  .moeve-voyage-meta dd {
    margin: 0;
  }

  .moeve-voyage-comment {
    background: #f8fafc;
    border-left: .3rem solid #38bdf8;
    margin: 0 0 1rem;
    padding: .65rem .8rem;
  }

  .moeve-voyage-comment p {
    margin: .25rem 0 0;
  }

  .moeve-demand-section h3,
  .moeve-voyage-people-grid h3 {
    margin: 0 0 .65rem;
  }

  .moeve-voyage-people-grid h3 span {
    background: #e2e8f0;
    border-radius: 999px;
    font-size: .75rem;
    margin-left: .25rem;
    padding: .15rem .45rem;
  }

  .moeve-demand-table-wrap {
    border: 1px solid var(--moeve-border);
    margin-bottom: 1rem;
    overflow-x: auto;
  }

  .moeve-demand-table {
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
    min-width: 68rem;
    width: 100%;
  }

  .moeve-demand-table th,
  .moeve-demand-table td {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: .6rem;
    text-align: left;
    vertical-align: top;
  }

  .moeve-demand-table thead th {
    background: #f8fafc;
  }

  .moeve-person-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
  }

  .moeve-person-chip {
    align-items: center;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    display: inline-flex;
    font-size: .78rem;
    gap: .3rem;
    padding: .2rem .45rem;
    text-decoration: none;
  }

  .moeve-person-chip.is-application {
    background: #fff7ed;
    border-color: #fdba74;
  }

  .moeve-voyage-people-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr));
  }

  .moeve-voyage-people-grid > section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: .4rem;
    padding: .8rem;
  }

  .moeve-person-list {
    display: grid;
    gap: .5rem;
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .moeve-person-list > li {
    align-items: flex-start;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .35rem;
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    justify-content: space-between;
    padding: .65rem;
  }

  .moeve-person-list-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    justify-content: flex-end;
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
    .moeve-cockpit-layout {
      grid-template-columns: minmax(0, 1fr);
    }

    .moeve-cockpit-voyage,
    .moeve-cockpit-application-list > li {
      display: block;
    }

    .moeve-cockpit-voyage-actions {
      align-items: flex-start;
      flex-direction: row;
      flex-wrap: wrap;
      margin-top: .65rem;
    }

    .moeve-voyage-people-grid {
      grid-template-columns: minmax(0, 1fr);
    }

    .moeve-voyage-card-header,
    .moeve-person-list > li {
      display: block;
    }

    .moeve-voyage-head-summary,
    .moeve-person-list-actions {
      justify-content: flex-start;
      margin-top: .65rem;
    }

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
