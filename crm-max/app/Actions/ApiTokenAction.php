<?php

namespace App\Actions;

use App\Models\Token;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ApiTokenAction
{
    public function createToken(string $userId, int $yearToExpire = 4)
    {
        $token = Str::uuid();
        $expiresAt = Carbon::now()->addYears($yearToExpire);

        return Token::create([
            'user_id' => $userId,
            'token' => str_replace('-',mt_rand(1,9),$token),
            'expires_at' => $expiresAt,
        ]);
    }
}
