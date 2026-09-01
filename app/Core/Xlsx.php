<?php

namespace App\Core;

/**
 * Generador de archivos .xlsx (Excel) en PHP puro, sin dependencias ni
 * ZipArchive: empaqueta los XML del formato Open XML en un ZIP con método
 * "store" (sin compresión), válido para Excel y para el importador de Aspel SAE.
 */
class Xlsx
{
    /**
     * Devuelve el binario del .xlsx.
     *
     * @param string[] $headers Encabezados de columna
     * @param array    $rows    Filas; cada fila es un array de celdas (string|int|float|null)
     */
    public static function crear(array $headers, array $rows, string $hoja = 'Pedidos'): string
    {
        $all = array_merge([$headers], $rows);
        $sheet = self::sheetXml($all);

        $files = [
            '[Content_Types].xml'         => self::contentTypes(),
            '_rels/.rels'                 => self::rels(),
            'xl/workbook.xml'             => self::workbook($hoja),
            'xl/_rels/workbook.xml.rels'  => self::workbookRels(),
            'xl/worksheets/sheet1.xml'    => $sheet,
        ];

        return self::zip($files);
    }

    /* ------------------------------------------------ XML ------------ */

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function colLetter(int $index): string
    {
        $s = '';
        $n = $index + 1;
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    }

    private static function sheetXml(array $rows): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $r => $cells) {
            $rowNum = $r + 1;
            $xml .= '<row r="' . $rowNum . '">';
            foreach (array_values($cells) as $c => $value) {
                $ref = self::colLetter($c) . $rowNum;
                if ($value === null || $value === '') {
                    continue; // celda vacía
                }
                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                        . self::esc((string) $value) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private static function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(string $hoja): string
    {
        $hoja = self::esc(mb_substr($hoja, 0, 31));
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $hoja . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    /* ------------------------------------------------ ZIP (store) --- */

    private static function zip(array $files): string
    {
        $data = '';
        $cdir = '';
        $count = 0;

        foreach ($files as $name => $content) {
            $crc      = crc32($content);
            $len      = strlen($content);
            $nameLen  = strlen($name);
            $offset   = strlen($data);

            // Local file header
            $data .= pack('V', 0x04034b50) . pack('v', 20) . pack('v', 0) . pack('v', 0)
                . pack('v', 0) . pack('v', 0)
                . pack('V', $crc) . pack('V', $len) . pack('V', $len)
                . pack('v', $nameLen) . pack('v', 0)
                . $name . $content;

            // Central directory header
            $cdir .= pack('V', 0x02014b50) . pack('v', 20) . pack('v', 20) . pack('v', 0) . pack('v', 0)
                . pack('v', 0) . pack('v', 0)
                . pack('V', $crc) . pack('V', $len) . pack('V', $len)
                . pack('v', $nameLen) . pack('v', 0) . pack('v', 0)
                . pack('v', 0) . pack('v', 0) . pack('V', 0)
                . pack('V', $offset)
                . $name;

            $count++;
        }

        $cdirSize   = strlen($cdir);
        $cdirOffset = strlen($data);

        $end = pack('V', 0x06054b50) . pack('v', 0) . pack('v', 0)
            . pack('v', $count) . pack('v', $count)
            . pack('V', $cdirSize) . pack('V', $cdirOffset) . pack('v', 0);

        return $data . $cdir . $end;
    }
}
