<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        Activity::insert([
            ['name' => 'Bienvenida al programa', 'file_url' => 'activities/modulo1.png', 'section_id' => 1, 'file_url_content' => 'activities/actividad_1.pdf'],
            ['name' => 'Uso básico de la plataforma', 'file_url' => 'activities/modulo1.png', 'section_id' => 1, 'file_url_content' => 'activities/actividad_2.pdf'],
            ['name' => 'Preparación del expediente', 'file_url' => 'activities/modulo1.png', 'section_id' => 1, 'file_url_content' => 'activities/actividad_3.pdf'],
            ['name' => 'Comunicación efectiva', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_4.pdf'],
            ['name' => 'Roles y responsabilidades', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_5.pdf'],
            ['name' => 'Manejo de incidencias', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_6.pdf'],
            ['name' => 'Seguimiento de acuerdos', 'file_url' => 'activities/modulo2.png', 'section_id' => 2, 'file_url_content' => 'activities/actividad_7.pdf'],
            ['name' => 'Mapeo de procesos', 'file_url' => 'activities/modulo3.png', 'section_id' => 3, 'file_url_content' => 'activities/actividad_8.pdf'],
            ['name' => 'Registro de actividades', 'file_url' => 'activities/modulo3.png', 'section_id' => 3, 'file_url_content' => 'activities/actividad_9.pdf'],
            ['name' => 'Indicadores básicos', 'file_url' => 'activities/modulo3.png', 'section_id' => 3, 'file_url_content' => 'activities/actividad_10.pdf'],
            ['name' => 'Control de calidad', 'file_url' => 'activities/modulo4.png', 'section_id' => 4, 'file_url_content' => 'activities/actividad_11.pdf'],
            ['name' => 'Plan de mejora', 'file_url' => 'activities/modulo4.png', 'section_id' => 4, 'file_url_content' => 'activities/actividad_12.pdf'],
            ['name' => 'Cierre y próximos pasos', 'file_url' => 'activities/modulo4.png', 'section_id' => 4, 'file_url_content' => 'activities/actividad_13.pdf'],
            ['name' => 'Documento de política interna', 'file_url' => null, 'section_id' => 5, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Evidencia de difusión de política', 'file_url' => null, 'section_id' => 5, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Registro de aceptación del equipo', 'file_url' => null, 'section_id' => 5, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Procedimiento de atención', 'file_url' => null, 'section_id' => 6, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Formato de seguimiento', 'file_url' => null, 'section_id' => 6, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Evidencia de resolución de casos', 'file_url' => null, 'section_id' => 6, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Lista de asistencia a capacitación', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Reporte de sesión realizada', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Convenio o constancia de capacitación', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Material utilizado en capacitación', 'file_url' => null, 'section_id' => 7, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Cronograma de actividades', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Evidencia de comunicación interna', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Material gráfico de difusión', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Registro de difusión', 'file_url' => null, 'section_id' => 8, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Declaratoria de cumplimiento', 'file_url' => null, 'section_id' => 9, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
            ['name' => 'Informe anual de acciones', 'file_url' => null, 'section_id' => 9, 'file_url_content' => 'activities/reporte_ejemplo_mejora.pdf'],
            ['name' => 'Evidencia de colaboración o mejora', 'file_url' => null, 'section_id' => 9, 'file_url_content' => 'activities/reporte_ejemplo_procesos.pdf'],
        ]);
    }
}
