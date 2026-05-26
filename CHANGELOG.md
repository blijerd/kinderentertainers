CHANGELOG

## [0.1.2]

### Added
- Algemene aanvraagflow op basis van actieve skill naast specifieke entertainer-aanvragen.
- `booking_request_matches` met eigen matchstatussen voor beschikbare entertainers.
- `FindAvailableEntertainersForRequest` action voor skill-, beschikbaarheids- en conflictfiltering.
- Filament-weergave voor aanvraagtype, gekozen skill, specifieke entertainer en gevonden matches.
- Featuretests voor specifieke aanvragen, algemene skill-aanvragen, matching en validatie.

### Changed
- `booking_requests.entertainer_id` is nullable en `booking_requests.skill_id` is toegevoegd.
- Beschikbaarheidscontrole sluit conflicterende optie-, in behandeling- en bevestigde aanvragen uit.

### Fixed
- Aanvraagvalidatie vereist nu een aanvraagtype met entertainer of skill volgens dat type.

## [0.1.0]

### Added
- Nieuw Laravel 13 platform voor Kinderentertainers.nl.
- Filament 5 adminpanel, Livewire frontend en entertainer-dashboard.
- Entertainers, skills, beschikbaarheid, tarieven en boekingsaanvragen.
- Rollen, policies, seeders, factories en basistests.

### Changed

### Fixed

### Removed
