<?php

namespace App\Services\Signers;

use App\Services\Signers\Contracts\SignerDriverInterface;
use App\Services\Signers\Drivers\DocuSignDriver;
use App\Services\Signers\Drivers\LocalPkiDriver;
use App\Services\Signers\Drivers\PeruriDriver;
use App\Services\Signers\Drivers\PrivyDriver;
use InvalidArgumentException;

class SignerManager
{
    /**
     * @var array<string, SignerDriverInterface>
     */
    protected array $drivers = [];

    public function __construct(
        LocalPkiDriver $localDriver,
        PrivyDriver $privyDriver,
        PeruriDriver $peruriDriver,
        DocuSignDriver $docuSignDriver
    ) {
        $this->drivers = [
            'local' => $localDriver,
            'privy' => $privyDriver,
            'peruri' => $peruriDriver,
            'docusign' => $docuSignDriver,
        ];
    }

    /**
     * Get a specific driver by key.
     */
    public function driver(?string $name = null): SignerDriverInterface
    {
        $name = $name ?: 'local';

        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Signer driver [{$name}] is not supported.");
        }

        return $this->drivers[$name];
    }

    /**
     * List all available drivers for UI selection.
     *
     * @return list<array{id: string, name: string}>
     */
    public function getAvailableDrivers(): array
    {
        $list = [];
        foreach ($this->drivers as $id => $driver) {
            $list[] = [
                'id' => $id,
                'name' => $driver->getDisplayName(),
            ];
        }

        return $list;
    }
}
