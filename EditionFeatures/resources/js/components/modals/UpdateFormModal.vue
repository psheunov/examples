<template>
    <modal align="flex justify-end" :show="showModal" class="add-new-menu-item-modal">
        <div slot="container">
            <div class="flex flex-wrap justify-between mb-6">
                <h2 class="text-90 font-normal text-xl">
                    {{ update ? 'Edit' : 'Create' }}
                </h2>

                <toggle-button v-model="newItem.active"
                               :color="switchColor"
                               :labels="toggleLabels"
                               :sync="true"
                               :width="78"/>
            </div>

            <form id="form" @submit.prevent="storeWithData" autocomplete="off">
                <div class="flex">
                    <div class="w-1/5 py-4">
                        <label class="inline-block text-80 pt-2 leading-tight">Title</label>
                    </div>

                    <div class="py-4 w-4/5">
                        <input
                            placeholder="title"
                            :class="{ 'border-danger': getError('title') }"
                            class="w-full form-control form-input form-input-bordered"
                            id="title"
                            type="text"
                            v-model="newItem.title"
                        />

                        <help-text class="error-text mt-2 text-danger" v-if="getError('title')">
                            {{ getError('title') }}
                        </help-text>
                    </div>
                </div>


                <div class="flex">
                    <div class="w-1/5 py-4">
                        <label class="inline-block text-80 pt-2 leading-tight">Code</label>
                    </div>
                    <div class="py-4 w-4/5">
                        <input
                            placeholder="code"
                            :class="{ 'border-danger': getError('code') }"
                            class="w-full form-control form-input form-input-bordered"
                            id="code"
                            type="text"
                            v-model="newItem.code"
                        />

                        <help-text class="error-text mt-2 text-danger" v-if="getError('code')">
                            {{ getError('code') }}
                        </help-text>
                    </div>
                </div>

                <div class="flex">
                    <div class="w-1/5 py-4">
                        <label class="inline-block text-80 pt-2 leading-tight">Link</label>
                    </div>
                    <div class="py-4 w-4/5">
                        <input
                            placeholder="link"
                            :class="{ 'border-danger': getError('link') }"
                            class="w-full form-control form-input form-input-bordered"
                            id="link"
                            type="text"
                            v-model="newItem.link"
                        />

                        <help-text class="error-text mt-2 text-danger" v-if="getError('link')">
                            {{ getError('link') }}
                        </help-text>
                    </div>
                </div>

                <div class="flex">
                    <div class="w-1/5 py-4">
                        <label class="inline-block text-80 pt-2 leading-tight">Description</label>
                    </div>
                    <div class="py-4 w-4/5">
                        <textarea
                            placeholder="Description"
                            :class="{ 'border-danger': getError('description') }"
                            class="w-full form-control form-input form-input-bordered"
                            id="description"
                            type="text"
                            style="height: 70px"
                            v-model="newItem.description"
                        />

                        <help-text class="error-text mt-2 text-danger" v-if="getError('description')">
                            {{ getError('description') }}
                        </help-text>
                    </div>
                </div>

                <div class="flex">
                    <div class="w-1/5 py-4">
                        <label class="inline-block text-80 pt-2 leading-tight">Order</label>
                    </div>
                    <div class="py-4 w-4/5">
                        <input
                            placeholder="order"
                            :class="{ 'border-danger': getError('order') }"
                            class="w-full form-control form-input form-input-bordered"
                            id="order"
                            type="text"
                            v-model="newItem.order"
                        />

                        <help-text class="error-text mt-2 text-danger" v-if="getError('order')">
                            {{ getError('order') }}
                        </help-text>
                    </div>
                </div>
            </form>
        </div>

        <div slot="buttons">
            <div class="ml-auto">
                <button
                    @click.prevent="$emit('closeModal')"
                    class="btn text-80 font-normal h-9 px-3 mr-3 btn-link"
                    type="button"
                >Close
                </button>

                <progress-button
                    @click.native.prevent="storeWithData(update ? 'updateItem' : 'createItem')"
                    :disabled="isItemUpdating"
                    :processing="isItemUpdating"
                >
                    {{ __(update ? 'update' : 'create') }}
                </progress-button>
            </div>
        </div>
    </modal>
</template>

<script>
import Modal from './Modal';

export default {
    props: [
        'newItem',
        'showModal',
        'update',
        'errors',
        'isItemUpdating',
    ],

    components: {Modal},
    data: () => ({
        toggleLabels: false,
        switchColor: null,
    }),

    mounted() {
        this.toggleLabels = {
            checked: this.__('novaMenuBuilder.menuItemActive'),
            unchecked: this.__('novaMenuBuilder.menuItemDisabled'),
        };
        this.switchColor = {checked: '#21b978', unchecked: '#dae1e7', disabled: '#eef1f4'};
    },

    computed: {},

    methods: {
        storeWithData(eventType) {
            this.$emit(eventType, this.newItem);
        },

        getError(key) {
            return (this.errors && this.errors[key] && this.errors[key][0]) || void 0;
        },
    },
};
</script>
