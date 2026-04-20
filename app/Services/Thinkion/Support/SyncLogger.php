<?php

namespace App\Services\Thinkion\Support;

use Illuminate\Support\Facades\Log;

class SyncLogger
{
    /**
     * Echo output to console when running in CLI mode (for live feedback).
     */
    private static function echoIfConsole(string $level, string $message, array $context = []): void
    {
        if (app()->runningInConsole() && config('app.debug', true)) {
            $ctx = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
            $time = date('Y-m-d H:i:s');
            echo "[{$time}] [THINKION_{$level}] {$message}{$ctx}\n";
        }
    }

    public static function logRequest(string $method, string $url, array $data = []): void
    {
        if (config('thinkion.logging.requests', true)) {
            Log::channel('thinkion')->info("THINKION-REQ [{$method}] {$url}", [
                'payload' => $data,
            ]);
            self::echoIfConsole('REQ', "[{$method}] {$url}", $data);
        }
    }

    public static function logResponse(\Illuminate\Http\Client\Response $response): void
    {
        if (config('thinkion.logging.responses', false)) {
            $body = $response->body();
            // Truncate very long responses in logs
            if (strlen($body) > 2000) {
                $body = substr($body, 0, 2000) . '... [TRUNCATED]';
            }
            Log::channel('thinkion')->info("THINKION-RES [{$response->status()}]", [
                'body' => $body,
            ]);
            self::echoIfConsole('RES', "[{$response->status()}]");
        }
    }

    public static function logInfo(string $message, array $context = []): void
    {
        Log::channel('thinkion')->info("THINKION: {$message}", $context);
        self::echoIfConsole('INFO', $message, $context);
    }

    public static function logWarning(string $message, array $context = []): void
    {
        Log::channel('thinkion')->warning("THINKION-WARN: {$message}", $context);
        self::echoIfConsole('WARN', $message, $context);
    }

    public static function logError(string $message, array $context = []): void
    {
        Log::channel('thinkion')->error("THINKION-ERR: {$message}", $context);
        self::echoIfConsole('ERR', $message, $context);
    }
}
