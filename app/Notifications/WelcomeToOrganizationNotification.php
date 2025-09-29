<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeToOrganizationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Welcome to {$this->tenant->name}! 🎉")
            ->greeting("Welcome to {$this->tenant->name}!")
            ->line("Congratulations! Your organization setup is now complete and you're ready to start using AdPro.")
            ->line("Here's what you can do next:")
            ->line("• Create your first billboard campaign")
            ->line("• Invite team members to collaborate")
            ->line("• Customize your organization branding")
            ->line("• Explore analytics and reporting features")
            ->action('Access Your Dashboard', route('tenant.dashboard', $this->tenant->uuid))
            ->line("If you have any questions, our support team is here to help!")
            ->salutation("Welcome to the AdPro family!");
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "Welcome to {$this->tenant->name}!",
            'message' => "Your organization setup is complete. Start creating billboard campaigns!",
            'action_url' => route('tenant.dashboard', $this->tenant->uuid),
            'action_text' => 'Access Dashboard',
            'tenant_id' => $this->tenant->id,
            'type' => 'setup_completed',
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'message' => "Welcome to {$this->tenant->name}! Your organization setup is complete.",
        ];
    }
}