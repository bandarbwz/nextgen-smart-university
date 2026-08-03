<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\FoodCourtService;
use Tests\TestCase;

class FoodCourtRulesTest extends TestCase
{
    private FoodCourtService $foodCourt;

    private array $ownerUser;

    private array $customerUser;

    private int $restaurantId;

    private int $categoryId;

    private int $burgerId;

    private int $friesId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->foodCourt = new FoodCourtService();

        $ownerId = $this->createUser('Restaurant Owner', 'owner@test.edu', 'Restaurant Owner');
        $customerId = $this->createUser('Student', 'diner@test.edu', 'Hungry Student');

        $this->ownerUser = $this->actingAs($ownerId, 'Restaurant Owner');
        $this->customerUser = $this->actingAs($customerId, 'Student');

        $restaurant = $this->foodCourt->createRestaurant([
            'owner_id' => $ownerId,
            'restaurant_name' => 'Campus Grill',
            'phone' => '0123456789',
        ]);

        $this->restaurantId = (int) $restaurant['id'];

        $category = $this->foodCourt->createCategory($this->ownerUser, [
            'restaurant_id' => $this->restaurantId,
            'category_name' => 'Mains',
        ]);

        $this->categoryId = (int) $category['id'];

        $this->burgerId = (int) $this->addItem('Beef Burger', 12.50)['id'];
        $this->friesId = (int) $this->addItem('Fries', 4.25)['id'];
    }

    public function testTheOrderTotalIsComputedFromTheMenuNotTheRequest(): void
    {
        $order = $this->foodCourt->placeOrder($this->customerUser, [
            'restaurant_id' => $this->restaurantId,
            'items' => [
                ['menu_item_id' => $this->burgerId, 'quantity' => 2, 'unit_price' => 0.01, 'price' => 0.01],
                ['menu_item_id' => $this->friesId, 'quantity' => 1, 'subtotal' => 0.01],
            ],
        ]);

        $this->assertSame(
            '29.25',
            $order['total_amount'],
            'A tampered price in the request must be ignored: 2 x 12.50 + 4.25 = 29.25.'
        );
        $this->assertSame('12.50', $order['items'][0]['unit_price']);
    }

    public function testRepeatingAnItemMergesIntoASingleLine(): void
    {
        $order = $this->foodCourt->placeOrder($this->customerUser, [
            'restaurant_id' => $this->restaurantId,
            'items' => [
                ['menu_item_id' => $this->burgerId, 'quantity' => 1],
                ['menu_item_id' => $this->burgerId, 'quantity' => 2],
            ],
        ]);

        $this->assertCount(1, $order['items']);
        $this->assertSame(3, (int) $order['items'][0]['quantity']);
        $this->assertSame('37.50', $order['total_amount']);
    }

    public function testAnUnavailableItemCannotBeOrdered(): void
    {
        $this->foodCourt->updateMenuItem($this->burgerId, $this->ownerUser, [
            'item_name' => 'Beef Burger',
            'price' => 12.50,
            'availability' => 'unavailable',
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('currently unavailable');

        $this->placeSimpleOrder();
    }

    public function testAnItemFromAnotherRestaurantCannotBeAddedToTheOrder(): void
    {
        $otherOwnerId = $this->createUser('Restaurant Owner', 'other.owner@test.edu', 'Other Owner');
        $otherOwner = $this->actingAs($otherOwnerId, 'Restaurant Owner');

        $otherRestaurant = $this->foodCourt->createRestaurant([
            'owner_id' => $otherOwnerId,
            'restaurant_name' => 'Rival Cafe',
            'phone' => '0987654321',
        ]);

        $otherCategory = $this->foodCourt->createCategory($otherOwner, [
            'restaurant_id' => $otherRestaurant['id'],
            'category_name' => 'Drinks',
        ]);

        $otherItem = $this->foodCourt->createMenuItem($otherOwner, [
            'restaurant_id' => $otherRestaurant['id'],
            'category_id' => $otherCategory['id'],
            'item_name' => 'Iced Coffee',
            'price' => 6.00,
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not on this menu');

        $this->foodCourt->placeOrder($this->customerUser, [
            'restaurant_id' => $this->restaurantId,
            'items' => [['menu_item_id' => $otherItem['id'], 'quantity' => 1]],
        ]);
    }

    public function testAnInactiveRestaurantCannotTakeOrders(): void
    {
        $this->foodCourt->updateRestaurant($this->restaurantId, $this->ownerUser, [
            'restaurant_name' => 'Campus Grill',
            'phone' => '0123456789',
            'status' => 'inactive',
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not currently accepting orders');

        $this->placeSimpleOrder();
    }

    public function testAnEmptyOrderIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('at least one item');

        $this->foodCourt->placeOrder($this->customerUser, [
            'restaurant_id' => $this->restaurantId,
            'items' => [],
        ]);
    }

    public function testAZeroQuantityLineIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('quantity of at least one');

        $this->foodCourt->placeOrder($this->customerUser, [
            'restaurant_id' => $this->restaurantId,
            'items' => [['menu_item_id' => $this->burgerId, 'quantity' => 0]],
        ]);
    }

    public function testTheOrderStatusMachineFollowsTheDocumentedSequence(): void
    {
        $order = $this->placeSimpleOrder();
        $id = (int) $order['id'];

        foreach (['Accepted', 'Preparing', 'Ready', 'Completed'] as $status) {
            $updated = $this->foodCourt->updateOrderStatus($id, $this->ownerUser, $status);

            $this->assertSame($status, $updated['order_status']);
        }

        $this->assertNotNull(
            $this->foodCourt->order($id, $this->ownerUser)['completed_at'],
            'Completing an order should stamp completed_at.'
        );
    }

    public function testAnOrderCannotSkipAheadInTheStatusMachine(): void
    {
        $order = $this->placeSimpleOrder();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('cannot move from Pending to Ready');

        $this->foodCourt->updateOrderStatus((int) $order['id'], $this->ownerUser, 'Ready');
    }

    public function testACompletedOrderCannotChangeStatusAgain(): void
    {
        $order = $this->placeSimpleOrder();
        $id = (int) $order['id'];

        foreach (['Accepted', 'Preparing', 'Ready', 'Completed'] as $status) {
            $this->foodCourt->updateOrderStatus($id, $this->ownerUser, $status);
        }

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('cannot move from Completed');

        $this->foodCourt->updateOrderStatus($id, $this->ownerUser, 'Cancelled');
    }

    public function testACustomerCanCancelBeforePreparationStarts(): void
    {
        $order = $this->placeSimpleOrder();

        $cancelled = $this->foodCourt->cancelOrder(
            (int) $order['id'],
            $this->customerUser,
            'Changed my mind'
        );

        $this->assertSame('Cancelled', $cancelled['order_status']);
        $this->assertSame('Changed my mind', $cancelled['cancellation_reason']);
    }

    public function testACustomerCannotCancelOncePreparationHasStarted(): void
    {
        $order = $this->placeSimpleOrder();
        $id = (int) $order['id'];

        $this->foodCourt->updateOrderStatus($id, $this->ownerUser, 'Accepted');
        $this->foodCourt->updateOrderStatus($id, $this->ownerUser, 'Preparing');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('preparation has started');

        $this->foodCourt->cancelOrder($id, $this->customerUser, null);
    }

    public function testACustomerCannotReadAnotherCustomersOrder(): void
    {
        $order = $this->placeSimpleOrder();

        $otherId = $this->createUser('Student', 'nosy@test.edu', 'Nosy Student');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not found');

        $this->foodCourt->order((int) $order['id'], $this->actingAs($otherId, 'Student'));
    }

    public function testAnOwnerCannotChangeAnotherRestaurantsMenu(): void
    {
        $intruderId = $this->createUser('Restaurant Owner', 'intruder@test.edu', 'Intruder');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('your own restaurant');

        $this->foodCourt->updateMenuItem(
            $this->burgerId,
            $this->actingAs($intruderId, 'Restaurant Owner'),
            ['item_name' => 'Hijacked', 'price' => 0.01]
        );
    }

    public function testAnOwnerCannotMoveAnotherRestaurantsOrder(): void
    {
        $order = $this->placeSimpleOrder();

        $intruderId = $this->createUser('Restaurant Owner', 'intruder@test.edu', 'Intruder');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('your own restaurant');

        $this->foodCourt->updateOrderStatus(
            (int) $order['id'],
            $this->actingAs($intruderId, 'Restaurant Owner'),
            'Accepted'
        );
    }

    public function testOnlyACompletedOrderCanBeReviewed(): void
    {
        $order = $this->placeSimpleOrder();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('completed order');

        $this->foodCourt->submitReview($this->customerUser, [
            'order_id' => $order['id'],
            'rating' => 5,
        ]);
    }

    public function testACompletedOrderCanBeReviewedOnce(): void
    {
        $order = $this->completeOrder();

        $review = $this->foodCourt->submitReview($this->customerUser, [
            'order_id' => $order['id'],
            'rating' => 4,
            'comment' => 'Good burger',
        ]);

        $this->assertSame(4, (int) $review['rating']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already been reviewed');

        $this->foodCourt->submitReview($this->customerUser, [
            'order_id' => $order['id'],
            'rating' => 1,
        ]);
    }

    public function testAnotherCustomerCannotReviewSomeoneElsesOrder(): void
    {
        $order = $this->completeOrder();

        $otherId = $this->createUser('Student', 'freerider@test.edu', 'Free Rider');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not found');

        $this->foodCourt->submitReview($this->actingAs($otherId, 'Student'), [
            'order_id' => $order['id'],
            'rating' => 1,
        ]);
    }

    public function testStudentsOnlySeeActiveRestaurants(): void
    {
        $this->foodCourt->updateRestaurant($this->restaurantId, $this->ownerUser, [
            'restaurant_name' => 'Campus Grill',
            'phone' => '0123456789',
            'status' => 'suspended',
        ]);

        $this->assertSame([], $this->foodCourt->restaurants($this->customerUser));
    }

    public function testSalesReportCountsOnlyCompletedOrders(): void
    {
        $this->completeOrder();
        $this->placeSimpleOrder();

        $report = $this->foodCourt->salesReport($this->restaurantId, $this->ownerUser, null, null);

        $this->assertCount(1, $report['sales']);
        $this->assertSame(1, (int) $report['sales'][0]['order_count']);
        $this->assertSame('12.50', $report['sales'][0]['revenue']);
    }

    private function addItem(string $name, float $price): array
    {
        return $this->foodCourt->createMenuItem($this->ownerUser, [
            'restaurant_id' => $this->restaurantId,
            'category_id' => $this->categoryId,
            'item_name' => $name,
            'price' => $price,
        ]);
    }

    private function placeSimpleOrder(): array
    {
        return $this->foodCourt->placeOrder($this->customerUser, [
            'restaurant_id' => $this->restaurantId,
            'items' => [['menu_item_id' => $this->burgerId, 'quantity' => 1]],
        ]);
    }

    private function completeOrder(): array
    {
        $order = $this->placeSimpleOrder();
        $id = (int) $order['id'];

        foreach (['Accepted', 'Preparing', 'Ready', 'Completed'] as $status) {
            $this->foodCourt->updateOrderStatus($id, $this->ownerUser, $status);
        }

        return $this->foodCourt->order($id, $this->customerUser);
    }
}
