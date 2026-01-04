<?php

namespace App\Service;

use App\Entity\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use OTPHP\TOTP;

class TwoFactorService
{
    private string $issuer;

    public function __construct(string $appName = 'wr506d-2025-2026')
    {
        $this->issuer = $appName;
    }

    /**
     * Generate a new TOTP secret for a user
     */
    public function generateSecret(): string
    {
        // from => https://github.com/Spomky-Labs/otphp/blob/11.4.x/doc/index.md
        $totp = TOTP::generate();
        return $totp->getSecret();
    }

    /**
     * Get TOTP instance for a user
     */
    private function getTOTP(User $user): TOTP
    {
        // recuperation du secret lie a l'utilisateur // si pas de secret alors ERREUR
        $secret = $user->getTwoFactorSecret();
        if ($secret === null) {
            throw new \RuntimeException('User does not have a 2FA secret');
        }

        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user->getEmail() ?? 'user');
        $totp->setIssuer($this->issuer);

        return $totp;
    }

    /**
     * Generate provisioning URI for QR code
     */
    public function getProvisioningUri(User $user): string
    {
        return $this->getTOTP($user)->getProvisioningUri();
    }

    /**
     * Generate QR code as base64 image data
     */
    public function getQrCode(User $user): string
    {
        $provisioningUri = $this->getProvisioningUri($user);

        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $provisioningUri,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    /**
     * Verify TOTP code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        if (!$secret) {
            return false;
        }

        $totp = TOTP::createFromSecret($secret);

        // Verifie le code avec une fenetre de ±1 (tolerance de 30 sec)
        return $totp->verify($code, null, 1);
    }

    /**
     * Generate backup codes
     * @return list<string>
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate 8-character alphanumeric codes
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        return $codes;
    }

    /**
     * Hash backup codes for storage
     * @param list<string> $codes
     * @return list<string>
     */
    public function hashBackupCodes(array $codes): array
    {
        return array_map(fn($code) => hash('sha256', $code), $codes);
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        $hashedCode = hash('sha256', $code);
        $backupCodes = $user->getTwoFactorBackupCodes();

        if ($backupCodes === null) {
            return false;
        }

        return in_array($hashedCode, $backupCodes, true);
    }
}
