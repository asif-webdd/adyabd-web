export const useNotification = () => {
    const { $api } = useNuxtApp()
    const notifications    = useState('notifications', () => [])
    const unreadCount      = useState('unread_count', () => 0)

    async function fetchNotifications() {
        const res = await $api.get('/notifications')
        if (res.success) notifications.value = res.data.data || []
    }

    async function fetchUnreadCount() {
        const res = await $api.get('/notifications/unread-count')
        if (res.success) unreadCount.value = res.data.count
    }

    async function markRead(id) {
        const res = await $api.put(`/notifications/${id}/read`)
        if (res.success) await fetchUnreadCount()
        return res
    }

    async function markAllRead() {
        const res = await $api.put('/notifications/read-all')
        if (res.success) { unreadCount.value = 0; await fetchNotifications() }
        return res
    }

    return { notifications, unreadCount, fetchNotifications, fetchUnreadCount, markRead, markAllRead }
}