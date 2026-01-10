<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait UserMetricsTrait
{
    /**
     * Calculate user metrics for a specific campaign
     *
     * @param int $userId
     * @param int|null $campainId
     * @return array
     */
    protected function calculateUserMetrics(int $userId, ?int $campainId = null): array
    {
        try {
            $campain = $campainId ? DB::table(env('MODEL_CAMPAIN'))->find($campainId) : null;
            $today = now()->startOfDay();

            // Si no hay campaña, retornar métricas vacías
            if (!$campain) {
                return $this->getEmptyMetrics();
            }

            $metrics = [];

            // Créditos asignados
            try {
                $metrics['nro_credits'] = DB::table(env('MODEL_CREDIT'))
                    ->where('user_id', $userId)
                    ->where('business_id', $campain->business_id)
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_credits'] = 0;
            }

            // Gestiones en la campaña
            try {
                $metrics['nro_gestions'] = DB::table(env('MODEL_MANAGEMENT'))
                    ->where('user_id', $userId)
                    ->where('campain_id', $campain->id)
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_gestions'] = 0;
            }

            // Gestiones del día
            try {
                $metrics['nro_gestions_dia'] = DB::table(env('MODEL_MANAGEMENT'))
                    ->where('user_id', $userId)
                    ->where('campain_id', $campain->id)
                    ->whereDate('created_at', $today)
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_gestions_dia'] = 0;
            }

            // Gestiones efectivas en la campaña
            try {
                $metrics['nro_gestions_efec'] = DB::table(env('MODEL_MANAGEMENT'))
                    ->where('user_id', $userId)
                    ->where('campain_id', $campain->id)
                    ->whereIn('state', ['EFECTIVA', 'PROMESA_PAGO', 'COMPROMISO_PAGO'])
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_gestions_efec'] = 0;
            }

            // Gestiones efectivas del día
            try {
                $metrics['nro_gestions_efec_dia'] = DB::table(env('MODEL_MANAGEMENT'))
                    ->where('user_id', $userId)
                    ->where('campain_id', $campain->id)
                    ->whereIn('state', ['EFECTIVA', 'PROMESA_PAGO', 'COMPROMISO_PAGO'])
                    ->whereDate('created_at', $today)
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_gestions_efec_dia'] = 0;
            }

            // Créditos pendientes
            try {
                $metrics['nro_pendientes'] = DB::table(env('MODEL_CREDIT'))
                    ->where('user_id', $userId)
                    ->where('business_id', $campain->business_id)
                    ->where('management_tray', 'PENDIENTE')
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_pendientes'] = 0;
            }

            // Créditos en proceso
            try {
                $metrics['nro_proceso'] = DB::table(env('MODEL_CREDIT'))
                    ->where('user_id', $userId)
                    ->where('business_id', $campain->business_id)
                    ->where('management_tray', 'EN PROCESO')
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_proceso'] = 0;
            }

            // Créditos en proceso del día
            try {
                $metrics['nro_proceso_dia'] = DB::table(env('MODEL_CREDIT'))
                    ->where('user_id', $userId)
                    ->where('business_id', $campain->business_id)
                    ->where('management_tray', 'EN PROCESO')
                    ->whereDate('last_sync_date', $today)
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_proceso_dia'] = 0;
            }

            // Llamadas del día
            try {
                $metrics['nro_calls'] = DB::table(env('MODEL_COLLECTION_CALL'))
                    ->where('user_id', $userId)
                    ->whereDate('created_at', $today)
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_calls'] = 0;
            }

            // Llamadas acumuladas
            try {
                $metrics['nro_calls_acum'] = DB::table(env('MODEL_COLLECTION_CALL'))
                    ->where('user_id', $userId)
                    ->count();
            } catch (\Exception $e) {
                $metrics['nro_calls_acum'] = 0;
            }

            return $metrics;
        } catch (\Exception $e) {
            Log::error('Error calculating user metrics', [
                'user_id' => $userId,
                'campain_id' => $campainId,
                'error' => $e->getMessage()
            ]);
            return $this->getEmptyMetrics();
        }
    }

    /**
     * Get empty metrics array
     *
     * @return array
     */
    protected function getEmptyMetrics(): array
    {
        return [
            'nro_credits' => 0,
            'nro_gestions' => 0,
            'nro_gestions_dia' => 0,
            'nro_gestions_efec' => 0,
            'nro_gestions_efec_dia' => 0,
            'nro_pendientes' => 0,
            'nro_proceso' => 0,
            'nro_proceso_dia' => 0,
            'nro_calls' => 0,
            'nro_calls_acum' => 0,
        ];
    }

    
    protected function calculateTimeState($user): string
    {
        $timeElapsed = abs(now()->diffInSeconds($user->updated_at));
        $hours = floor($timeElapsed / 3600);
        $minutes = floor(($timeElapsed % 3600) / 60);
        $seconds = $timeElapsed % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}