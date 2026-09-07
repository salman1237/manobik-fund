<?php

namespace App\Contracts;

interface SmsGateway
{
    public function send(string $toPhoneNumber, string $message): void;
}
