<?php
namespace App\Services;

class EmailService
{
    public function send(string $to, string $subject, string $body): bool
    {
        $headers = 'From: ' . config('MAIL_FROM_NAME') . ' <' . config('MAIL_FROM') . ">\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }
}
