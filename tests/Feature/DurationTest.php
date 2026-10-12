<?php

namespace Amarenkov\MutableContentTasks\Tests\Feature;

use Amarenkov\MutableContentTasks\Support\Duration;

use Amarenkov\MutableContentTasks\Tests\TestCase;

class DurationTest extends TestCase
{
    public function test_clock(): void
    {
        $this->assertSame('00:00:00', Duration::clock(0));
        $this->assertSame('01:05:09', Duration::clock(3909));
        $this->assertSame('27:00:00', Duration::clock(27 * 3600));
    }

    public function test_hours_in_the_locale(): void
    {
        $this->assertSame('1.5 h', Duration::hours(5400));
        $this->assertSame('0.1 h', Duration::hours(30));

        app()->setLocale('ru');

        $this->assertSame('1,5 ч.', Duration::hours(5400));
        $this->assertSame('10,0 ч.', Duration::hours(36000));
    }
}
