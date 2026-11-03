import {useCategories} from "../composables/useCategories.js";
import {defineStore} from "pinia";

export const useCategoriesStore = defineStore("Categories", {
    state: ()=> ({
        items: []
    }),
    actions: {

    }
});