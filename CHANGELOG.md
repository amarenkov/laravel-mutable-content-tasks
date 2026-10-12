# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Point`: a task, a comment or a point of another type (the `point_type` LOV, `Point::typeField()`), with `excerpt` and `description`; the excerpt is taken from the description when empty. Points form a tree through `parent_id`.
- `Actor`: who acts on points, an application user for now (`Actor::forUser()`, the user model from `mutable-content-tasks.user_model` or the auth provider).
- `Participant`: an actor in a point with a role (`author`, `executor`), once per role.
- `TimeEntry`: time an actor worked, on a point or on nothing in particular, `from` and `to` as date and time fields; `running()`, `isRunning()`, `seconds()`.
- Services `Points::new($type)` and `Points::of($point)` (update, move, delete, add and remove participants) and `Time::of($actor)` (start, stop); the actor given to `by()` is the author of the change log entry, the creator of a point becomes its author participant.
- Events dispatched after commit: `PointCreated`, `PointUpdated`, `PointMoved`, `PointDeleted`, `ParticipantAdded`, `ParticipantRemoved`, `TimeEntryStarted`, `TimeEntryStopped`.
- Migrations in the `tasks` schema (publish tag `mutable-content-tasks-migrations`), LOVs and classes registered in the core registries, Russian labels.

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-tasks/commits/main
