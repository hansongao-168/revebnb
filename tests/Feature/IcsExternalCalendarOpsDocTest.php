<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcsExternalCalendarOpsDocTest extends TestCase
{
    use RefreshDatabase;

    public function test_ics_external_calendar_ops_doc_page_renders(): void
    {
        $this->get(route('docs.ics-external-calendar'))
            ->assertOk()
            ->assertSee('外部日历（ICS）同步', false)
            ->assertSee('合并进前台可订', false);
    }
}
