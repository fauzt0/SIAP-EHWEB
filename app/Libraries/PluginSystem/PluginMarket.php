<?php

declare(strict_types=1);

namespace App\Libraries\PluginSystem;

/**
 * Catálogo local del Market. El remoto, si existe, solo se muestra:
 * no se instala nada que no esté ya en disco.
 */
class PluginMarket
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function getCatalog(): array
    {
        $local  = self::scanLocalPlugins();
        $remote = self::fetchRemoteCatalog((string) env('PLUGIN_MARKET_URL'));

        $bySlug = [];
        foreach ($local as $plugin) {
            $bySlug[(string) ($plugin['slug'] ?? spl_object_id((object) $plugin))] = $plugin;
        }

        foreach ($remote as $plugin) {
            $slug = (string) ($plugin['slug'] ?? '');
            if ($slug === '' || isset($bySlug[$slug])) {
                continue;
            }
            $plugin['installable'] = false;
            $plugin['source']      = 'remote';
            $bySlug[$slug]         = $plugin;
        }

        return array_values($bySlug);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getInstalled(): array
    {
        return PluginManager::installedRows();
    }

    /**
     * Plugins en disco que todavía no tienen fila en sys_plugins.
     *
     * @return list<array<string, mixed>>
     */
    public static function getAvailable(): array
    {
        $installed = [];
        foreach (self::getInstalled() as $row) {
            $installed[(string) $row['slug']] = true;
        }

        $available = [];
        foreach (self::scanLocalPlugins() as $plugin) {
            if (! isset($installed[(string) ($plugin['slug'] ?? '')])) {
                $available[] = $plugin;
            }
        }

        return $available;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function scanLocalPlugins(): array
    {
        $plugins = [];

        foreach (PluginManager::onDiskManifests() as $entry) {
            $manifest = $entry['manifest'];
            if (! $entry['readable']) {
                $plugins[] = [
                    'slug'        => basename($entry['path']),
                    'name'        => basename($entry['path']),
                    'version'     => '—',
                    'description' => 'plugin.json no es JSON válido.',
                    'icon'        => 'fa-triangle-exclamation',
                    'installable' => false,
                    'source'      => 'local',
                    'error'       => 'Manifiesto ilegible.',
                ];
                continue;
            }

            $errors = PluginManager::manifestErrors($manifest, $entry['path']);
            $manifest['installable'] = $errors === [];
            $manifest['source']      = 'local';
            $manifest['error']       = $errors[0] ?? '';
            $plugins[]               = $manifest;
        }

        return $plugins;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function fetchRemoteCatalog(string $url): array
    {
        if (preg_match('#^https://#i', $url) !== 1) {
            return [];
        }

        $context = stream_context_create([
            'http' => [
                'timeout'         => 3,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'max_redirects'   => 0,
                'header'          => "Accept: application/json\r\nUser-Agent: ERP-PluginMarket\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $items = array_is_list($decoded) ? $decoded : ($decoded['plugins'] ?? []);
        if (! is_array($items)) {
            return [];
        }

        $clean = [];
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['slug']) || empty($item['name'])) {
                continue;
            }
            $clean[] = [
                'slug'        => (string) $item['slug'],
                'name'        => (string) $item['name'],
                'version'     => (string) ($item['version'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
                'icon'        => 'fa-cloud',
                'installable' => false,
                'source'      => 'remote',
            ];
        }

        return $clean;
    }
}
