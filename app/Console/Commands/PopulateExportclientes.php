<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use App\Models\Exportcliente;
use App\Services\ClienteService;

class PopulateExportclientes extends Command
{
    protected $signature = 'export:populate-exportclientes {--missing : Only populate missing exportclientes entries}';

    protected $description = 'Populate the exportclientes table for existing clientes (use --missing to skip existing entries)';

    public function handle()
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        $onlyMissing = $this->option('missing');
        $this->info('Starting PopulateExportclientes (onlyMissing=' . ($onlyMissing ? 'true' : 'false') . ')');

        $service = new ClienteService();
        $total = Cliente::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Cliente::chunk(500, function ($clientes) use ($service, $onlyMissing, $bar) {
            foreach ($clientes as $cliente) {
                try {
                    if ($onlyMissing) {
                        if (Exportcliente::where('cliente_id', $cliente->id)->exists()) {
                            $bar->advance();
                            continue;
                        }
                    }
                    $service->exportclienteStore($cliente->id);
                } catch (\Throwable $e) {
                    Log::error('PopulateExportclientes error: ' . $e->getMessage(), ['cliente_id' => $cliente->id]);
                    $this->error('Error processing cliente ' . $cliente->id . ': ' . $e->getMessage());
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info('\nDone.');

        return 0;
    }
}
