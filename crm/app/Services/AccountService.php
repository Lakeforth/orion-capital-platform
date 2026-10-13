<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class AccountService
{
    public function verifyStatus($user): array
    {
        if (in_array($user->status, ['banned', 'locked', 'suspended','unavailable'])) {
            return [
                'status' => false,
                'message' => $this->message()[$user->status]
            ];
        }
        return [
            'status' => true,
        ];
    }
    public function validateTransactionPin($user, $pin): array
    {
        if(!$user->pin){
            return [
                'status' => false,
                'message' => 'Pin not set'
            ];
        }
        if(!Hash::check($pin, $user->pin)){
            return [
                'status' => false,
                'message' => 'Invalid transaction pin'
            ];
        }

        return [ 'status' => true ];
    }

    public function message(): array
    {
        return [
            'banned' => 'Your account has been banned, please contact support',
            'locked' => 'Your account has been locked, please contact support',
            'suspended' => 'Your account has been locked, please contact support',
            'unavailable' => 'Your account is currently, please try again later',
        ];
    }


    public function getAuthUser()
    {

    }


}
