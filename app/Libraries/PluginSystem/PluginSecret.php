<?php

declare(strict_types=1);

namespace App\Libraries\PluginSystem;

use Throwable;

/**
 * Cifrado de ajustes type=password. El valor en sys_plugin_settings
 * no queda en claro. Sin encryption.key no se guarda el secreto.
 */
final class PluginSecret
{
    private const PREFIX = 'enc1:';

    public static function ready(): bool
    {
        $key = config('Encryption')->key ?? '';

        return is_string($key) && $key !== '';
    }

    public static function seal(string $plain): ?string
    {
        if (! self::ready()) {
            return null;
        }

        try {
            $cipher = service('encrypter')->encrypt($plain);
        } catch (Throwable $e) {
            log_message('error', '[PluginSystem] no se cifró un ajuste: {message}', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! is_string($cipher) || $cipher === '') {
            return null;
        }

        return self::PREFIX . base64_encode($cipher);
    }

    public static function open(string $stored): string
    {
        if (! str_starts_with($stored, self::PREFIX)) {
            return $stored;
        }

        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || $raw === '') {
            return '';
        }

        try {
            $plain = service('encrypter')->decrypt($raw);
        } catch (Throwable $e) {
            log_message('error', '[PluginSystem] no se pudo leer un ajuste cifrado: {message}', [
                'message' => $e->getMessage(),
            ]);

            return '';
        }

        return is_string($plain) ? $plain : '';
    }
}
