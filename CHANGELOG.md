# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-tasks/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-tasks/releases/tag/v0.1.0
