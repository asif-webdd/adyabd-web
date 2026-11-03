export const useCategories = ()=> {
    const {$api} = useNuxtApp()
    async function getCategories(params = {}){
        return await $api.get("/categories")
    }

    return {getCategories}
}