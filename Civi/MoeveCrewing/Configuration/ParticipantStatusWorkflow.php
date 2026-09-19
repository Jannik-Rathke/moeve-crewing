<?php

declare(strict_types=1);

namespace Civi\MoeveCrewing\Configuration;

/**
 * Resolves the participant statuses used by the crewing bulk workflow.
 */
final class ParticipantStatusWorkflow {

  public const SETTING_NAME = 'moeve_crewing_participant_status_workflow';

  /**
   * @param array<int, array<string, mixed>> $statuses
   * @return array{
   *   assigned: array{allowed: array<int, string>, default: string},
   *   declined: array{allowed: array<int, string>, default: string},
   *   configured: bool,
   *   errors: array<int, string>
   * }
   */
  public static function resolve(array $statuses): array {
    $fallback = self::fallback($statuses);
    $json = trim((string) \Civi::settings()->get(self::SETTING_NAME));
    if ($json === '') {
      $errors = [];
      if ($fallback['assigned']['allowed'] === []) {
        $errors[] = 'Es ist kein Teilnahmestatus für zugewiesene Crew verfügbar.';
      }
      if ($fallback['declined']['allowed'] === []) {
        $errors[] = 'Wählen Sie mindestens einen Teilnahmestatus für Absagen aus.';
      }
      return $fallback + [
        'configured' => FALSE,
        'errors' => $errors,
      ];
    }

    try {
      $decoded = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      return $fallback + [
        'configured' => TRUE,
        'errors' => [
          'Die gespeicherte Statusverwendung enthält ungültiges JSON.',
        ],
      ];
    }
    if (!is_array($decoded)) {
      return $fallback + [
        'configured' => TRUE,
        'errors' => [
          'Die gespeicherte Statusverwendung besitzt kein gültiges Format.',
        ],
      ];
    }

    $known = [];
    foreach ($statuses as $status) {
      $name = trim((string) ($status['name'] ?? ''));
      if ($name !== '') {
        $known[$name] = $status;
      }
    }

    $errors = [];
    $resolved = [];
    foreach (['assigned', 'declined'] as $target) {
      $targetLabel = $target === 'assigned'
        ? 'zugewiesene Crew'
        : 'Absagen';
      $configuration = $decoded[$target] ?? NULL;
      if (!is_array($configuration)) {
        $errors[] = sprintf(
          'Für „%s“ fehlt eine gültige Statuskonfiguration.',
          $targetLabel
        );
        $resolved[$target] = $fallback[$target];
        continue;
      }

      $allowed = [];
      $configuredAllowed = $configuration['allowed'] ?? NULL;
      if (!is_array($configuredAllowed)) {
        $errors[] = sprintf(
          'Die erlaubten Status für „%s“ besitzen kein gültiges Format.',
          $targetLabel
        );
      }
      else {
        foreach ($configuredAllowed as $name) {
          if (!is_string($name) || trim($name) === '') {
            $errors[] = sprintf(
              'Die Statusauswahl für „%s“ enthält einen ungültigen Eintrag.',
              $targetLabel
            );
            continue;
          }
          $name = trim($name);
          if (!isset($known[$name])) {
            $errors[] = sprintf(
              'Der für „%s“ konfigurierte Teilnahmestatus „%s“ ist nicht mehr vorhanden.',
              $targetLabel,
              $name
            );
            continue;
          }
          $allowed[$name] = $name;
        }
      }

      if ($allowed === []) {
        $errors[] = sprintf(
          'Für „%s“ ist kein vorhandener Teilnahmestatus ausgewählt.',
          $targetLabel
        );
        $resolved[$target] = $fallback[$target];
        continue;
      }

      $default = $configuration['default'] ?? '';
      if (!is_string($default) || !isset($allowed[$default])) {
        $errors[] = sprintf(
          'Der Standardstatus für „%s“ gehört nicht zur erlaubten Auswahl.',
          $targetLabel
        );
        $default = self::preferredDefault(
          array_values($allowed),
          $known,
          $target
        );
      }

      $resolved[$target] = [
        'allowed' => array_values($allowed),
        'default' => $default,
      ];
    }

    return [
      'assigned' => $resolved['assigned'],
      'declined' => $resolved['declined'],
      'configured' => TRUE,
      'errors' => array_values(array_unique($errors)),
    ];
  }

