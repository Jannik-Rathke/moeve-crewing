<div class="crm-block crm-form-block crm-moeve-crewing-decision-form-block">
  <div class="help">
    <p>
      Ändern Sie Teilnahmestatus und Crewing-Funktion gemeinsam. Bei Auswahl
      einer Bordfunktion wird die Rolle „potentielles Crewmitglied“ entfernt.
      Die ursprünglich angegebenen Wunschfunktionen bleiben unverändert.
    </p>
  </div>

  <section class="moeve-decision-context" aria-label="Bewerbung">
    <div>
      <span class="moeve-context-label">Person</span>
      <a href="{$application.contactUrl|escape}">
        {$application.displayName|escape}
      </a>
    </div>
    <div>
      <span class="moeve-context-label">Törn</span>
      <a href="{$application.eventUrl|escape}">
        {$application.eventTitle|escape}
      </a>
      {if $application.eventDateLabel}
        <small>{$application.eventDateLabel|escape}</small>
      {/if}
    </div>
    <div>
      <span class="moeve-context-label">Eingegangen</span>
      <span>{if $application.registerDateLabel}{$application.registerDateLabel|escape}{else}–{/if}</span>
    </div>
    <div>
      <span class="moeve-context-label">Aktueller Status</span>
      <span class="moeve-current-status">
        <i style="--moeve-current-color: {$application.status.color|escape};"></i>
        {$application.status.label|escape}
      </span>
    </div>
  </section>

  <section class="moeve-decision-details">
    <div>
      <h3>Wunschfunktionen</h3>
      {if $application.preferences}
        <div class="moeve-decision-badges">
          {foreach from=$application.preferences item=preference}
            <span class="moeve-decision-badge is-preference">{$preference|escape}</span>
          {/foreach}
        </div>
      {else}
        <span class="moeve-empty-value">Keine Wunschfunktion angegeben</span>
      {/if}
    </div>
    <div>
      <h3>Derzeit zugewiesen</h3>
      {if $application.assignedRoles}
        <div class="moeve-decision-badges">
          {foreach from=$application.assignedRoles item=assignedRole}
            <span class="moeve-decision-badge is-assigned">{$assignedRole|escape}</span>
          {/foreach}
        </div>
      {elseif $application.isCandidate}
        <span class="moeve-decision-badge is-candidate">potentielles Crewmitglied</span>
      {else}
        <span class="moeve-empty-value">Keine Crewing-Funktion</span>
      {/if}
    </div>
  </section>

  {if $application.multipleRoleWarning}
    <div class="messages warning no-popup">
      <div class="icon inform-icon"></div>
      <p>
        Diese Teilnahme besitzt momentan mehrere Crewing-Funktionen. Beim
        Speichern werden sie durch die ausgewählte einzelne Funktion ersetzt.
      </p>
    </div>
  {/if}

  <div class="crm-section">
    <div class="label">{$form.status_id.label}</div>
    <div class="content">
      {$form.status_id.html}
      <div class="description">
        Es werden die in CiviCRM vorhandenen Teilnahmestatus angeboten.
      </div>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.role_choice.label}</div>
    <div class="content">
      {$form.role_choice.html}
      <div class="description">
        „Bewerbung offen“ behält die Bewerberrolle. Eine Bordfunktion ersetzt
        die Bewerberrolle und jede zuvor zugewiesene Crewing-Funktion.
      </div>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons moeve-decision-actions">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
    <a href="{$returnUrl|escape}" class="crm-button">Abbrechen</a>
  </div>
</div>

{literal}
<style>
  .crm-moeve-crewing-decision-form-block {
    padding: 1rem;
  }

  .moeve-decision-context,
  .moeve-decision-details {
    display: grid;
    gap: .75rem;
    grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
    margin: 0 0 1rem;
  }

  .moeve-decision-context > div,
  .moeve-decision-details > div {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: .4rem;
    padding: .75rem;
  }

  .moeve-context-label {
    color: #64748b;
    display: block;
    font-size: .8rem;
    font-weight: 700;
    margin-bottom: .2rem;
    text-transform: uppercase;
  }

  .moeve-decision-context small {
    color: #64748b;
    display: block;
    margin-top: .2rem;
  }

  .moeve-current-status {
    align-items: center;
    display: inline-flex;
    gap: .4rem;
  }

  .moeve-current-status i {
    background: var(--moeve-current-color, #64748b);
    border: 1px solid rgba(15, 23, 42, .2);
    border-radius: 50%;
    height: .8rem;
    width: .8rem;
  }

  .moeve-decision-details h3 {
    font-size: 1rem;
    margin: 0 0 .5rem;
  }

  .moeve-decision-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
  }

  .moeve-decision-badge {
    border: 1px solid transparent;
    border-radius: 999px;
    display: inline-block;
    font-size: .8rem;
    padding: .2rem .5rem;
  }

  .moeve-decision-badge.is-preference {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1e40af;
  }

  .moeve-decision-badge.is-assigned {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
  }

  .moeve-decision-badge.is-candidate {
    background: #fff7ed;
    border-color: #fdba74;
    color: #9a3412;
  }

  .moeve-empty-value {
    color: #64748b;
    font-style: italic;
  }

  .moeve-decision-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
  }
</style>
{/literal}
