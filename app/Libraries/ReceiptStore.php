<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * #2948 — receipt photo storage for expenses.
 *
 * Rules this class exists to enforce:
 *  - Files live under WRITEPATH (writable/receipts/YYYY/MM/), NEVER in public/.
 *    deploy.sh excludes writable/ from rsync, so receipts survive a deploy and the
 *    month directories are created at runtime (writable is 2770 root:apache with
 *    the setgid bit, so a new subdir inherits group apache).
 *  - The type is decided by MAGIC BYTES, never by the browser's Content-Type and
 *    never by the filename extension.
 *  - The stored name is generated here. The client's filename is kept for display
 *    only and never touches a path.
 */
class ReceiptStore
{
    /** 12 MB — comfortably above an iPhone photo, below anything that will OOM GD. */
    public const MAX_BYTES = 12 * 1024 * 1024;

    /**
     * HEIC is converted to JPEG on the way in (D1635, Jason 2026-09-21: "install what
     * you need it will be iphone photos").
     *
     * It has to be this wrapper and not heif-convert/ImageMagick: AlmaLinux 10's
     * libheif ships with NO HEVC decoder — `heif-convert --list-decoders` prints an
     * EMPTY "HEIC decoders:" section — so installing libheif-tools does not make an
     * iPhone photo readable. /usr/local/bin/heic2jpeg wraps a pillow-heif venv, which
     * bundles its own codecs. It lives in bin_t so php-fpm may exec it.
     */
    public const HEIC_CONVERTER = '/usr/local/bin/heic2jpeg';

    /** magic-byte signature => [mime, extension, GD can render it?] */
    private const TYPES = [
        'jpeg' => ['image/jpeg', 'jpg',  true],
        'png'  => ['image/png',  'png',  true],
        'webp' => ['image/webp', 'webp', true],
        'heic' => ['image/heic', 'heic', false],
    ];

    public function baseDir(): string
    {
        return WRITEPATH . 'receipts/';
    }

