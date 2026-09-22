<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\AreaAprendizaje;
use App\Models\Asistencia;
use App\Models\AvisoAvance;
use App\Models\EscalaEvaluacion;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\IndicadorEvaluacion;
use App\Models\Periodo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['demo.enabled' => true, 'demo.user_password' => 'Audit-only-2026!', 'demo.reset_passwords' => true]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_ten_independent_logins_navigation_routes_and_group_boundaries(): void
    {
        $counts = ['DIR-001' => 24, 'ADM-001' => 24, 'DOC-001' => 4, 'DOC-002' => 4, 'DOC-003' => 4, 'DOC-004' => 8, 'DOC-005' => 4, 'DOC-006' => 24, 'DOC-007' => 24, 'ENC-0001' => 24];
        foreach ($counts as $code => $count) {
            $this->assertGuest();
            $user = User::where('codigo_usuario', $code)->firstOrFail();
            $this->post('/iniciar-sesion', ['codigo_usuario' => $code, 'password' => config('demo.user_password')])->assertRedirect('/panel');
            $this->assertAuthenticatedAs($user);
            $panel = $this->get('/panel')->assertOk();
            $admin = in_array($code, ['DIR-001', 'ADM-001']);
            foreach (['estudiantes', 'asistencia', 'actividades', 'evaluaciones', 'justificaciones', 'avisos', 'estructura'] as $module) {
                $allowed = $module === 'estructura' ? $admin : ($code !== 'DOC-007' || in_array($module, ['estudiantes', 'avisos']));
                $this->get('/'.$module)->assertStatus($allowed ? 200 : 403);
                $link = 'href="'.url('/'.$module).'"';
                $allowed ? $panel->assertSee($link, false) : $panel->assertDontSee($link, false);
            }
            $this->assertSame($count, $this->get('/estudiantes')->viewData('estudiantes')->total());
            foreach (Grupo::with(['grado', 'seccion'])->get() as $group) {
                $allowed = $admin || $code === 'ENC-0001' || $group->tieneDocente($user);
                foreach (['asistencia', 'actividades', 'evaluaciones'] as $module) {
                    $response = $this->get('/'.$module.'?grupo_id='.$group->id.'&fecha=2026-09-17');
                    $response->assertStatus($allowed && $code !== 'DOC-007' ? 200 : 403);
                    if ($module === 'asistencia' && $allowed && $code !== 'DOC-007') {
                        $response->assertSee('Sección '.$group->seccion->nombre);
                        $this->assertCount(4, $response->viewData('attendances'));
                        foreach ($response->viewData('attendances') as $attendance) {
                            $this->assertSame('2026-09-17', $attendance->fecha->toDateString());
                            $this->assertSame($group->id, $attendance->asignacion->grupo_id);
                        }
                    }
                }
            }
            foreach (['direccion', 'administracion', 'docencia', 'familia'] as $area) {
                $allowed = $code === 'DIR-001' || ($area === 'administracion' && $code === 'ADM-001') || ($area === 'docencia' && str_starts_with($code, 'DOC-')) || ($area === 'familia' && $code === 'ENC-0001');
                $this->get('/panel/'.$area)->assertStatus($allowed ? 200 : 403);
            }
            $this->post('/cerrar-sesion')->assertRedirect('/iniciar-sesion');
        }
    }

    public function test_specialist_cannot_bypass_indexes_with_detail_or_write_urls(): void
    {
        $this->actingAs(User::where('codigo_usuario', 'DOC-007')->firstOrFail());
        $activity = Actividad::firstOrFail();
        $this->get('/actividades/'.$activity->id)->assertForbidden();
        $student = $activity->grupo->asignaciones()->first()->estudiante;
        $student->update(['informacion_medica' => 'PRIVATE MEDICAL AUDIT', 'direccion' => 'PRIVATE ADDRESS AUDIT']);
        $this->get('/estudiantes/'.$student->id)->assertOk()->assertDontSee('PRIVATE MEDICAL AUDIT')->assertDontSee('PRIVATE ADDRESS AUDIT');
        $this->post('/actividades/'.$activity->id.'/calificaciones', [])->assertForbidden();
        $this->post('/actividades/'.$activity->id.'/publicar', [])->assertForbidden();
        $evaluation = Evaluacion::factory()->create(['asignacion_id' => $activity->grupo->asignaciones()->first()->id]);
        $this->get('/evaluaciones/'.$evaluation->id.'/editar')->assertForbidden();
        $this->put('/evaluaciones/'.$evaluation->id, [])->assertForbidden();
        $this->post('/evaluaciones', [])->assertForbidden();
        $this->post('/asistencia', [])->assertForbidden();
    }

    public function test_physical_education_cannot_modify_legacy_titular_activity(): void
    {
        $this->actingAs(User::where('codigo_usuario', 'DOC-006')->firstOrFail());
        $activity = Actividad::whereNull('tipo_docente')->firstOrFail();
        $this->post('/actividades/'.$activity->id.'/calificaciones', [])->assertForbidden();
        $this->post('/actividades/'.$activity->id.'/publicar')->assertForbidden();
    }

    public function test_physical_education_records_only_its_area_in_six_groups(): void
    {
        $teacher = User::where('codigo_usuario', 'DOC-006')->firstOrFail();
        $this->actingAs($teacher);
        foreach (Grupo::all() as $group) {
            $period = Periodo::where('ciclo_id', $group->ciclo_id)->firstOrFail();
            $this->post('/actividades', ['grupo_id' => $group->id, 'periodo_id' => $period->id,
                'titulo' => 'EF audit '.$group->id, 'area_aprendizaje' => 'Educación Física',
                'fecha' => $period->fecha_inicio->toDateString(), 'tipo' => 'numerica', 'punteo_maximo' => 20])->assertSessionHasNoErrors()->assertRedirect();
            $activity = Actividad::where('titulo', 'EF audit '.$group->id)->firstOrFail();
            $results = $group->asignaciones()->where('estado', 'activa')->pluck('id')->mapWithKeys(fn ($id) => [$id => 15])->all();
            $this->post('/actividades/'.$activity->id.'/calificaciones', ['resultados' => $results])->assertSessionHasNoErrors()->assertRedirect();
            $this->post('/actividades/'.$activity->id.'/publicar')->assertSessionHasNoErrors()->assertRedirect();
            $this->post('/asistencia', ['grupo_id' => $group->id, 'fecha' => '2026-09-15',
                'asistencias' => array_fill_keys(array_keys($results), Asistencia::PRESENTE)])->assertSessionHasNoErrors()->assertRedirect();
            $area = AreaAprendizaje::firstOrCreate(['nombre' => 'Educación Física'], ['activo' => true]);
            $indicator = IndicadorEvaluacion::factory()->create(['grado_id' => $group->grado_id, 'area_id' => $area->id]);
            $scale = EscalaEvaluacion::where('activo', true)->firstOrFail();
            $payload = ['grupo_id' => $group->id, 'periodo_id' => $period->id, 'indicador_id' => $indicator->id,
                'resultados' => array_fill_keys(array_keys($results), $scale->id)];
            $this->post('/evaluaciones', $payload)->assertSessionHasNoErrors()->assertRedirect();
            $this->post('/evaluaciones/publicar', $payload)->assertSessionHasNoErrors()->assertRedirect();
            $general = IndicadorEvaluacion::factory()->create(['grado_id' => $group->grado_id]);
            $this->post('/evaluaciones', [...$payload, 'indicador_id' => $general->id])->assertForbidden();
        }
    }

    public function test_unlinked_students_are_hidden_in_every_family_query(): void
    {
        $family = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $group = Grupo::firstOrFail();
        $hidden = $group->asignaciones()->firstOrFail()->estudiante;
        $family->encargado->estudiantes()->detach($hidden->id);
        $this->actingAs($family);
        $this->get('/estudiantes/'.$hidden->id)->assertForbidden();
        $this->get('/estudiantes')->assertDontSee($hidden->nombre_completo);
        $response = $this->get('/asistencia?grupo_id='.$group->id.'&fecha=2026-09-17')->assertOk()->assertDontSee($hidden->nombre_completo);
        $this->assertCount(3, $response->viewData('assignments'));
        $this->get('/asistencia/resumen-mensual?grupo_id='.$group->id.'&mes=2026-09')->assertOk()->assertDontSee($hidden->nombre_completo);
        foreach (Actividad::where('grupo_id', $group->id)->get() as $activity) {
            $response = $this->get('/actividades/'.$activity->id);
            $activity->publicada ? $response->assertOk()->assertDontSee($hidden->nombre_completo) : $response->assertForbidden();
        }
        $this->get('/justificaciones')->assertOk()->assertDontSee($hidden->nombre_completo);
        $notice = AvisoAvance::create(['autor_id' => User::where('codigo_usuario', 'DOC-007')->value('id'),
            'estudiante_id' => $hidden->id, 'asunto' => 'Private hidden progress', 'mensaje' => 'Private', 'activo' => true, 'fecha_publicacion' => now()]);
        $this->get('/avisos')->assertDontSee($notice->asunto);
        $this->post('/avisos', ['estudiante_id' => $hidden->id, 'asunto' => 'Write', 'mensaje' => 'Forbidden'])->assertForbidden();
    }

    public function test_administrators_can_create_and_publish_and_family_cannot_write_grades(): void
    {
        foreach (['DIR-001', 'ADM-001'] as $code) {
            $this->actingAs(User::where('codigo_usuario', $code)->firstOrFail());
            $group = Grupo::firstOrFail();
            $this->get('/actividades?grupo_id='.$group->id)->assertOk()->assertViewHas('canCreate', true);
            $period = Periodo::where('ciclo_id', $group->ciclo_id)->firstOrFail();
            $indicator = IndicadorEvaluacion::factory()->create(['grado_id' => $group->grado_id]);
            $ids = $group->asignaciones()->pluck('id')->all();
            $scale = EscalaEvaluacion::firstOrFail();
            $payload = ['grupo_id' => $group->id, 'periodo_id' => $period->id, 'indicador_id' => $indicator->id,
                'resultados' => array_fill_keys($ids, $scale->id)];
            $this->post('/evaluaciones', $payload)->assertSessionHasNoErrors()->assertRedirect();
            $this->post('/evaluaciones/publicar', $payload)->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->actingAs(User::where('codigo_usuario', 'ENC-0001')->firstOrFail());
        $activity = Actividad::firstOrFail();
        $this->post('/actividades/'.$activity->id.'/calificaciones', [])->assertForbidden();
        $this->post('/evaluaciones', [])->assertForbidden();
    }

    public function test_family_announcements_respect_active_links_and_reject_mixed_targets(): void
    {
        $family = User::where('codigo_usuario', 'ENC-0001')->firstOrFail();
        $teacher = User::where('codigo_usuario', 'DOC-001')->firstOrFail();
        $own = Grupo::where('docente_id', $teacher->id)->firstOrFail();
        $other = Grupo::where('docente_id', '!=', $teacher->id)->firstOrFail();
        $foreignStudent = $other->asignaciones()->first()->estudiante;
        $this->actingAs($teacher)->post('/avisos', ['grupo_id' => $own->id, 'estudiante_id' => $foreignStudent->id, 'asunto' => 'Mixed', 'mensaje' => 'Test'])->assertStatus(422);
        $this->actingAs($teacher)->post('/avisos', ['estudiante_id' => $foreignStudent->id, 'asunto' => 'Foreign', 'mensaje' => 'Test'])->assertForbidden();
        foreach ([$teacher, User::where('codigo_usuario', 'DIR-001')->firstOrFail()] as $author) {
            $this->actingAs($author)->post('/avisos', ['grupo_id' => $own->id, 'asunto' => $author->codigo_usuario.' announcement', 'mensaje' => 'Test'])->assertRedirect();
        }
        $this->actingAs($family)->get('/avisos')->assertSee('DOC-001 announcement')->assertSee('DIR-001 announcement');
        $own->asignaciones()->update(['estado' => 'finalizada']);
        $this->get('/avisos')->assertDontSee('DOC-001 announcement')->assertDontSee('DIR-001 announcement');
        $this->post('/avisos', ['grupo_id' => $own->id, 'asunto' => 'Family write', 'mensaje' => 'Test'])->assertForbidden();
    }
}
