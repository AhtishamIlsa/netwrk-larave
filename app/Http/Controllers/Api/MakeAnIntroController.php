<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MakeAnIntro\MakeAnIntroRequest;
use App\Models\Introduction;
use App\Models\User;
use Illuminate\Http\Request;

class MakeAnIntroController extends Controller
{
    public function validation(Request $request, MakeAnIntroRequest $validated)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Validation is succeed',
        ]);
    }

    public function create(Request $request, MakeAnIntroRequest $validated)
    {
        $data = $validated->validated();

        $from = $data['from'];
        $introduce = $data['introduce'];
        $recipients = $data['to'];
        $message = $data['message'] ?? '';

        $groupId = $request->query('groupId');
        $requestStatus = $groupId ? 'pending' : null;

        $fromEmail = $from['email'] ?? null;
        $fromId = $from['id'] ?? null;
        if (!$fromId && $fromEmail) {
            $fromId = optional(User::where('email', $fromEmail)->first())->id;
        }

        $introduceEmail = $introduce['email'] ?? null;
        $introduceId = $introduce['id'] ?? null;
        if (!$introduceId && $introduceEmail) {
            $introduceId = optional(User::where('email', $introduceEmail)->first())->id;
        }

        $saved = [];
        foreach ($recipients as $recipient) {
            $toEmail = $recipient['email'] ?? null;
            $toId = $recipient['id'] ?? null;
            if (!$toId && $toEmail) {
                $toId = optional(User::where('email', $toEmail)->first())->id;
            }

            $intro = Introduction::create([
                'introduced_from_id' => $fromId,
                'introduced_from_email' => $fromEmail,
                'introduced_from_first_name' => $from['firstName'] ?? null,
                'introduced_from_last_name' => $from['lastName'] ?? null,

                'introduced_id' => $introduceId,
                'introduced_email' => $introduceEmail,
                'introduced_first_name' => $introduce['firstName'] ?? null,
                'introduced_last_name' => $introduce['lastName'] ?? null,
                'introduced_status' => 'pending',
                'introduced_is_attempt' => false,
                'introduced_message' => 'new introduction',

                'introduced_to_id' => $toId,
                'introduced_to_email' => $toEmail,
                'introduced_to_first_name' => $recipient['firstName'] ?? null,
                'introduced_to_last_name' => $recipient['lastName'] ?? null,
                'introduced_to_status' => 'pending',
                'introduced_to_is_attempt' => false,
                'introduced_to_message' => 'new introduction',

                'over_all_status' => 'pending',
                'request_status' => $requestStatus,
                'message' => $message,
            ]);

            $saved[] = [
                'introduced' => [
                    'id' => $intro->introduced_id,
                    'email' => $intro->introduced_email,
                    'name' => trim(($intro->introduced_first_name ?? '').' '.($intro->introduced_last_name ?? '')),
                    'firstName' => $intro->introduced_first_name,
                    'lastName' => $intro->introduced_last_name,
                    'introducedStatus' => $intro->introduced_status,
                    'introducedIsAttempt' => (bool) $intro->introduced_is_attempt,
                    'introducedMessage' => $intro->introduced_message,
                ],
                'introducedTo' => [
                    'id' => $intro->introduced_to_id,
                    'email' => $intro->introduced_to_email,
                    'name' => trim(($intro->introduced_to_first_name ?? '').' '.($intro->introduced_to_last_name ?? '')),
                    'firstName' => $intro->introduced_to_first_name,
                    'lastName' => $intro->introduced_to_last_name,
                    'introducedToStatus' => $intro->introduced_to_status,
                    'introducedToIsAttempt' => (bool) $intro->introduced_to_is_attempt,
                    'introducedToMessage' => $intro->introduced_to_message,
                ],
                'message' => $intro->message,
                'created_at' => $intro->created_at?->toIso8601String(),
                'overAllStatus' => $intro->over_all_status,
                'introducedFrom' => [
                    'id' => $intro->introduced_from_id,
                    'email' => $intro->introduced_from_email,
                    'name' => trim(($intro->introduced_from_first_name ?? '').' '.($intro->introduced_from_last_name ?? '')),
                    'firstName' => $intro->introduced_from_first_name,
                    'lastName' => $intro->introduced_from_last_name,
                ],
                'introductionId' => $intro->id,
            ];
        }

        $pageNumber = 1; $limitNumber = 10;
        $totalCount = Introduction::where('introduced_from_email', $fromEmail)->count();
        $totalPages = (int) ceil($totalCount / max($limitNumber, 1));
        $messageText = 'Intro sent successfully';

        return response()->json([
            'status' => 'success',
            'totalRecords' => $totalCount,
            'totalPages' => $totalPages,
            'currentPage' => $pageNumber,
            'limit' => $limitNumber,
            'count' => count($saved),
            'data' => ['referrals' => $saved],
            'message' => $messageText,
            'userExist' => true,
        ]);
    }
}


