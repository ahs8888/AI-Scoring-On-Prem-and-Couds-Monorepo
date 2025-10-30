<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DecryptionService
{
    protected string $encryptionKey;
    protected string $method;
    
    public function __construct()
    {
        $this->encryptionKey = config('phase10b.encryption.key');
        $this->method = config('phase10b.encryption.method', 'aes-256-cbc');
    }
    
    /**
     * Decrypt an encrypted file
     */
    public function decryptFile(string $encryptedFilePath): ?string
    {
        try {
            if (!Storage::exists($encryptedFilePath)) {
                Log::error('Encrypted file not found', ['path' => $encryptedFilePath]);
                return null;
            }
            
            $encryptedContent = Storage::get($encryptedFilePath);
            
            // Decrypt the content
            $decrypted = $this->decrypt($encryptedContent);
            
            if ($decrypted === false) {
                Log::error('Decryption failed', ['path' => $encryptedFilePath]);
                return null;
            }
            
            // Save decrypted file
            $decryptedPath = str_replace('.enc', '', $encryptedFilePath);
            Storage::put($decryptedPath, $decrypted);
            
            return $decryptedPath;
            
        } catch (\Exception $e) {
            Log::error('Decryption exception', [
                'path' => $encryptedFilePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Decrypt data
     */
    protected function decrypt(string $data): string|false
    {
        $ivLength = openssl_cipher_iv_length($this->method);
        
        // Extract IV from the beginning of the encrypted data
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt(
            $encrypted,
            $this->method,
            $this->encryptionKey,
            0,
            $iv
        );
    }
    
    /**
     * Decompress a compressed file
     */
    public function decompressFile(string $compressedPath): ?string
    {
        try {
            if (!Storage::exists($compressedPath)) {
                return null;
            }
            
            $compressed = Storage::get($compressedPath);
            $decompressed = gzuncompress($compressed);
            
            if ($decompressed === false) {
                return null;
            }
            
            $decompressedPath = str_replace('.gz', '', $compressedPath);
            Storage::put($decompressedPath, $decompressed);
            
            return $decompressedPath;
            
        } catch (\Exception $e) {
            Log::error('Decompression failed', [
                'path' => $compressedPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Decrypt and decompress if needed
     */
    public function decryptAndDecompress(string $filePath, bool $isCompressed = false): ?string
    {
        // First decrypt
        $decryptedPath = $this->decryptFile($filePath);
        
        if (!$decryptedPath) {
            return null;
        }
        
        // Then decompress if needed
        if ($isCompressed) {
            return $this->decompressFile($decryptedPath);
        }
        
        return $decryptedPath;
    }
}
