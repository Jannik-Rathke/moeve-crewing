<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Configuration;

/**
 * Provider-independent defaults.
 *
 * Administrators may override these values in the extension settings.
 * Code must never depend on database IDs or numeric option values.
 */
final class DefaultConfiguration {

  public const ROLE_OPTION_GROUP = 'participant_role';

  public const INDIVIDUAL_GROUP = 'moeve_crewing_individual';
  public const PARTICIPANT_GROUP = 'moeve_crewing_participant';
  public const EVENT_GROUP = 'moeve_crewing_event';

  public const CAPABILITIES_FIELD = 'faehigkeiten';
  public const PREFERENCES_FIELD = 'gewuenschte_funktionen';

  /**
   * Default role-to-field mapping.
   *
   * @return array<string, array<string, string>>
   */
  public static function roles(): array {
    return [
      'Kapitaen' => [
        'label' => 'Kapitänin/Kapitän',
        'enabled_field' => 'freigeschaltet_Kapitaen',
        'minimum_field' => 'mindestanzahlKapitaen',
      ],
      'Steuermann' => [
        'label' => 'Steuerfrau/mann',
        'enabled_field' => 'freigeschaltet_Steuermann',
        'minimum_field' => 'mindestanzahlSteuermann',
      ],
      'Maschinist' => [
        'label' => 'Maschinist*in',
        'enabled_field' => 'freigeschaltet_Maschinist',
        'minimum_field' => 'mindestanzahlMaschinist',
      ],
      'Maschinenassistent' => [
        'label' => 'Maschinenassistent*in',
        'enabled_field' => 'freigeschaltet_Maschinenassistent',
        'minimum_field' => 'mindestanzahlMaschinenassistent',
      ],
      'Bootsmann' => [
        'label' => 'Bootsmann/-frau',
        'enabled_field' => 'freigeschaltet_Bootsmann',
        'minimum_field' => 'mindestanzahlBootsmann',
      ],
      'Bootsleuteassistent' => [
        'label' => 'Bootsleuteassistent*in',
        'enabled_field' => 'freigeschaltet_Bootsleuteassistent',
        'minimum_field' => 'mindestanzahlBootsleuteassistent',
      ],
      'Wachfuehrer' => [
        'label' => 'Wachführer*in',
        'enabled_field' => 'freigeschaltet_Wachfuehrer',
        'minimum_field' => 'mindestanzahlWachfuehrer',
      ],
      'Copi' => [
        'label' => 'Copi / Wachführerassistent*in',
        'enabled_field' => 'freigeschaltet_Copi',
        'minimum_field' => 'mindestanzahlCopi',
      ],
      'Deckshand' => [
        'label' => 'Deckshand',
        'enabled_field' => 'freigeschaltet_Deckshand',
        'minimum_field' => 'mindestanzahlDeckshand',
      ],
      'Stammanwaerter' => [
        'label' => 'Stammanwärter*in',
        'enabled_field' => 'freigeschaltet_Stammanwaerter',
        'minimum_field' => 'mindestanzahlStammanwaerter',
      ],
      'Teilnehmer' => [
        'label' => 'Teilnehmer*in',
        'enabled_field' => 'freigeschaltet_Teilnehmer',
        'minimum_field' => 'mindestanzahlTeilnehmer',
      ],
      'Lehrer' => [
        'label' => 'Lehrer*in',
        'enabled_field' => 'freigeschaltet_Lehrer',
        'minimum_field' => 'mindestanzahlLehrer',
      ],
      'Bordarzt' => [
        'label' => 'Bordarzt/-ärztin',
        'enabled_field' => 'freigeschaltet_Bordarzt',
        'minimum_field' => 'mindestanzahlBordarzt',
      ],
    ];
  }

}
