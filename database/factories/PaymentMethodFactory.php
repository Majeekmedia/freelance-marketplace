<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentMethod> */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'label' => 'Payment method '.$this->faker->unique()->numberBetween(1, 9999),
            'type' => PaymentMethodType::BANK_TRANSFER,
            'is_enabled' => true,
            'sort_order' => 0,
        ];
    }

    /** A complete bank transfer method. */
    public function bank(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::BANK_TRANSFER,
            'bank_name' => 'GTBank',
            'account_name' => 'Trust-Prime Ltd',
            'account_number' => '0123456789',
            'routing_detail' => 'SWIFT GTBINGLA',
            'crypto_asset' => null,
            'crypto_network' => null,
            'crypto_address' => null,
        ]);
    }

    /** A complete crypto method. */
    public function crypto(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::CRYPTO,
            'crypto_asset' => 'USDT',
            'crypto_network' => 'TRC-20',
            'crypto_address' => 'TXyz1234567890abcdef',
            'bank_name' => null,
            'account_name' => null,
            'account_number' => null,
            'routing_detail' => null,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['is_enabled' => false]);
    }
}
