import {defineStore} from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: null,
        loading: false,
        error: null,

        _hydrated: false,
        _fetchMePromise: null,
    }),

    getters: {
        accessToken: (state) => state.token,
        isLoggedIn: (state) => !!state.token && !!state.user,
        userName: (state) => state.user?.name ?? '',
        userPhone: (state) => state.user?.phone ?? '',
        userEmail: (state) => state.user?.email ?? '',
        userAvatar: (state) => state.user?.avatar ?? null,
        isSeller: (state) => !!state.user?.seller,
        isActiveSeller: (state) => state.user?.seller?.status === 'active',
    },

    actions: {
        _save({token, user} = {}) {
            const tokenCookie = useCookie('authenticationToken', {
                maxAge: 60 * 60 * 24 * 30,
                sameSite: 'lax',
            })

            if (token) {
                this.token = token
                tokenCookie.value = token
            }

            if (user) {
                this.user = user
            }
        },

        _clear() {
            const tokenCookie = useCookie('authenticationToken')
            this.token = null
            this.user = null
            this._hydrated = false
            this._fetchMePromise = null
            tokenCookie.value = null
        },

        async init() {
            if (this._hydrated) return

            const tokenCookie = useCookie('authenticationToken')

            if (tokenCookie.value) {
                this.token = tokenCookie.value
            }

            this._hydrated = true

            if (this.token && !this.user) {
                try {
                    await this.fetchMe()
                } catch {
                    this._clear()
                }
            }
        },

        async login(phone, password) {
            const api = useApi()

            this.loading = true
            this.error = null

            try {
                const data = await api.post('/auth/login', {phone, password,})
                this._save({token: data.token, user: data.user})
                return true
            } catch (e) {
                this.error = e.message
                return false
            } finally {
                this.loading = false
            }
        },

        async _authCall(fn) {
            this.loading = true
            this.error = null
            try {
                const data = await fn(useApi())
                if (data?.token) this._save({token: data.token, user: data.user})
                return true
            } catch (e) {
                this.error = e.message
                return false
            } finally {
                this.loading = false
            }
        },

        register(payload) {
            return this._authCall((api) => api.post('/auth/register', payload))
        },

        sendLoginOtp(phone) {
            return this._authCall((api) => api.post('/auth/send-otp', {phone, type: 'login'}))
        },

        // Works only for phones that already have an account (backend returns a token then).
        async loginWithOtp(phone, otp) {
            this.loading = true
            this.error = null
            try {
                const data = await useApi().post('/auth/verify-otp', {phone, otp, type: 'login'})
                if (!data?.token) {
                    this.error = 'এই নম্বরে কোনো অ্যাকাউন্ট নেই। অনুগ্রহ করে আগে রেজিস্ট্রেশন করুন।'
                    return false
                }
                this._save({token: data.token, user: data.user})
                return true
            } catch (e) {
                this.error = e.message
                return false
            } finally {
                this.loading = false
            }
        },

        socialLogin(provider, token) {
            return this._authCall((api) => api.post('/auth/social-login', {provider, token}))
        },

        async updateProfile(payload) {
            const data = await useApi().put('/user/profile', payload)
            if (data) this.user = {...this.user, ...data}
            return data
        },

        fetchMe() {
            if (!this.token) return Promise.resolve(null)

            if (this._fetchMePromise) {
                return this._fetchMePromise
            }

            this._fetchMePromise = useApi().get('/auth/me').then((data) => {
                this.user = data
                return data
            }).catch((e) => {
                if (e.status === 401) {
                    this._clear()
                }

                throw e
            }).finally(() => {
                this._fetchMePromise = null
            })

            return this._fetchMePromise
        },

        async logout() {
            try {
                await useApi().post('/auth/logout')
            } catch {
            }

            this._clear()

            return navigateTo('/auth/login')
        },
    },
})