    /**
     * Sniff the real type from the first bytes of the file.
     *
     * Signatures are checked by hand rather than through finfo so the accepted set
     * is exactly these four and nothing else. An iPhone HEIC is an ISO-BMFF box:
     * 4 bytes of length, 'ftyp', then a brand of heic/heix/mif1/msf1.
     */
    public function sniff(string $path): ?array
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) { return null; }
        $head = (string) fread($fh, 32);
        fclose($fh);

        if (strlen($head) < 12) { return null; }

        if (str_starts_with($head, "\xFF\xD8\xFF"))                     { return self::TYPES['jpeg']; }
        if (str_starts_with($head, "\x89PNG\x0D\x0A\x1A\x0A"))          { return self::TYPES['png']; }
        if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP') { return self::TYPES['webp']; }
        if (substr($head, 4, 4) === 'ftyp'
            && in_array(substr($head, 8, 4), ['heic', 'heix', 'heim', 'heis', 'hevc', 'mif1', 'msf1'], true)) {
            return self::TYPES['heic'];
        }

        return null;
    }

    /**
     * Store one uploaded file against an expense. Returns the expense_receipts row
     * data ready to insert, or a string error message.
     *
     * @return array|string
     */
    public function store(UploadedFile $file, int $expenseId)
    {
        if (! $file->isValid()) {
            // UPLOAD_ERR_INI_SIZE is the one that matters here: PHP discards the file
            // before the app sees it, so without public/.user.ini raising the limit
            // every phone photo would fail with nothing in the log.
            return $file->getErrorString() . ' (' . $file->getError() . ')';
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return 'That photo is larger than ' . round(self::MAX_BYTES / 1048576) . ' MB.';
        }

        $tmp  = $file->getTempName();
        $type = $this->sniff($tmp);
        if ($type === null) {
            return 'That file is not a photo (JPEG, PNG, WebP or HEIC).';
        }
        [$mime, $ext, $previewable] = $type;

        $sub = date('Y/m') . '/';
        $dir = $this->baseDir() . $sub;
        if (! is_dir($dir) && ! @mkdir($dir, 0770, true)) {
            return 'Could not create the receipt folder on the server.';
        }

        $base    = $sub . $expenseId . '-' . bin2hex(random_bytes(8));
        $stored  = $base . '.' . $ext;
        $target  = $this->baseDir() . $stored;
        $convErr   = null;
        $converted = false;

        if ($ext === 'heic') {
            // Convert to JPEG so the list can show a thumbnail. If the converter is
            // missing or fails, fall through and keep the HEIC as-is with
            // previewable=0 — losing the preview is acceptable, losing the receipt
            // she just photographed is not.
            $jpegStored = $base . '.jpg';
            $jpegTarget = $this->baseDir() . $jpegStored;

            if (is_executable(self::HEIC_CONVERTER)) {
                $cmd = escapeshellcmd(self::HEIC_CONVERTER)
                     . ' ' . escapeshellarg($tmp) . ' ' . escapeshellarg($jpegTarget) . ' 3000 2>&1';
                exec($cmd, $out, $rc);
                if ($rc === 0 && is_file($jpegTarget) && filesize($jpegTarget) > 0) {
                    $stored      = $jpegStored;
                    $target      = $jpegTarget;
                    $mime        = 'image/jpeg';
                    $previewable = true;
                    $converted   = true;
                } else {
                    $convErr = trim(implode(' ', (array) $out));
                    if (is_file($jpegTarget)) { @unlink($jpegTarget); }
                }
            } else {
                $convErr = self::HEIC_CONVERTER . ' is not executable';
            }

            if ($convErr !== null) {
                log_message('error', 'ReceiptStore: HEIC conversion failed, storing original — ' . $convErr);
            }
        }

        // Nothing to copy when the converter already wrote the JPEG to $target.
        if (! $converted && ! @copy($tmp, $target)) {
            return 'Could not save the photo on the server.';
        }
        @chmod($target, 0660);

        return [
            'expense_id'    => $expenseId,
            'stored_name'   => $stored,
            'original_name' => mb_substr((string) $file->getClientName(), 0, 255),
            'mime'          => $mime,
            // the size of what we actually hold — a converted HEIC is smaller than
            // the upload, so getSize() would misreport the stored file
            'bytes'         => (int) (filesize($target) ?: $file->getSize()),
            'sha256'        => hash_file('sha256', $target) ?: null,
            'previewable'   => $previewable ? 1 : 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
    }

    /** Absolute path of a stored receipt, or null if the row points at nothing. */
    public function pathFor(array $row): ?string
    {
        // stored_name is generated by store(); reject anything that is not, in case a
        // row is ever hand-edited.
        $name = (string) ($row['stored_name'] ?? '');
        if ($name === '' || ! preg_match('#^\d{4}/\d{2}/\d+-[0-9a-f]{16}\.(jpg|png|webp|heic)$#', $name)) {
            return null;
        }
        $path = $this->baseDir() . $name;
        return is_file($path) ? $path : null;
    }

    public function delete(array $row): void
    {
        $path = $this->pathFor($row);
        if ($path) { @unlink($path); }
    }

    /**
     * Downscaled JPEG for the list, generated on demand and cached beside the
     * original. Returns the thumbnail path, or null when GD cannot read the format
     * (HEIC) — callers show a generic file badge in that case.
     */
    public function thumb(array $row, int $max = 160): ?string
    {
        $path = $this->pathFor($row);
        if ($path === null || empty($row['previewable'])) { return null; }

        $thumb = $path . '.t' . $max . '.jpg';
        if (is_file($thumb) && filemtime($thumb) >= filemtime($path)) { return $thumb; }

        $img = @imagecreatefromstring((string) file_get_contents($path));
        if ($img === false) { return null; }

        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1.0, $max / max($w, $h));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $out = imagecreatetruecolor($tw, $th);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
        $ok = imagejpeg($out, $thumb, 82);
        imagedestroy($img);
        imagedestroy($out);

        if ($ok) { @chmod($thumb, 0660); }
        return $ok ? $thumb : null;
    }

    /** The rule Jason set: a receipt is required on every expense except fuel. */
    public static function requiredFor(?string $category): bool
    {
        return $category !== 'fuel';
    }
}
