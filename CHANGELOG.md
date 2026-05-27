# Changelog

## [0.1.2]

### Added
- Entertainer-dashboard voor eigen algemene aanvraagmatches met accepteren en afwijzen.
- Extra tests voor specifieke en algemene Schminker-aanvragen, matchfiltering, conflicten, eigen matchtoegang en adminzicht.
- README-installatiestappen en expliciete PHP-extensievereisten.

### Changed
- Booking request validatie vertrouwt bij specifieke aanvragen alleen de route-entertainer en blokkeert `entertainer_id` uit de request payload.
- Algemene matching negeert regio/werkgebied voorlopig als harde blokkade en controleert ook conflicten via bestaande algemene aanvraagmatches.
- Filament aanvraagresource toont aanvraagtype, skill, specifieke entertainer, matchaantal en matchstatussen met filters voor status, type, skill, datum en match-entertainer.

### Fixed
- Skillselectie in specifieke aanvraagformulieren selecteert niet langer meerdere opties tegelijk.
- Algemene aanvraagmatches zetten de aanvraag op `optie` wanneer een entertainer de match accepteert.

## [0.1.1]

### Added
- Correcte Livewire componentlocatie voor `availability-check`, `booking-request-form` en `entertainer-index`.
- Entertainer-dashboard CRUD voor eigen profiel, skills, beschikbaarheid en tarieven.
- Algemene aanvraagflow op basis van actieve skill met matches voor beschikbare entertainers.
- Tests voor Livewire filters, aanvraagvalidatie, beschikbaarheidsconflicten en entertainer/admin-autorisatie.

### Changed
- Beschikbaarheidslogica controleert nu overlappende optie-, bezet- en niet-beschikbaar blokken en conflicterende aanvragen.
- Booking request validatie controleert aanvraagtype, actieve entertainer of skill, toekomstige datum, tijdsvolgorde, B2B-bedrijfsnaam en toegestane skills.
- Filament resources hebben Nederlandse labels, extra filters en policy-gestuurde autorisatie.
- Database-indexen en relaties zijn aangescherpt voor aanvragen, skills, beschikbaarheid en matches.

### Fixed
- Foutieve componentbestanden met lightning-prefix zijn vervangen door conventionele Livewire componentviews.
- Repository ignore-regels sluiten dependency-, environment-, macOS- en testcachebestanden uit.

## [0.1.0]

### Added
- Eerste Laravel 13 basis voor Kinderentertainers.nl.
- Filament 5 adminpanel, Livewire frontend en entertainer-dashboard.
- Entertainers, skills, beschikbaarheid, tarieven, boekingsaanvragen, rollen, policies, seeders, factories en basistests.
