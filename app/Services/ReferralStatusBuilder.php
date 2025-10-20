<?php

namespace App\Services;

class ReferralStatusBuilder
{
    public const PENDING = 'pending';
    public const NEWINTRODUCTION = 'new introduction';
    public const DECLINE = 'decline';
    public const NOMATCH = 'no match';
    public const YOUDECLINE = 'declined (You)';
    public const AWAITINGRESPONSE = 'awaiting response';
    public const CONNECTED = 'connected';
    public const PARTIAL = 'partial';

    public static function getStatusResult(string $introducedStatus, string $introducedToStatus): array
    {
        $map = [
            self::CONNECTED.'-'.self::CONNECTED => [
                'overAllStatus' => self::CONNECTED,
                'introducedMessage' => self::CONNECTED,
                'introducedToMessage' => self::CONNECTED,
            ],
            self::DECLINE.'-'.self::DECLINE => [
                'overAllStatus' => self::DECLINE,
                'introducedMessage' => self::YOUDECLINE,
                'introducedToMessage' => self::YOUDECLINE,
            ],
            self::CONNECTED.'-'.self::PENDING => [
                'overAllStatus' => self::PARTIAL,
                'introducedMessage' => self::AWAITINGRESPONSE,
                'introducedToMessage' => self::NEWINTRODUCTION,
            ],
            self::PENDING.'-'.self::CONNECTED => [
                'overAllStatus' => self::PARTIAL,
                'introducedMessage' => self::NEWINTRODUCTION,
                'introducedToMessage' => self::AWAITINGRESPONSE,
            ],
            self::CONNECTED.'-'.self::DECLINE => [
                'overAllStatus' => self::PARTIAL,
                'introducedMessage' => self::NOMATCH,
                'introducedToMessage' => self::YOUDECLINE,
            ],
            self::DECLINE.'-'.self::CONNECTED => [
                'overAllStatus' => self::PARTIAL,
                'introducedMessage' => self::YOUDECLINE,
                'introducedToMessage' => self::NOMATCH,
            ],
            self::DECLINE.'-'.self::PENDING => [
                'overAllStatus' => self::PARTIAL,
                'introducedMessage' => self::YOUDECLINE,
                'introducedToMessage' => self::NEWINTRODUCTION,
            ],
            self::PENDING.'-'.self::DECLINE => [
                'overAllStatus' => self::PARTIAL,
                'introducedMessage' => self::NEWINTRODUCTION,
                'introducedToMessage' => self::YOUDECLINE,
            ],
            self::PENDING.'-'.self::PENDING => [
                'overAllStatus' => self::PENDING,
                'introducedMessage' => self::NEWINTRODUCTION,
                'introducedToMessage' => self::NEWINTRODUCTION,
            ],
        ];

        $key = $introducedStatus.'-'.$introducedToStatus;
        return $map[$key] ?? [
            'overAllStatus' => 'error',
            'introducedMessage' => 'Unexpected status',
            'introducedToMessage' => 'Unexpected status',
        ];
    }
}


