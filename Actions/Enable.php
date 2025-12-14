<?php

namespace App\Vito\Plugins\NodeDogConsulting\VitoLaravelNightwatch\Actions;

use App\Actions\Worker\CreateWorker;
use App\Models\Worker;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class Enable extends Action
{
    public function name(): string
    {
        return 'Enable Laravel Nightwatch';
    }

    public function active(): bool
    {
        $worker = Worker::where('site_id', $this->site->id)
            ->where('name', "nightwatch-{$this->site->id}")
            ->first();

        return $worker === null;
    }

    public function handle(Request $request): void
    {
        $port  = $request->input('nightwatch_port');
        $token = $request->input('nightwatch_token');

        if (empty($port)) {
            session()->flash('error', 'Nightwatch port is required.');
            return;
        }

        if (empty($token)) {
            session()->flash('error', 'Nightwatch token is required.');
            return;
        }

        if (!is_numeric($port) || $port < 1 || $port > 65535) {
            session()->flash('error', 'Nightwatch port must be a valid port number (1-65535).');
            return;
        }

        $site = $this->site;
        $phpPath = '/usr/bin/php' . $site->php_version;
        $envPath = $site->type_data['env_path'] ?? $site->path . '/.env';

        $site->server->ssh($site->user)->exec("
            # Ensure file ends with newline
            sed -i -e '\$a\\' {$envPath}

            # Add header comment if none of the Nightwatch vars exist
            if ! grep -q 'NIGHTWATCH' {$envPath}; then
                echo '' >> {$envPath}
                echo '# Laravel Nightwatch' >> {$envPath}
            fi

            # Add NIGHTWATCH_TOKEN if missing
            if ! grep -q '^NIGHTWATCH_TOKEN=' {$envPath}; then
                echo 'NIGHTWATCH_TOKEN={$token}' >> {$envPath}
            fi

            # Add NIGHTWATCH_INGEST_URI if missing
            if ! grep -q '^NIGHTWATCH_INGEST_URI=' {$envPath}; then
                echo 'NIGHTWATCH_INGEST_URI=127.0.0.1:{$port}' >> {$envPath}
            fi

            # Add NIGHTWATCH_PORT if missing
            if ! grep -q '^NIGHTWATCH_PORT=' {$envPath}; then
                echo 'NIGHTWATCH_PORT={$port}' >> {$envPath}
            fi
        ");

        $command = "cd {$site->path} && {$phpPath} artisan nightwatch:agent --listen-on=127.0.0.1:{$port}";

        app(CreateWorker::class)->create(
            $site->server,
            [
                'name' => "nightwatch-{$site->id}",
                'command' => $command,
                'user' => $site->user,
                'auto_start' => true,
                'auto_restart' => true,
                'numprocs' => 1,
            ],
            $site
        );

        session()->flash('success', "Laravel Nightwatch has been enabled and started!\n\nEnvironment variables have been added to your .env file.\nCheck the Workers tab to manage the Nightwatch agent.");
    }
}
