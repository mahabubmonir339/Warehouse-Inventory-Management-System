<template>
    <admin-layout>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tag Assignment</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Assign RFID tags to inventory items</p>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Warehouse</label>
            <select
              v-model="selectedWarehouse"
              class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
            >
              <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                {{ warehouse.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Item</label>
            <input
              v-model="itemSearch"
              type="text"
              placeholder="Search by name or code..."
              class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
            />
          </div>
        </div>
      </div>

      <!-- Assignment Form -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Assign</h3>
        <form @submit.prevent="submitAssignment" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Tag</label>
              <select
                v-model="form.rfid_tag_id"
                required
                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              >
                <option value="">Choose a tag...</option>
                <option
                  v-for="tag in availableTags"
                  :key="tag.id"
                  :value="tag.id"
                >
                  {{ tag.tag_code }} ({{ tag.tag_type }})
                </option>
              </select>
              <span v-if="errors.rfid_tag_id" class="text-red-600 text-sm">{{ errors.rfid_tag_id[0] }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Item</label>
              <select
                v-model="form.item_id"
                required
                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              >
                <option value="">Choose an item...</option>
                <option
                  v-for="item in filteredItems"
                  :key="item.id"
                  :value="item.id"
                >
                  {{ item.name }} ({{ item.code }})
                </option>
              </select>
              <span v-if="errors.item_id" class="text-red-600 text-sm">{{ errors.item_id[0] }}</span>
            </div>

            <div class="flex items-end">
              <button
                type="submit"
                :disabled="submitting"
                class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
              >
                {{ submitting ? 'Assigning...' : 'Assign Tag' }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Tags List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Available Tags</h3>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tag Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Assigned</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="tag in tags.data" :key="tag.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ tag.tag_code }}</td>
                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                  <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                    {{ tag.tag_type }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                  {{ tag.assignment?.item?.name || '-' }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <span :class="{
                    'px-2 py-1 text-xs font-semibold rounded': true,
                    'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': tag.status === 'active',
                    'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300': tag.status === 'inactive',
                  }">
                    {{ tag.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span v-if="tag.assignment" class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold rounded">
                    Yes
                  </span>
                  <span v-else class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 text-xs font-semibold rounded">
                    No
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="tags.links" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
          <nav class="flex justify-between">
            <button
              v-for="link in tags.links"
              :key="link.label"
              :disabled="!link.url || link.active"
              @click="goToPage(link.url)"
              :class="{
                'px-3 py-1 text-sm rounded': true,
                'bg-blue-600 text-white': link.active,
                'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white': !link.active,
              }"
              v-html="link.label"
            />
          </nav>
        </div>
      </div>
    </div>
  </div>
  </admin-layout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  tags: Object,
  items: Array,
  warehouses: Array,
});

const selectedWarehouse = ref(props.warehouses[0]?.id || null);
const itemSearch = ref('');
const submitting = ref(false);
const errors = reactive({});
const tags = ref(props.tags);

const form = reactive({
  rfid_tag_id: '',
  item_id: '',
  warehouse_id: selectedWarehouse.value,
});

const availableTags = computed(() => {
  return props.tags?.data?.filter(tag => !tag.assignment) || [];
});

const filteredItems = computed(() => {
  return props.items.filter(item => {
    const search = itemSearch.value.toLowerCase();
    return item.name.toLowerCase().includes(search) ||
           item.code.toLowerCase().includes(search);
  });
});

const submitAssignment = async () => {
  submitting.value = true;
  try {
    const response = await axios.post(route('rfid.assign.store'), {
      rfid_tag_id: form.rfid_tag_id,
      item_id: form.item_id,
    });
    
    // Reset form
    form.rfid_tag_id = '';
    form.item_id = '';
    Object.keys(errors).forEach(key => delete errors[key]);
    
    // Refresh data
    router.visit(route('rfid.assign'));
  } catch (error) {
    if (error.response?.data?.errors) {
      Object.assign(errors, error.response.data.errors);
    }
  } finally {
    submitting.value = false;
  }
};

const goToPage = (url) => {
  if (url) {
    router.visit(url);
  }
};
</script>
