<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Verify outgoing mail before relying on it.
 *
 * Replaces the "send test email" button that would have lived in an admin SMTP
 * form. The requirement it satisfies is the same — prove delivery works — but
 * the surface is a console command reachable only with server access, so mail
 * settings cannot be repointed by a web session.
 *
 *   php artisan mail:test you@example.com
 *
 * On failure it prints the SMTP response. It never prints the password.
 */
class MailTestCommand extends Command
{
    protected $signature = 'mail:test {to : Address to send the test message to}';

    protected $description = 'Send a test email through the configured transport to verify delivery';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Not a valid email address: {$to}");

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');

        $this->line("Mailer:  <info>{$mailer}</info>");

        if ($mailer === 'smtp') {
            $port = config('mail.mailers.smtp.port');
            $scheme = config('mail.mailers.smtp.scheme') ?? 'none';
            $this->line("Server:  <info>{$host}:{$port}</info> (encryption: {$scheme})");
            $this->line('Auth:    '.($this->hasCredentials() ? 'username + password set' : '<comment>no credentials configured</comment>'));
        } else {
            $this->warn("Not using SMTP. In production this should be `smtp`, not `{$mailer}`.");
        }

        $from = (string) config('mail.from.address');
        $this->line("From:    <info>{$from}</info>");
        $this->newLine();

        try {
            Mail::raw(
                "This is a test message from Trust-Prime.\n\n"
                ."Sent at: ".now()->toDateTimeString()."\n"
                ."If you received this, outgoing email is configured correctly.",
                function ($message) use ($to): void {
                    $message->to($to)
                        ->subject('Trust-Prime mail test');
                }
            );
        } catch (Throwable $e) {
            // The message can include the SMTP response, which is useful.
            // It must never include the password — Laravel and Symfony do not
            // put credentials in transport exceptions, but this command never
            // echoes configuration values regardless.
            $this->error('FAILED to send.');
            $this->line($e->getMessage());
            $this->newLine();
            $this->line('Common causes: wrong port for the encryption type');
            $this->line('(587 = tls/STARTTLS, 465 = ssl), blocked outbound SMTP on');
            $this->line('the host, or a provider requiring an app password.');

            return self::FAILURE;
        }

        $this->info("Sent. Check the inbox for {$to} (and the spam folder).");
        $this->line('If it does not arrive, verify SPF and DKIM for the from-domain.');

        return self::SUCCESS;
    }

    /** Report presence only — never the value. */
    private function hasCredentials(): bool
    {
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');

        return filled($username) && filled($password);
    }
}
