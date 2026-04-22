<?php

namespace Tests\Feature;

use App\Services\MemPalaceClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemPalaceClientTest extends TestCase
{
    /**
     * Test successful remember method call
     */
    public function test_remember_successful(): void
    {
        Http::fake([
            'http://localhost:8001/mine' => Http::response([], 200),
        ]);

        $client = new MemPalaceClient();

        $sourceId = 'test-source-123';
        $content = 'Test event content';

        $result = $client->remember($sourceId, $content);

        $this->assertTrue($result);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($sourceId, $content) {
            return $request->url() === 'http://localhost:8001/mine' &&
                   $request['source_id'] === $sourceId &&
                   $request['content'] === $content;
        });
    }

    /**
     * Test failure in remember method call
     */
    public function test_remember_failure(): void
    {
        Http::fake([
            'http://localhost:8001/mine' => Http::response([], 500),
        ]);

        $client = new MemPalaceClient();

        $sourceId = 'test-source-123';
        $content = 'Test event content';

        $result = $client->remember($sourceId, $content);

        $this->assertFalse($result);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($sourceId, $content) {
            return $request->url() === 'http://localhost:8001/mine' &&
                   $request['source_id'] === $sourceId &&
                   $request['content'] === $content;
        });
    }
}