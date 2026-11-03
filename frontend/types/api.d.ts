declare module '#app' {
    interface NuxtApp {
        $api: {
            get: (path: string, params?: any, opts?: any) => Promise<any>
            post: (path: string, body?: any, opts?: any) => Promise<any>
            put: (path: string, body?: any, opts?: any) => Promise<any>
            patch: (path: string, body?: any, opts?: any) => Promise<any>
            delete: (path: string, opts?: any) => Promise<any>
            upload: (path: string, formData: FormData, opts?: any) => Promise<any>
        }
    }
}
