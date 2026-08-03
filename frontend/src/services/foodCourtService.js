import { apiClient } from './apiClient';

export const foodCourtService = {
    async restaurants() {
        const { data } = await apiClient.get('/food-court/restaurants');

        return data.data.restaurants;
    },

    async restaurant(id) {
        const { data } = await apiClient.get(`/food-court/restaurants/${id}`);

        return data.data.restaurant;
    },

    async menu(restaurantId) {
        const { data } = await apiClient.get(`/food-court/restaurants/${restaurantId}/menu`);

        return data.data.menu;
    },

    async placeOrder({ restaurantId, items, paymentMethod }) {
        const { data } = await apiClient.post('/food-court/orders', {
            restaurant_id: restaurantId,
            payment_method: paymentMethod,
            items,
        });

        return data.data.order;
    },

    async orders(status) {
        const { data } = await apiClient.get('/food-court/orders', {
            params: status ? { status } : {},
        });

        return data.data.orders;
    },

    async cancelOrder(id, reason) {
        const { data } = await apiClient.put(`/food-court/orders/${id}/cancel`, { reason });

        return data.data.order;
    },

    async updateOrderStatus(id, orderStatus) {
        const { data } = await apiClient.put(`/food-court/orders/${id}/status`, {
            order_status: orderStatus,
        });

        return data.data.order;
    },
};
