<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected $apiKey;
    protected $loopsApiUrl = 'https://app.loops.so/api/v1/transactional';

    public function __construct()
    {
        $this->apiKey = config('loops.api_key');
    }

    /**
     * Send email via Loops transactional API
     */
    protected function sendEmail(string $email, string $templateId, array $dataVariables): bool
    {
        if (!$this->apiKey) {
            Log::error('Loops API Key not configured.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->loopsApiUrl, [
                'transactionalId' => $templateId,
                'email' => $email,
                'dataVariables' => $dataVariables,
            ]);

            if ($response->successful()) {
                Log::info('Email sent successfully via Loops.', [
                    'email' => $email,
                    'templateId' => $templateId,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Failed to send email via Loops.', [
                    'email' => $email,
                    'templateId' => $templateId,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending email via Loops: ' . $e->getMessage(), [
                'email' => $email,
                'templateId' => $templateId
            ]);
            return false;
        }
    }

    /**
     * Send OTP email
     *
     * @param string $email
     * @param string $otp
     * @param string $username
     * @return bool
     */
    public function sendOtpEmail(string $email, string $otp, string $username): bool
    {
        $templateId = config('loops.templates.otp');
        
        if (!$templateId) {
            throw new \Exception('OTP template ID not configured');
        }

        $dataVariables = [
            'username' => $this->capitalizeEachWord($username),
            'verificationCode' => $otp,
        ];

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send password reset email
     *
     * @param string $email
     * @param string $resetUrl
     * @param string $username
     * @return bool
     */
    public function sendPasswordResetEmail(string $email, string $resetUrl, string $username): bool
    {
        $templateId = config('loops.templates.password_reset');
        
        if (!$templateId) {
            throw new \Exception('Password reset template ID not configured');
        }

        $dataVariables = [
            'username' => $this->capitalizeEachWord($username),
            'resetUrl' => $resetUrl,
        ];

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send login credentials from group email
     *
     * @param string $email
     * @param string $loginUrl
     * @param string $username
     * @return bool
     */
    public function sendLoginCredentialsFromGroupEmail(string $email, string $loginUrl, string $username): bool
    {
        $templateId = config('loops.templates.login_credentials_from_group');
        
        if (!$templateId) {
            throw new \Exception('Login credentials template ID not configured');
        }

        $dataVariables = [
            'username' => $this->capitalizeEachWord($username),
            'loginUrl' => $loginUrl,
        ];

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send welcome email
     *
     * @param string $email
     * @param string $username
     * @return bool
     */
    public function sendWelcomeEmail(string $email, string $username): bool
    {
        $templateId = config('loops.templates.welcome');
        
        if (!$templateId) {
            throw new \Exception('Welcome template ID not configured');
        }

        $dataVariables = [
            'username' => $this->capitalizeEachWord($username),
        ];

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send introduction email
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendIntroEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.intro');
        
        if (!$templateId) {
            throw new \Exception('Intro template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send introduction decline email to introducer
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendIntroduceFromEmailDecline(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.introduce_from_email_decline');
        
        if (!$templateId) {
            throw new \Exception('Introduce decline template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send introduction decline email to introduced person
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendIntroduceFromEmailDeclineIntroducedTo(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.introduce_from_email_decline_introduced_to');
        
        if (!$templateId) {
            throw new \Exception('Introduce decline introduced to template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send introduction accept email to introducer
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendIntroduceFromEmailAccept(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.introduce_from_email_accept');
        
        if (!$templateId) {
            throw new \Exception('Introduce accept template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send introduction accept email to introduced person
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendIntroduceFromEmailAcceptIntroducedTo(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.introduce_from_email_accept_introduced_to');
        
        if (!$templateId) {
            throw new \Exception('Introduce accept introduced to template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send referral reminder email
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendReminderEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.reminder');
        
        if (!$templateId) {
            throw new \Exception('Reminder template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send referral revoke email
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendRevokeEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.revoke');
        
        if (!$templateId) {
            throw new \Exception('Revoke template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send join group email to group creator
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendJoinGroupEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.join_group_email');
        
        if (!$templateId) {
            throw new \Exception('Join group email template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send group invite email
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendJoinGroupInviteEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.join_group_invite');
        
        if (!$templateId) {
            throw new \Exception('Join group invite template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send swap group request email
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendSwapGroupRequestEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.swap_group_request');
        
        if (!$templateId) {
            throw new \Exception('Swap group request template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send swap group request reject email
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendSwapGroupRequestRejectEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.swap_group_request_reject');
        
        if (!$templateId) {
            throw new \Exception('Swap group request reject template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Send referral group request email
     *
     * @param string $email
     * @param array $dataVariables
     * @return bool
     */
    public function sendReferralGroupRequestEmail(string $email, array $dataVariables): bool
    {
        $templateId = config('loops.templates.referral_group_request');
        
        if (!$templateId) {
            throw new \Exception('Referral group request template ID not configured');
        }

        return $this->sendEmail($email, $templateId, $dataVariables);
    }

    /**
     * Capitalize each word in a string
     *
     * @param string $text
     * @return string
     */
    private function capitalizeEachWord(string $text): string
    {
        return ucwords(strtolower($text));
    }
}