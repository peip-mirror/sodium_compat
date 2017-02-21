<?php

/**
 * Class ParagonIE_Sodium_File
 */
class ParagonIE_Sodium_File extends ParagonIE_Sodium_Core_Util
{
    const BUFFER_SIZE = 8192;

    /**
     * Box a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box(), but produces
     * the same result.
     *
     * @param string $inputFile  Absolute path to a file on the filesystem
     * @param string $outputFile Absolute path to a file on the filesystem
     * @param string $nonce      Number to be used only once
     * @param string $keyPair    ECDH secret key and ECDH public key concatenated
     *
     * @return bool
     * @throws Error
     * @throws TypeError
     */
    public static function box($inputFile, $outputFile, $nonce, $keyPair)
    {
        if (!is_string($inputFile)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($outputFile)) {
            throw new TypeError('Argument 2 must be a string.');
        }
        if (!is_string($nonce)) {
            throw new TypeError('Argument 3 must be a string');
        }
        if (!is_string($keyPair)) {
            throw new TypeError('Argument 4 must be a string');
        }
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_BOX_NONCEBYTES bytes');
        }
        if (self::strlen($keyPair) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_BOX_KEYPAIRBYTES bytes');
        }

        /** @var int $size */
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $ifp */
        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var resource $ofp */
        $ofp = fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new Error('Could not open output file for writing');
        }

        $res = self::box_encrypt($ifp, $ofp, $size, $nonce, $keyPair);
        fclose($ifp);
        fclose($ofp);
        return $res;
    }

    /**
     * Seal a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box_open(), but produces
     * the same result.
     *
     * Warning: Does not protect against TOCTOU attacks. You should
     * just load the file into memory and use crypto_box_open() if
     * you are worried about those.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $nonce
     * @param string $ecdhKeypair
     * @return bool
     * @throws Error
     * @throws TypeError
     */
    public static function box_open($inputFile, $outputFile, $nonce, $ecdhKeypair)
    {
        if (!is_string($inputFile)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($outputFile)) {
            throw new TypeError('Argument 2 must be a string.');
        }
        if (!is_string($nonce)) {
            throw new TypeError('Argument 3 must be a string');
        }
        if (!is_string($ecdhKeypair)) {
            throw new TypeError('Argument 4 must be a string');
        }
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_BOX_NONCEBYTES bytes');
        }
        if (self::strlen($ecdhKeypair) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_BOX_KEYPAIRBYTES bytes');
        }

        /** @var int $size */
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $ifp */
        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var resource $ofp */
        $ofp = fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new Error('Could not open output file for writing');
        }

        $res = self::box_decrypt($ifp, $ofp, $size, $nonce, $ecdhKeypair);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($nonce);
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
        } catch (Error $ex) {
            unset($ephKeypair);
        }
        return $res;
    }

    /**
     * Seal a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box_seal(), but produces
     * the same result.
     *
     * @param string $inputFile  Absolute path to a file on the filesystem
     * @param string $outputFile Absolute path to a file on the filesystem
     * @param string $publicKey  ECDH public key
     *
     * @return bool
     * @throws Error
     * @throws TypeError
     */
    public static function box_seal($inputFile, $outputFile, $publicKey)
    {
        if (!is_string($inputFile)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($outputFile)) {
            throw new TypeError('Argument 2 must be a string.');
        }
        if (!is_string($publicKey)) {
            throw new TypeError('Argument 3 must be a string');
        }
        if (self::strlen($publicKey) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_BOX_PUBLICKEYBYTES bytes');
        }

        /** @var int $size */
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $ifp */
        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var resource $ofp */
        $ofp = fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new Error('Could not open output file for writing');
        }

        /** @var string $ephKeypair */
        $ephKeypair = ParagonIE_Sodium_Compat::crypto_box_keypair();

        /** @var string $msgKeypair */
        $msgKeypair = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey(
            ParagonIE_Sodium_Compat::crypto_box_secretkey($ephKeypair),
            $publicKey
        );

        /** @var string $ephemeralPK */
        $ephemeralPK = ParagonIE_Sodium_Compat::crypto_box_publickey($ephKeypair);

        /** @var string $nonce */
        $nonce = ParagonIE_Sodium_Compat::crypto_generichash(
            $ephemeralPK . $publicKey,
            '',
            24
        );

        /** @var int $firstWrite */
        $firstWrite = fwrite(
            $ofp,
            $ephemeralPK,
            ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES
        );
        if (!is_int($firstWrite)) {
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
            throw new Error('Could not write to output file');
        }
        if ($firstWrite !== ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES) {
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
            throw new Error('Error writing public key to output file');
        }

        $res = self::box_encrypt($ifp, $ofp, $size, $nonce, $msgKeypair);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($nonce);
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
        } catch (Error $ex) {
            unset($ephKeypair);
        }
        return $res;
    }

    /**
     * Seal a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box_seal_open(), but produces
     * the same result.
     *
     * Warning: Does not protect against TOCTOU attacks. You should
     * just load the file into memory and use crypto_box_seal_open() if
     * you are worried about those.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $ecdhKeypair
     * @return bool
     * @throws Error
     * @throws TypeError
     */
    public static function box_seal_open($inputFile, $outputFile, $ecdhKeypair)
    {
        if (!is_string($inputFile)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($outputFile)) {
            throw new TypeError('Argument 2 must be a string.');
        }
        if (!is_string($ecdhKeypair)) {
            throw new TypeError('Argument 3 must be a string');
        }
        if (self::strlen($ecdhKeypair) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_BOX_KEYPAIRBYTES bytes');
        }

        $publicKey = ParagonIE_Sodium_Compat::crypto_box_publickey($ecdhKeypair);

        /** @var int $size */
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $ifp */
        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var resource $ofp */
        $ofp = fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new Error('Could not open output file for writing');
        }

        $ephemeralPK = fread($ifp, ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES);
        if (self::strlen($ephemeralPK) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES) {
            throw new Error('Could not read public key from sealed file');
        }

        $nonce = ParagonIE_Sodium_Compat::crypto_generichash(
            $ephemeralPK . $publicKey,
            '',
            24
        );
        $msgKeypair = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey(
            ParagonIE_Sodium_Compat::crypto_box_secretkey($ecdhKeypair),
            $ephemeralPK
        );

        $res = self::box_decrypt($ifp, $ofp, $size, $nonce, $msgKeypair);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($nonce);
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
        } catch (Error $ex) {
            unset($ephKeypair);
        }
        return $res;
    }

    /**
     * Calculate the BLAKE2b hash of a file.
     *
     * @param string      $filePath     Absolute path to a file on the filesystem
     * @param string|null $key          BLAKE2b key
     * @param int         $outputLength Length of hash output
     *
     * @return string                   BLAKE2b hash
     * @throws Error
     * @throws TypeError
     */
    public static function generichash($filePath, $key = '', $outputLength = 32)
    {
        if (!is_string($filePath)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($key)) {
            if ($key === null) {
                $key = '';
            } else {
                throw new TypeError('Argument 2 must be a string');
            }
        }
        if (!empty($key)) {
            if (self::strlen($key) < ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_KEYBYTES_MIN) {
                throw new TypeError('Argument 2 must be at least CRYPTO_GENERICHASH_KEYBYTES_MIN bytes');
            }
            if (self::strlen($key) > ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_KEYBYTES_MAX) {
                throw new TypeError('Argument 2 must be at most CRYPTO_GENERICHASH_KEYBYTES_MAX bytes');
            }
        }
        if (!is_int($outputLength)) {
            if (!is_numeric($outputLength)) {
                throw new TypeError('Argument 3 must be an integer');
            }
            $outputLength = (int) $outputLength;
        }
        if ($outputLength < ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_BYTES_MIN) {
            throw new Error('Argument 3 must be at least CRYPTO_GENERICHASH_BYTES_MIN');
        }
        if ($outputLength > ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_BYTES_MAX) {
            throw new Error('Argument 3 must be at least CRYPTO_GENERICHASH_BYTES_MAX');
        }

        /** @var int $size */
        $size = filesize($filePath);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $fp */
        $fp = fopen($filePath, 'rb');
        if (!is_resource($fp)) {
            throw new Error('Could not open input file for reading');
        }
        $ctx = ParagonIE_Sodium_Compat::crypto_generichash_init($key, $outputLength);
        while ($size > 0) {
            $blockSize = $size > 64
                ? 64
                : $size;
            ParagonIE_Sodium_Compat::crypto_generichash_update($ctx, fread($fp, $blockSize));
            $size -= $blockSize;
        }

        fclose($fp);
        return ParagonIE_Sodium_Compat::crypto_generichash_final($ctx, $outputLength);
    }

    /**
     * Encrypt a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_secretbox(), but produces
     * the same result.
     *
     * @param string $inputFile  Absolute path to a file on the filesystem
     * @param string $outputFile Absolute path to a file on the filesystem
     * @param string $nonce      Number to be used only once
     * @param string $key        Encryption key
     *
     * @return bool
     * @throws Error
     * @throws TypeError
     */
    public static function secretbox($inputFile, $outputFile, $nonce, $key)
    {
        if (!is_string($inputFile)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($outputFile)) {
            throw new TypeError('Argument 2 must be a string.');
        }
        if (!is_string($nonce)) {
            throw new TypeError('Argument 3 must be a string');
        }
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_SECRETBOX_NONCEBYTES bytes');
        }
        if (!is_string($key)) {
            throw new TypeError('Argument 4 must be a string');
        }
        if (self::strlen($key) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_KEYBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_SECRETBOX_KEYBYTES bytes');
        }

        /** @var int $size */
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $ifp */
        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var resource $ofp */
        $ofp = fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new Error('Could not open output file for writing');
        }

        $res = self::secretbox_encrypt($ifp, $ofp, $size, $nonce, $key);
        fclose($ifp);
        fclose($ofp);
        return $res;
    }
    /**
     * Seal a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_secretbox_open(), but produces
     * the same result.
     *
     * Warning: Does not protect against TOCTOU attacks. You should
     * just load the file into memory and use crypto_secretbox_open() if
     * you are worried about those.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $nonce
     * @param string $key
     * @return bool
     * @throws Error
     * @throws TypeError
     */
    public static function secretbox_open($inputFile, $outputFile, $nonce, $key)
    {
        if (!is_string($inputFile)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($outputFile)) {
            throw new TypeError('Argument 2 must be a string.');
        }
        if (!is_string($nonce)) {
            throw new TypeError('Argument 3 must be a string');
        }
        if (!is_string($key)) {
            throw new TypeError('Argument 4 must be a string');
        }
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_SECRETBOX_NONCEBYTES bytes');
        }
        if (self::strlen($key) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_KEYBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_SECRETBOXBOX_KEYBYTES bytes');
        }

        /** @var int $size */
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $ifp */
        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var resource $ofp */
        $ofp = fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new Error('Could not open output file for writing');
        }

        $res = self::secretbox_decrypt($ifp, $ofp, $size, $nonce, $key);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($key);
        } catch (Error $ex) {
            unset($key);
        }
        return $res;
    }

    /**
     * Sign a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_sign_detached(), but produces
     * the same result.
     *
     * @param string $filePath  Absolute path to a file on the filesystem
     * @param string $secretKey Secret signing key
     *
     * @return string           Ed25519 signature
     * @throws Error
     * @throws TypeError
     */
    public static function sign($filePath, $secretKey)
    {
        if (!is_string($filePath)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($secretKey)) {
            throw new TypeError('Argument 2 must be a string');
        }
        if (self::strlen($secretKey) !== ParagonIE_Sodium_Compat::CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new TypeError('Argument 2 must be CRYPTO_SIGN_SECRETKEYBYTES bytes');
        }

        /** @var int $size */
        $size = filesize($filePath);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $fp */
        $fp = fopen($filePath, 'rb');
        if (!is_resource($fp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var string $az */
        $az = hash('sha512', self::substr($secretKey, 0, 32), true);

        $az[0] = self::intToChr(self::chrToInt($az[0]) & 248);
        $az[31] = self::intToChr((self::chrToInt($az[31]) & 63) | 64);

        /** @var resource $hs */
        $hs = hash_init('sha512');
        hash_update($hs, self::substr($az, 32, 32));
        $hs = self::updateHashWithFile($hs, $fp, $size);

        /** @var string $nonceHash */
        $nonceHash = hash_final($hs, true);

        /** @var string $pk */
        $pk = self::substr($secretKey, 32, 32);

        /** @var string $nonce */
        $nonce = ParagonIE_Sodium_Core_Ed25519::sc_reduce($nonceHash) . self::substr($nonceHash, 32);

        /** @var string $sig */
        $sig = ParagonIE_Sodium_Core_Ed25519::ge_p3_tobytes(
            ParagonIE_Sodium_Core_Ed25519::ge_scalarmult_base($nonce)
        );

        /** @var resource $hs */
        $hs = hash_init('sha512');
        hash_update($hs, self::substr($sig, 0, 32));
        hash_update($hs, self::substr($pk, 0, 32));
        $hs = self::updateHashWithFile($hs, $fp, $size);

        /** @var string $hramHash */
        $hramHash = hash_final($hs, true);

        /** @var string $hram */
        $hram = ParagonIE_Sodium_Core_Ed25519::sc_reduce($hramHash);

        /** @var string $sigAfter */
        $sigAfter = ParagonIE_Sodium_Core_Ed25519::sc_muladd($hram, $az, $nonce);

        /** @var string $sig */
        $sig = self::substr($sig, 0, 32) . self::substr($sigAfter, 0, 32);

        try {
            ParagonIE_Sodium_Compat::memzero($az);
        } catch (Error $ex) {
            $az = null;
        }
        fclose($fp);
        return $sig;
    }

    /**
     * Verify a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_sign_verify_detached(), but
     * produces the same result.
     *
     * @param string $sig       Ed25519 signature
     * @param string $filePath  Absolute path to a file on the filesystem
     * @param string $publicKey Signing public key
     *
     * @return bool
     * @throws Error
     * @throws Exception
     */
    public static function verify($sig, $filePath, $publicKey)
    {
        if (!is_string($sig)) {
            throw new TypeError('Argument 1 must be a string.');
        }
        if (!is_string($filePath)) {
            throw new TypeError('Argument 2 must be a string.');
        }
        if (!is_string($publicKey)) {
            throw new TypeError('Argument 3 must be a string');
        }
        if (self::strlen($sig) !== ParagonIE_Sodium_Compat::CRYPTO_SIGN_BYTES) {
            throw new TypeError('Argument 1 must be CRYPTO_SIGN_BYTES bytes');
        }
        if (self::strlen($publicKey) !== ParagonIE_Sodium_Compat::CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_SIGN_PUBLICKEYBYTES bytes');
        }
        if (self::strlen($sig) < 64) {
            throw new Exception('Signature is too short');
        }

        if (ParagonIE_Sodium_Core_Ed25519::check_S_lt_L(self::substr($sig, 32, 32))) {
            throw new Exception('S < L - Invalid signature');
        }
        if (ParagonIE_Sodium_Core_Ed25519::small_order($sig)) {
            throw new Exception('Signature is on too small of an order');
        }
        if ((self::chrToInt($sig[63]) & 224) !== 0) {
            throw new Exception('Invalid signature');
        }
        $d = 0;
        for ($i = 0; $i < 32; ++$i) {
            $d |= self::chrToInt($publicKey[$i]);
        }
        if ($d === 0) {
            throw new Exception('All zero public key');
        }


        /** @var int $size */
        $size = filesize($filePath);
        if (!is_int($size)) {
            throw new Error('Could not obtain the file size');
        }

        /** @var resource $fp */
        $fp = fopen($filePath, 'rb');
        if (!is_resource($fp)) {
            throw new Error('Could not open input file for reading');
        }

        /** @var bool The original value of ParagonIE_Sodium_Compat::$fastMult */
        $orig = ParagonIE_Sodium_Compat::$fastMult;

        // Set ParagonIE_Sodium_Compat::$fastMult to true to speed up verification.
        ParagonIE_Sodium_Compat::$fastMult = true;

        /** @var ParagonIE_Sodium_Core_Curve25519_Ge_P3 $A */
        $A = ParagonIE_Sodium_Core_Ed25519::ge_frombytes_negate_vartime($publicKey);

        /** @var resource $hs */
        $hs = hash_init('sha512');
        hash_update($hs, self::substr($sig, 0, 32));
        hash_update($hs, self::substr($publicKey, 0, 32));
        $hs = self::updateHashWithFile($hs, $fp, $size);
        /** @var string $hDigest */
        $hDigest = hash_final($hs, true);

        /** @var string $h */
        $h = ParagonIE_Sodium_Core_Ed25519::sc_reduce($hDigest) . self::substr($hDigest, 32);

        /** @var ParagonIE_Sodium_Core_Curve25519_Ge_P2 $R */
        $R = ParagonIE_Sodium_Core_Ed25519::ge_double_scalarmult_vartime(
            $h,
            $A,
            self::substr($sig, 32)
        );

        /** @var string $rcheck */
        $rcheck = ParagonIE_Sodium_Core_Ed25519::ge_tobytes($R);

        // Close the file handle
        fclose($fp);

        // Reset ParagonIE_Sodium_Compat::$fastMult to what it was before.
        ParagonIE_Sodium_Compat::$fastMult = $orig;
        return self::verify_32($rcheck, self::substr($sig, 0, 32));
    }

    /**
     * @param resource $ifp
     * @param resource $ofp
     * @param int      $mlen
     * @param string   $nonce
     * @param string   $boxKeypair
     * @return bool
     */
    protected static function box_encrypt($ifp, $ofp, $mlen, $nonce, $boxKeypair)
    {
        return self::secretbox_encrypt(
            $ifp,
            $ofp,
            $mlen,
            $nonce,
            ParagonIE_Sodium_Crypto::box_beforenm(
                ParagonIE_Sodium_Crypto::box_secretkey($boxKeypair),
                ParagonIE_Sodium_Crypto::box_publickey($boxKeypair)
            )
        );
    }


    /**
     * @param resource $ifp
     * @param resource $ofp
     * @param int      $mlen
     * @param string   $nonce
     * @param string   $boxKeypair
     * @return bool
     */
    protected static function box_decrypt($ifp, $ofp, $mlen, $nonce, $boxKeypair)
    {
        return self::secretbox_decrypt(
            $ifp,
            $ofp,
            $mlen,
            $nonce,
            ParagonIE_Sodium_Crypto::box_beforenm(
                ParagonIE_Sodium_Crypto::box_secretkey($boxKeypair),
                ParagonIE_Sodium_Crypto::box_publickey($boxKeypair)
            )
        );
    }

    /**
     * Encrypt a file
     *
     * @param resource $ifp
     * @param resource $ofp
     * @param int $mlen
     * @param string $nonce
     * @param string $key
     * @return bool
     */
    protected static function secretbox_encrypt($ifp, $ofp, $mlen, $nonce, $key)
    {
        $plaintext = fread($ifp, 32);
        $first32 = ftell($ifp);

        /** @var string $subkey */
        $subkey = ParagonIE_Sodium_Core_HSalsa20::hsalsa20($nonce, $key);

        /** @var string $realNonce */
        $realNonce = ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8);

        /** @var string $block0 */
        $block0 = str_repeat("\x00", 32);

        /** @var int $mlen - Length of the plaintext message */
        $mlen0 = $mlen;
        if ($mlen0 > 64 - ParagonIE_Sodium_Crypto::secretbox_xsalsa20poly1305_ZEROBYTES) {
            $mlen0 = 64 - ParagonIE_Sodium_Crypto::secretbox_xsalsa20poly1305_ZEROBYTES;
        }
        $block0 .= ParagonIE_Sodium_Core_Util::substr($plaintext, 0, $mlen0);

        /** @var string $block0 */
        $block0 = ParagonIE_Sodium_Core_Salsa20::salsa20_xor(
            $block0,
            $realNonce,
            $subkey
        );

        $state = new ParagonIE_Sodium_Core_Poly1305_State(
            ParagonIE_Sodium_Core_Util::substr(
                $block0,
                0,
                ParagonIE_Sodium_Crypto::onetimeauth_poly1305_KEYBYTES
            )
        );

        // Pre-write 16 blank bytes for the Poly1305 tag
        $start = ftell($ofp);
        fwrite($ofp, str_repeat("\x00", 16));

        /** @var string $c */
        $cBlock = ParagonIE_Sodium_Core_Util::substr(
            $block0,
            ParagonIE_Sodium_Crypto::secretbox_xsalsa20poly1305_ZEROBYTES
        );
        $state->update($cBlock);
        fwrite($ofp, $cBlock);
        $mlen -= 32;

        /** @var int $iter */
        $iter = 1;

        /** @var int $incr */
        $incr = self::BUFFER_SIZE >> 6;

        fseek($ifp, $first32, SEEK_SET);
        while ($mlen > 0) {
            $blockSize = $mlen > self::BUFFER_SIZE
                ? self::BUFFER_SIZE
                : $mlen;
            $plaintext = fread($ifp, $blockSize);
            $cBlock = ParagonIE_Sodium_Core_Salsa20::salsa20_xor_ic(
                $plaintext,
                $realNonce,
                $iter,
                $subkey
            );
            fwrite($ofp, $cBlock, $blockSize);
            $state->update($cBlock);

            $mlen -= $blockSize;
            $iter += $incr;
        }
        try {
            ParagonIE_Sodium_Compat::memzero($block0);
            ParagonIE_Sodium_Compat::memzero($subkey);
        } catch (Error $ex) {
            $block0 = null;
            $subkey = null;
        }
        $end = ftell($ofp);
        fseek($ofp, $start, SEEK_SET);
        fwrite($ofp, $state->finish(), ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_MACBYTES);
        fseek($ofp, $end, SEEK_SET);
        unset($state);

        return true;
    }

    /**
     * Encrypt a file
     *
     * @param resource $ifp
     * @param resource $ofp
     * @param int $mlen
     * @param string $nonce
     * @param string $key
     * @return bool
     * @throws Exception
     */
    protected static function secretbox_decrypt($ifp, $ofp, $mlen, $nonce, $key)
    {
        $tag = fread($ifp, 16);

        /** @var string $subkey */
        $subkey = ParagonIE_Sodium_Core_HSalsa20::hsalsa20($nonce, $key);

        /** @var string $realNonce */
        $realNonce = ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8);

        /** @var string $block0 */
        $block0 = ParagonIE_Sodium_Core_Salsa20::salsa20(
            64,
            ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8),
            $subkey
        );
        $state = new ParagonIE_Sodium_Core_Poly1305_State(self::substr($block0, 0, 32));
        if (!self::onetimeauth_verify($state, $ifp, $tag, $mlen)) {
            throw new Exception('Invalid MAC');
        }
        $first32 = fread($ifp, 32);
        fwrite($ofp, self::xorStrings(self::substr($block0, 32), $first32));
        $mlen -= 32;

        /** @var int $iter */
        $iter = 1;

        /** @var int $incr */
        $incr = self::BUFFER_SIZE >> 6;

        while ($mlen > 0) {
            $blockSize = $mlen > self::BUFFER_SIZE
                ? self::BUFFER_SIZE
                : $mlen;
            $ciphertext = fread($ifp, $blockSize);
            $pBlock = ParagonIE_Sodium_Core_Salsa20::salsa20_xor_ic(
                $ciphertext,
                $realNonce,
                $iter,
                $subkey
            );
            fwrite($ofp, $pBlock, $blockSize);
            $mlen -= $blockSize;
            $iter += $incr;
        }
        return true;
    }

    /**
     * @param ParagonIE_Sodium_Core_Poly1305_State $state
     * @param resource $ifp
     * @param string $tag
     * @param int $mlen
     * @return bool
     */
    protected static function onetimeauth_verify(ParagonIE_Sodium_Core_Poly1305_State $state, $ifp, $tag = '', $mlen = 0)
    {
        /** @var int $pos */
        $pos = ftell($ifp);

        /** @var int $iter */
        $iter = 1;

        /** @var int $incr */
        $incr = self::BUFFER_SIZE >> 6;

        while ($mlen > 0) {
            $blockSize = $mlen > self::BUFFER_SIZE
                ? self::BUFFER_SIZE
                : $mlen;
            $ciphertext = fread($ifp, $blockSize);
            $state->update($ciphertext);
            $mlen -= $blockSize;
            $iter += $incr;
        }
        $res = ParagonIE_Sodium_Core_Util::verify_16($tag, $state->finish());

        fseek($ifp, $pos, SEEK_SET);
        return $res;
    }

    /**
     * Update a hash context with the contents of a file, without
     * loading the entire file into memory.
     *
     * @param resource $hash
     * @param resource $fp
     * @param int $size
     * @return resource
     * @throws Error
     * @throws TypeError
     */
    public static function updateHashWithFile($hash, $fp, $size = 0)
    {
        if (!is_resource($hash)) {
            throw new TypeError('Argument 1 must be a resource');
        }
        if (!is_resource($fp)) {
            throw new TypeError('Argument 2 must be a resource');
        }
        if (!is_int($size)) {
            throw new TypeError('Argument 3 must be an integer');
        }
        /** @var int $originalPosition */
        $originalPosition = ftell($fp);

        // Move file pointer to beginning of file
        fseek($fp, 0, SEEK_SET);
        for ($i = 0; $i < $size; $i += self::BUFFER_SIZE) {
            /** @var string $message */
            $message = fread(
                $fp,
                ($size - $i) > self::BUFFER_SIZE
                    ? $size - $i
                    : self::BUFFER_SIZE
            );
            if (!is_string($message)) {
                throw new Error('Unexpected error reading from file.');
            }
            hash_update($hash, $message);
        }
        // Reset file pointer's position
        fseek($fp, $originalPosition, SEEK_SET);
        return $hash;
    }
}
