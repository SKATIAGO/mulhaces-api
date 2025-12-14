<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\Demo\PricingComparisonDemo;

class DemoPricingComparison extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:pricing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demuestra que el código refactorizado produce los mismos resultados que el legacy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ejecutando comparación Legacy vs Refactorizado...');
        
        PricingComparisonDemo::runComparison();
        
        $this->info('Demostración de extensibilidad...');
        PricingComparisonDemo::demonstrateExtensibility();
        
        return Command::SUCCESS;
    }
}
