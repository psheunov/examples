export default {
    async getTree(locale) {
        return Nova.request().get(`/nova-vendor/edition-features/features/tree/${locale}`);
    },

    async update(feature, locale) {
        return Nova.request().post(`/nova-vendor/edition-features/features/update/${locale}`, feature);
    },

    async createRoot(feature, locale) {
        return Nova.request().post(`/nova-vendor/edition-features/features/create-root/${locale}`, feature);
    },

    async createChild(feature, parentId, locale) {
        return Nova.request().post(`/nova-vendor/edition-features/features/create-child/${locale}/${parentId}`, feature);
    },

    async deleteItem(id){
        return Nova.request().delete(`/nova-vendor/edition-features/features/delete/${id}`);
    },

    async getLocales() {
        return Nova.request().get('/nova-vendor/edition-features/locales');
    }
};
