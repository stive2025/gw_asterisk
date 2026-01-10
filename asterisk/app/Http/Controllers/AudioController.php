<?php

namespace App\Http\Controllers;

use App\Http\Responses\ResponseBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class AudioController extends Controller
{
    /**
     * Subir y mover audio a carpeta public con estructura de fechas
     */
    public function upload(Request $request)
    {
        try {
            // Validar que se haya enviado un archivo
            $request->validate([
                'record' => 'required|file|mimes:mp3,wav,ogg,m4a,flac,webm|max:51200', // max 50MB
                'date' => 'nullable|date_format:Y-m-d',
            ]);

            // Tipos de archivos de audio permitidos
            $allowedTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'webm'];
            
            $file = $request->file('record');
            $extension = strtolower($file->getClientOriginalExtension());
            $date = $request->input('date', now()->format('Y-m-d'));

            // Verificar extensión permitida
            if (!in_array($extension, $allowedTypes)) {
                return ResponseBase::error(
                    'Solo se permiten archivos de audio (mp3, wav, ogg, m4a, flac, webm)',
                    ['extension' => $extension],
                    422
                );
            }

            // Crear la estructura de carpetas basada en la fecha: YYYY/MM/DD
            $dateObj = \Carbon\Carbon::parse($date);
            $year = $dateObj->format('Y');
            $month = $dateObj->format('m');
            $day = $dateObj->format('d');
            
            $relativePath = "audios/{$year}/{$month}/{$day}";
            $publicPath = public_path($relativePath);

            // Crear directorios si no existen
            if (!File::exists($publicPath)) {
                File::makeDirectory($publicPath, 0755, true);
                Log::info('Created directory structure', ['path' => $publicPath]);
            }

            // Generar nombre único para el archivo
            $filename = $file->getClientOriginalName();
            $destinationPath = $publicPath . '/' . $filename;

            // Si el archivo ya existe, agregar timestamp
            if (File::exists($destinationPath)) {
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
                $destinationPath = $publicPath . '/' . $filename;
            }

            // Mover el archivo
            if ($file->move($publicPath, $filename)) {
                $publicUrl = url("{$relativePath}/{$filename}");

                Log::info('Audio file uploaded successfully', [
                    'destination' => $destinationPath,
                    'url' => $publicUrl,
                    'original_name' => $file->getClientOriginalName()
                ]);

                return ResponseBase::success([
                    'path' => $destinationPath,
                    'relative_path' => "{$relativePath}/{$filename}",
                    'url' => $publicUrl,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'date' => $date
                ], 'El archivo ha sido subido correctamente');
            }

            return ResponseBase::error(
                'Hubo un error al subir el archivo',
                [],
                500
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBase::error(
                'Error de validación',
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Error uploading audio file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseBase::error(
                'Error al subir el audio',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
