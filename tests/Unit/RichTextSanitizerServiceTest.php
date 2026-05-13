<?php

namespace Tests\Unit;

use App\Services\RichTextSanitizerService;
use PHPUnit\Framework\TestCase;

class RichTextSanitizerServiceTest extends TestCase
{
    public function test_strips_script_tags(): void
    {
        $service = new RichTextSanitizerService;
        $html = '<p>Hello</p><script>alert(1)</script>';

        $sanitized = $service->sanitize($html);

        $this->assertIsString($sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
    }

    public function test_null_returns_null(): void
    {
        $service = new RichTextSanitizerService;

        $this->assertNull($service->sanitize(null));
    }
}
