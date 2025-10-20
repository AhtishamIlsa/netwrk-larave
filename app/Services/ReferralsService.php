<?php

namespace App\Services;

use App\Models\Introduction;
use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReferralsService
{
    public function getReferrals(array $args): array
    {
        $page = (int)($args['page'] ?? 1);
        $limit = (int)($args['limit'] ?? 10);
        $email = $args['email'] ?? null;
        $filter = $args['filter'] ?? [];

        $query = Introduction::query();
        if ($email) {
            $query->where(function ($q) use ($email) {
                $q->where('introduced_from_email', $email)
                    ->orWhere('introduced_email', $email)
                    ->orWhere('introduced_to_email', $email);
            });
        }

        // Status filtering semantics matching NestJS
        if (!empty($filter) && isset($filter['status']) && isset($filter['userId'])) {
            $normalizedStatus = strtolower(trim((string)$filter['status']));
            $userId = $filter['userId'];

            $query->where(function ($q) use ($normalizedStatus, $userId) {
                if ($normalizedStatus === ReferralStatusBuilder::PENDING) {
                    $q->where('over_all_status', ReferralStatusBuilder::PENDING)
                        ->where('introduced_from_id', $userId);
                } elseif ($normalizedStatus === ReferralStatusBuilder::CONNECTED) {
                    $q->where(function ($q2) use ($userId) {
                        $q2->where(function ($q3) use ($userId) {
                            $q3->where('over_all_status', ReferralStatusBuilder::CONNECTED)
                               ->where('introduced_from_id', $userId);
                        })
                        ->orWhere(function ($q3) use ($userId) {
                            $q3->where('introduced_message', 'connected')
                               ->where('introduced_id', $userId);
                        })
                        ->orWhere(function ($q3) use ($userId) {
                            $q3->where('introduced_to_message', 'connected')
                               ->where('introduced_to_id', $userId);
                        });
                    });
                } elseif ($normalizedStatus === ReferralStatusBuilder::NEWINTRODUCTION) {
                    $q->where(function ($q2) use ($userId) {
                        $q2->where(function ($q3) use ($userId) {
                            $q3->where('introduced_status', ReferralStatusBuilder::NEWINTRODUCTION)
                               ->where('introduced_id', $userId);
                        })
                        ->orWhere(function ($q3) use ($userId) {
                            $q3->where('introduced_to_status', ReferralStatusBuilder::NEWINTRODUCTION)
                               ->where('introduced_to_id', $userId);
                        });
                    });
                } elseif (in_array($normalizedStatus, [ReferralStatusBuilder::AWAITINGRESPONSE, ReferralStatusBuilder::NOMATCH], true)) {
                    $q->where(function ($q2) use ($userId) {
                        $q2->where(function ($q3) use ($userId) {
                            $q3->where('introduced_to_message', ReferralStatusBuilder::AWAITINGRESPONSE)
                               ->where('introduced_to_id', $userId);
                        })
                        ->orWhere(function ($q3) use ($userId) {
                            $q3->where('introduced_message', ReferralStatusBuilder::AWAITINGRESPONSE)
                               ->where('introduced_id', $userId);
                        });
                    });
                } else {
                    $q->where(function ($q2) use ($userId) {
                        $q2->where(function ($q3) use ($userId) {
                            $q3->where('over_all_status', ReferralStatusBuilder::PENDING)
                               ->where('introduced_from_id', $userId);
                        })
                        ->orWhere(function ($q3) use ($userId) {
                            $q3->where('introduced_status', ReferralStatusBuilder::PENDING)
                               ->where('introduced_id', $userId);
                        })
                        ->orWhere(function ($q3) use ($userId) {
                            $q3->where('introduced_to_status', ReferralStatusBuilder::PENDING)
                               ->where('introduced_to_id', $userId);
                        });
                    });
                }
            });
        } elseif (!empty($filter) && !empty($filter['search'])) {
            $words = preg_split('/\s+/', strtolower(trim((string)$filter['search'])));
            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere(DB::raw('LOWER(introduced_first_name)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(introduced_last_name)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(introduced_to_first_name)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(introduced_to_last_name)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(introduced_from_first_name)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(introduced_from_last_name)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(over_all_status)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(introduced_to_status)'), 'like', "%$word%")
                      ->orWhere(DB::raw('LOWER(introduced_status)'), 'like', "%$word%");
                }
            });
        }

        $totalCount = (clone $query)->count();
        $records = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $data = $records->map(function (Introduction $intro) {
            return $this->formatIntroduction($intro);
        })->values()->all();

        return [
            'data' => $data,
            'totalCount' => $totalCount,
        ];
    }

    public function getIntroductionDetailsById(string $introductionId, string $userId): array
    {
        $intro = Introduction::query()
            ->where('id', $introductionId)
            ->where(function ($q) use ($userId) {
                $q->where('introduced_from_id', $userId)
                  ->orWhere('introduced_id', $userId)
                  ->orWhere('introduced_to_id', $userId);
            })
            ->firstOrFail();

        return $this->formatIntroduction($intro, $userId);
    }

    public function updateIntroductionStatusById(string $introductionId, string $userId, string $status): array
    {
        $intro = Introduction::query()
            ->where('id', $introductionId)
            ->firstOrFail();

        if ($intro->introduced_id === $userId) {
            $response = ReferralStatusBuilder::getStatusResult($status, $intro->introduced_to_status);
            $intro->introduced_status = $status;
            $intro->introduced_is_attempt = true;
            $intro->introduced_message = $response['introducedMessage'];
        } elseif ($intro->introduced_to_id === $userId) {
            $response = ReferralStatusBuilder::getStatusResult($intro->introduced_status, $status);
            $intro->introduced_to_status = $status;
            $intro->introduced_to_is_attempt = true;
            $intro->introduced_to_message = $response['introducedToMessage'];
        } else {
            abort(404, 'User is not part of this introduction');
        }

        $intro->over_all_status = $response['overAllStatus'];
        $intro->save();

        if ($intro->over_all_status === ReferralStatusBuilder::CONNECTED) {
            $this->createReciprocalContactsOnConnected($intro);
        }

        return $this->formatIntroduction($intro->fresh());
    }

    public function updateIntroductionRequestStatus(string $introductionId, string $status): array
    {
        $intro = Introduction::query()->findOrFail($introductionId);

        $intro->request_status = $status;
        $intro->save();

        return $this->formatIntroduction($intro->fresh());
    }

    public function sendReminderForReferral(string $introductionId, string $userId, string $message): array
    {
        $intro = Introduction::query()
            ->where('id', $introductionId)
            ->where(function ($q) use ($userId) {
                $q->where('introduced_from_id', $userId)
                  ->orWhere('introduced_id', $userId)
                  ->orWhere('introduced_to_id', $userId);
            })
            ->firstOrFail();

        $intro->reminder_message = $message;
        $intro->save();

        return ['message' => 'Reminder email sent to user.'];
    }

    public function revokeReferral(string $introductionId, string $userId, bool $revoke): array
    {
        $intro = Introduction::query()
            ->where('id', $introductionId)
            ->where('introduced_from_id', $userId)
            ->firstOrFail();

        if ($intro->revoke) {
            abort(400, 'You already revoke this referral');
        }

        $intro->revoke = $revoke;
        $intro->save();

        return ['message' => 'Referral revoked successfully'];
    }

    private function createReciprocalContactsOnConnected(Introduction $intro): void
    {
        // Create the introduced (A) as contact for introduced_to (B)
        if ($intro->introduced_to_id) {
            Contact::firstOrCreate(
                [
                    'user_id' => $intro->introduced_to_id,
                    'email' => $intro->introduced_email,
                ],
                [
                    'first_name' => $intro->introduced_first_name,
                    'last_name' => $intro->introduced_last_name,
                ]
            );
        }

        // Create the introduced_to (B) as contact for introduced (A)
        if ($intro->introduced_id) {
            Contact::firstOrCreate(
                [
                    'user_id' => $intro->introduced_id,
                    'email' => $intro->introduced_to_email,
                ],
                [
                    'first_name' => $intro->introduced_to_first_name,
                    'last_name' => $intro->introduced_to_last_name,
                ]
            );
        }
    }

    private function formatIntroduction(Introduction $intro, ?string $userId = null): array
    {
        $introduced = [
            'id' => $intro->introduced_id,
            'email' => $intro->introduced_email,
            'name' => trim(($intro->introduced_first_name ?? '').' '.($intro->introduced_last_name ?? '')),
            'firstName' => $intro->introduced_first_name,
            'lastName' => $intro->introduced_last_name,
            'introducedStatus' => $intro->introduced_status,
            'introducedIsAttempt' => (bool) $intro->introduced_is_attempt,
            'introducedMessage' => $intro->introduced_message,
        ];
        $introducedTo = [
            'id' => $intro->introduced_to_id,
            'email' => $intro->introduced_to_email,
            'name' => trim(($intro->introduced_to_first_name ?? '').' '.($intro->introduced_to_last_name ?? '')),
            'firstName' => $intro->introduced_to_first_name,
            'lastName' => $intro->introduced_to_last_name,
            'introducedToStatus' => $intro->introduced_to_status,
            'introducedToIsAttempt' => (bool) $intro->introduced_to_is_attempt,
            'introducedToMessage' => $intro->introduced_to_message,
        ];
        $introducedFrom = [
            'id' => $intro->introduced_from_id,
            'email' => $intro->introduced_from_email,
            'name' => trim(($intro->introduced_from_first_name ?? '').' '.($intro->introduced_from_last_name ?? '')),
            'firstName' => $intro->introduced_from_first_name,
            'lastName' => $intro->introduced_from_last_name,
        ];

        $result = [
            'introduced' => $introduced,
            'introducedTo' => $introducedTo,
            'introducedFrom' => $introducedFrom,
            'message' => $intro->message,
            'reminder_message' => $intro->reminder_message,
            'created_at' => $intro->created_at?->toIso8601String(),
            'overAllStatus' => $intro->over_all_status,
            'introductionId' => $intro->id,
            'revoke' => (bool) $intro->revoke,
        ];

        if (!is_null($intro->request_status)) {
            $result['requestStatus'] = $intro->request_status;
        }

        return $result;
    }
}


