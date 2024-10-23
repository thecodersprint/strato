<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class Uninstall
{
    public function uninstall(Service $service): void
    {
        Validator::make([
            'service' => $service->id,
        ], $service->handler()->deletionRules())->validate();

        $service->status = ServiceStatus::UNINSTALLING;
        $service->save();

        dispatch(function () use ($service) {
            Log::info('Service uninstall initiated', ['service_id' => $service->id]);
            
            $service->handler()->uninstall();
            $service->delete();
        
            Log::info('Service uninstalled and deleted', ['service_id' => $service->id]);
        })->catch(function () use ($service) {
            Log::error('Service uninstall failed', ['service_id' => $service->id]);
            
            $service->status = ServiceStatus::FAILED;
            $service->save();
            
            Log::info('Service status set to FAILED', ['service_id' => $service->id]);
        })->onConnection('ssh');
    }
}
