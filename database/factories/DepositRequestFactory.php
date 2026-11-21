<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DepositStatus;
use App\Models\DepositRequest;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DepositRequest> */
class DepositRequestFactory extends Factory
{
    protected $model = DepositRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(5_000, 500_000);

        return [
            'user_id' => User::factory()->approvedPoster(),
            'payment_method_id' => PaymentMethod::factory()->crypto(),
            'amount_requested_cents' => $amount,
            'credited_amount_cents' => null,
            'currency' => 'USD',
            'transfer_reference' => 'REF'.$this->faker->numerify('#########'),
            'proof_path' => 'deposits/'.$this->faker->uuid().'.png',
            'method_snapshot' => [
                'method_id' => 1,
                'label' => 'USDT — TRC-20',
                'type' => 'crypto',
                'crypto_asset' => 'USDT',
                'crypto_network' => 'TRC-20',
                'crypto_address' => 'TXyz1234567890abcdef',
            ],
            'status' => DepositStatus::PENDING,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DepositStatus::PENDING,
            'credited_amount_cents' => null,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => ['user_id' => $user->id]);
    }
}
