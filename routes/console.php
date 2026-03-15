<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetVerificationCodeMail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ccs:mail-test {to : Recipient email address}', function () {
    $to = (string) $this->argument('to');

    try {
        Mail::to($to)->send(new PasswordResetVerificationCodeMail('123456', 5));
        $this->info('Mail sent successfully.');
        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error('Mail send failed: '.$e->getMessage());
        return self::FAILURE;
    }
})->purpose('Send a test email using the current SMTP configuration');
