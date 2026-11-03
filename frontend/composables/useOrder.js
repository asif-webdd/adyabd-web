export const useOrder = () => {
    const {$api} = useNuxtApp()

    async function getOrders(params = {}) {
        return $api.get('/orders', params)
    }

    async function getOrder(orderNumber) {
        return $api.get(`/orders/${orderNumber}`)
    }

    async function placeOrder(data) {
        return $api.post('/orders', data)
    }

    async function cancelOrder(id, reason) {
        return $api.put(`/orders/${id}/cancel`, {reason})
    }

    async function trackOrder(id) {
        return $api.get(`/orders/${id}/track`)
    }

    async function requestReturn(id, reason) {
        return $api.post(`/orders/${id}/return`, {reason})
    }

    async function getAddresses() {
        return $api.get('/addresses')
    }

    async function addAddress(data) {
        return $api.post('/addresses', data)
    }

    async function deleteAddress(id) {
        return $api.delete(`/addresses/${id}`)
    }

    async function setDefaultAddress(id) {
        return $api.put(`/addresses/${id}/default`)
    }

    async function validateCoupon(code, orderAmount) {
        return $api.post('/coupons/validate', {code, order_amount: orderAmount})
    }

    return {
        getOrders,
        getOrder,
        placeOrder,
        cancelOrder,
        trackOrder,
        requestReturn,
        getAddresses,
        addAddress,
        deleteAddress,
        setDefaultAddress,
        validateCoupon
    }
}