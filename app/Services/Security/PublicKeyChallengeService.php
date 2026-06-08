<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicKeyChallengeService
{
    public function generatePlaintext(): string
    {
        return 'PUBLIC-KEY-' . Str::random(10) . '-CHALLENGE';
    }

    public function encrypt(string $message, string $publicKey): string|false
    {
        $tempDir = sys_get_temp_dir() . '/gnupg_' . uniqid();
        mkdir($tempDir, 0700);

        try {
            putenv('GNUPGHOME=' . $tempDir);
            $gpg = new \gnupg();
            $gpg->seterrormode(\gnupg::ERROR_EXCEPTION);

            $importInfo = $gpg->import($publicKey);
            if (empty($importInfo['fingerprint'])) {
                throw new \RuntimeException('Failed to import public key.');
            }

            $gpg->addencryptkey($importInfo['fingerprint']);

            return $gpg->encrypt($message);
        } catch (\Throwable $e) {
            Log::error('Public-key challenge encryption failed: ' . $e->getMessage());

            return false;
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $object;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
