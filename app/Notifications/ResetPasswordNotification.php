<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    protected $token; // 👈 You were missing this

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token; // 👈 Now we store the token
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset Your Password - Netbil')
            ->greeting('Hey there,')
            ->line('You requested a password reset.')
            ->action('Reset Password', $url)
            ->line('If you didn’t request this, just ignore it — no action needed.')
            ->line("This link will expire in 60 minutes.")
->salutation('Regards, Netbil Team');

    }

    /**
     * Get the array representation of the notification (optional).
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}