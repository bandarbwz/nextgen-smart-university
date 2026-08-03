<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\FoodCourtService;
use App\Validation\FoodCourtValidator;

class FoodCourtController extends Controller
{
    public function __construct(
        private readonly FoodCourtService $foodCourt = new FoodCourtService(),
        private readonly FoodCourtValidator $validator = new FoodCourtValidator()
    ) {
        parent::__construct();
    }

    public function restaurants(): void
    {
        $user = $this->authenticate();

        Response::success('Restaurants retrieved.', [
            'restaurants' => $this->foodCourt->restaurants($user),
        ]);
    }

    public function restaurant(string $id): void
    {
        $user = $this->authenticate();

        $restaurant = $this->run(fn () => $this->foodCourt->restaurant((int) $id, $user));

        Response::success('Restaurant retrieved.', ['restaurant' => $restaurant]);
    }

    public function storeRestaurant(): void
    {
        $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->restaurant($data, true);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $restaurant = $this->run(fn () => $this->foodCourt->createRestaurant($data));

        Response::success('Restaurant created.', ['restaurant' => $restaurant], 201);
    }

    public function updateRestaurant(string $id): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $data = Request::body();
        $errors = $this->validator->restaurant($data, false);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $restaurant = $this->run(
            fn () => $this->foodCourt->updateRestaurant((int) $id, $user, $data)
        );

        Response::success('Restaurant updated.', ['restaurant' => $restaurant]);
    }

    public function destroyRestaurant(string $id): void
    {
        $this->authenticateAsAdministrator();

        $this->run(fn () => $this->foodCourt->deleteRestaurant((int) $id));

        Response::success('Restaurant deleted.');
    }

    public function categories(): void
    {
        $this->authenticate();

        $restaurantId = $this->queryInt('restaurant_id');

        if ($restaurantId === null) {
            Response::error('A restaurant_id query parameter is required.', 400);
        }

        Response::success('Categories retrieved.', [
            'categories' => $this->foodCourt->categories($restaurantId),
        ]);
    }

    public function storeCategory(): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $data = Request::body();
        $errors = $this->validator->category($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $category = $this->run(fn () => $this->foodCourt->createCategory($user, $data));

        Response::success('Category created.', ['category' => $category], 201);
    }

    public function destroyCategory(string $id): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $this->run(fn () => $this->foodCourt->deleteCategory((int) $id, $user));

        Response::success('Category deleted.');
    }

    public function menu(string $restaurantId): void
    {
        $user = $this->authenticate();

        $menu = $this->run(fn () => $this->foodCourt->menu((int) $restaurantId, $user));

        Response::success('Menu retrieved.', ['menu' => $menu]);
    }

    public function storeMenuItem(): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $data = Request::body();
        $errors = $this->validator->menuItem($data, true);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $item = $this->run(fn () => $this->foodCourt->createMenuItem($user, $data));

        Response::success('Menu item created.', ['menu_item' => $item], 201);
    }

    public function updateMenuItem(string $id): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $data = Request::body();
        $errors = $this->validator->menuItem($data, false);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $item = $this->run(fn () => $this->foodCourt->updateMenuItem((int) $id, $user, $data));

        Response::success('Menu item updated.', ['menu_item' => $item]);
    }

    public function destroyMenuItem(string $id): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $this->run(fn () => $this->foodCourt->deleteMenuItem((int) $id, $user));

        Response::success('Menu item deleted.');
    }

    public function storeOrder(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->order($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $order = $this->run(fn () => $this->foodCourt->placeOrder($user, $data));

        Response::success('Order placed.', ['order' => $order], 201);
    }

    public function orders(): void
    {
        $user = $this->authenticate();

        Response::success('Orders retrieved.', [
            'orders' => $this->foodCourt->orders($user, $this->queryString('status')),
        ]);
    }

    public function order(string $id): void
    {
        $user = $this->authenticate();

        $order = $this->run(fn () => $this->foodCourt->order((int) $id, $user));

        Response::success('Order retrieved.', ['order' => $order]);
    }

    public function cancelOrder(string $id): void
    {
        $user = $this->authenticate();

        $data = Request::body();

        $order = $this->run(fn () => $this->foodCourt->cancelOrder(
            (int) $id,
            $user,
            isset($data['reason']) ? trim((string) $data['reason']) : null,
        ));

        Response::success('Order cancelled.', ['order' => $order]);
    }

    public function updateOrderStatus(string $id): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $data = Request::body();
        $errors = $this->validator->orderStatus($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $order = $this->run(fn () => $this->foodCourt->updateOrderStatus(
            (int) $id,
            $user,
            $data['order_status'],
        ));

        Response::success('Order status updated.', ['order' => $order]);
    }

    public function reviews(string $restaurantId): void
    {
        $this->authenticate();

        Response::success('Reviews retrieved.', [
            'reviews' => $this->foodCourt->reviews((int) $restaurantId),
        ]);
    }

    public function storeReview(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->review($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $review = $this->run(fn () => $this->foodCourt->submitReview($user, $data));

        Response::success('Review submitted.', ['review' => $review], 201);
    }

    public function destroyReview(string $id): void
    {
        $user = $this->authenticate();

        $this->run(fn () => $this->foodCourt->deleteReview((int) $id, $user));

        Response::success('Review deleted.');
    }

    public function salesReport(string $restaurantId): void
    {
        $user = $this->authenticateAs(['Restaurant Owner']);

        $report = $this->run(fn () => $this->foodCourt->salesReport(
            (int) $restaurantId,
            $user,
            $this->queryString('from'),
            $this->queryString('to'),
        ));

        Response::success('Sales report generated.', $report);
    }
}
