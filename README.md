# amarenkov/laravel-mutable-content-tasks

[![tests](https://github.com/amarenkov/laravel-mutable-content-tasks/actions/workflows/tests.yml/badge.svg)](https://github.com/amarenkov/laravel-mutable-content-tasks/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/amarenkov/laravel-mutable-content-tasks)](https://packagist.org/packages/amarenkov/laravel-mutable-content-tasks)
![coverage](https://img.shields.io/badge/coverage-98%25-brightgreen)

Task domain for [`amarenkov/laravel-mutable-content`](https://github.com/amarenkov/laravel-mutable-content):
tasks, their participants and time tracking, with services and events to build any interface on.

## Scope

- Models with mutable fields, so an application adds its own task fields without migrations.
- Services and events: creating and changing tasks, assigning participants, starting and pausing
  time tracking. Every change lands in the core change log with its author.
- **No user interface.** Screens, bots and notifications channels belong to the application; the
  package does not depend on any UI kit, so it fits any project built on laravel-mutable-content.

## Requirements

- PHP 8.4
- Laravel 13
- PostgreSQL or MariaDB 10.7+, as required by `amarenkov/laravel-mutable-content`

## Installation

```bash
composer require amarenkov/laravel-mutable-content-tasks
```

## Usage

Every change goes through a service. `by()` names the actor doing it: its user becomes the author
of the change log entry; `logComment()` adds a comment to the entry.

```php
use Amarenkov\MutableContentTasks\Lovs\ParticipantRole;
use Amarenkov\MutableContentTasks\Lovs\PointType;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Point;
use Amarenkov\MutableContentTasks\Models\TimeEntry;
use Amarenkov\MutableContentTasks\Services\Points;
use Amarenkov\MutableContentTasks\Services\Time;
use Amarenkov\MutableContentTasks\Support\Duration;

$me = Actor::forUser($request->user());

$task = Points::new(PointType::TASK)->by($me)->fields(['description' => 'Rewrite the app'])->save();
$part = Points::new(PointType::TASK)->in($task)->by($me)->fields(['description' => 'Models'])->save();

Points::of($task)->by($me)->addParticipant($executor, ParticipantRole::EXECUTOR);
Points::of($task)->by($me)->syncParticipants(ParticipantRole::EXECUTOR, [$executor]); // the only executor
Points::of($part)->by($me)->logComment('Typo')->update(['description' => 'Domain models']);
Points::of($part)->by($me)->moveTo(null);
Points::of($task)->by($me)->close(); // time running on it stops
Points::of($task)->by($me)->reopen();
Points::of($part)->by($me)->delete();

Time::of($me)->on($task)->comment('Models')->start();
Time::of($me)->stop();
Time::entry($entry)->by($me)->update([TimeEntry::FIELD_FROM => now()->subHour(), TimeEntry::FIELD_POINT_ID => $task->id]);
Time::entry($entry)->by($me)->delete();

Point::query()->whereParticipant($me, ParticipantRole::EXECUTOR)->whereOpen()->get();
$task->isClosed();
TimeEntry::last($me, onPoint: true);
TimeEntry::secondsOn($me, $task);
Duration::clock(5400); // 01:30:00
Duration::hours(5400); // 1.5 h
TimeEntry::report($me, now()->startOfDay(), now()->endOfDay()); // [point id => seconds], most first
```

The creator of a point becomes its `author` participant. A point with children cannot be deleted,
and a point cannot be moved inside itself; both throw `DomainException`. Starting time tracking
stops the running entry of the actor, unless it runs on the same point: then it goes on.
A point is `open` or `closed` (the `point_status` LOV; a point without a status is open). Closing
stops the time running on the point, and time cannot be started on a closed point until it is
reopened (`DomainException`).
A corrected entry keeps its actor, must end after it starts, not in the future and not overlap
other entries of the actor; a finished entry cannot be made running again (`DomainException`).

Events are dispatched after the transaction commits: `PointCreated`, `PointUpdated` (with the
changed fields), `PointMoved`, `PointClosed`, `PointReopened`, `PointDeleted`, `ParticipantAdded`, `ParticipantRemoved`,
`TimeEntryStarted`, `TimeEntryStopped`, `TimeEntryUpdated` (with the changed fields and the
previous point), `TimeEntryDeleted`. They carry ids, so listeners may be queued.

## Translations

Strings are in `lang/{en,ru}/tasks.php` under the `mutable-content-tasks` namespace. Publish them
to override:

```bash
php artisan vendor:publish --tag=mutable-content-tasks-lang
```

## License

MIT. See [LICENSE](LICENSE).
