<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        LandingContent::insert([
            [
                'name' => null,
                'position' => 1,
                'description' => null,
                'file_url' => '/landing/banner1.jpg',
                'landing_content_type_id' => 1,
                'link' => null,
            ],
            [
                'name' => null,
                'position' => 2,
                'description' => null,
                'file_url' => '/landing/banner2.png',
                'landing_content_type_id' => 1,
                'link' => null,
            ],
            [
                'name' => '¿Cuáles son los requisitos y documentos?',
                'position' => 1,
                'description' => '<p>Las empresas o usuarios que deseen obtener el Certifica deben:</p>
                    <ul>
                        <li>Estar legalmente constituidas y operar en el estado de la comunidad.</li>
                        <li>Contar con registro en el registro de usuarios del organizacion.</li>
                        <li>Acreditar la capacitación operativa y revisión documental, ya sea:
                            <ul>
                                <li>A través del sistema Certifica</li>
                                <li>Mediante un usuario externo</li>
                            </ul>
                        </li>
                        <li>Presentar evidencia documental que demuestre:
                            <ul>
                                <li>Políticas internas de operación</li>
                                <li>Protocolos de atención a casos de procesos y seguimiento</li>
                                <li>Acciones de capacitación y difusión interna</li>
                                <li>Evidencias de implementación y seguimiento</li>
                            </ul>
                        </li>
                    </ul>',
                'file_url' => null,
                'landing_content_type_id' => 2,
                'link' => null,
            ],
            [
                'name' => '¿Cuánto tarda la validación?',
                'description' => '<p>El proceso de validación se realiza una vez que el expediente esté completo. El tiempo puede variar según el volumen de solicitudes, pero la revisión se efectúa dentro del periodo establecido en la convocatoria, siempre y cuando la documentación se haya entregado en tiempo y forma.</p>',
                'position' => 2,
                'file_url' => null,
                'landing_content_type_id' => 2,
                'link' => null,
            ],
            [
                'name' => '¿Qué pasa si falta un documento?',
                'description' => '<p>Si la documentación está incompleta o presenta inconsistencias:</p>
                    <ul>
                        <li>El expediente pasará a estatus de correcciones.</li>
                        <li>Se notificará a la empresa para que suba los documentos faltantes o realice ajustes dentro del plazo indicado.</li>
                        <li>Si no se atienden las observaciones en el tiempo establecido, el proceso no podrá continuar.</li>
                    </ul>',
                'position' => 3,
                'file_url' => null,
                'landing_content_type_id' => 2,
                'link' => null,
            ],
            [
                'name' => '¿Cómo se obtiene el certificacion Certifica?',
                'description' => '<p>El certificacion se otorga cuando la empresa:</p>
                    <ol>
                        <li>Completa su inscripción dentro del periodo de la convocatoria.</li>
                        <li>Acredita la capacitación requerida.</li>
                        <li>Entrega correctamente todas las evidencias y protocolos solicitados.</li>
                        <li>Obtiene dictamen favorable tras la revisión por parte del área validadora.</li>
                    </ol>
                    <p>Una vez aprobado el expediente, se emite el Certifica.</p>',
                'position' => 4,
                'file_url' => null,
                'landing_content_type_id' => 2,
                'link' => null,
            ],
            [
                'name' => '¿Tiene costo?',
                'description' => '<p>No. La participación en la convocatoria y el proceso para obtener el Certifica no tiene costo.</p>',
                'position' => 5,
                'file_url' => null,
                'landing_content_type_id' => 2,
                'link' => null,
            ],
            [
                'name' => '¿Quién valida la información?',
                'description' => '<p>La validación de la documentación y evidencias es realizada por el personal designado por el organizacion, a través de las áreas responsables del programa Certifica.</p>',
                'position' => 6,
                'file_url' => null,
                'landing_content_type_id' => 2,
                'link' => null,
            ],
            [
                'name' => '¿Necesitas ayuda?',
                'description' => '<p>Para dudas o aclaraciones sobre la convocatoria y el proceso, puedes comunicarte a través del correo oficial:
                    <a href="mailto:contacto@certifica.local">contacto@certifica.local</a></p>',
                'position' => 7,
                'file_url' => null,
                'landing_content_type_id' => 2,
                'link' => null,
            ],
            [
                'name' => '1. Registro en la convocatoria vigente',
                'description' => '<ul><li>Estar inscrito dentro del periodo establecido en la convocatoria oficial del Certifica.</li><li>Contar con un expediente activo como usuario del organizacion.</li></ul>',
                'position' => 1,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '2. Aceptar los lineamientos del programa',
                'description' => '<ul><li>Reconocer y aceptar las reglas, tiempos, alcances y condiciones del proceso de capacitación.</li><li>Comprometerse a cumplir el programa completo dentro del periodo de vigencia.</li></ul>',
                'position' => 2,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '3. Cursar los cuatro módulos obligatorios',
                'description' => '<p>El usuario deberá completar en su totalidad los siguientes módulos:</p><ul><li>Módulo 1: Introducción a la plataforma</li><li>Módulo 2: Comunicación y colaboración</li><li>Módulo 3: Atención a casos</li><li>Módulo 4: Calidad y mejora continua</li></ul>',
                'position' => 3,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '4. Visualización completa del contenido formativo',
                'description' => '<ul><li>Ver todos los materiales definidos por Certifica (videos, recursos o contenidos digitales).</li><li>No se permitirá avanzar ni concluir el proceso sin completar el contenido obligatorio.</li></ul>',
                'position' => 4,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '5. Cumplimiento del orden y reglas de avance',
                'description' => '<ul><li>Los módulos deberán cursarse conforme a la secuencia establecida.</li><li>El sistema podrá bloquear el acceso a módulos posteriores hasta completar el anterior.</li></ul>',
                'position' => 5,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '6. Entrega de evidencias cuando aplique',
                'description' => '<ul><li>En caso de que algún módulo solicite evidencia complementaria (formatos, documentos o actividades), esta deberá cargarse conforme a las indicaciones.</li><li>Las evidencias deberán corresponder al módulo y tema indicado.</li></ul>',
                'position' => 6,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '7. Atender observaciones en caso de revisión',
                'description' => '<ul><li>En caso de recibir observaciones por parte del área validadora, el usuario deberá atenderlas dentro del plazo establecido.</li><li>La falta de atención a observaciones puede impedir la conclusión del proceso.</li></ul>',
                'position' => 7,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '8. Cumplir con la vigencia de la convocatoria',
                'description' => '<ul><li>Todo el programa deberá completarse dentro del periodo de tiempo definido en la convocatoria.</li><li>Una vez concluida la vigencia, no se permitirá continuar con el proceso ni subir evidencias.</li></ul>',
                'position' => 8,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '9. Conclusión total del programa',
                'description' => '<ul><li>Para considerar acreditada la capacitación, el usuario deberá haber completado el 100% del programa.</li><li>No se aceptan avances parciales como acreditación final.</li></ul>',
                'position' => 9,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],
            [
                'name' => '10. Validación final por la autoridad',
                'description' => '<ul><li>La acreditación de la capacitación estará sujeta a la validación por parte de la instancia correspondiente.</li><li>El cumplimiento del programa no garantiza automáticamente la obtención del certificacion sin dicha validación.</li></ul>',
                'position' => 10,
                'file_url' => null,
                'landing_content_type_id' => 3,
                'link' => null,
            ],

            [
                'name' => '1. Registro del usuario',
                'description' => '<ul><li>Estar registrado en el registro de usuarios del Estado.</li></ul>',
                'position' => 1,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '2. Capacitación externa',
                'description' => '<p>Cubrir los 4 módulos obligatorios establecidos en la convocatoria:</p><ul><li>Introducción a la plataforma</li><li>Comunicación y colaboración</li><li>Atención a casos</li><li>Calidad y mejora continua</li></ul>',
                'position' => 2,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '3. Alinear los contenidos al temario oficial',
                'description' => '<ul><li>Los temas deben corresponder a los contenidos mínimos definidos para cada módulo.</li><li>No se aceptan capacitaciones parciales o con temarios incompletos.</li></ul>',
                'position' => 3,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '4. Contar con evidencia documental de la capacitación',
                'description' => '<ul><li>Constancias, listas de asistencia, materiales o reportes que acrediten la realización del curso.</li><li>Las evidencias deben ser claras, legibles y verificables.</li></ul>',
                'position' => 4,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '5. Acreditar la duración mínima requerida',
                'description' => '<ul><li>La capacitación debe cumplir con el tiempo mínimo establecido en la convocatoria o lineamientos oficiales.</li></ul>',
                'position' => 5,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '6. Identificar a la empresa participante',
                'description' => '<p>Las evidencias deben indicar claramente:</p><ul><li>Nombre de la empresa o usuario.</li><li>Nombre del curso.</li><li>Fecha(s) de impartición.</li></ul>',
                'position' => 6,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '7. Demostrar la participación del personal',
                'description' => '<ul><li>Listas de asistencia o documentos que acrediten que el personal de la empresa recibió la capacitación.</li></ul>',
                'position' => 7,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '8. Presentar constancia o comprobante de conclusión',
                'description' => '<ul><li>Documento que indique que la capacitación fue concluida satisfactoriamente.</li></ul>',
                'position' => 8,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '9. Cumplir con la vigencia de la convocatoria',
                'description' => '<ul><li>La capacitación y la entrega de evidencias deben realizarse dentro del periodo establecido.</li><li>No se aceptan evidencias fuera de vigencia.</li></ul>',
                'position' => 9,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '10. Atender observaciones en caso de revisión',
                'description' => '<ul><li>En caso de observaciones, el usuario deberá corregir o complementar la información solicitada dentro del plazo indicado.</li></ul>',
                'position' => 10,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],
            [
                'name' => '11. Aceptar el proceso de validación',
                'description' => '<ul><li>La capacitación queda sujeta a revisión y validación por la autoridad correspondiente.</li><li>El cumplimiento no garantiza automáticamente la aprobación si la documentación es insuficiente.</li></ul>',
                'position' => 11,
                'file_url' => null,
                'landing_content_type_id' => 4,
                'link' => null,
            ],

            /**
             * ----------------------------------------------------------------------------------------------------
             * | Instituciones
             * ----------------------------------------------------------------------------------------------------
             */
            [
                'name' => 'De machos a hombres',
                'description' => '<span>Tipo de institución:</span><p>Consultoría empresarial</p><span>Programas / Cursos principales</span><p>Desarrollo de procesos de consultoría enfocados en mejora continua, liderazgo consciente y transformación de narrativas sociales.</p>',
                'position' => 12,
                'file_url' => '/institutions/idmah.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Instituto de Formación Beta',
                'description' => '<span>Tipo de institución:</span><p>Organización de la sociedad civil / Instituto de formación</p><span>Programas / Cursos principales</span><p>Programas formativos en estudios de procesos, destacando el Laboratorio de mejora continua: procesos, poder y política.</p>',
                'position' => 13,
                'file_url' => '/institutions/ilsb.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Academia Corporativa Gamma',
                'description' => '<span>Tipo de institución:</span><p>Universidad privada</p><span>Programas / Cursos principales</span><p>Diplomado en procesos de procesos y estrategias institucionales para la atención y erradicación de la procesos y la seguimiento.</p>',
                'position' => 14,
                'file_url' => '/institutions/uvm.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Centro de Desarrollo Delta',
                'description' => '<span>Tipo de institución:</span><p>Universidad privada</p><span>Programas / Cursos principales</span><p>Programas de educación continua orientados a la transformación igualitaria y la promoción de la equidad de procesos.</p>',
                'position' => 15,
                'file_url' => '/institutions/tm.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Instituto de Buenas Prácticas',
                'description' => '<span>Tipo de institución:</span><p>Organismo público autónomo</p><span>Programas / Cursos principales</span><p>Oferta educativa en derechos humanos con capacitación operativa, incluyendo justicia, diversidad sexual, mejora continua y prevención de la procesos.</p>',
                'position' => 16,
                'file_url' => '/institutions/cndh.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Centro de Asesoría Operativa',
                'description' => '<span>Tipo de institución:</span><p>Institución gubernamental</p><span>Programas / Cursos principales</span><p>Programas de capacitación para el cambio cultural orientados a la igualdad sustantiva, prevención de la procesos y promoción de mejora continua no hegemónicas.</p>',
                'position' => 17,
                'file_url' => '/institutions/inequipos.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Red de Consultoría Empresarial',
                'description' => '<span>Tipo de institución:</span><p>Red internacional / Organización de la sociedad civil</p><span>Programas / Cursos principales</span><p>Formación especializada para profesionales en mejora continua corresponsables y estrategias de intervención con hombres.</p>',
                'position' => 18,
                'file_url' => '/institutions/menengage.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Instituto Global de Formación',
                'description' => '<span>Tipo de institución:</span><p>Organismo internacional</p><span>Programas / Cursos principales</span><p>Plataforma de formación en igualdad de procesos, políticas públicas, prevención de la procesos, cuidados y desarrollo sostenible.</p>',
                'position' => 19,
                'file_url' => '/institutions/onu.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Universidad Nacional Autónoma de México (UNAM)',
                'description' => '<span>Tipo de institución:</span><p>Espacio cultural / Organización social</p><span>Programas / Cursos principales</span><p>Programas académicos y cursos en igualdad de procesos, derechos humanos, prevención de procesoss y formación docente con capacitación operativa.</p>',
                'position' => 20,
                'file_url' => '/institutions/unam.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'GENDES, A.C.',
                'description' => '<span>Tipo de institución:</span><p>Organización de la sociedad civil</p><span>Programas / Cursos principales</span><p>Procesos de sensibilización, capacitación y formación en mejora continua y prevención de la procesos desde un enfoque teórico-práctico</p>',
                'position' => 23,
                'file_url' => '/institutions/gendes.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'ARKALI Espacio Cultural',
                'description' => '<span>Tipo de institución:</span><p>Espacio cultural / Organización social</p><span>Programas / Cursos principales</span><p>Iniciativas formativas en cultura de paz, diversidad cultural, salud mental y derechos humanos</p>',
                'position' => 21,
                'file_url' => '/institutions/arkali.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Centro de Capacitación Norte',
                'description' => '<span>Tipo de institución:</span><p>Organización de la sociedad civil</p><span>Programas / Cursos principales</span><p>Desarrollo de talleres, procesos formativos y materiales de capacitación general.</p>',
                'position' => 22,
                'file_url' => '/institutions/voces.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],
            [
                'name' => 'Centro de Mejora Continua',
                'description' => '<span>Tipo de institución:</span><p>Centro de capacitación</p><span>Programas / Cursos principales</span><p>Programas de formación operativa, comunicación interna, documentación de procesos y mejora continua.</p>',
                'position' => 24,
                'file_url' => '/institutions/viccali.png',
                'landing_content_type_id' => 5,
                'link' => null,
            ],

            // Recursos
            [
                'name' => 'ANEXO 1: GUIA PARA LA ELABORACION DE PROCEDIMIENTOS OPERATIVOS ',
                'description' => null,
                'position' => 1,
                'file_url' => '/landing/ANEXO_1.pdf',
                'landing_content_type_id' => 6,
                'link' => null,
            ],
            [
                'name' => 'ANEXO 2: GUIA TECNICA PARA LA EVALUACION DOCUMENTAL DE LOS REQUISITOS PARA LA CERTIFICACION',
                'description' => null,
                'position' => 2,
                'file_url' => '/landing/ANEXO_2.pdf',
                'landing_content_type_id' => 6,
                'link' => null,
            ],
            [
                'name' => 'ANEXO 3. Documento base para la revisión en la selección de Usuarioes del Certificacion "Certifica"',
                'description' => null,
                'position' => 3,
                'file_url' => '/landing/ANEXO_3.pdf',
                'landing_content_type_id' => 6,
                'link' => null,
            ],
            [
                'name' => 'ANEXO 4. Evaluación del cumplimiento de requisitos documentales del Certificacion "Certifica"',
                'description' => null,
                'position' => 4,
                'file_url' => '/landing/ANEXO_4.pdf',
                'landing_content_type_id' => 6,
                'link' => null,
            ],
            [
                'name' => 'Lineamientos del programa',
                'description' => null,
                'position' => 5,
                'file_url' => '/landing/Lineamientos.pdf',
                'landing_content_type_id' => 6,
                'link' => null,
            ],
            [
                'name' => 'Certifica iniciar sesión',
                'description' => null,
                'position' => 1,
                'file_url' => '/landing/certifica_iniciar_sesión.mp4',
                'landing_content_type_id' => 7,
                'link' => null,
            ],
            [
                'name' => 'Ver material de apoyo',
                'description' => null,
                'position' => 2,
                'file_url' => '/landing/ver_material_de_apoyo.mp4',
                'landing_content_type_id' => 7,
                'link' => null,
            ],
        ]);
    }
}
