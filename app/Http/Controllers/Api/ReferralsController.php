<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referrals\UpdateIntroStatusRequest;
use App\Http\Requests\Referrals\UpdateRequestStatusQuery;
use App\Http\Requests\Referrals\SendReminderRequest;
use App\Services\ReferralsService;
use Illuminate\Http\Request;

class ReferralsController extends Controller
{
    public function __construct(private ReferralsService $referralsService) {}

    public function getUserReferrals(Request $request)
    {
        $page = (int)($request->query('page', 1));
        $limit = (int)($request->query('limit', 10));
        $status = $request->query('status');
        $search = $request->query('search');
        $filter = $request->query('filter', []);

        if (is_string($filter)) {
            $filter = json_decode($filter, true) ?: [];
        }
        if ($status) {
            $filter['status'] = $status;
            $filter['userId'] = $request->user()->id;
        }
        if ($search) {
            $filter['search'] = $search;
        }

        $result = $this->referralsService->getReferrals([
            'email' => $request->user()->email,
            'userId' => $request->user()->id,
            'page' => $page,
            'limit' => $limit,
            'filter' => $filter,
        ]);

        $total = $result['totalCount'];
        $totalPages = (int) ceil($total / max($limit, 1));

        return response()->json([
            'status' => 'success',
            'totalRecords' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit,
            'count' => count($result['data']),
            'data' => ['referrals' => $result['data']],
        ]);
    }

    public function updateStatus(string $introductionId, UpdateIntroStatusRequest $request)
    {
        $data = $this->referralsService->updateIntroductionStatusById(
            $introductionId,
            $request->user()->id,
            $request->validated()['status']
        );
        return response()->json([
            'status' => 'success',
            'message' => 'success',
            'data' => ['referrals' => $data],
        ]);
    }

    public function updateRequestStatus(string $introductionId, UpdateRequestStatusQuery $request)
    {
        $data = $this->referralsService->updateIntroductionRequestStatus(
            $introductionId,
            $request->validated()['status']
        );
        return response()->json([
            'status' => 'success',
            'message' => 'Request '.$request->validated()['status'].' successfully',
            'data' => ['referrals' => $data],
        ]);
    }

    public function getDetail(string $introductionId, Request $request)
    {
        $data = $this->referralsService->getIntroductionDetailsById(
            $introductionId,
            $request->user()->id
        );
        return response()->json([
            'status' => 'success',
            'message' => 'success',
            'data' => ['referrals' => $data],
        ]);
    }

    public function sendReminder(string $introductionId, SendReminderRequest $request)
    {
        $this->referralsService->sendReminderForReferral(
            $introductionId,
            $request->user()->id,
            $request->validated()['message']
        );
        return response()->json([
            'status' => 'success',
            'message' => 'Reminder email sent to user.',
        ]);
    }

    public function revokeReferral(string $introductionId, Request $request)
    {
        $revoke = filter_var($request->query('revoke', true), FILTER_VALIDATE_BOOLEAN);
        $resp = $this->referralsService->revokeReferral(
            $introductionId,
            $request->user()->id,
            $revoke
        );
        return response()->json([
            'status' => 'success',
            'message' => $resp['message'],
        ]);
    }
}


