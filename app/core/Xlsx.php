<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Generador mínimo de archivos XLSX (Excel 2007+) sin dependencias externas.
 * Usa la paleta del sistema y permite gráficos de barras y circulares nativos.
 */
final class Xlsx
{
    public const S_DEFAULT  = 0;
    public const S_TITLE    = 1;
    public const S_SUBTITLE = 2;
    public const S_HEAD     = 3;
    public const S_LABEL    = 4;
    public const S_VALUE    = 5;
    public const S_TEXT     = 6;
    public const S_MONEY    = 7;
    public const S_INT      = 8;
    public const S_BOLD     = 9;
    public const S_SECTION  = 10;
    public const S_NOTE     = 11;
    public const S_PCT      = 12;

    /** @var array<int,array{name:string,widths:array<int,float>,rows:array<int,array{cells:array,height:?int}>,charts:array,freeze:?int}> */
    private array $sheets = [];

    /** Crea una hoja y devuelve su índice (0-based). */
    public function addSheet(string $name, array $widths = [], ?int $freeze = null): int
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $name) ?? 'Hoja';
        $this->sheets[] = [
            'name'   => mb_substr($name, 0, 31),
            'widths' => $widths,
            'rows'   => [],
            'charts' => [],
            'freeze' => $freeze,
        ];
        return count($this->sheets) - 1;
    }

    /** Celda lista para insertar. Los numéricos se escriben como números de Excel. */
    public static function celda($valor, int $estilo = self::S_DEFAULT): array
    {
        return ['v' => $valor, 's' => $estilo];
    }

    /** Escribe una fila completa en la hoja (las filas pueden ir en cualquier orden). */
    public function fila(int $hoja, int $numero, array $celdas, ?int $altura = null): void
    {
        $this->sheets[$hoja]['rows'][$numero] = ['cells' => $celdas, 'height' => $altura];
    }

    /**
     * Añade un gráfico. $series = [['name' => 'Ventas', 'values' => [1,2,3]], ...].
     * $ancla = ['c1'=>9,'f1'=>2,'c2'=>19,'f2'=>18] (columnas/filas base 0).
     */
    public function grafico(int $hoja, string $tipo, string $titulo, array $categorias, array $series, array $ancla, string $direccion = 'col', array $colores = []): void
    {
        $colores = $colores ?: ['54458E', 'A47CB8', 'CBB2D2', 'B8801F', '8FB08A', '6E6490'];
        $this->sheets[$hoja]['charts'][] = compact('tipo', 'titulo', 'categorias', 'series', 'ancla', 'direccion', 'colores');
    }

    /** Devuelve el archivo como cadena binaria. */
    public function bytes(): string
    {
        $archivos = [
            '[Content_Types].xml' => self::contentTypes(),
            '_rels/.rels'         => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml'     => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(),
            'xl/styles.xml'       => self::styles(),
        ];

        foreach ($this->sheets as $i => $hoja) {
            $n = $i + 1;
            $archivos['xl/worksheets/sheet' . $n . '.xml'] = $this->sheetXml($hoja, $n);
            $archivos['xl/worksheets/_rels/sheet' . $n . '.xml.rels'] = $this->sheetRels($n, count($hoja['charts']));
            if ($hoja['charts'] === []) {
                continue;
            }
            $archivos['xl/drawings/drawing' . $n . '.xml'] = $this->drawingXml($n, $hoja['charts']);
            $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
            foreach ($hoja['charts'] as $j => $chart) {
                $c = $n . '_' . ($j + 1);
                $archivos['xl/charts/chart' . $c . '.xml'] = self::chartXml($chart);
                $rels .= '<Relationship Id="rId' . ($j + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart' . $c . '.xml"/>';
            }
            $archivos['xl/drawings/_rels/drawing' . $n . '.xml.rels'] = $rels . '</Relationships>';
        }

        return self::zip($archivos);
    }

    /** Envía el archivo al navegador como descarga. */
    public function descargar(string $nombre): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre) . '"');
        header('Content-Length: ' . strlen($this->bytes()));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $this->bytes();
        exit;
    }

    private function contentTypes(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($this->sheets as $i => $hoja) {
            $n = $i + 1;
            $x .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            if ($hoja['charts'] === []) {
                continue;
            }
            $x .= '<Override PartName="/xl/drawings/drawing' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
            foreach ($hoja['charts'] as $j => $chart) {
                $x .= '<Override PartName="/xl/charts/chart' . $n . '_' . ($j + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>';
            }
        }
        return $x . '</Types>';
    }

    private function workbook(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($this->sheets as $i => $hoja) {
            $x .= '<sheet name="' . self::xml($hoja['name']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        return $x . '</sheets></workbook>';
    }

    private function workbookRels(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $hoja) {
            $x .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $x .= '<Relationship Id="rId' . (count($this->sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return $x . '</Relationships>';
    }

    private function sheetRels(int $n, int $charts): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        if ($charts > 0) {
            $x .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing' . $n . '.xml"/>';
        }
        return $x . '</Relationships>';
    }

    private function sheetXml(array $hoja, int $n): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        if ($hoja['rows'] !== []) {
            $x .= '<dimension ref="A1:' . self::colName(max(1, ...array_map(static fn ($f) => count($f['cells']), $hoja['rows']))) . '" />';
        }
        $x .= '<sheetViews><sheetView showGridLines="0" workbookViewId="0">';
        if ($hoja['freeze'] !== null) {
            $f = $hoja['freeze'];
            $x .= '<pane ySplit="' . $f . '" topLeftCell="A' . ($f + 1) . '" activePane="bottomLeft" state="frozen"/>';
        }
        $x .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="16"/>';
        if ($hoja['widths'] !== []) {
            $x .= '<cols>';
            foreach ($hoja['widths'] as $i => $ancho) {
                $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float)$ancho . '" customWidth="1"/>';
            }
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        ksort($hoja['rows']);
        foreach ($hoja['rows'] as $numero => $fila) {
            $x .= '<row r="' . (int)$numero . '"' . ($fila['height'] !== null ? ' ht="' . (int)$fila['height'] . '" customHeight="1"' : '') . '>';
            foreach (array_values($fila['cells']) as $j => $celda) {
                if ($celda === null) {
                    continue;
                }
                $ref = self::colName($j + 1) . (int)$numero;
                $estilo = (int)($celda['s'] ?? 0);
                $valor = $celda['v'] ?? '';
                if (is_int($valor) || is_float($valor)) {
                    $x .= '<c r="' . $ref . '" s="' . $estilo . '"><v>' . $valor . '</v></c>';
                } else {
                    $x .= '<c r="' . $ref . '" s="' . $estilo . '" t="inlineStr"><is><t xml:space="preserve">' . self::xml((string)$valor) . '</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($hoja['charts'] !== []) {
            $x .= '<drawing r:id="rId1"/>';
        }
        return $x . '</worksheet>';
    }

    private function drawingXml(int $n, array $charts): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
        foreach ($charts as $j => $chart) {
            $a = $chart['ancla'];
            $id = ($n * 1000) + $j + 2;
            $x .= '<xdr:twoCellAnchor>'
                . '<xdr:from><xdr:col>' . (int)$a['c1'] . '</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . (int)$a['f1'] . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
                . '<xdr:to><xdr:col>' . (int)$a['c2'] . '</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . (int)$a['f2'] . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
                . '<xdr:graphicFrame macro=""><xdr:nvGraphicFramePr><xdr:cNvPr id="' . $id . '" name="Grafico ' . ($j + 1) . '"/><xdr:cNvGraphicFramePr/></xdr:nvGraphicFramePr>'
                . '<xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm>'
                . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">'
                . '<c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="rId' . ($j + 1) . '"/>'
                . '</a:graphicData></a:graphic></xdr:graphicFrame><xdr:clientData/></xdr:twoCellAnchor>';
        }
        return $x . '</xdr:wsDr>';
    }

    private static function chartXml(array $chart): string
    {
        $esCircular = $chart['tipo'] === 'pie';
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<c:chart>'
            . '<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:r>'
            . '<a:rPr lang="es" sz="1200" b="1"><a:solidFill><a:srgbClr val="54458E"/></a:solidFill></a:rPr>'
            . '<a:t>' . self::xml((string)$chart['titulo']) . '</a:t></a:r></a:p></c:rich></c:tx><c:overlay val="0"/></c:title>'
            . '<c:autoTitleDeleted val="0"/><c:plotArea><c:layout/>';

        if ($esCircular) {
            $x .= '<c:pieChart><c:varyColors val="1"/>';
            foreach ($chart['series'] as $si => $serie) {
                $x .= self::serieXml($serie, $chart['categorias'], $si, $chart['colores'], $esCircular);
            }
            $x .= '<c:firstSliceAng val="0"/></c:pieChart>';
        } else {
            $x .= '<c:barChart><c:barDir val="' . ($chart['direccion'] === 'bar' ? 'bar' : 'col') . '"/>'
                . '<c:grouping val="clustered"/><c:varyColors val="0"/>';
            foreach ($chart['series'] as $si => $serie) {
                $x .= self::serieXml($serie, $chart['categorias'], $si, $chart['colores'], $esCircular);
            }
            $x .= '<c:gapWidth val="80"/><c:axId val="111111111"/><c:axId val="222222222"/></c:barChart>'
                . '<c:catAx><c:axId val="111111111"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/>'
                . '<c:axPos val="b"/><c:tickLblPos val="nextTo"/><c:crossAx val="222222222"/><c:crosses val="autoZero"/></c:catAx>'
                . '<c:valAx><c:axId val="222222222"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/>'
                . '<c:axPos val="l"/><c:numFmt formatCode="0.00" sourceLinked="0"/><c:tickLblPos val="nextTo"/>'
                . '<c:crossAx val="111111111"/><c:crosses val="autoZero"/></c:valAx>';
        }
        $x .= '</c:plotArea><c:legend><c:legendPos val="b"/><c:overlay val="0"/></c:legend><c:plotVisOnly val="1"/></c:chart>'
            . '<c:spPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln><a:solidFill><a:srgbClr val="E6D9EA"/></a:solidFill></a:ln></c:spPr>'
            . '</c:chartSpace>';
        return $x;
    }

    private static function serieXml(array $serie, array $categorias, int $indice, array $colores, bool $circular): string
    {
        $cats = self::cadenaLiteral(array_map('strval', $categorias));
        $vals = self::numeroLiteral(array_map(static fn ($v) => (float)$v, $serie['values'] ?? []));
        $x = '<c:ser><c:idx val="' . $indice . '"/><c:order val="' . $indice . '"/>'
            . '<c:tx><c:v>' . self::xml((string)($serie['name'] ?? 'Serie')) . '</c:v></c:tx>';
        if ($circular) {
            foreach (array_values($serie['values'] ?? []) as $i => $v) {
                $x .= '<c:dPt><c:idx val="' . $i . '"/><c:spPr><a:solidFill><a:srgbClr val="' . $colores[$i % count($colores)] . '"/></a:solidFill></c:spPr></c:dPt>';
            }
        } else {
            $x .= '<c:spPr><a:solidFill><a:srgbClr val="' . $colores[$indice % count($colores)] . '"/></a:solidFill><a:ln><a:noFill/></a:ln></c:spPr>';
        }
        return $x . '<c:cat>' . $cats . '</c:cat><c:val>' . $vals . '</c:val></c:ser>';
    }

    private static function cadenaLiteral(array $valores): string
    {
        $x = '<c:strLit><c:ptCount val="' . count($valores) . '"/>';
        foreach ($valores as $i => $v) {
            $x .= '<c:pt idx="' . $i . '"><c:v>' . self::xml($v) . '</c:v></c:pt>';
        }
        return $x . '</c:strLit>';
    }

    private static function numeroLiteral(array $valores): string
    {
        $x = '<c:numLit><c:formatCode>0.00</c:formatCode><c:ptCount val="' . count($valores) . '"/>';
        foreach ($valores as $i => $v) {
            $x .= '<c:pt idx="' . $i . '"><c:v>' . $v . '</c:v></c:pt>';
        }
        return $x . '</c:numLit>';
    }

    private static function styles(): string
    {
        $borde = '<left style="thin"><color rgb="FFCBB2D2"/></left><right style="thin"><color rgb="FFCBB2D2"/></right>'
            . '<top style="thin"><color rgb="FFCBB2D2"/></top><bottom style="thin"><color rgb="FFCBB2D2"/></bottom><diagonal/>';
        $xf = static fn (int $font, int $fill, int $border, string $align = '', int $num = 0): string =>
            '<xf numFmtId="' . $num . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"'
            . ($num > 0 ? ' applyNumberFormat="1"' : '') . '>' . ($align !== '' ? '<alignment horizontal="' . $align . '" vertical="center" wrapText="1"/>' : '<alignment vertical="center"/>') . '</xf>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Bs. &quot;#,##0.00"/></numFmts>'
            . '<fonts count="8">'
            . '<font><sz val="11"/><name val="Calibri"/><color rgb="FF2F2750"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FFE6D9EA"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><color rgb="FF6E6490"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="18"/><color rgb="FF54458E"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF54458E"/><name val="Calibri"/></font>'
            . '<font><sz val="9"/><color rgb="FF6E6490"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF54458E"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF4EEF7"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE6D9EA"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="3"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>' . $borde . '</border><border>' . $borde . '</border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="13">'
            . $xf(0, 0, 0)
            . $xf(1, 2, 0, 'left')
            . $xf(2, 2, 0, 'left')
            . $xf(3, 2, 2, 'center')
            . $xf(4, 3, 2, 'center')
            . $xf(5, 3, 2, 'center')
            . $xf(0, 5, 2, 'left')
            . $xf(0, 3, 2, 'right', 164)
            . $xf(0, 3, 2, 'right', 2)
            . $xf(6, 3, 2, 'left')
            . $xf(6, 4, 2, 'left')
            . $xf(7, 5, 2, 'left')
            . $xf(0, 3, 2, 'right', 10)
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private static function zip(array $archivos): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $fecha = ((int)date('Y') - 1980) << 9 | ((int)date('n') << 5) | (int)date('j');
        $hora = ((int)date('H') << 11) | ((int)date('i') << 5) | ((int)date('s') >> 1);
        foreach ($archivos as $nombre => $datos) {
            $crc = crc32($datos);
            $tam = strlen($datos);
            $long = strlen($nombre);
            $entrada = pack('VvvvvvVVVvv', 0x04034B50, 20, 0, 0, $hora, $fecha, $crc, $tam, $tam, $long, 0) . $nombre . $datos;
            $local .= $entrada;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, 0, $hora, $fecha, $crc, $tam, $tam, $long, 0, 0, 0, 0, 0, $offset) . $nombre;
            $offset += strlen($entrada);
        }
        $total = count($archivos);
        $fin = pack('VvvvvVVv', 0x06054B50, 0, 0, $total, $total, strlen($central), $offset, 0);
        return $local . $central . $fin;
    }

    private static function colName(int $numero): string
    {
        $nombre = '';
        while ($numero > 0) {
            $numero--;
            $nombre = chr(65 + ($numero % 26)) . $nombre;
            $numero = intdiv($numero, 26);
        }
        return $nombre === '' ? 'A' : $nombre;
    }

    private static function xml(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
