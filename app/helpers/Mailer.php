<?php
declare(strict_types=1);

class Mailer {
    public function isEnabled(): bool {
        return env('MAIL_ENABLED', '0') === '1';
    }

    public function send(string $to, string $subject, string $textBody): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        $from = env('MAIL_FROM', 'no-reply@sgplopypc.gob.mx');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from,
        ];

        return @mail($to, $subject, $textBody, implode("\r\n", $headers));
    }
}
