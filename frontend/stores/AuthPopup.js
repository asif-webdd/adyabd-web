import {defineStore} from "pinia";

export const useAuthPopupStore = defineStore("authPopup", {
    state: () => ({
        visible: false,
        _onSuccess: null
    }),
    actions: {
        open(onSuccess = null) {
            this._onSuccess = onSuccess
            this.visible = true
        },
        close(){
            this.visible = false
            this._onSuccess = null
        },
        onLoginSuccess(){
            this.visible = false
            if (typeof this._onSuccess === 'function'){
                this._onSuccess()
            }
            this._onSuccess = null
        }
    }
})