<template>
    <div>
        <div class="flex justify-content-end mb-6">
            <div @click.prevent="openAddModal()" class="btn btn-default bg-primary text-white">Create</div>
        </div>
        <vue-nestable v-model="items">
            <vue-nestable-handle
                slot-scope="{ item }"
                :item="item">
                <div class="list-item">
                    <div class="flex">
                        <button
                            class="appearance-none cursor-pointer fill-current hover:text-primary flex px-4 items-center focus:outline-none"
                            v-if="hasChildren(item)"
                            @click.prevent="toggleChildrenCascade"
                        >
                            <arrow-icon class="btn-cascade"></arrow-icon>
                        </button>
                        {{ item.title }}
                    </div>

                    <div>
                        <a href="/"
                           class="cursor-pointer text-70 hover:text-primary mr-3 inline-flex items-center has-tooltip"
                           @click.prevent="openEditModal(item)">
                            <edit-icon></edit-icon>
                        </a>
                        <a href="/"
                           class="cursor-pointer text-70 hover:text-primary mr-3 inline-flex items-center has-tooltip"
                           @click.prevent="deleteItem(item.id)">
                            <delete-icon></delete-icon>
                        </a>
                        <div @click.prevent="openAddModal(item.id)" class="btn btn-default bg-primary text-white">+
                        </div>
                    </div>
                </div>
            </vue-nestable-handle>
        </vue-nestable>
        <update-form-modal
            @closeModal="closeAddModal"
            @createItem="createItem"
            @updateItem="updateItem"
            :newItem="newItem"
            :showModal="showAddModal"
            :update="update"
            :errors="errors"
            :isItemUpdating="isItemUpdating"
        >
        </update-form-modal>
    </div>
</template>
<script>

import UpdateFormModal from "./modals/UpdateFormModal";
import api from "../api";
import {VueNestable, VueNestableHandle} from 'vue-nestable';
import EditIcon from "./icons/EditIcon";
import DeleteIcon from "./icons/DeleteIcon";
import ArrowIcon from "./icons/ArrowIcon";

export default {
    components: {ArrowIcon, DeleteIcon, EditIcon, UpdateFormModal, VueNestable, VueNestableHandle},
    props: [
        'items',
        'locale',
    ],
    data: () => ({
        showAddModal: false,
        update: false,
        errors: {},
        parentId: null,
        isItemUpdating: false,
        newItem: {
            active: true,
            title: 'Test',
            code: 'test code',
            link: 'test link',
            description: 'test description'
        },
    }),
    mounted() {

    },
    methods: {
        openEditModal(item) {
            this.update = true;
            this.newItem = item;
            this.showAddModal = true;
        },
        openAddModal(parentId = null) {
            this.newItem = {};
            this.parentId = parentId;
            this.showAddModal = true;
        },
        closeAddModal() {
            this.newItem = {
                active: true
            };
            this.isItemUpdating = false;
            this.showAddModal = false;
            this.update = false;
        },
        async refreshData() {
            this.newItem = {
                active: true
            };
            this.isItemUpdating = false;
            this.showAddModal = false;
            this.update = false;
            this.$emit('refreshData');
        },

        async updateItem(item) {
            try {
                this.isItemUpdating = true;
                this.errors = {};
                await api.update(item, this.locale);
                await this.refreshData();
                this.$toasted.show('Item updated', {type: 'success'});
            } catch (e) {
                this.isItemUpdating = false;
                console.error(e);
                this.handleErrors(e);
            }
        },
        async createItem(item) {
            try {
                this.errors = {};
                this.isItemUpdating = true;
                if (this.parentId === null) {
                    await api.createRoot(item, this.locale);
                } else {
                    await api.createChild(item, this.parentId, this.locale);
                }
                await this.refreshData();
                this.$toasted.show('Item created', {type: 'success'});
            } catch (e) {
                this.isItemUpdating = false;
                this.handleErrors(e);
            }
        },

        async deleteItem(id) {
            await api.deleteItem(id);
            await this.refreshData();
            this.$toasted.show('Deleted', {type: 'success'});
        },

        hasChildren(item) {
            return Array.isArray(item.children) && item.children.length;
        },

        toggleChildrenCascade(event) {
            event.target.closest('.nestable-item').classList.toggle("hidden-child")
        },

        handleErrors(res) {
            let errors = res.response && res.response.data && res.response.data.errors;
            if (errors) {
                this.errors = errors;
                Object.values(errors).map(error => this.$toasted.show(error, {type: 'error'}));
            }
        }
    }
}
</script>
<style>
.hidden-child ol {
    display: none;
}

.hidden-child .btn-cascade {
    transform: rotate(180deg);
    transform-origin: center center;
}

ol {
    list-style: none;
}

.list-item {
    display: flex;
    justify-content: space-between;
    background: #ebebeb;
    border: 1px solid #c9c9c9;
    margin-bottom: 10px;
    padding: 10px 20px;
    text-align: center;
    border-radius: 5px;
    align-items: center;
}
</style>
