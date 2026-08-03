<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\FoodCategory;
use App\Models\FoodOrder;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use Throwable;

class FoodCourtService
{
    /**
     * Allowed forward transitions. An order can also be cancelled from any state
     * that has not yet reached preparation, which is handled separately.
     */
    private const NEXT_STATUS = [
        'Pending' => ['Accepted', 'Cancelled'],
        'Accepted' => ['Preparing', 'Cancelled'],
        'Preparing' => ['Ready'],
        'Ready' => ['Completed'],
        'Completed' => [],
        'Cancelled' => [],
    ];

    private const CANCELLABLE_BY_CUSTOMER = ['Pending', 'Accepted'];

    public function __construct(
        private readonly Restaurant $restaurants = new Restaurant(),
        private readonly FoodCategory $categories = new FoodCategory(),
        private readonly MenuItem $menu = new MenuItem(),
        private readonly FoodOrder $orders = new FoodOrder(),
        private readonly RestaurantReview $reviews = new RestaurantReview()
    ) {
    }

    public function restaurants(array $user): array
    {
        if ($user['role'] === 'Restaurant Owner') {
            return $this->restaurants->forOwner($user['user_id']);
        }

        return $this->restaurants->listing(
            $user['role'] === 'Administrator' ? null : 'active'
        );
    }

    public function restaurant(int $id, array $user): array
    {
        $restaurant = $this->restaurants->find($id);

        if ($restaurant === null) {
            throw new ApiException('Restaurant not found.', 404);
        }

        if ($restaurant['status'] !== 'active' && !$this->canManage($restaurant, $user)) {
            throw new ApiException('Restaurant not found.', 404);
        }

        $restaurant['categories'] = $this->categories->forRestaurant($id);
        $restaurant['menu'] = $this->menu->forRestaurant($id, !$this->canManage($restaurant, $user));

        return $restaurant;
    }

    public function createRestaurant(array $fields): array
    {
        $id = $this->restaurants->create([
            'owner_id' => (int) $fields['owner_id'],
            'restaurant_name' => $fields['restaurant_name'],
            'description' => $fields['description'] ?? null,
            'location' => $fields['location'] ?? null,
            'phone' => $fields['phone'],
            'opening_time' => $fields['opening_time'] ?? null,
            'closing_time' => $fields['closing_time'] ?? null,
            'status' => $fields['status'] ?? 'active',
        ]);

        return $this->restaurants->find($id);
    }

    public function updateRestaurant(int $id, array $user, array $fields): array
    {
        $restaurant = $this->requireManageable($id, $user);

        $this->restaurants->update($id, [
            'restaurant_name' => $fields['restaurant_name'],
            'description' => $fields['description'] ?? null,
            'location' => $fields['location'] ?? null,
            'phone' => $fields['phone'],
            'opening_time' => $fields['opening_time'] ?? null,
            'closing_time' => $fields['closing_time'] ?? null,
            'status' => $fields['status'] ?? $restaurant['status'],
        ]);

        return $this->restaurants->find($id);
    }

    public function deleteRestaurant(int $id): void
    {
        if (!$this->restaurants->exists($id)) {
            throw new ApiException('Restaurant not found.', 404);
        }

        $this->restaurants->delete($id);
    }

    public function categories(int $restaurantId): array
    {
        return $this->categories->forRestaurant($restaurantId);
    }

    public function createCategory(array $user, array $fields): array
    {
        $restaurantId = (int) $fields['restaurant_id'];

        $this->requireManageable($restaurantId, $user);

        $id = $this->categories->create([
            'restaurant_id' => $restaurantId,
            'category_name' => $fields['category_name'],
            'description' => $fields['description'] ?? null,
        ]);

        return $this->categories->find($id);
    }

    public function deleteCategory(int $id, array $user): void
    {
        $category = $this->categories->find($id);

        if ($category === null) {
            throw new ApiException('Category not found.', 404);
        }

        $this->requireManageable((int) $category['restaurant_id'], $user);

        if ($this->categories->hasItems($id)) {
            throw new ApiException('This category still has menu items and cannot be deleted.', 409);
        }

        $this->categories->delete($id);
    }

    public function menu(int $restaurantId, array $user): array
    {
        $restaurant = $this->restaurants->find($restaurantId);

        if ($restaurant === null) {
            throw new ApiException('Restaurant not found.', 404);
        }

        return $this->menu->forRestaurant($restaurantId, !$this->canManage($restaurant, $user));
    }

