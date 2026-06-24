<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$outputDir = $root.'/storage/app/public/activities';
$tmpDir = sys_get_temp_dir().'/certifica-course-assets';

@mkdir($outputDir, 0775, true);
@mkdir($tmpDir, 0775, true);

$modules = [
    'modulo1.png' => ['Módulo 1', 'Introducción a la plataforma', '#2563eb', '#0ea5e9'],
    'modulo2.png' => ['Módulo 2', 'Comunicación y colaboración', '#16a34a', '#14b8a6'],
    'modulo3.png' => ['Módulo 3', 'Gestión de procesos', '#7c3aed', '#db2777'],
    'modulo4.png' => ['Módulo 4', 'Calidad y mejora continua', '#ea580c', '#f59e0b'],
];

$documents = [
    'actividad_1.pdf' => ['Bienvenida al programa', 'Presentación general del flujo de trabajo, las etapas del expediente y las entregas esperadas.'],
    'actividad_2.pdf' => ['Uso básico de la plataforma', 'Guía rápida para navegar, consultar módulos, cargar archivos y revisar el estado de avance.'],
    'actividad_3.pdf' => ['Preparación del expediente', 'Recomendaciones para organizar documentos, responsables, fechas y evidencias antes de iniciar.'],
    'actividad_4.pdf' => ['Comunicación efectiva', 'Buenas prácticas para compartir información clara, dar seguimiento y cerrar acuerdos.'],
    'actividad_5.pdf' => ['Roles y responsabilidades', 'Material para definir responsables, participantes, revisores y tiempos de atención.'],
    'actividad_6.pdf' => ['Manejo de incidencias', 'Proceso estándar para registrar, clasificar y dar seguimiento a situaciones operativas.'],
    'actividad_7.pdf' => ['Seguimiento de acuerdos', 'Formato de apoyo para documentar compromisos, fechas, responsables y resultados.'],
    'actividad_8.pdf' => ['Mapeo de procesos', 'Guía para identificar entradas, actividades, responsables, salidas y puntos de control.'],
    'actividad_9.pdf' => ['Registro de actividades', 'Recomendaciones para documentar actividades realizadas con evidencia verificable.'],
    'actividad_10.pdf' => ['Indicadores básicos', 'Material para definir métricas simples, fuentes de datos y frecuencia de revisión.'],
    'actividad_11.pdf' => ['Control de calidad', 'Checklist para revisar consistencia, completitud y legibilidad de documentos.'],
    'actividad_12.pdf' => ['Plan de mejora', 'Plantilla para priorizar acciones, responsables, fechas objetivo e indicadores.'],
    'actividad_13.pdf' => ['Cierre y próximos pasos', 'Resumen de actividades finales para preparar la revisión documental.'],
    'reporte_ejemplo_procesos.pdf' => ['Ejemplo de reporte de procesos', 'Documento de ejemplo para presentar procesos, actividades y evidencias de soporte.'],
    'reporte_ejemplo_mejora.pdf' => ['Ejemplo de reporte de mejora', 'Documento de ejemplo para presentar hallazgos, acciones correctivas y resultados.'],
];

foreach ($modules as $filename => [$title, $subtitle, $from, $to]) {
    $svgPath = $tmpDir.'/'.$filename.'.svg';
    file_put_contents($svgPath, moduleSvg($title, $subtitle, $from, $to));
    run(['magick', $svgPath, $outputDir.'/'.$filename]);
}

foreach ($documents as $filename => [$title, $description]) {
    $svgPath = $tmpDir.'/'.$filename.'.svg';
    file_put_contents($svgPath, documentSvg($title, $description));
    run(['magick', $svgPath, $outputDir.'/'.$filename]);
}

echo "Assets generados en {$outputDir}\n";

function run(array $command): void
{
    $escaped = array_map('escapeshellarg', $command);
    $cmd = implode(' ', $escaped).' 2>&1';
    exec($cmd, $output, $code);

    if ($code !== 0) {
        throw new RuntimeException("Fallo comando: {$cmd}\n".implode("\n", $output));
    }
}

