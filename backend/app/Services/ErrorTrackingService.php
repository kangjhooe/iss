<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ErrorTrackingService
{
    /**
     * Track and log error with context.
     */
    public function track(Throwable $exception, array $context = []): void
    {
        $errorData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ];

        // Log error
        Log::error('Application Error', $errorData);

        // In production, you can send to external service like Sentry, Bugsnag, etc.
        if (config('app.env') === 'production') {
            $this->sendToExternalService($errorData);
        }

        // Send email notification for critical errors (optional)
        if ($this->isCriticalError($exception)) {
            $this->notifyAdmins($errorData);
        }
    }

    /**
     * Send error to external tracking service.
     */
    protected function sendToExternalService(array $errorData): void
    {
        // Example: Send to Sentry, Bugsnag, or custom service
        // You can implement this based on your preferred service
        
        // Example for Sentry (if installed):
        // \Sentry\captureException($exception);
        
        // Example for custom API:
        // Http::post(config('services.error_tracking.url'), $errorData);
    }

    /**
     * Check if error is critical.
     */
    protected function isCriticalError(Throwable $exception): bool
    {
        // Define critical error types
        $criticalTypes = [
            \Illuminate\Database\QueryException::class,
            \PDOException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        ];

        foreach ($criticalTypes as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Notify admins about critical errors.
     */
    protected function notifyAdmins(array $errorData): void
    {
        // Only send email if configured
        if (!config('app.error_notification_enabled', false)) {
            return;
        }

        $admins = config('app.error_notification_emails', []);

        if (empty($admins)) {
            return;
        }

        try {
            Mail::send('emails.error-notification', ['error' => $errorData], function ($message) use ($admins) {
                $message->to($admins)
                    ->subject('Critical Error: ' . config('app.name'));
            });
        } catch (\Exception $e) {
            Log::warning('Failed to send error notification email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track API request error.
     */
    public function trackApiError(Throwable $exception, array $requestData = []): void
    {
        $context = array_merge([
            'endpoint' => request()->path(),
            'request_data' => $requestData,
            'response_status' => method_exists($exception, 'getStatusCode') 
                ? $exception->getStatusCode() 
                : 500,
        ], $requestData);

        $this->track($exception, $context);
    }

    /**
     * Track validation error.
     */
    public function trackValidationError(array $errors, array $requestData = []): void
    {
        Log::warning('Validation Error', [
            'errors' => $errors,
            'request_data' => $requestData,
            'endpoint' => request()->path(),
            'user_id' => auth()->id(),
        ]);
    }
}
