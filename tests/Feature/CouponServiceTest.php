<?php

declare(strict_types=1);

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(CouponService::class);
    $this->user = User::factory()->create();
    $this->plan = Plan::factory()->create([
        'price' => 100,
        'currency' => 'USD',
    ]);
});

describe('CouponService::validateCoupon', function (): void {
    it('validates a valid percentage coupon', function (): void {
        $coupon = Coupon::factory()->percentage(20)->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeTrue();
        expect($result['coupon']->id)->toBe($coupon->id);
        expect($result['discount'])->toBe(20.0);
        expect($result['original_amount'])->toBe(100.0);
        expect($result['final_amount'])->toBe(80.0);
    });

    it('validates a valid fixed amount coupon', function (): void {
        $coupon = Coupon::factory()->fixedAmount(15.00, 'USD')->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeTrue();
        expect($result['discount'])->toBe(15.0);
        expect($result['final_amount'])->toBe(85.0);
    });

    it('validates a trial extension coupon', function (): void {
        $coupon = Coupon::factory()->trialExtension(7)->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeTrue();
        expect($result['trial_days'])->toBe(7);
    });

    it('rejects non-existent coupon code', function (): void {
        $result = $this->service->validateCoupon(
            'NOTEXIST',
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toBe('Coupon code not found');
    });

    it('rejects expired coupon', function (): void {
        $coupon = Coupon::factory()->expired()->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toBe('This coupon has expired');
    });

    it('rejects inactive coupon', function (): void {
        $coupon = Coupon::factory()->inactive()->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toBe('This coupon is not currently active');
    });

    it('rejects exhausted coupon', function (): void {
        $coupon = Coupon::factory()->exhausted()->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toBe('This coupon has reached its maximum redemptions');
    });

    it('rejects coupon not applicable to selected plan', function (): void {
        $otherPlan = Plan::factory()->create();
        $coupon = Coupon::factory()->create();
        $coupon->plans()->attach($otherPlan);

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toBe('This coupon is not valid for the selected plan');
    });

    it('accepts coupon valid for all plans when no restrictions', function (): void {
        $coupon = Coupon::factory()->create();
        // No plans attached = valid for all

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeTrue();
    });

    it('rejects coupon when user has exceeded per-user limit', function (): void {
        $coupon = Coupon::factory()->create([
            'max_redemptions_per_user' => 1,
        ]);

        // Create a redemption for this user
        $coupon->redemptions()->create([
            'user_id' => $this->user->id,
            'order_id' => Order::factory()->create(['user_id' => $this->user->id])->id,
            'discount_amount' => 10,
            'original_amount' => 100,
            'final_amount' => 90,
            'currency' => 'USD',
        ]);

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toBe('You have already used this coupon');
    });

    it('rejects first-time customer coupon for existing customers', function (): void {
        $coupon = Coupon::factory()->firstTimeOnly()->create();

        // Create an order for this user
        Order::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toBe('This coupon is only valid for first-time customers');
    });

    it('accepts first-time customer coupon for new customers', function (): void {
        $coupon = Coupon::factory()->firstTimeOnly()->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeTrue();
    });

    it('rejects coupon when order amount is below minimum', function (): void {
        $coupon = Coupon::factory()->minimumOrder(200.00)->create();

        $result = $this->service->validateCoupon(
            $coupon->code,
            $this->user,
            $this->plan, // Plan price is 100
            'stripe',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toContain('Minimum order amount');
    });

    it('handles case-insensitive coupon codes', function (): void {
        $coupon = Coupon::factory()->create(['code' => 'TESTCODE']);

        $result = $this->service->validateCoupon(
            'testcode',
            $this->user,
            $this->plan,
            'stripe',
        );

        expect($result['valid'])->toBeTrue();
    });
});

describe('CouponService::redeemCoupon', function (): void {
    it('creates redemption record', function (): void {
        $coupon = Coupon::factory()->percentage(20)->create();
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $redemption = $this->service->redeemCoupon(
            $coupon,
            $this->user,
            $order,
            100.0,
            20.0,
            'USD',
        );

        expect($redemption)->not->toBeNull();
        expect($redemption->coupon_id)->toBe($coupon->id);
        expect($redemption->user_id)->toBe($this->user->id);
        expect($redemption->order_id)->toBe($order->id);
        expect((float) $redemption->discount_amount)->toBe(20.0);
        expect((float) $redemption->original_amount)->toBe(100.0);
        expect((float) $redemption->final_amount)->toBe(80.0);
    });
});

describe('CouponService::generateCode', function (): void {
    it('generates unique coupon codes', function (): void {
        $codes = [];

        for ($i = 0; $i < 10; $i++) {
            $codes[] = $this->service->generateCode();
        }

        expect(array_unique($codes))->toHaveCount(10);
    });

    it('generates codes of specified length', function (): void {
        $code = $this->service->generateCode(12);

        expect(mb_strlen($code))->toBe(12);
    });
});

describe('CouponService::createCoupon', function (): void {
    it('creates a coupon with provided data', function (): void {
        $data = [
            'code' => 'NEWCODE',
            'name' => 'New Coupon',
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => 15,
            'is_active' => true,
        ];

        $coupon = $this->service->createCoupon($data);

        expect($coupon)->not->toBeNull();
        expect($coupon->code)->toBe('NEWCODE');
        expect($coupon->name)->toBe('New Coupon');
    });

    it('attaches plans when plan_ids provided', function (): void {
        $plan1 = Plan::factory()->create();
        $plan2 = Plan::factory()->create();

        $data = [
            'code' => 'PLANCODE',
            'name' => 'Plan Restricted',
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => 10,
            'plan_ids' => [$plan1->id, $plan2->id],
        ];

        $coupon = $this->service->createCoupon($data);

        expect($coupon->plans)->toHaveCount(2);
    });
});

describe('CouponService::toggleActive', function (): void {
    it('toggles coupon active status', function (): void {
        $coupon = Coupon::factory()->create(['is_active' => true]);

        $result = $this->service->toggleActive($coupon);
        expect($result->is_active)->toBeFalse();

        $result = $this->service->toggleActive($result);
        expect($result->is_active)->toBeTrue();
    });
});
