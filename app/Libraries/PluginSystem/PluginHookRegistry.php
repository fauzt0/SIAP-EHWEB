<?php

declare(strict_types=1);

namespace App\Libraries\PluginSystem;

/**
 * Bus de acciones y filtros de los plugins.
 * Cada callback queda aislado: un plugin roto no tumba el ERP (V12-6).
 */
class PluginHookRegistry
{
    /** @var array<string, array<int, list<callable>>> */
    private static array $actions = [];

    /** @var array<string, array<int, list<callable>>> */
    private static array $filters = [];

    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][$priority][] = $callback;
    }

    public static function doAction(string $hook, mixed ...$args): void
    {
        if (empty(self::$actions[$hook])) {
            return;
        }

        ksort(self::$actions[$hook]);

        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    call_user_func($callback, ...$args);
                } catch (\Throwable $e) {
                    log_message('error', '[PluginSystem] action {hook} falló: {message}', [
                        'hook'    => $hook,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Ejecuta la acción y junta los arrays que devuelvan los callbacks.
     *
     * @return list<mixed>
     */
    public static function doActionCollect(string $hook, mixed ...$args): array
    {
        $results = [];

        if (empty(self::$actions[$hook])) {
            return $results;
        }

        ksort(self::$actions[$hook]);

        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $result = call_user_func($callback, ...$args);
                } catch (\Throwable $e) {
                    log_message('error', '[PluginSystem] action {hook} falló: {message}', [
                        'hook'    => $hook,
                        'message' => $e->getMessage(),
                    ]);
                    continue;
                }

                if (is_array($result)) {
                    $results = array_merge($results, $result);
                }
            }
        }

        return $results;
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
    }

    public static function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (empty(self::$filters[$hook])) {
            return $value;
        }

        ksort(self::$filters[$hook]);

        foreach (self::$filters[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $value = call_user_func($callback, $value, ...$args);
                } catch (\Throwable $e) {
                    log_message('error', '[PluginSystem] filter {hook} falló: {message}', [
                        'hook'    => $hook,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $value;
    }

    /** @return list<string> */
    public static function wiredHooks(): array
    {
        return ['alert.detect', 'layout.footer'];
    }
}
