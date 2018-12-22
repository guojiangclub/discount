<?php

namespace iBrand\Component\Discount\Test;

use Carbon\Carbon;
use Faker\Factory;
use iBrand\Component\Discount\Actions\OrderFixedDiscountAction;
use iBrand\Component\Discount\Actions\OrderPercentageDiscountAction;
use iBrand\Component\Discount\Checkers\CartQuantityRuleChecker;
use iBrand\Component\Discount\Checkers\ItemTotalRuleChecker;
use iBrand\Component\Discount\Contracts\AdjustmentContract;
use iBrand\Component\Discount\Models\Action;
use iBrand\Component\Discount\Models\Coupon;
use iBrand\Component\Discount\Models\Discount;
use iBrand\Component\Discount\Models\Rule;
use iBrand\Component\Discount\Repositories\DiscountRepository;
use iBrand\Component\Discount\Test\Models\Adjustment;
use iBrand\Component\Discount\Test\Models\User;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * Class BaseTest
 * @package iBrand\Component\Discount\Test
 */
abstract class BaseTest extends TestCase
{
    use DatabaseMigrations;

    protected $user;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->loadMigrationsFrom(__DIR__ . '/database');

        $this->seedData();

        $this->app->bind(AdjustmentContract::class,Adjustment::class);
    }

    /**
     * @param $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('repository.cache.enabled', true);

    }

    /**
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Prettus\Repository\Providers\RepositoryServiceProvider::class,
            \Orchestra\Database\ConsoleServiceProvider::class,
            \iBrand\Component\Discount\Providers\DiscountServiceProvider::class
        ];
    }

    /**
     *
     */
    protected function seedData()
    {
        $faker = Factory::create('zh_CN');
        $this->seedDiscount($faker);
        $this->seedCoupon($faker);

        $this->user = new User([
            'id' => 1,
            'name' => 'ibrand'
        ]);

        $this->seedUserCoupon($faker);
    }


    /**
     * @param $faker
     */
    protected function seedDiscount($faker)
    {
//创建一个未开始的活动
        Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(3, 100),
            'used' => $faker->randomDigitNotNull,
            'starts_at' => Carbon::now()->addDay(1),
            'ends_at' => Carbon::now()->addDay(2),
        ]);

        //创建一个状态未禁用的活动
        Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(3, 100),
            'used' => $faker->randomDigitNotNull,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'status' => 0,
        ]);

        //创建一个已经全部用完活动
        Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => 100,
            'used' => 100,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'status' => 0,
        ]);

        //创建一个有效的优惠活动,订单满数量减
        $discount = Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(80, 100),
            'used' => 20,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
        ]);
        //购物车数量满2,则减去10元
        Rule::create(['discount_id' => $discount->id, 'type' => CartQuantityRuleChecker::TYPE, 'configuration' => json_encode(['count' => 2])]);
        Action::create(['discount_id' => $discount->id, 'type' => OrderFixedDiscountAction::TYPE, 'configuration' => json_encode(['amount' => 10])]);

        //创建一个有效的优惠活动,订单满金额减
        $discount = Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(80, 100),
            'used' => 20,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
        ]);
        //订单满100-10
        Rule::create(['discount_id' => $discount->id, 'type' => ItemTotalRuleChecker::TYPE, 'configuration' => json_encode(['amount' => 100])]);
        Action::create(['discount_id' => $discount->id, 'type' => OrderFixedDiscountAction::TYPE, 'configuration' => json_encode(['amount' => 10])]);


        //创建一个有效的优惠活动,订单满金额打折
        $discount = Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(80, 100),
            'used' => 20,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
        ]);
        //订单满120打8折
        Rule::create(['discount_id' => $discount->id, 'type' => ItemTotalRuleChecker::TYPE, 'configuration' => json_encode(['amount' => 120])]);
        Action::create(['discount_id' => $discount->id, 'type' => OrderPercentageDiscountAction::TYPE, 'configuration' => json_encode(['percentage' => 80])]);
    }

    /**
     * @param $faker
     */
    protected function seedCoupon($faker)
    {
        //创建一个未开始的优惠券
        Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(3, 100),
            'used' => $faker->randomDigitNotNull,
            'starts_at' => Carbon::now()->addDay(1),
            'ends_at' => Carbon::now()->addDay(2),
            'coupon_based' => 1,
        ]);

        //创建一个状态禁用的优惠券
        Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(3, 100),
            'used' => $faker->randomDigitNotNull,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'status' => 0,
            'coupon_based' => 1,
        ]);

        //创建一个已经全部用完优惠券
        Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => 100,
            'used' => 100,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'status' => 0,
            'coupon_based' => 1,
        ]);

        //创建一个有效的优惠优惠券,订单满数量减
        $discount = Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(80, 100),
            'used' => 20,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'coupon_based' => 1,
        ]);
        //购物车数量满2,则减去10元
        Rule::create(['discount_id' => $discount->id, 'type' => CartQuantityRuleChecker::TYPE, 'configuration' => json_encode(['count' => 2])]);
        Action::create(['discount_id' => $discount->id, 'type' => OrderFixedDiscountAction::TYPE, 'configuration' => json_encode(['amount' => 10])]);

        //创建一个有效的优惠优惠券,订单满金额减
        $discount = Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(80, 100),
            'used' => 20,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'coupon_based' => 1,
        ]);
        //订单满100-10
        Rule::create(['discount_id' => $discount->id, 'type' => ItemTotalRuleChecker::TYPE, 'configuration' => json_encode(['amount' => 100])]);
        Action::create(['discount_id' => $discount->id, 'type' => OrderFixedDiscountAction::TYPE, 'configuration' => json_encode(['amount' => 10])]);

        //创建一个有效的优惠优惠券,订单满金额打折
        $discount = Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(80, 100),
            'used' => 20,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'coupon_based' => 1,
        ]);
        //订单满100打8折
        Rule::create(['discount_id' => $discount->id, 'type' => ItemTotalRuleChecker::TYPE, 'configuration' => json_encode(['amount' => 120])]);
        Action::create(['discount_id' => $discount->id, 'type' => OrderFixedDiscountAction::TYPE, 'configuration' => json_encode(['percentage' => 80])]);


        //创建一个有效的优惠券,订单满金额打折
        $discount = Discount::create([
            'title' => $faker->word,
            'label' => $faker->word,
            'usage_limit' => $faker->numberBetween(80, 100),
            'used' => 20,
            'starts_at' => Carbon::now()->addDay(-1),
            'ends_at' => Carbon::now()->addDay(2),
            'coupon_based' => 1,
        ]);
        //订单数量满3件打9折
        Rule::create(['discount_id' => $discount->id, 'type' => CartQuantityRuleChecker::TYPE, 'configuration' => json_encode(['count' => 3])]);
        Action::create(['discount_id' => $discount->id, 'type' => OrderFixedDiscountAction::TYPE, 'configuration' => json_encode(['percentage' => 90])]);
    }

    protected function seedUserCoupon($faker)
    {
        $repository =$this->app->make(DiscountRepository::class);

        //get active discount coupons
        $discounts = $repository->findActive(1);

        //生成20张有效券
        for ($i=0;$i<20;$i++){
            Coupon::create(['discount_id'=>$discounts->random()->id,'user_id'=>$this->user->id,]);
        }

    }

}