  /**
   * @param array<int, array<string, mixed>> $statuses
   * @param array<int, string> $assignedAllowed
   * @param array<int, string> $declinedAllowed
   * @return array{json: string, assignedCount: int, declinedCount: int}
   */
  public static function encode(
    array $statuses,
    array $assignedAllowed,
    string $assignedDefault,
    array $declinedAllowed,
    string $declinedDefault
  ): array {
    $known = [];
    foreach ($statuses as $status) {
      $name = trim((string) ($status['name'] ?? ''));
      if ($name !== '') {
        $known[$name] = $status;
      }
    }

    $assignedAllowed = self::validateAllowed(
      $assignedAllowed,
      $known,
      'zugewiesene Crew'
    );
    $declinedAllowed = self::validateAllowed(
      $declinedAllowed,
      $known,
      'Absagen'
    );
    self::validateDefault(
      $assignedDefault,
      $assignedAllowed,
      'zugewiesene Crew'
    );
    self::validateDefault(
      $declinedDefault,
      $declinedAllowed,
      'Absagen'
    );

    try {
      $json = json_encode(
        [
          'assigned' => [
            'allowed' => $assignedAllowed,
            'default' => $assignedDefault,
          ],
          'declined' => [
            'allowed' => $declinedAllowed,
            'default' => $declinedDefault,
          ],
        ],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
      );
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException(
        'Die Statusverwendung konnte nicht gespeichert werden.',
        0,
        $exception
      );
    }

    return [
      'json' => $json,
      'assignedCount' => count($assignedAllowed),
      'declinedCount' => count($declinedAllowed),
    ];
  }

  /**
   * @param array<int, array<string, mixed>> $statuses
   * @return array{
   *   assigned: array{allowed: array<int, string>, default: string},
   *   declined: array{allowed: array<int, string>, default: string}
   * }
   */
  private static function fallback(array $statuses): array {
    $known = [];
    $assigned = [];
    $declined = [];
    foreach ($statuses as $status) {
      $name = trim((string) ($status['name'] ?? ''));
      if ($name === '') {
        continue;
      }
      $known[$name] = $status;
      $assigned[] = $name;
      if ((string) ($status['class'] ?? '') === 'Negative') {
        $declined[] = $name;
      }
    }

    return [
      'assigned' => [
        'allowed' => $assigned,
        'default' => self::preferredDefault($assigned, $known, 'assigned'),
      ],
      'declined' => [
        'allowed' => $declined,
        'default' => self::preferredDefault($declined, $known, 'declined'),
      ],
    ];
  }

  /**
   * @param array<int, string> $allowed
   * @param array<string, array<string, mixed>> $known
   */
  private static function preferredDefault(
    array $allowed,
    array $known,
    string $target
  ): string {
    $preferred = $target === 'assigned'
      ? ['Registered', 'Attended']
      : ['Rejected', 'Cancelled', 'No-show'];
    foreach ($preferred as $name) {
      if (in_array($name, $allowed, TRUE)) {
        return $name;
      }
    }
    foreach ($allowed as $name) {
      if (!empty($known[$name]['isActive'])) {
        return $name;
      }
    }
    return (string) ($allowed[0] ?? '');
  }

  /**
   * @param array<int, string> $allowed
   * @param array<string, array<string, mixed>> $known
   * @return array<int, string>
   */
  private static function validateAllowed(
    array $allowed,
    array $known,
    string $targetLabel
  ): array {
    $normalized = [];
    foreach ($allowed as $name) {
      $name = trim((string) $name);
      if ($name === '' || !isset($known[$name])) {
        throw new \RuntimeException(sprintf(
          'Für „%s“ wurde ein unbekannter Teilnahmestatus ausgewählt.',
          $targetLabel
        ));
      }
      $normalized[$name] = $name;
    }
    if ($normalized === []) {
      throw new \RuntimeException(sprintf(
        'Wählen Sie mindestens einen Teilnahmestatus für „%s“ aus.',
        $targetLabel
      ));
    }
    return array_values($normalized);
  }

  /**
   * @param array<int, string> $allowed
   */
  private static function validateDefault(
    string $default,
    array $allowed,
    string $targetLabel
  ): void {
    if ($default === '' || !in_array($default, $allowed, TRUE)) {
      throw new \RuntimeException(sprintf(
        'Der Standardstatus für „%s“ muss auch in dieser Spalte ausgewählt sein.',
        $targetLabel
      ));
    }
  }

}
