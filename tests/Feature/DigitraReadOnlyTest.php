<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Digitra\User as DigitraUser;
use App\Models\Digitra\Establecimiento;
use App\Models\Digitra\Reserva;
use App\Models\Digitra\Huesped;
use Exception;

class DigitraReadOnlyTest extends TestCase
{
    /**
     * Test que la lectura funciona correctamente
     */
    public function test_puede_leer_datos_de_digitra(): void
    {
        // Esto debe funcionar sin problemas
        $usuarios = DigitraUser::limit(1)->get();
        $this->assertNotNull($usuarios);

        $propiedades = Establecimiento::limit(1)->get();
        $this->assertNotNull($propiedades);

        echo "\n✅ LECTURA: Funciona correctamente\n";
    }

    /**
     * Test que NO se puede crear registros (bloqueado por $guarded)
     */
    public function test_no_puede_crear_usuario_digitra(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);

        DigitraUser::create([
            'name' => 'Test Usuario',
            'email' => 'test@test.com',
        ]);

        echo "\n🚫 CREATE bloqueado por mass-assignment\n";
    }

    /**
     * Test que Observer bloquea saves directos
     */
    public function test_observer_bloquea_save_directo(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('OPERACIÓN BLOQUEADA');

        $user = new DigitraUser();
        $user->name = 'Test';
        $user->email = 'test@test.com';
        $user->save(); // Esto debería ser bloqueado por el Observer

        echo "\n🚫 SAVE directo bloqueado por Observer\n";
    }

    /**
     * Test que NO se puede actualizar registros
     */
    public function test_no_puede_actualizar_establecimiento(): void
    {
        $establecimiento = Establecimiento::first();

        if ($establecimiento) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('OPERACIÓN BLOQUEADA');

            $establecimiento->nombre = 'Test Update';
            $establecimiento->save();
        }

        echo "\n🚫 UPDATE bloqueado correctamente\n";
        $this->assertTrue(true);
    }

    /**
     * Test que NO se puede eliminar registros
     */
    public function test_no_puede_eliminar_reserva(): void
    {
        $reserva = Reserva::first();

        if ($reserva) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('OPERACIÓN BLOQUEADA');

            $reserva->delete();
        }

        echo "\n🚫 DELETE bloqueado correctamente\n";
        $this->assertTrue(true);
    }

    /**
     * Test de mass-assignment bloqueado
     */
    public function test_mass_assignment_bloqueado(): void
    {
        $this->expectException(Exception::class);

        Huesped::create([
            'nombres' => 'Test',
            'apellidos' => 'Test',
        ]);

        echo "\n🚫 MASS-ASSIGNMENT bloqueado correctamente\n";
    }

    /**
     * Test que la conexión es la correcta
     */
    public function test_usa_conexion_correcta(): void
    {
        $user = new DigitraUser();
        $this->assertEquals('mysql', $user->getConnectionName());

        echo "\n✅ Conexión MySQL configurada correctamente\n";
    }
}
