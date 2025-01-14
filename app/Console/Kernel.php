<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcesarPagos; // Agregar al principio del archivo

class Kernel extends ConsoleKernel
{
    /**
     * Los comandos de consola registrados por la aplicación.
     *
     * @var array
     */
    protected $commands = [
        ProcesarPagos::class, // Registrar el comando aquí
    ];

    /**
     * Definir las programaciones de los comandos.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Aquí es donde se programa la ejecución del comando
        $schedule->command('suscripciones:procesar')->everyMinute();


    }

    /**
     * Registrar cualquier comando de consola.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
