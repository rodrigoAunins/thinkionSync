<?php

namespace App\Services\Thinkion;

use App\Exceptions\ThinkionApiException;
use App\Services\Thinkion\Support\SyncLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class ApiClient
{
    private string $baseUrl;
    private string $token;
    private int $timeout;
    private int $retries;
    private int $retrySleepMs;

    public function __construct()
    {
        $code = config('thinkion.api.client_code');
        $this->baseUrl = config('thinkion.api.base_url')
            ?: "https://{$code}.thinkerp.cc";
        $this->token = config('thinkion.api.token');
        $this->timeout = config('thinkion.api.timeout', 60);
        $this->retries = config('thinkion.api.retries', 3);
        $this->retrySleepMs = config('thinkion.api.retry_sleep_ms', 1000);
    }

    /**
     * Fetch a single page of a report from Thinkion API.
     *
     * @param int    $reportId
     * @param string $dateInit       Format: Y-m-d
     * @param string $dateEnd        Format: Y-m-d
     * @param array  $establishments Array of establishment IDs
     * @param string|null $pageToken Pagination token from previous response
     * @return array{data: array, page: string|null}
     * @throws ThinkionApiException
     */
    public function fetchReport(
        int $reportId,
        string $dateInit,
        string $dateEnd,
        array $establishments,
        ?string $pageToken = null
    ): array {
        $url = rtrim($this->baseUrl, '/') . '/online/reporting/public/';

        $payload = [
            'id_report' => $reportId,
            'date_init' => $dateInit,
            'date_end' => $dateEnd,
            'establishments' => $establishments,
        ];

        if ($pageToken !== null) {
            $payload['page'] = $pageToken;
        }

        SyncLogger::logRequest('POST', $url, $payload);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retries) {
            $attempt++;

            try {
                /** @var Response $response */
                $response = Http::acceptJson()
                    ->withHeaders([
                        'X-Server-Token' => $this->token,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout($this->timeout)
                    ->post($url, $payload);

                SyncLogger::logResponse($response);

                if ($response->successful()) {
                    $body = $response->json() ?? [];
                    return [
                        'data' => $body['data'] ?? [],
                        'page' => $body['page'] ?? null,
                    ];
                }

                // Non-retryable client errors
                if ($response->status() >= 400 && $response->status() < 500) {
                    throw new ThinkionApiException(
                        "Thinkion API client error [{$response->status()}]: {$response->body()}",
                        $response->status(),
                        $response->body()
                    );
                }

                // Server error — retryable
                $lastException = new ThinkionApiException(
                    "Thinkion API server error [{$response->status()}]: {$response->body()}",
                    $response->status(),
                    $response->body()
                );

                SyncLogger::logWarning("Attempt {$attempt}/{$this->retries} failed with status {$response->status()}, retrying...");

            } catch (ThinkionApiException $e) {
                // If it's a client error (4xx), don't retry
                if ($e->getHttpStatus() >= 400 && $e->getHttpStatus() < 500) {
                    throw $e;
                }
                $lastException = $e;
                SyncLogger::logWarning("Attempt {$attempt}/{$this->retries} exception: {$e->getMessage()}, retrying...");
            } catch (\Throwable $e) {
                $lastException = new ThinkionApiException(
                    'Network / unexpected error: ' . $e->getMessage(),
                    0,
                    '',
                    $e
                );
                SyncLogger::logWarning("Attempt {$attempt}/{$this->retries} network error: {$e->getMessage()}, retrying...");
            }

            if ($attempt < $this->retries) {
                usleep($this->retrySleepMs * 1000);
            }
        }

        SyncLogger::logError("All {$this->retries} attempts failed for report {$reportId}");
        throw $lastException ?? new ThinkionApiException("All retry attempts exhausted");
    }

    /**
     * Fetch ALL pages of a report by iterating through pagination tokens.
     *
     * @return array All data rows concatenated across pages
     */
    public function fetchAllPages(
        int $reportId,
        string $dateInit,
        string $dateEnd,
        array $establishments
    ): array {
        $allData = [];
        $pageToken = null;
        $pageCount = 0;

        do {
            $pageCount++;
            SyncLogger::logInfo("Fetching page {$pageCount} for report {$reportId} ({$dateInit} → {$dateEnd})");

            $result = $this->fetchReport($reportId, $dateInit, $dateEnd, $establishments, $pageToken);
            $pageData = $result['data'] ?? [];
            $pageToken = $result['page'] ?? null;

            if (!empty($pageData)) {
                foreach ($pageData as $row) {
                    $allData[] = $row;
                }
            }

            SyncLogger::logInfo("Page {$pageCount}: received " . count($pageData) . " rows" .
                ($this->isValidToken($pageToken) ? ", more pages available" : ", last page"));

        } while ($this->isValidToken($pageToken));

        SyncLogger::logInfo("Total: {$pageCount} page(s), " . count($allData) . " rows for report {$reportId}");

        return $allData;
    }

    /**
     * Check if a pagination token is valid and should trigger another request.
     */
    private function isValidToken(mixed $token): bool
    {
        if ($token === null || $token === false || $token === '') {
            return false;
        }

        // Thinkion sometimes returns ':' as an empty/end token for some reports
        if ($token === ':') {
            return false;
        }

        return true;
    }

    /**
     * Simple connectivity test — fetches first page of a report.
     */
    public function testConnection(int $reportId, array $establishments): array
    {
        $dateEnd = now()->format('Y-m-d');
        $dateInit = now()->subDays(1)->format('Y-m-d');

        return $this->fetchReport($reportId, $dateInit, $dateEnd, $establishments);
    }

    /**
     * Get the constructed base URL (useful for diagnostics).
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
