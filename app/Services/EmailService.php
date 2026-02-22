<?php
namespace App\Services;

class EmailService
{
    public function send(string $to, string $subject, string $body): bool
    {
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: ' . ($_ENV['MAIL_FROM_NAME'] ?? 'Cata Treco') . ' <' . ($_ENV['MAIL_FROM'] ?? 'no-reply@localhost') . '>';
        return @mail($to, $subject, $body, $headers);
    }
}
