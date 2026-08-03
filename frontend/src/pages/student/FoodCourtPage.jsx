import { useEffect, useState } from 'react';
import { Minus, Plus, ShoppingCart, Store, Utensils, X } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Alert } from '../../components/Alert';
import { foodCourtService } from '../../services/foodCourtService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const orderVariants = {
    Pending: 'warning',
    Accepted: 'warning',
    Preparing: 'warning',
    Ready: 'success',
    Completed: 'success',
    Cancelled: 'neutral',
};

function money(value) {
    return Number(value).toFixed(2);
}

export function FoodCourtPage() {
    const { notify } = useToast();

    const [restaurants, setRestaurants] = useState([]);
    const [activeRestaurant, setActiveRestaurant] = useState(null);
    const [menu, setMenu] = useState([]);
    const [cart, setCart] = useState({});
    const [orders, setOrders] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isPlacing, setIsPlacing] = useState(false);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        Promise.all([foodCourtService.restaurants(), foodCourtService.orders()])
            .then(([restaurantData, orderData]) => {
                setRestaurants(restaurantData);
                setOrders(orderData);
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load the food court.').message))
            .finally(() => setIsLoading(false));
    }, []);

    async function openRestaurant(restaurant) {
        setActiveRestaurant(restaurant);
        setCart({});

        try {
            setMenu(await foodCourtService.menu(restaurant.id));
        } catch (error) {
            notify(readApiError(error, 'Unable to load this menu.').message, 'error');
        }
    }

    function changeQuantity(item, delta) {
        setCart((current) => {
            const next = { ...current };
            const quantity = (next[item.id]?.quantity ?? 0) + delta;

            if (quantity <= 0) {
                delete next[item.id];
            } else {
                next[item.id] = { item, quantity };
            }

            return next;
        });
    }

    const cartLines = Object.values(cart);
    const cartTotal = cartLines.reduce(
        (total, line) => total + Number(line.item.price) * line.quantity,
        0,
    );

    async function handlePlaceOrder() {
        setIsPlacing(true);

        try {
            await foodCourtService.placeOrder({
                restaurantId: activeRestaurant.id,
                paymentMethod: 'Cash',
                items: cartLines.map((line) => ({
                    menu_item_id: line.item.id,
                    quantity: line.quantity,
                })),
            });

            notify('Order placed.');
            setCart({});
            setOrders(await foodCourtService.orders());
        } catch (error) {
            notify(readApiError(error, 'Unable to place this order.').message, 'error');
        } finally {
            setIsPlacing(false);
        }
    }

    async function handleCancel(orderId) {
        try {
            await foodCourtService.cancelOrder(orderId, 'Cancelled by customer');

            notify('Order cancelled.');
            setOrders(await foodCourtService.orders());
        } catch (error) {
            notify(readApiError(error, 'Unable to cancel this order.').message, 'error');
        }
    }

    if (isLoading) {
        return (
            <>
                <PageHeader title="Food court" subtitle="Order from campus restaurants." />
                <SkeletonRows rows={4} height={72} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Food court"
                subtitle="Order from campus restaurants and track your orders."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {activeRestaurant === null ? (
                <>
                    <h2 className="nsu-section-title">Restaurants</h2>

                    {restaurants.length === 0 ? (
                        <div className="nsu-card">
                            <EmptyState
                                icon={Store}
                                title="No restaurants open"
                                description="Campus restaurants appear here once they are active."
                            />
                        </div>
                    ) : (
                        <div className="nsu-grid nsu-grid--cards">
                            {restaurants.map((restaurant) => (
                                <article className="nsu-card" key={restaurant.id}>
                                    <div className="nsu-card__body">
                                        <h3 className="nsu-section-title">
                                            {restaurant.restaurant_name}
                                        </h3>
                                        <p
                                            style={{
                                                color: 'var(--color-muted-foreground)',
                                                fontSize: 'var(--text-sm)',
                                            }}
                                        >
                                            {restaurant.location ?? 'Campus food court'}
                                            {restaurant.average_rating
                                                ? ` — rated ${restaurant.average_rating}/5`
                                                : ''}
                                        </p>
                                        <Button
                                            variant="secondary"
                                            icon={Utensils}
                                            onClick={() => openRestaurant(restaurant)}
                                        >
                                            View menu
                                        </Button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                </>
            ) : (
                <>
                    <div className="nsu-toolbar">
                        <Button variant="ghost" icon={X} onClick={() => setActiveRestaurant(null)}>
                            Back to restaurants
                        </Button>
                    </div>

                    <h2 className="nsu-section-title">{activeRestaurant.restaurant_name}</h2>

                    <div
                        className="nsu-grid"
                        style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))' }}
                    >
                        <section className="nsu-card">
                            <div className="nsu-card__body">
                                <h3 className="nsu-section-title">Menu</h3>

                                {menu.length === 0 ? (
                                    <p style={{ color: 'var(--color-muted-foreground)', margin: 0 }}>
                                        Nothing on the menu yet.
                                    </p>
                                ) : (
                                    <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
                                        {menu.map((item) => (
                                            <li key={item.id} className="nsu-menu-line">
                                                <div>
                                                    <p className="nsu-menu-line__name">
                                                        {item.item_name}
                                                    </p>
                                                    <p className="nsu-menu-line__meta">
                                                        {item.category_name} — {money(item.price)}
                                                    </p>
                                                </div>
                                                <div className="nsu-menu-line__actions">
                                                    <button
                                                        type="button"
                                                        className="nsu-chat__action"
                                                        onClick={() => changeQuantity(item, -1)}
                                                        aria-label={`Remove one ${item.item_name}`}
                                                        disabled={!cart[item.id]}
                                                    >
                                                        <Minus size={14} />
                                                    </button>
                                                    <span className="tabular" style={{ minWidth: 18, textAlign: 'center' }}>
                                                        {cart[item.id]?.quantity ?? 0}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        className="nsu-chat__action"
                                                        onClick={() => changeQuantity(item, 1)}
                                                        aria-label={`Add one ${item.item_name}`}
                                                    >
                                                        <Plus size={14} />
                                                    </button>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </section>

                        <section className="nsu-card">
                            <div className="nsu-card__body">
                                <h3 className="nsu-section-title">Your cart</h3>

                                {cartLines.length === 0 ? (
                                    <EmptyState
                                        icon={ShoppingCart}
                                        title="Cart is empty"
                                        description="Add items from the menu to place an order."
                                    />
                                ) : (
                                    <>
                                        <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
                                            {cartLines.map((line) => (
                                                <li key={line.item.id} className="nsu-menu-line">
                                                    <span>
                                                        {line.quantity} x {line.item.item_name}
                                                    </span>
                                                    <span className="tabular">
                                                        {money(line.item.price * line.quantity)}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>

                                        <p
                                            className="tabular"
                                            style={{
                                                fontFamily: 'var(--font-heading)',
                                                fontWeight: 600,
                                                fontSize: 'var(--text-lg)',
                                                marginTop: 'var(--space-md)',
                                            }}
                                        >
                                            Total {money(cartTotal)}
                                        </p>

                                        <Button
                                            icon={ShoppingCart}
                                            isLoading={isPlacing}
                                            onClick={handlePlaceOrder}
                                        >
                                            Place order
                                        </Button>
                                    </>
                                )}
                            </div>
                        </section>
                    </div>
                </>
            )}

            <h2 className="nsu-section-title" style={{ marginTop: 'var(--space-xl)' }}>
                My orders
            </h2>

            <div className="nsu-card">
                {orders.length === 0 ? (
                    <EmptyState
                        icon={ShoppingCart}
                        title="No orders yet"
                        description="Orders you place appear here with their live status."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Your food orders</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Order</th>
                                    <th scope="col">Restaurant</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Placed</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.map((order) => (
                                    <tr key={order.id}>
                                        <td className="tabular">{order.order_number}</td>
                                        <td>{order.restaurant_name}</td>
                                        <td className="tabular">{money(order.total_amount)}</td>
                                        <td className="tabular">{order.ordered_at}</td>
                                        <td>
                                            <Badge variant={orderVariants[order.order_status] ?? 'neutral'}>
                                                {order.order_status}
                                            </Badge>
                                        </td>
                                        <td>
                                            {['Pending', 'Accepted'].includes(order.order_status) && (
                                                <Button
                                                    variant="ghost"
                                                    onClick={() => handleCancel(order.id)}
                                                >
                                                    Cancel
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
