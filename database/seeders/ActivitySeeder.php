<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Activity::insert([
            ['name' => 'Conceptos básicos', 'file_url' => 'activities/modulo1.png', 'section_id' => 1, 'file_url_content' => 'activities/actividad_1.pdf'],
            ['name' => 'Cómo implementar la perspectiva de género en una empresa o institución', 'file_url' => 'activities/modulo1.png', 'section_id' => 1, 'file_url_content' => 'activities/actividad_2.pdf'],
            ['name' => 'Marco legal nacional e internacional relacionado', 'file_url' => 'activities/modulo1.png', 'section_id' => 1, 'file_url_content' => 'activities/actividad_3.pdf'],
            ['name' => 'Qué es la prevención y cuáles son sus niveles', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_4.pdf'],
            ['name' => 'Violencia estructural y abuso de poder', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_5.pdf'],
            ['name' => 'Tipos y modalidades de violencia en el entorno laboral', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_6.pdf'],
            ['name' => 'Alerta de violencia en el entorno laboral', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_7.pdf'],
            ['name' => 'Acoso y hostigamiento', 'file_url' => 'activities/modulo3.png', 'section_id' => 3, 'file_url_content' => 'activities/actividad_8.pdf'],
            ['name' => 'Líneas de acción para atender sin revictimizar', 'file_url' => 'activities/modulo3.png', 'section_id' => 3, 'file_url_content' => 'activities/actividad_9.pdf'],
            ['name' => 'Primeros auxilios psicológicos', 'file_url' => 'activities/modulo3.png', 'section_id' => 3, 'file_url_content' => 'activities/actividad_10.pdf'],
            ['name' => 'Concepto de género y masculinidades', 'file_url' => 'activities/modulo4.png', 'section_id' => 4, 'file_url_content' => 'activities/actividad_11.pdf'],
            ['name' => 'Líneas de atención para reparación de daño', 'file_url' => 'activities/modulo4.png', 'section_id' => 4, 'file_url_content' => 'activities/actividad_12.pdf'],
            ['name' => 'Programas de reeducación con hombres', 'file_url' => 'activities/modulo4.png', 'section_id' => 4, 'file_url_content' => 'activities/actividad_13.pdf'],
            ['name' => 'Reglamento interno', 'file_url' => null, 'section_id' => 5, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Código de ética', 'file_url' => null, 'section_id' => 5, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Evidencia de mecanismos para el seguimiento y evaluación continua de la política', 'file_url' => null, 'section_id' => 5, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Evidencia de la realización de una actividad de socialización', 'file_url' => null, 'section_id' => 5, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Protocolos de atención', 'file_url' => null, 'section_id' => 6, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Dos evidencias de socialización del protocolo', 'file_url' => null, 'section_id' => 6, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Plan de capacitación', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Evidencia de listas o registros en la participación de talleres de empoderamiento femenino y actividades reeducativas dirigidas a hombres', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Fotografía de las sesiones realizadas', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Convenio(s) con instituciones capacitadoras', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Evidencia del diseño de metodología de actividades realizadas', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Evidencia del cronograma de ejecución, temáticas abordadas o formatos utilizados', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Evidencia del material gráfico generado', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Evidencia de difusión', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Declaratoria de cumplimiento', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
            ['name' => 'Informe anual de acciones', 'file_url' => null, 'section_id' => 9, 'file_url_content' => 'activities/Ejemplo_Reporte_Perspectiva_Genero.pdf'],
            ['name' => 'Documentos que acrediten colaboración con organizaciones de la sociedad civil durante el año', 'file_url' => null, 'section_id' => 9, 'file_url_content' => 'activities/Ejemplo_Reporte_Prevencion_Acoso.pdf'],
        ]);
    }
}
