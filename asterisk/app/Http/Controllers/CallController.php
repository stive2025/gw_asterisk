<?php

namespace App\Http\Controllers;

use App\Http\Responses\ResponseBase;
use App\Services\AsteriskService;
use App\Services\WebSocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    public function dial(Request $request)
    {
        try {
            $asterisk_service = new AsteriskService(
                env('ASTERISK_SERVER_IP'),
                env('ASTERISK_USERNAME'),
                env('ASTERISK_PASSWORD')
            );

            $originate_call = $asterisk_service->originateCall(
                $request->input('channel', ''),
                $request->input('exten', ''),
                $request->input('context', ''),
                $request->input('priority', '1'),
                $request->input('application') ?? '',
                $request->input('data', ''),
                $request->input('timeout', 30000),
                $request->input('caller_id', ''),
                $request->input('variables', []),
                $request->input('account', ''),
                $request->input('async', 'true'),
                $request->input('action_id', '')
            );

            try {
                $userId = $request->input('user_id');
                $campainId = $request->input('campain_id');

                if ($userId && $campainId) {
                    // Actualizar estado del usuario en la base de datos
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $user->update([
                            'status' => 'EN LLAMADA',
                            'updated_at' => now()
                        ]);
                    }

                    // Enviar notificación WebSocket
                    $ws = new WebSocketService();
                    $ws->connect();
                    $ws->sendDialUpdate($userId, $campainId);
                    $ws->disconnect();
                }
            } catch (\Exception $wsError) {
                Log::error('WebSocket notification failed on dial', [
                    'error' => $wsError->getMessage(),
                    'user_id' => $userId ?? null
                ]);
            }

            return ResponseBase::success($originate_call, 'Llamada iniciada correctamente');
        } catch (\Exception $e) {
            Log::error('Error dialing call', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return ResponseBase::error(
                'Error al iniciar la llamada',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function hangup(Request $request)
    {
        try {
            $asterisk_service = new AsteriskService(
                env('ASTERISK_SERVER_IP'),
                env('ASTERISK_USERNAME'),
                env('ASTERISK_PASSWORD')
            );
            
            $hangup_call = $asterisk_service->hangup(
                $request->input('channel')
            );

            try {
                $userId = $request->input('user_id');
                $campainId = $request->input('campain_id');

                if ($userId && $campainId) {
                    // Actualizar estado del usuario en la base de datos
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $user->update([
                            'status' => 'CONECTADO',
                            'updated_at' => now()
                        ]);
                    }

                    // Enviar notificación WebSocket
                    $ws = new WebSocketService();
                    $ws->connect();
                    $ws->sendCallUpdate($userId, $campainId);
                    $ws->disconnect();
                }
            } catch (\Exception $wsError) {
                Log::error('WebSocket notification failed on hangup', [
                    'error' => $wsError->getMessage(),
                    'user_id' => $userId ?? null
                ]);
            }

            return ResponseBase::success($hangup_call, 'Llamada colgada correctamente');
        } catch (\Exception $e) {
            Log::error('Error hanging up call', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return ResponseBase::error(
                'Error al colgar la llamada',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}