<?php

namespace Amarenkov\MutableContentTasks\Tests\Feature;

use Amarenkov\MutableContentTasks\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_translations_namespace_is_registered(): void
    {
        $this->assertArrayHasKey('mutable-content-tasks', app('translator')->getLoader()->namespaces());
    }
}
