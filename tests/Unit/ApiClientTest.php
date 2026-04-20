<?php

namespace Tests\Unit;

use App\Services\Thinkion\ApiClient;
use App\Exceptions\ThinkionApiException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'thinkion.api.client_code' => 'test123',
            'thinkion.api.token' => 'test-token-12345',
            'thinkion.api.timeout' => 10,
            'thinkion.api.retries' => 2,
            'thinkion.api.retry_sleep_ms' => 10, // Fast retries for tests
            'thinkion.logging.requests' => false,
            'thinkion.logging.responses' => false,
        ]);
    }

    public function test_successful_api_call_returns_data(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Test Row 1'],
                    ['id' => 2, 'name' => 'Test Row 2'],
                ],
                'page' => null,
            ], 200),
        ]);

        $client = new ApiClient();
        $result = $client->fetchReport(233, '2025-01-01', '2025-01-31', [1, 2]);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        $this->assertNull($result['page']);
    }

    public function test_pagination_returns_page_token(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response([
                'data' => [['id' => 1]],
                'page' => 'next_page_token_abc',
            ], 200),
        ]);

        $client = new ApiClient();
        $result = $client->fetchReport(233, '2025-01-01', '2025-01-31', [1, 2]);

        $this->assertEquals('next_page_token_abc', $result['page']);
    }

    public function test_fetch_all_pages_iterates_tokens(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::sequence()
                ->push([
                    'data' => [['id' => 1], ['id' => 2]],
                    'page' => 'page2_token',
                ], 200)
                ->push([
                    'data' => [['id' => 3]],
                    'page' => null,
                ], 200),
        ]);

        $client = new ApiClient();
        $allData = $client->fetchAllPages(233, '2025-01-01', '2025-01-15', [1]);

        $this->assertCount(3, $allData);
        $this->assertEquals(1, $allData[0]['id']);
        $this->assertEquals(3, $allData[2]['id']);
    }

    public function test_client_error_throws_without_retry(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response('Unauthorized', 401),
        ]);

        $client = new ApiClient();

        $this->expectException(ThinkionApiException::class);
        $this->expectExceptionMessage('client error');

        $client->fetchReport(233, '2025-01-01', '2025-01-15', [1]);
    }

    public function test_server_error_retries_and_throws(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response('Internal Server Error', 500),
        ]);

        $client = new ApiClient();

        $this->expectException(ThinkionApiException::class);
        $this->expectExceptionMessage('server error');

        $client->fetchReport(233, '2025-01-01', '2025-01-15', [1]);
    }

    public function test_not_found_error_throws(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response('Not Found', 404),
        ]);

        $client = new ApiClient();

        $this->expectException(ThinkionApiException::class);

        $client->fetchReport(233, '2025-01-01', '2025-01-15', [1]);
    }

    public function test_empty_data_returns_empty_array(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response([
                'data' => [],
                'page' => null,
            ], 200),
        ]);

        $client = new ApiClient();
        $result = $client->fetchReport(233, '2025-01-01', '2025-01-15', [1]);

        $this->assertEmpty($result['data']);
    }

    public function test_constructs_correct_base_url(): void
    {
        $client = new ApiClient();
        $this->assertEquals('https://test123.thinkerp.cc', $client->getBaseUrl());
    }

    public function test_sends_correct_headers(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response(['data' => [], 'page' => null], 200),
        ]);

        $client = new ApiClient();
        $client->fetchReport(233, '2025-01-01', '2025-01-15', [1]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Server-Token', 'test-token-12345')
                && $request->hasHeader('Content-Type', 'application/json');
        });
    }

    public function test_sends_correct_payload(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response(['data' => [], 'page' => null], 200),
        ]);

        $client = new ApiClient();
        $client->fetchReport(233, '2025-01-01', '2025-01-31', [1, 2]);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['id_report'] === 233
                && $body['date_init'] === '2025-01-01'
                && $body['date_end'] === '2025-01-31'
                && $body['establishments'] === [1, 2];
        });
    }

    public function test_sends_page_token_when_provided(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response(['data' => [], 'page' => null], 200),
        ]);

        $client = new ApiClient();
        $client->fetchReport(233, '2025-01-01', '2025-01-15', [1], 'my_page_token');

        Http::assertSent(function ($request) {
            return $request->data()['page'] === 'my_page_token';
        });
    }
}
