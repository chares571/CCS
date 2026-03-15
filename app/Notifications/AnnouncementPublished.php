<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = (string) ($this->announcement->title ?? 'Announcement');
        $preview = trim(strip_tags((string) $this->announcement->renderedContent()));
        if (mb_strlen($preview) > 180) {
            $preview = mb_substr($preview, 0, 177).'...';
        }

        return (new MailMessage)
            ->subject('New Announcement: '.$title)
            ->greeting('Hello!')
            ->line('A new announcement has been published.')
            ->line($title)
            ->line($preview !== '' ? $preview : 'Open the portal to read the full announcement.')
            ->action('Open Announcement Feed', route('homepage.feed'));
    }
}

