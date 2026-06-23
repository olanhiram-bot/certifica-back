<?php

/**
 * @return string|array{size: float, unit: string}
 */
function formatFileSize(float $bytes, string $presentation = 'text', int $decimals = 2): array|string
{
    if ($bytes <= 0) {
        return $presentation === 'array' ? ['size' => 0.0, 'unit' => 'B'] : '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    // Calculamos el factor usando logaritmos en base 1024
    $factor = (int) floor(log($bytes, 1024));

    // Evitamos que el factor exceda el índice del array $units
    $factor = min($factor, count($units) - 1);

    $size = $bytes / pow(1024, $factor);

    if ($presentation === 'array') {
        return [
            'size' => (float) round($size, $decimals),
            'unit' => $units[$factor],
        ];
    }

    return round($size, $decimals).' '.$units[$factor];
}

function workingDay(int $days): int
{
    return $days;
}
