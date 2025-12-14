<?php

namespace App\Vito\Plugins\NodeDogConsulting\VitoLaravelNightwatch\Actions;

use App\Actions\Worker\DeleteWorker;
use App\Models\Worker;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class Disable extends Action
{
    public function name(): string
    {
        return 'Disable Laravel Nightwatch';
    }

    public function active(): bool
    {
        $worker = Worker::where('site_id', $this->site->id)
            ->where('name', "nightwatch-{$this->site->id}")
            ->first();

        return $worker !== null;
    }

    public function handle(Request $request): void
    {
        $worker = Worker::where('site_id', $this->site->id)
            ->where('name', "nightwatch-{$this->site->id}")
            ->first();

        if ($worker) {
            app(DeleteWorker::class)->delete($worker);
            session()->flash('success', 'Laravel Nightwatch has been disabled for this site.');
        } else {
            session()->flash('error', 'Nightwatch worker not found.');
        }
    }
}
