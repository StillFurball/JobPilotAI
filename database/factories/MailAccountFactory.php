<?php

namespace Database\Factories;

use App\Models\MailAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailAccount>
 */
class MailAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'gmail',
            'password' => 'abcdefghijklmnop',
        ];
    }
}
