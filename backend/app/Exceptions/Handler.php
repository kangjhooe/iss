<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  Throwable  $e
     * @return Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response
    {
        // Handle authentication exceptions FIRST for API routes to prevent redirect
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
                return $this->handleAuthenticationException();
            }
        }
        
        // Handle API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions.
     *
     * @param  Request  $request
     * @param  Throwable  $e
     * @return JsonResponse
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return $this->handleValidationException($e);
        }

        // Handle model not found exceptions
        if ($e instanceof ModelNotFoundException) {
            return $this->handleModelNotFoundException($e);
        }

        // Handle not found HTTP exceptions
        if ($e instanceof NotFoundHttpException) {
            return $this->handleNotFoundException();
        }

        // Handle authentication exceptions
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->handleAuthenticationException();
        }

        // Handle authorization exceptions
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->handleAuthorizationException();
        }

        // Handle generic exceptions
        return $this->handleGenericException($e);
    }

    /**
     * Handle validation exceptions.
     *
     * @param  ValidationException  $e
     * @return JsonResponse
     */
    protected function handleValidationException(ValidationException $e): JsonResponse
    {
        $errors = [];
        foreach ($e->errors() as $field => $messages) {
            $errors[$field] = is_array($messages) ? $messages[0] : $messages;
        }

        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Handle model not found exceptions.
     *
     * @param  ModelNotFoundException  $e
     * @return JsonResponse
     */
    protected function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());
        $message = "Data {$model} tidak ditemukan.";

        Log::warning('Model not found', [
            'model' => $model,
            'ids' => $e->getIds(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Handle not found HTTP exceptions.
     *
     * @return JsonResponse
     */
    protected function handleNotFoundException(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Endpoint tidak ditemukan.',
        ], 404);
    }

    /**
     * Handle authentication exceptions.
     *
     * @return JsonResponse
     */
    protected function handleAuthenticationException(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Anda harus login untuk mengakses resource ini.',
        ], 401);
    }

    /**
     * Handle authorization exceptions.
     *
     * @return JsonResponse
     */
    protected function handleAuthorizationException(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini.',
        ], 403);
    }

    /**
     * Handle generic exceptions.
     *
     * @param  Throwable  $e
     * @return JsonResponse
     */
    protected function handleGenericException(Throwable $e): JsonResponse
    {
        // Log the exception
        Log::error('Unhandled exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);

        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $message = config('app.debug') 
            ? $e->getMessage() 
            : 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
        ], $statusCode);
    }
}
