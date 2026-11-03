export const useBlog = () => {
    const { $api } = useNuxtApp()

    async function getPosts(params = {}) {
        return $api.get('/blog', params)
    }

    async function getPost(slug) {
        return $api.get(`/blog/${slug}`)
    }

    async function getBlogCategories() {
        return $api.get('/blog/categories')
    }

    async function getPopularPosts() {
        return $api.get('/blog/popular')
    }

    async function getBlogTags() {
        return $api.get('/blog/tags')
    }

    async function getComments(postId) {
        return $api.get(`/blog/${postId}/comments`)
    }

    async function addComment(postId, data) {
        return $api.post(`/blog/${postId}/comments`, data)
    }

    async function toggleLike(postId) {
        return $api.post(`/blog/${postId}/like`)
    }

    return { getPosts, getPost, getBlogCategories, getPopularPosts, getBlogTags, getComments, addComment, toggleLike }
}