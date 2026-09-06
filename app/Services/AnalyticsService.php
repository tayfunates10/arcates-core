<?php
declare(strict_types=1);

namespace Arcates\Services;

use Arcates\Core\App;
use Arcates\Core\Logger;

final class AnalyticsService
{
    public function track(): void
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
                || ($_SERVER['HTTP_DNT'] ?? '') === '1'
                || http_response_code() >= 400
            ) {
                return;
            }

            $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
            if ($ua === '' || preg_match(
                '/bot|spider|crawler|slurp|bingpreview|facebookexternalhit|headless|monitor|uptime/',
                $ua
            )) {
                return;
            }

            $path = $this->normalizedPath((string) ($_SERVER['REQUEST_URI'] ?? '/'));
            if ($this->excluded($path)) {
                return;
            }

            $limit = max(25, min(5000, (int) App::config('analytics.daily_path_limit', 500)));
            $count = App::db()->fetch(
                'SELECT COUNT(DISTINCT path) AS total FROM analytics_daily WHERE day=CURDATE()'
            );
            if ((int) ($count['total'] ?? 0) >= $limit) {
                $known = App::db()->fetch(
                    'SELECT 1 AS found FROM analytics_daily WHERE day=CURDATE() AND path=? LIMIT 1',
                    [$path]
                );
                if (!$known) {
                    $path = '/diger';
                }
            }

            $referrer = $this->referrerHost();
            App::db()->execute(
                'INSERT INTO analytics_daily(day,path,referrer_host,pageviews,created_at,updated_at) '
                . 'VALUES(CURDATE(),?,?,1,NOW(),NOW()) '
                . 'ON DUPLICATE KEY UPDATE pageviews=pageviews+1,updated_at=NOW()',
                [$path, $referrer]
            );
        } catch (\Throwable $e) {
            Logger::error('Analytics disabled for request', [
                'type' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function normalizedPath(string $uri): string
    {
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $path = rawurldecode($path);
        $path = preg_replace('/[[:cntrl:]]/u', '', $path) ?? '/';
        $path = preg_replace('#/+#', '/', $path) ?? '/';
        $path = '/' . ltrim($path, '/');
        return mb_substr($path, 0, 255);
    }

    private function excluded(string $path): bool
    {
        $admin = '/' . trim((string) App::config('app.admin_path', 'yonetim'), '/');
        foreach ([$admin, '/assets/', '/uploads/', '/install', '/sitemap.xml', '/robots.txt', '/favicon.ico'] as $skip) {
            if ($path === $skip || str_starts_with($path, rtrim($skip, '/') . '/')) {
                return true;
            }
        }
        return false;
    }

    private function referrerHost(): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer === '') {
            return '';
        }
        $host = strtolower((string) (parse_url($referer, PHP_URL_HOST) ?? ''));
        $appHost = strtolower((string) (parse_url((string) App::config('app.url', ''), PHP_URL_HOST) ?? ''));
        if ($host === '' || $host === $appHost) {
            return '';
        }
        return mb_substr($host, 0, 190);
    }
}
