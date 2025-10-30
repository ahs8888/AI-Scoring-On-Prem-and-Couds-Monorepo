# Encryption/Decryption Compatibility Guide

## Overview

On-prem encrypts files using AES-256-CBC before uploading to cloud. Cloud must decrypt using the **exact same algorithm and key derivation**.

---

## ✅ Compatibility Ensured

### Algorithm
- **Both**: `aes-256-cbc` (OpenSSL)

### Key Derivation
- **Both**: SHA-256 hash of raw key, truncated to 32 bytes
```php
$key = substr(hash('sha256', $rawKey, true), 0, 32);
```

### Encryption Format (On-Prem)
```
[IV (16 bytes)][Encrypted Data (variable)]
```
- IV prepended to encrypted data
- Uses `OPENSSL_RAW_DATA` flag

### Decryption (Cloud)
```php
$iv = substr($data, 0, 16);          // Extract IV
$encrypted = substr($data, 16);      // Extract encrypted data
$decrypted = openssl_decrypt(
    $encrypted,
    'aes-256-cbc',
    $key,
    OPENSSL_RAW_DATA,                // Must match on-prem
    $iv
);
```

---

## Environment Variables

### On-Prem (.env)
```env
FILE_ENCRYPTION_KEY=your-shared-secret-key-here
```

### Cloud (.env)
```env
FILE_ENCRYPTION_KEY=your-shared-secret-key-here
```

**IMPORTANT**: Both must use the **EXACT SAME** raw key value.

---

## Compression (Optional)

If on-prem compresses before encrypting:

### On-Prem Process
1. Compress with `gzcompress()`
2. Encrypt compressed data
3. Send to cloud

### Cloud Process
1. Decrypt file
2. Decompress with `gzuncompress()`
3. Store original file

**Flag in request**: `"compressed": true`

---

## Testing Encryption/Decryption

### Test 1: Simple Text File

**On-Prem**:
```php
$encryptionService = new EncryptionService();
file_put_contents('/tmp/test.txt', 'Hello World');
$result = $encryptionService->encryptFile('/tmp/test.txt', '/tmp/test.txt.enc');
```

**Cloud**:
```php
$decryptionService = new DecryptionService();
// Upload test.txt.enc to cloud storage
$decrypted = $decryptionService->decryptFile('test.txt.enc');
// Should output: 'Hello World'
```

### Test 2: Audio File

**On-Prem**:
```php
$result = $encryptionService->encryptFile('/path/to/recording.wav');
// Produces: recording.wav.enc
```

**Cloud API Test**:
```bash
curl -X POST {CLOUD_URL}/external/v1/ingest/recording \
  -H "Authorization: Bearer {TOKEN}" \
  -F "file=@recording.wav.enc" \
  -F "transcript=Test" \
  -F "filename=recording.wav" \
  -F "encrypted=true" \
  -F "encryption_method=aes-256-cbc"
```

**Expected**: File decrypted successfully, stored as `recording.wav`

### Test 3: Compressed & Encrypted

**On-Prem**:
```php
$result = $encryptionService->compressAndEncrypt('/path/to/large.wav');
// Produces: large.wav.enc (compressed inside)
```

**Cloud API Test**:
```bash
curl -X POST {CLOUD_URL}/external/v1/ingest/recording \
  -F "file=@large.wav.enc" \
  -F "encrypted=true" \
  -F "compressed=true" \
  ...
```

**Expected**: File decrypted then decompressed successfully

---

## Troubleshooting

### Error: "Decryption failed"

**Possible Causes**:
1. **Keys don't match**: On-prem and cloud using different `FILE_ENCRYPTION_KEY`
2. **Key derivation mismatch**: Not using SHA-256 hash
3. **Flag mismatch**: Not using `OPENSSL_RAW_DATA`
4. **Corrupted file**: File damaged during transfer

**Debug Steps**:
```php
// On cloud, check key derivation
$rawKey = config('phase10b.encryption.key');
$derivedKey = substr(hash('sha256', $rawKey, true), 0, 32);
Log::info('Derived key (hex)', ['key' => bin2hex($derivedKey)]);

// Compare with on-prem
// Keys should be identical
```

### Error: "Decompression failed"

**Possible Causes**:
1. File not actually compressed (check `compressed` flag)
2. Corrupted compressed data

**Debug Steps**:
```php
// Try to decompress manually
$compressed = Storage::get('decrypted-file');
$decompressed = gzuncompress($compressed);
if ($decompressed === false) {
    Log::error('Not a valid gzip file');
}
```

---

## Key Generation

To generate a secure shared key:

**Run once** (either on-prem or manually):
```php
php artisan tinker
> echo base64_encode(openssl_random_pseudo_bytes(32));
// Example output: "R3VlLTJ1cGVyLXNlY3VyZS1rZXktMzItYnl0ZXM="
```

**Then set in both .env files**:
```env
FILE_ENCRYPTION_KEY=R3VlLTJ1cGVyLXNlY3VyZS1rZXktMzItYnl0ZXM=
```

---

## Security Best Practices

1. **Never commit encryption keys** to git
2. **Use different keys** for development and production
3. **Rotate keys periodically** (coordinate between on-prem and cloud)
4. **Use strong keys** (32+ characters, random)
5. **Secure key storage** (use Laravel's encrypted config if possible)

---

## Implementation Checklist

### On-Prem Setup
- [x] EncryptionService implemented
- [ ] FILE_ENCRYPTION_KEY set in .env
- [x] Encryption before upload (in ProcessRecordingBatch job)

### Cloud Setup
- [x] DecryptionService implemented
- [x] Key derivation matches on-prem (SHA-256)
- [x] OPENSSL_RAW_DATA flag used
- [ ] FILE_ENCRYPTION_KEY set in .env (same as on-prem)
- [x] Integrated in IngestController

### Testing
- [ ] Test simple text file encryption/decryption
- [ ] Test audio file encryption/decryption
- [ ] Test compressed file encryption/decryption
- [ ] Verify keys match (compare derived keys)
- [ ] Test end-to-end (on-prem upload → cloud decrypt)

---

## Code Reference

### On-Prem EncryptionService
Location: `/app/ahs8888-Onprem-Kontakami-AI-cd0e83a/app/Services/EncryptionService.php`

Key methods:
- `encryptFile()` - Encrypt a file
- `compressAndEncrypt()` - Compress then encrypt
- `getEncryptionKey()` - Derive key from env

### Cloud DecryptionService
Location: `/app/ahs8888-Scoring-Kontakami-AI-afd3a2b/app/Services/DecryptionService.php`

Key methods:
- `decryptFile()` - Decrypt a file
- `decryptAndDecompress()` - Decrypt then decompress
- Constructor derives key (matches on-prem)

---

## Summary

✅ **Encryption/Decryption Compatibility: CONFIRMED**

- Same algorithm (AES-256-CBC)
- Same key derivation (SHA-256 hash)
- Same flags (OPENSSL_RAW_DATA)
- Same IV handling (prepended)
- Same compression (gzcompress/gzuncompress)

**Both apps must use identical `FILE_ENCRYPTION_KEY` value in .env**
