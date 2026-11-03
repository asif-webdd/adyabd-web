import useApi from "../composables/useApi.js"
export default defineNuxtPlugin(()=> {
    const api = useApi()
    return {
        provide: {
            api
        }
    }
})