    public function createMenuItem(array $user, array $fields): array
    {
        $restaurantId = (int) $fields['restaurant_id'];

        $this->requireManageable($restaurantId, $user);

        $category = $this->categories->find((int) $fields['category_id']);

        if ($category === null || (int) $category['restaurant_id'] !== $restaurantId) {
            throw new ApiException('The category does not belong to this restaurant.', 422);
        }

        $id = $this->menu->create([
            'restaurant_id' => $restaurantId,
            'category_id' => (int) $fields['category_id'],
            'item_name' => $fields['item_name'],
            'description' => $fields['description'] ?? null,
            'price' => round((float) $fields['price'], 2),
            'availability' => $fields['availability'] ?? 'available',
            'preparation_time' => isset($fields['preparation_time'])
                ? (int) $fields['preparation_time']
                : null,
        ]);

        return $this->menu->findDetailed($id);
    }

    public function updateMenuItem(int $id, array $user, array $fields): array
    {
        $item = $this->menu->findDetailed($id);

        if ($item === null) {
            throw new ApiException('Menu item not found.', 404);
        }

        $this->requireManageable((int) $item['restaurant_id'], $user);

        $this->menu->update($id, [
            'item_name' => $fields['item_name'],
            'description' => $fields['description'] ?? null,
            'price' => round((float) $fields['price'], 2),
            'availability' => $fields['availability'] ?? $item['availability'],
            'preparation_time' => isset($fields['preparation_time'])
                ? (int) $fields['preparation_time']
                : null,
        ]);

        return $this->menu->findDetailed($id);
    }

    public function deleteMenuItem(int $id, array $user): void
    {
        $item = $this->menu->findDetailed($id);

        if ($item === null) {
            throw new ApiException('Menu item not found.', 404);
        }

        $this->requireManageable((int) $item['restaurant_id'], $user);
        $this->menu->delete($id);
    }

