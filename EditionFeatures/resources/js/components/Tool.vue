<template>
    <div>
        <heading class="mb-6">Edition Features</heading>
        <div class="flex">
            <div class="w-1/5 py-4">
                <label class="inline-block text-80 pt-2 leading-tight">Lang: </label>
            </div>

            <div class="py-4 w-4/5">
                <select name="lang" id="lang" v-model="locale"
                        class="w-full form-control form-input form-input-bordered">
                    <option :value="locale"
                            :key="index"
                            v-for="(name,locale, index) in locales">{{ name }}
                    </option>
                </select>
            </div>
        </div>
        <items-list
            @refreshData="refreshData"
            :locale="locale"
            :items="editionGroups"
        >
        </items-list>
    </div>
</template>

<script>
import api from '../api';
import ItemsList from "./ItemsList";

export default {
    components: {ItemsList},
    data: () => ({
        editionGroups: [],
        groupChild: [],
        currentItem: null,
        locale: 'en',
        locales: {}
    }),
    mounted() {
        this.getTree();
        this.getLocales();
    },
    watch: {
        locale() {
            this.getTree();
        }
    },
    methods: {
        refreshData() {
            this.getTree();
        },
        async getTree() {
            const editionGroups = (await api.getTree(this.locale)).data;
            this.editionGroups = Object.values(editionGroups);
        },

        async getLocales() {
            this.locales = (await api.getLocales()).data;
        }
    }
}
</script>

<style>
/* Scoped Styles */
</style>
