<?php

namespace App\Core;

/**
 * Carga segura de imágenes subidas por el panel.
 *
 * Valida tipo MIME real (finfo), tamaño y extensión; renombra a un nombre
 * aleatorio para evitar colisiones y path traversal, y guarda bajo
 * public/uploads/{subdir}. Devuelve rutas relativas (uploads/...) que
 * asset() puede resolver.
 */
class Upload
{
    private const MAX_BYTES = 3145728; // 3 MB
    private const MAX_LADO  = 1600;    // px máx. del lado más largo tras optimizar
    private const MAX_MPX   = 40;      // no procesar imágenes de más de 40 megapíxeles
    private const MAX_DOC_BYTES = 5242880;                 // 5 MB para documentos (CV)
    private const DOC_MIME = ['application/pdf' => 'pdf']; // solo PDF
    private const MIME_EXT  = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Procesa una entrada $_FILES de tipo múltiple (name="campo[]").
     *
     * @return array{rutas: string[], errores: string[]}
     */
    public static function imagenes(?array $files, string $subdir, int $maximo): array
    {
        $rutas = [];
        $errores = [];

        $lista = self::normalizar($files);
        if (!$lista) {
            return ['rutas' => [], 'errores' => []];
        }

        foreach ($lista as $archivo) {
            if (count($rutas) >= $maximo) {
                $errores[] = 'Se alcanzó el máximo de imágenes permitidas.';
                break;
            }
            $res = self::guardar($archivo, $subdir);
            if ($res['ruta'] !== null) {
                $rutas[] = $res['ruta'];
            } elseif ($res['error'] !== null) {
                $errores[] = $res['error'];
            }
        }

        return ['rutas' => $rutas, 'errores' => $errores];
    }

    /** Guarda un archivo individual. @return array{ruta: ?string, error: ?string} */
    private static function guardar(array $archivo, string $subdir): array
    {
        $err = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return ['ruta' => null, 'error' => null]; // campo vacío: se ignora
        }
        if ($err !== UPLOAD_ERR_OK) {
            return ['ruta' => null, 'error' => 'No se pudo subir una de las imágenes.'];
        }

        $tmp    = (string) ($archivo['tmp_name'] ?? '');
        $nombre = (string) ($archivo['name'] ?? 'imagen');

        if (!is_uploaded_file($tmp)) {
            return ['ruta' => null, 'error' => 'Archivo de imagen inválido.'];
        }
        if ((int) ($archivo['size'] ?? 0) > self::MAX_BYTES) {
            return ['ruta' => null, 'error' => 'La imagen "' . $nombre . '" supera el máximo de 3 MB.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);
        if (!isset(self::MIME_EXT[$mime])) {
            return ['ruta' => null, 'error' => 'Solo se permiten imágenes JPG, PNG o WebP.'];
        }

        $dir = ROOT_PATH . '/public/uploads/' . trim($subdir, '/');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ruta' => null, 'error' => 'No se pudo preparar la carpeta de imágenes.'];
        }

        $archivoNombre = bin2hex(random_bytes(8)) . '.' . self::MIME_EXT[$mime];
        $destino = $dir . '/' . $archivoNombre;

        if (!move_uploaded_file($tmp, $destino)) {
            return ['ruta' => null, 'error' => 'No se pudo guardar la imagen.'];
        }

        // Redimensiona/optimiza para que las fotos grandes se vean y carguen bien.
        self::optimizar($destino, $mime);

        return ['ruta' => 'uploads/' . trim($subdir, '/') . '/' . $archivoNombre, 'error' => null];
    }

