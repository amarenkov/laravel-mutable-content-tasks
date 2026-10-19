# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-10-20

### Added

- Point status: `open` or `closed` (the `point_status` LOV, `Point::FIELD_STATUS`; new points are open, a point without a status counts as open), `isClosed()`, scopes `whereOpen()` and `whereClosed()`.
- `Points::of($point)->close()` stops the time running on the point and closes it, `reopen()` opens it again; events `PointClosed` and `PointReopened`.

### Changed

- Starting time on a closed point throws `DomainException`.

## [0.1.1] - 2026-10-19

### Added

- `Time::entry($entry)`: correct a time entry (`update()`: from, to, point, comment; the period must end after it starts, not lie in the future and not overlap other entries of the actor, a finished entry cannot run again) and `delete()` it; events `TimeEntryUpdated` (changed fields, previous point) and `TimeEntryDeleted`.

## [0.1.0] - 2026-10-15

### Added

- `Point`: a task, a comment or a point of another type (the `point_type` LOV, `Point::typeField()`), with `excerpt` (a short plain line, required) and `description`; an empty excerpt is taken from the description and follows it until changed by hand, a point with neither throws `DomainException`. Points form a tree through `parent_id`; `whereParticipant($actor, $role)` finds the points of an actor.
- `Actor`: who acts on points, an application user for now (`Actor::forUser()`, the user model from `mutable-content-tasks.user_model` or the auth provider).
- `Participant`: an actor in a point with a role (`author`, `executor`), once per role.
- `TimeEntry`: time an actor worked, on a point or on nothing in particular, `from` and `to` as date and time fields; `running()`, `isRunning()`, `seconds()`, `last($actor, $onPoint)`, `secondsOn($actor, $point)`, `secondsByPoint($actor, $pointIds)` (one query for a list) and `report($actor, $from, $to)`: seconds by point within a period, entries cut by its bounds.
- Services `Points::new($type)` and `Points::of($point)` (update, move, delete, add, remove and sync participants of a role) and `Time::of($actor)` (start, stop; starting on the point already running goes on); the actor given to `by()` is the author of the change log entry, the creator of a point becomes its author participant.
- `Support\Duration`: `clock()` (01:05:09) and `hours()` ("1.5 h" in the application locale).
- Events dispatched after commit: `PointCreated`, `PointUpdated`, `PointMoved`, `PointDeleted`, `ParticipantAdded`, `ParticipantRemoved`, `TimeEntryStarted`, `TimeEntryStopped`.
- Migrations in the `tasks` schema (publish tag `mutable-content-tasks-migrations`), LOVs and classes registered in the core registries, Russian labels.

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-tasks/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/amarenkov/laravel-mutable-content-tasks/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/amarenkov/laravel-mutable-content-tasks/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-tasks/releases/tag/v0.1.0
