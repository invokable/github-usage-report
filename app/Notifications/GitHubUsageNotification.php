<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;

class GitHubUsageNotification extends Notification
{
    use Queueable;

    protected float $percentage = 0.0;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected int $year,
        protected int $month,
        protected float $grossQuantity,
        protected float $grossAmount,
        protected array $usageItems,
    ) {
        $this->percentage = $this->grossQuantity / config('services.github.included_credits') * 100;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return [DiscordChannel::class];
    }

    public function toDiscordWebhook(object $notifiable): DiscordMessage
    {
        $items = collect($this->usageItems)->map(function ($item) {
            return '- '.data_get($item, 'model').': '.data_get($item, 'grossQuantity').' / '.data_get($item, 'grossAmount');
        })->implode(PHP_EOL);

        $content = <<<USAGE
**AI Credits**: $this->grossQuantity ($this->percentage %)
**Cost**: $this->grossAmount

$items
USAGE;

        return DiscordMessage::create()
            ->with([
                'flags' => 1 << 15,
                'components' => [
                    [
                        'type' => 10,
                        'content' => $content,
                    ],
                ],
            ]);
    }
}
