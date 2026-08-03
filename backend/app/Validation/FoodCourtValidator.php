<?php

declare(strict_types=1);

namespace App\Validation;

class FoodCourtValidator
{
    private const PAYMENT_METHODS = [
        'Cash', 'Online Banking', 'Credit Card', 'Debit Card', 'E-Wallet',
    ];

    private const ORDER_STATUSES = [
        'Pending', 'Accepted', 'Preparing', 'Ready', 'Completed', 'Cancelled',
    ];

    public function restaurant(array $data, bool $requireOwner): array
    {
        $validator = (new Validator())
            ->required($data, 'restaurant_name', 'Restaurant name')
            ->maxLength($data, 'restaurant_name', 255, 'Restaurant name')
            ->required($data, 'phone', 'Phone')
            ->phone($data, 'phone', 'Phone')
            ->inList($data, 'status', ['active', 'inactive', 'suspended'], 'Status');

        if ($requireOwner) {
            $validator
                ->required($data, 'owner_id', 'Owner')
                ->integer($data, 'owner_id', 'Owner');
        }

        return $validator->errors();
    }

    public function category(array $data): array
    {
        return (new Validator())
            ->required($data, 'restaurant_id', 'Restaurant')
            ->integer($data, 'restaurant_id', 'Restaurant')
            ->required($data, 'category_name', 'Category name')
            ->maxLength($data, 'category_name', 150, 'Category name')
            ->errors();
    }

    public function menuItem(array $data, bool $requireRestaurant): array
    {
        $validator = (new Validator())
            ->required($data, 'item_name', 'Item name')
            ->maxLength($data, 'item_name', 255, 'Item name')
            ->required($data, 'price', 'Price')
            ->numberBetween($data, 'price', 0.01, 999999, 'Price')
            ->inList($data, 'availability', ['available', 'unavailable'], 'Availability')
            ->positiveInteger($data, 'preparation_time', 'Preparation time');

        if ($requireRestaurant) {
            $validator
                ->required($data, 'restaurant_id', 'Restaurant')
                ->integer($data, 'restaurant_id', 'Restaurant')
                ->required($data, 'category_id', 'Category')
                ->integer($data, 'category_id', 'Category');
        }

        return $validator->errors();
    }

    public function order(array $data): array
    {
        $errors = (new Validator())
            ->required($data, 'restaurant_id', 'Restaurant')
            ->integer($data, 'restaurant_id', 'Restaurant')
            ->inList($data, 'payment_method', self::PAYMENT_METHODS, 'Payment method')
            ->errors();

        if (!is_array($data['items'] ?? null) || $data['items'] === []) {
            $errors['items'][] = 'At least one item is required.';
        }

        return $errors;
    }

    public function orderStatus(array $data): array
    {
        return (new Validator())
            ->required($data, 'order_status', 'Order status')
            ->inList($data, 'order_status', self::ORDER_STATUSES, 'Order status')
            ->errors();
    }

    public function review(array $data): array
    {
        return (new Validator())
            ->required($data, 'order_id', 'Order')
            ->integer($data, 'order_id', 'Order')
            ->required($data, 'rating', 'Rating')
            ->numberBetween($data, 'rating', 1, 5, 'Rating')
            ->maxLength($data, 'comment', 1000, 'Comment')
            ->errors();
    }
}
