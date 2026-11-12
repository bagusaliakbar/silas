<?php

namespace App\Services;

use App\Models\DigitalCertificate;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CertificateService
{
    /**
     * Resolve or generate a valid openssl.cnf path for cross-platform compatibility.
     */
    protected function getOpenSslConfigPath(): string
    {
        $envPath = getenv('OPENSSL_CONF') ?: ($_ENV['OPENSSL_CONF'] ?? null);
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        // Priority candidates for complete OpenSSL configuration
        $potentialPaths = [
            'C:/Users/Bagus Ali Akbar/.config/herd/bin/php85/extras/ssl/openssl.cnf',
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:/xampp/apache/conf/openssl.cnf',
        ];

        foreach ($potentialPaths as $p) {
            if (file_exists($p)) {
                return $p;
            }
        }

        // Fallback: bundled in local storage
        $fallbackPath = storage_path('app/openssl.cnf');
        if (! file_exists($fallbackPath)) {
            $minimalCnf = <<<'EOT'
[ req ]
default_bits = 2048
default_keyfile = privkey.pem
distinguished_name = req_distinguished_name
prompt = no
x509_extensions = v3_ca

[ req_distinguished_name ]
countryName = ID
stateOrProvinceName = Jawa Barat
localityName = Subang
organizationName = Universitas Subang
organizationalUnitName = Fakultas Ilmu Komputer
commonName = Program Studi Sistem Informasi
emailAddress = kaprodi.si@unsub.ac.id

[ v3_ca ]
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer
basicConstraints = critical,CA:true
keyUsage = critical,digitalSignature,cRLSign,keyCertSign
EOT;
            if (! is_dir(dirname($fallbackPath))) {
                @mkdir(dirname($fallbackPath), 0755, true);
            }
            file_put_contents($fallbackPath, $minimalCnf);
        }

        return $fallbackPath;
    }

    /**
     * Generate a self-signed X.509 PKI certificate for a user/organization.
     *
     * @param  array{name: string, organization?: string|null, organizational_unit?: string|null, email?: string|null, country?: string|null, passphrase?: string|null, days_valid?: int|null}  $data
     */
    public function generateSelfSignedCertificate(User $user, array $data): DigitalCertificate
    {
        $country = strtoupper(substr($data['country'] ?? 'ID', 0, 2));
        $commonName = $data['name'];
        $organization = $data['organization'] ?? 'Universitas Subang';
        $orgUnit = $data['organizational_unit'] ?? 'Fakultas Ilmu Komputer';
        $email = $data['email'] ?? $user->email;
        $passphrase = $data['passphrase'] ?? null;
        $daysValid = (int) ($data['days_valid'] ?? 365);

        $cnfPath = $this->getOpenSslConfigPath();

        $dn = [
            'countryName' => $country,
            'stateOrProvinceName' => 'Jawa Barat',
            'localityName' => 'Subang',
            'organizationName' => $organization,
            'organizationalUnitName' => $orgUnit,
            'commonName' => $commonName,
            'emailAddress' => $email,
        ];

        // 1. Generate RSA Private Key
        $privateKeyConfig = [
            'config' => $cnfPath,
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privKeyResource = openssl_pkey_new($privateKeyConfig);
        if (! $privKeyResource) {
            throw new RuntimeException('Failed to generate private key: '.openssl_error_string());
        }

        // 2. Generate CSR
        $csrConfig = [
            'config' => $cnfPath,
            'digest_alg' => 'sha256',
        ];
        $csrResource = openssl_csr_new($dn, $privKeyResource, $csrConfig);
        if (! $csrResource) {
            throw new RuntimeException('Failed to generate certificate request: '.openssl_error_string());
        }

        // 3. Self-sign Certificate
        $serialNumber = (int) (time() + random_int(1000, 999999));
        $signConfig = [
            'config' => $cnfPath,
            'digest_alg' => 'sha256',
        ];
        $certResource = openssl_csr_sign($csrResource, null, $privKeyResource, $daysValid, $signConfig, $serialNumber);
        if (! $certResource) {
            throw new RuntimeException('Failed to sign certificate: '.openssl_error_string());
        }

        // 4. Export Certificate and Private Key
        $certPem = '';
        $privateKeyPem = '';
        openssl_x509_export($certResource, $certPem);
        openssl_pkey_export($privKeyResource, $privateKeyPem, $passphrase, ['config' => $cnfPath]);

        // 5. Generate PKCS#12 (.p12) bundle
        $p12Data = null;
        openssl_pkcs12_export($certResource, $p12Data, $privKeyResource, $passphrase ?? '', [
            'friendly_name' => $commonName,
        ]);

        // 6. Save files to secure storage
        $certDir = 'certificates/'.$user->id;
        Storage::disk('local')->makeDirectory($certDir);

        $uniqueKey = Str::random(12);
        $crtPath = $certDir.'/'.$uniqueKey.'.crt';
        $keyPath = $certDir.'/'.$uniqueKey.'.key';
        $p12Path = $certDir.'/'.$uniqueKey.'.p12';

        Storage::disk('local')->put($crtPath, $certPem);
        Storage::disk('local')->put($keyPath, $privateKeyPem);
        if ($p12Data) {
            Storage::disk('local')->put($p12Path, $p12Data);
        }

        // 7. Parse certificate metadata
        $parsed = openssl_x509_parse($certPem);
        $validFrom = isset($parsed['validFrom_time_t']) ? date('Y-m-d H:i:s', $parsed['validFrom_time_t']) : now();
        $validTo = isset($parsed['validTo_time_t']) ? date('Y-m-d H:i:s', $parsed['validTo_time_t']) : now()->addDays($daysValid);
        $serialHex = isset($parsed['serialNumberHex']) ? $parsed['serialNumberHex'] : dechex($serialNumber);

        $isFirst = ! $user->digitalCertificates()->exists();

        return DigitalCertificate::create([
            'user_id' => $user->id,
            'name' => $commonName,
            'organization' => $organization,
            'organizational_unit' => $orgUnit,
            'email' => $email,
            'country' => $country,
            'certificate_path' => $crtPath,
            'private_key_path' => $keyPath,
            'p12_path' => $p12Path,
            'passphrase' => $passphrase,
            'serial_number' => strtoupper((string) $serialHex),
            'issuer' => $parsed['issuer']['CN'] ?? ($organization.' Root CA'),
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'is_active' => true,
            'is_default' => $isFirst,
        ]);
    }

    /**
     * Import a PKCS#12 (.p12 / .pfx) file.
     */
    public function importPkcs12(User $user, string $p12Content, ?string $passphrase, string $name): DigitalCertificate
    {
        $certs = [];
        if (! openssl_pkcs12_read($p12Content, $certs, $passphrase ?? '')) {
            throw new RuntimeException('Failed to read PKCS#12 certificate. Passphrase might be incorrect.');
        }

        $certPem = $certs['cert'];
        $privateKeyPem = $certs['pkey'];

        $certDir = 'certificates/'.$user->id;
        Storage::disk('local')->makeDirectory($certDir);

        $uniqueKey = Str::random(12);
        $crtPath = $certDir.'/'.$uniqueKey.'.crt';
        $keyPath = $certDir.'/'.$uniqueKey.'.key';
        $p12Path = $certDir.'/'.$uniqueKey.'.p12';

        Storage::disk('local')->put($crtPath, $certPem);
        Storage::disk('local')->put($keyPath, $privateKeyPem);
        Storage::disk('local')->put($p12Path, $p12Content);

        $parsed = openssl_x509_parse($certPem);
        $validFrom = isset($parsed['validFrom_time_t']) ? date('Y-m-d H:i:s', $parsed['validFrom_time_t']) : now();
        $validTo = isset($parsed['validTo_time_t']) ? date('Y-m-d H:i:s', $parsed['validTo_time_t']) : now()->addYear();
        $serialHex = $parsed['serialNumberHex'] ?? Str::random(16);

        $isFirst = ! $user->digitalCertificates()->exists();

        return DigitalCertificate::create([
            'user_id' => $user->id,
            'name' => $name,
            'organization' => $parsed['subject']['O'] ?? 'Imported Certificate',
            'organizational_unit' => $parsed['subject']['OU'] ?? null,
            'email' => $parsed['subject']['emailAddress'] ?? $user->email,
            'country' => $parsed['subject']['C'] ?? 'ID',
            'certificate_path' => $crtPath,
            'private_key_path' => $keyPath,
            'p12_path' => $p12Path,
            'passphrase' => $passphrase,
            'serial_number' => strtoupper((string) $serialHex),
            'issuer' => $parsed['issuer']['CN'] ?? 'External CA',
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'is_active' => true,
            'is_default' => $isFirst,
        ]);
    }
}
