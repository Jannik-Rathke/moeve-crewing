<div class="crm-block crm-form-block crm-moeve-crewing-setup-form-block">
  <div class="help">
    <p>
      Diese Einrichtung legt die empfohlenen Crewing-Rollen, Feldgruppen und
      benutzerdefinierten Felder an.
    </p>
    <p>
      Bereits vorhandene Einträge werden nicht überschrieben oder gelöscht.
      Abweichende, nicht kompatible Einträge werden als Fehler gemeldet.
    </p>
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

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