function moduleSvg(string $title, string $subtitle, string $from, string $to): string
{
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="720" viewBox="0 0 1280 720">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$from}"/>
      <stop offset="100%" stop-color="{$to}"/>
    </linearGradient>
  </defs>
  <rect width="1280" height="720" fill="url(#bg)"/>
  <circle cx="1090" cy="110" r="190" fill="#ffffff" opacity="0.14"/>
  <circle cx="150" cy="610" r="150" fill="#ffffff" opacity="0.12"/>
  <rect x="80" y="90" width="1120" height="540" rx="28" fill="#ffffff" opacity="0.92"/>
  <text x="130" y="210" font-family="Arial, sans-serif" font-size="42" font-weight="700" fill="#1f2937">Certifica</text>
  <text x="130" y="330" font-family="Arial, sans-serif" font-size="72" font-weight="700" fill="#111827">{$title}</text>
  <text x="130" y="415" font-family="Arial, sans-serif" font-size="42" fill="#344054">{$subtitle}</text>
  <rect x="130" y="500" width="300" height="8" rx="4" fill="{$from}"/>
  <text x="130" y="560" font-family="Arial, sans-serif" font-size="28" fill="#475467">Material de capacitación estándar</text>
</svg>
SVG;
}

function documentSvg(string $title, string $description): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $lineSvg = '';
    $y = 360;

    foreach (wrapText($description, 64) as $line) {
        $safeLine = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lineSvg .= "  <text x=\"90\" y=\"{$y}\" font-family=\"Arial, sans-serif\" font-size=\"28\" fill=\"#344054\">{$safeLine}</text>\n";
        $y += 42;
    }

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1240" height="1754" viewBox="0 0 1240 1754">
  <rect width="1240" height="1754" fill="#f8fafc"/>
  <rect x="60" y="60" width="1120" height="1634" rx="18" fill="#ffffff" stroke="#d9dee8" stroke-width="2"/>
  <rect x="60" y="60" width="1120" height="170" rx="18" fill="#2563eb"/>
  <text x="90" y="155" font-family="Arial, sans-serif" font-size="54" font-weight="700" fill="#ffffff">Certifica</text>
  <text x="90" y="285" font-family="Arial, sans-serif" font-size="44" font-weight="700" fill="#111827">{$safeTitle}</text>
{$lineSvg}
  <text x="90" y="610" font-family="Arial, sans-serif" font-size="32" font-weight="700" fill="#111827">Objetivo</text>
  <text x="90" y="665" font-family="Arial, sans-serif" font-size="28" fill="#344054">Revisar el material, completar la actividad y preparar evidencia</text>
  <text x="90" y="707" font-family="Arial, sans-serif" font-size="28" fill="#344054">clara, legible y verificable para el expediente.</text>
  <text x="90" y="810" font-family="Arial, sans-serif" font-size="32" font-weight="700" fill="#111827">Checklist de entrega</text>
  <text x="120" y="875" font-family="Arial, sans-serif" font-size="28" fill="#344054">1. Describe la actividad realizada y su alcance.</text>
  <text x="120" y="925" font-family="Arial, sans-serif" font-size="28" fill="#344054">2. Adjunta evidencia en PDF con fecha y responsable.</text>
  <text x="120" y="975" font-family="Arial, sans-serif" font-size="28" fill="#344054">3. Verifica que el archivo sea legible antes de enviarlo.</text>
  <text x="120" y="1025" font-family="Arial, sans-serif" font-size="28" fill="#344054">4. Conserva el respaldo interno para futuras revisiones.</text>
  <text x="90" y="1180" font-family="Arial, sans-serif" font-size="32" font-weight="700" fill="#111827">Notas</text>
  <text x="90" y="1235" font-family="Arial, sans-serif" font-size="28" fill="#344054">Este documento es material demostrativo generado para la versión</text>
  <text x="90" y="1277" font-family="Arial, sans-serif" font-size="28" fill="#344054">reproducible de Certifica. Puede reemplazarse por contenido formal</text>
  <text x="90" y="1319" font-family="Arial, sans-serif" font-size="28" fill="#344054">cuando el programa se adapte a otra organización.</text>
  <rect x="90" y="1510" width="1060" height="2" fill="#d9dee8"/>
  <text x="90" y="1580" font-family="Arial, sans-serif" font-size="24" fill="#667085">Certifica · Material de apoyo local</text>
</svg>
SVG;
}

function wrapText(string $text, int $limit): array
{
    $words = preg_split('/\s+/', $text) ?: [];
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $candidate = trim($line.' '.$word);
        if (mb_strlen($candidate) > $limit && $line !== '') {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $candidate;
        }
    }

    if ($line !== '') {
        $lines[] = $line;
    }

    return $lines;
}
