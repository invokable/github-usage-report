<?php

namespace App\Console\Commands;

use App\Notifications\GitHubUsageNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

#[Signature('github:usage')]
#[Description('Command description')]
class GitHubUsageCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $url = 'https://api.github.com/users/'.config('services.github.user').'/settings/billing/ai_credit/usage';

        $response = Http::withToken(config('services.github.token'))
            ->withHeader('X-GitHub-Api-Version', '2026-03-10')
            ->accept('application/vnd.github+json')
            ->get($url)
            ->throw();

        $year = $response->json('timePeriod.year');
        $month = $response->json('timePeriod.month');

        $grossQuantity = $response->collect('usageItems')->sum('grossQuantity');
        $grossAmount = $response->collect('usageItems')->sum('grossAmount');

        $usageItems = $response->collect('usageItems')
            ->map(fn ($item) => Arr::only($item, ['model', 'grossQuantity', 'grossAmount']))
            ->toArray();

        Notification::route('discord-webhook', config('services.discord.webhook'))
            ->notify(new GitHubUsageNotification(
                year: $year,
                month: $month,
                grossQuantity: $grossQuantity,
                grossAmount: $grossAmount,
                usageItems: $usageItems,
            ));
    }
}