    /**
     * Convierte la entrada $_FILES (múltiple o simple) en una lista uniforme
     * de arreglos individuales {name, type, tmp_name, error, size}.
     */
    private static function normalizar(?array $files): array
    {
        if (!$files || !isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return [$files]; // campo simple
        }
        $out = [];
        foreach (array_keys($files['name']) as $i) {
            $out[] = [
                'name'     => $files['name'][$i]     ?? '',
                'type'     => $files['type'][$i]     ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$i]     ?? 0,
            ];
        }
        return $out;
    }

    /**
     * Redimensiona la imagen si excede MAX_LADO, corrige la orientación EXIF
     * (fotos de celular) y la recomprime. Silencioso: si algo falla, deja el
     * archivo original tal cual.
     */
    private static function optimizar(string $ruta, string $mime): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }
        $info = @getimagesize($ruta);
        if (!$info) {
            return;
        }
        [$w, $h] = $info;
        if ($w < 1 || $h < 1 || ($w * $h) > self::MAX_MPX * 1000000) {
            return; // demasiado grande: no arriesgar memoria
        }

        $memPrev = ini_get('memory_limit');
        @ini_set('memory_limit', '384M');

        $src = self::cargar($ruta, $mime);
        if (!$src) {
            @ini_set('memory_limit', (string) $memPrev);
            return;
        }

        $cambio = false;

        // Autorrotación por EXIF (solo JPEG).
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($ruta);
            $orient = (int) ($exif['Orientation'] ?? 0);
            $grados = [3 => 180, 6 => -90, 8 => 90][$orient] ?? 0;
            if ($grados !== 0) {
                $rot = @imagerotate($src, $grados, 0);
                if ($rot) {
                    imagedestroy($src);
                    $src = $rot;
                    $cambio = true;
                }
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // Redimensionar si excede el lado máximo.
        if ($w > self::MAX_LADO || $h > self::MAX_LADO) {
            $ratio = self::MAX_LADO / max($w, $h);
            $nw = max(1, (int) round($w * $ratio));
            $nh = max(1, (int) round($h * $ratio));
            $dst = imagecreatetruecolor($nw, $nh);
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transp = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transp);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
            $cambio = true;
        }

        if ($cambio) {
            switch ($mime) {
                case 'image/jpeg': @imagejpeg($src, $ruta, 82); break;
                case 'image/png':  @imagepng($src, $ruta, 6);  break;
                case 'image/webp': @imagewebp($src, $ruta, 82); break;
            }
        }
        imagedestroy($src);
        @ini_set('memory_limit', (string) $memPrev);
    }

    private static function cargar(string $ruta, string $mime)
    {
        switch ($mime) {
            case 'image/jpeg': return @imagecreatefromjpeg($ruta);
            case 'image/png':  return @imagecreatefrompng($ruta);
            case 'image/webp': return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : null;
        }
        return null;
    }

    /**
     * Sube un logo: valida, recorta el fondo blanco/transparente para dejarlo
     * ajustado y centrado, y lo guarda como PNG. @return array{ruta:?string, error:?string}
     */
    public static function logo(?array $file, string $subdir): array
    {
        $lista = self::normalizar($file);
        if (!$lista) {
            return ['ruta' => null, 'error' => null];
        }
        $a   = $lista[0];
        $err = (int) ($a['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return ['ruta' => null, 'error' => null];
        }
        if ($err !== UPLOAD_ERR_OK) {
            return ['ruta' => null, 'error' => 'No se pudo subir la imagen.'];
        }
        $tmp = (string) ($a['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            return ['ruta' => null, 'error' => 'Archivo de imagen inválido.'];
        }
        if ((int) ($a['size'] ?? 0) > self::MAX_BYTES) {
            return ['ruta' => null, 'error' => 'La imagen supera el máximo de 3 MB.'];
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset(self::MIME_EXT[$mime])) {
            return ['ruta' => null, 'error' => 'Solo se permiten imágenes JPG, PNG o WebP.'];
        }
        if (!function_exists('imagecreatetruecolor')) {
            return ['ruta' => null, 'error' => 'El procesamiento de imágenes no está disponible.'];
        }
        $info = @getimagesize($tmp);
        if (!$info || ($info[0] * $info[1]) > self::MAX_MPX * 1000000) {
            return ['ruta' => null, 'error' => 'La imagen no es válida o es demasiado grande.'];
        }

        @ini_set('memory_limit', '384M');
        $src = self::cargar($tmp, $mime);
        if (!$src) {
            return ['ruta' => null, 'error' => 'No se pudo procesar la imagen.'];
        }
        $out = self::recortarLogo($src);
        imagedestroy($src);

        $dir = ROOT_PATH . '/public/uploads/' . trim($subdir, '/');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            imagedestroy($out);
            return ['ruta' => null, 'error' => 'No se pudo preparar la carpeta de imágenes.'];
        }
        $nombre = bin2hex(random_bytes(8)) . '.png';
        @imagepng($out, $dir . '/' . $nombre, 8);
        imagedestroy($out);

        return ['ruta' => 'uploads/' . trim($subdir, '/') . '/' . $nombre, 'error' => null];
    }

    /** Recorta el fondo (blanco/transparente) de un logo y lo escala. Devuelve un recurso GD. */
    private static function recortarLogo($src)
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $work = 500;
        $maxOut = 360;
        $tol = 244;

        $ratio = min(1, $work / max($w, $h));
        $ww = max(1, (int) round($w * $ratio));
        $wh = max(1, (int) round($h * $ratio));
        $tmp = imagecreatetruecolor($ww, $wh);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        imagefilledrectangle($tmp, 0, 0, $ww, $wh, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $ww, $wh, $w, $h);

        $minX = $ww; $minY = $wh; $maxX = -1; $maxY = -1;
        for ($y = 0; $y < $wh; $y++) {
            for ($x = 0; $x < $ww; $x++) {
                $rgba = imagecolorat($tmp, $x, $y);
                $al = ($rgba >> 24) & 0x7F;
                $fondo = $al > 100
                    || ((($rgba >> 16) & 0xFF) >= $tol && (($rgba >> 8) & 0xFF) >= $tol && ($rgba & 0xFF) >= $tol);
                if (!$fondo) {
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }
        if ($maxX < 0) {
            return $tmp; // imagen vacía: se deja tal cual
        }

        $pad = (int) round(max($ww, $wh) * 0.04);
        $minX = max(0, $minX - $pad); $minY = max(0, $minY - $pad);
        $maxX = min($ww - 1, $maxX + $pad); $maxY = min($wh - 1, $maxY + $pad);
        $cw = $maxX - $minX + 1; $ch = $maxY - $minY + 1;

        $r2 = min(1, $maxOut / max($cw, $ch));
        $ow = max(1, (int) round($cw * $r2));
        $oh = max(1, (int) round($ch * $r2));
        $out = imagecreatetruecolor($ow, $oh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefilledrectangle($out, 0, 0, $ow, $oh, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $tmp, 0, 0, $minX, $minY, $ow, $oh, $cw, $ch);
        imagedestroy($tmp);

        return $out;
    }

    /** XML de factura (CFDI). No lo usa documento() (solo PDF, para CVs). */
    private const XML_MIME = ['text/xml' => 'xml', 'application/xml' => 'xml'];

    /**
     * Guarda un CV en PDF bajo storage/{subdir} (FUERA de la web, protegido por
     * .htaccess). Devuelve solo el nombre del archivo (no una ruta web).
     * @return array{nombre:?string, error:?string}
     */
    public static function documento(?array $file, string $subdir): array
    {
        return self::guardarDocumento($file, $subdir, self::DOC_MIME, self::MAX_DOC_BYTES, 'El CV debe ser un archivo PDF.', 'El CV supera el máximo de 5 MB.');
    }

    /** PDF de una factura (mismo límite/carpeta que documento(), nombre de error propio). */
    public static function facturaPdf(?array $file, string $subdir): array
    {
        return self::guardarDocumento($file, $subdir, self::DOC_MIME, self::MAX_DOC_BYTES, 'El PDF de la factura debe ser un archivo PDF.', 'El PDF de la factura supera el máximo de 5 MB.');
    }

    /** XML de una factura (CFDI). */
    public static function facturaXml(?array $file, string $subdir): array
    {
        return self::guardarDocumento($file, $subdir, self::XML_MIME, self::MAX_DOC_BYTES, 'El XML de la factura debe ser un archivo XML.', 'El XML de la factura supera el máximo de 5 MB.');
    }

    /**
     * Logo adjunto a una cotización (formulario público). A diferencia de logo(),
     * se guarda sin procesar bajo storage/{subdir} (privado, no accesible por URL) —
     * es solo una referencia para ventas, no se muestra en el sitio.
     */
    public static function logoCotizacion(?array $file, string $subdir): array
    {
        return self::guardarDocumento($file, $subdir, self::MIME_EXT, self::MAX_BYTES, 'El logo debe ser una imagen JPG, PNG o WebP.', 'El logo supera el máximo de 3 MB.');
    }

    /**
     * Guarda un documento genérico bajo storage/{subdir} (FUERA de la web,
     * protegido por .htaccess) validando su tipo MIME real contra $mimeExt.
     * Devuelve solo el nombre del archivo (no una ruta web).
     * @param array<string,string> $mimeExt mime => extensión
     * @return array{nombre:?string, error:?string}
     */
    private static function guardarDocumento(?array $file, string $subdir, array $mimeExt, int $maxBytes, string $errorTipo, string $errorTamano): array
    {
        $lista = self::normalizar($file);
        if (!$lista) {
            return ['nombre' => null, 'error' => null];
        }
        $a   = $lista[0];
        $err = (int) ($a['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return ['nombre' => null, 'error' => null];
        }
        if ($err !== UPLOAD_ERR_OK) {
            return ['nombre' => null, 'error' => 'No se pudo subir el archivo.'];
        }
        $tmp = (string) ($a['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            return ['nombre' => null, 'error' => 'Archivo inválido.'];
        }
        if ((int) ($a['size'] ?? 0) > $maxBytes) {
            return ['nombre' => null, 'error' => $errorTamano];
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset($mimeExt[$mime])) {
            return ['nombre' => null, 'error' => $errorTipo];
        }
        $dir = ROOT_PATH . '/storage/' . trim($subdir, '/');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['nombre' => null, 'error' => 'No se pudo preparar el almacenamiento.'];
        }
        $nombre = bin2hex(random_bytes(10)) . '.' . $mimeExt[$mime];
        if (!move_uploaded_file($tmp, $dir . '/' . $nombre)) {
            return ['nombre' => null, 'error' => 'No se pudo guardar el archivo.'];
        }
        return ['nombre' => $nombre, 'error' => null];
    }

    /** Ruta absoluta de un documento guardado (o null si no existe). */
    public static function documentoRuta(string $subdir, ?string $nombre): ?string
    {
        if (!$nombre) {
            return null;
        }
        $abs = ROOT_PATH . '/storage/' . trim($subdir, '/') . '/' . basename($nombre);
        return is_file($abs) ? $abs : null;
    }

    /** Borra un documento de storage/{subdir}. Silencioso. */
    public static function borrarDocumento(string $subdir, ?string $nombre): void
    {
        if (!$nombre) {
            return;
        }
        $abs = ROOT_PATH . '/storage/' . trim($subdir, '/') . '/' . basename($nombre);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    /** Borra un archivo subido (ruta relativa uploads/...). Silencioso. */
    public static function borrar(?string $rutaRelativa): void
    {
        if (!$rutaRelativa) {
            return;
        }
        $rutaRelativa = ltrim(str_replace('\\', '/', $rutaRelativa), '/');
        if (strpos($rutaRelativa, 'uploads/') !== 0 || strpos($rutaRelativa, '..') !== false) {
            return; // fuera del área de subidas: no tocar
        }
        $abs = ROOT_PATH . '/public/' . $rutaRelativa;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