    /**
     * The client sends only item ids and quantities. Prices and the order total
     * are read from the menu so a tampered payload cannot change what is charged.
     */
    public function placeOrder(array $user, array $fields): array
    {
        $restaurantId = (int) $fields['restaurant_id'];
        $restaurant = $this->restaurants->find($restaurantId);

        if ($restaurant === null) {
            throw new ApiException('Restaurant not found.', 404);
        }

        if ($restaurant['status'] !== 'active') {
            throw new ApiException('This restaurant is not currently accepting orders.', 409);
        }

        $lines = $this->normaliseLines($fields['items'] ?? []);

        if ($lines === []) {
            throw new ApiException('An order must contain at least one item.', 422);
        }

        $menuItems = $this->menu->findManyForOrder(array_keys($lines));
        $total = 0.0;
        $resolved = [];

        foreach ($lines as $menuItemId => $quantity) {
            $item = $menuItems[$menuItemId] ?? null;

            if ($item === null || (int) $item['restaurant_id'] !== $restaurantId) {
                throw new ApiException('One of the selected items is not on this menu.', 422);
            }

            if ($item['availability'] !== 'available') {
                throw new ApiException($item['item_name'] . ' is currently unavailable.', 409);
            }

            $unitPrice = round((float) $item['price'], 2);
            $subtotal = round($unitPrice * $quantity, 2);
            $total += $subtotal;

            $resolved[] = [
                'menu_item_id' => $menuItemId,
                'item_name' => $item['item_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $orderId = $this->orders->create([
                'order_number' => $this->orders->nextOrderNumber(),
                'customer_id' => $user['user_id'],
                'restaurant_id' => $restaurantId,
                'total_amount' => round($total, 2),
                'payment_method' => $fields['payment_method'] ?? 'Cash',
                'payment_status' => 'unpaid',
                'order_status' => 'Pending',
                'ordered_at' => gmdate('Y-m-d H:i:s'),
            ]);

            foreach ($resolved as $line) {
                $this->orders->addItem($line + ['order_id' => $orderId]);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->orders->findDetailed($orderId);
    }

    public function orders(array $user, ?string $status): array
    {
        if ($user['role'] === 'Restaurant Owner') {
            $owned = $this->restaurants->forOwner($user['user_id']);

            if ($owned === []) {
                return [];
            }

            return $this->orders->forRestaurant((int) $owned[0]['id'], $status);
        }

        return $this->orders->forCustomer($user['user_id']);
    }

    public function order(int $id, array $user): array
    {
        $order = $this->orders->findDetailed($id);

        if ($order === null) {
            throw new ApiException('Order not found.', 404);
        }

        $isCustomer = (int) $order['customer_id'] === $user['user_id'];
        $isOwner = (int) $order['owner_id'] === $user['user_id'];

        if (!$isCustomer && !$isOwner && $user['role'] !== 'Administrator') {
            throw new ApiException('Order not found.', 404);
        }

        return $order;
    }

    public function cancelOrder(int $id, array $user, ?string $reason): array
    {
        $order = $this->order($id, $user);

        if (!in_array($order['order_status'], self::CANCELLABLE_BY_CUSTOMER, true)) {
            throw new ApiException(
                'An order can no longer be cancelled once preparation has started.',
                409
            );
        }

        $this->orders->update($id, [
            'order_status' => 'Cancelled',
            'cancellation_reason' => $reason,
        ]);

        return $this->orders->findDetailed($id);
    }

    public function updateOrderStatus(int $id, array $user, string $status): array
    {
        $order = $this->orders->findDetailed($id);

        if ($order === null) {
            throw new ApiException('Order not found.', 404);
        }

        $this->requireManageable((int) $order['restaurant_id'], $user);

        $allowed = self::NEXT_STATUS[$order['order_status']] ?? [];

        if (!in_array($status, $allowed, true)) {
            throw new ApiException(
                'An order cannot move from ' . $order['order_status'] . ' to ' . $status . '.',
                409
            );
        }

        $this->orders->changeStatus($id, $status);

        return $this->orders->findDetailed($id);
    }

    public function reviews(int $restaurantId): array
    {
        return $this->reviews->forRestaurant($restaurantId);
    }

    public function submitReview(array $user, array $fields): array
    {
        $orderId = (int) $fields['order_id'];
        $order = $this->orders->find($orderId);

        if ($order === null || (int) $order['customer_id'] !== $user['user_id']) {
            throw new ApiException('Order not found.', 404);
        }

        if ($order['order_status'] !== 'Completed') {
            throw new ApiException('Only a completed order can be reviewed.', 409);
        }

        if ($this->reviews->existsForOrder($orderId)) {
            throw new ApiException('This order has already been reviewed.', 409);
        }

        $id = $this->reviews->create([
            'restaurant_id' => (int) $order['restaurant_id'],
            'order_id' => $orderId,
            'customer_id' => $user['user_id'],
            'rating' => (int) $fields['rating'],
            'comment' => $fields['comment'] ?? null,
        ]);

        return $this->reviews->find($id);
    }

    public function deleteReview(int $id, array $user): void
    {
        $review = $this->reviews->find($id);

        if ($review === null) {
            throw new ApiException('Review not found.', 404);
        }

        if ((int) $review['customer_id'] !== $user['user_id'] && $user['role'] !== 'Administrator') {
            throw new ApiException('You can only remove your own review.', 403);
        }

        $this->reviews->delete($id);
    }

    public function salesReport(int $restaurantId, array $user, ?string $from, ?string $to): array
    {
        $this->requireManageable($restaurantId, $user);

        return [
            'sales' => $this->orders->salesReport($restaurantId, $from, $to),
            'popular_items' => $this->menu->popularItems($restaurantId, 10),
        ];
    }

    /**
     * @return array<int, int> menu item id => quantity, merged so a repeated id
     *                         becomes one line rather than several
     */
    private function normaliseLines(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $menuItemId = (int) ($item['menu_item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($menuItemId <= 0) {
                throw new ApiException('Every order line needs a menu item.', 422);
            }

            if ($quantity <= 0) {
                throw new ApiException('Every order line needs a quantity of at least one.', 422);
            }

            $lines[$menuItemId] = ($lines[$menuItemId] ?? 0) + $quantity;
        }

        return $lines;
    }

    private function requireManageable(int $restaurantId, array $user): array
    {
        $restaurant = $this->restaurants->find($restaurantId);

        if ($restaurant === null) {
            throw new ApiException('Restaurant not found.', 404);
        }

        if (!$this->canManage($restaurant, $user)) {
            throw new ApiException('You can only manage your own restaurant.', 403);
        }

        return $restaurant;
    }

    private function canManage(array $restaurant, array $user): bool
    {
        return $user['role'] === 'Administrator'
            || (int) $restaurant['owner_id'] === $user['user_id'];
    }
}
