<?php

namespace fiskalizimi;

interface ISigner
{
    // Returns base64 encoded signature of the signed bytes
    public function signBytes(string $data): string;
}

class Signer implements ISigner
{
    private string $key;

    public function __construct(string $pemKey)
    {
        $this->key = $pemKey;
    }

    public function signBytes(string $data): string
    {
        // Load the private key
        $privateKey = openssl_pkey_get_private($this->key);
        if (!$privateKey) {
            throw new \Exception('Error loading private key.');
        }

        // Hash the data using SHA256
        $hash = hash('sha256', $data, true);

        // Sign the hash
        $signature = '';
        $success = openssl_sign($hash, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$success) {
            throw new \Exception('Error signing data.');
        }

        // Free the private key resource
        openssl_free_key($privateKey);

        // Return the base64-encoded signature
        return base64_encode($signature);
    }
}