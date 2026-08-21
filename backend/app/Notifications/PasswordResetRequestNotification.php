<?php

namespace App\Notifications;

use App\Models\PasswordResetRequest;
use Illuminate\Notifications\Notification;

class PasswordResetRequestNotification extends Notification
{
    public function __construct(
        public PasswordResetRequest $requestModel
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $request = $this->requestModel;
        $request->loadMissing(['institution:id,name,npsn', 'user:id,name,email']);
        $institutionName = $request->institution?->name ?? '-';
        $adminName = $request->user?->name ?? $request->email;

        return [
            'type' => 'password_reset_request',
            'action' => 'submitted',
            'request_id' => $request->id,
            'user_id' => $request->user_id,
            'institution_id' => $request->institution_id,
            'institution_name' => $institutionName,
            'admin_name' => $adminName,
            'email' => $request->email,
            'npsn' => $request->npsn,
            'message' => "Permintaan reset sandi dari {$institutionName} ({$adminName}).",
            'created_at' => now()->toIso8601String(),
        ];
    }
}
