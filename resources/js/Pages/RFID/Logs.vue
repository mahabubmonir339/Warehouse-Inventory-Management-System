<template>
  <admin-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">RFID Scan Logs</h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            View and analyze historical RFID scan records
          </p>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >Warehouse</label
              >
              <select
                v-model="selectedWarehouse"
                @change="applyFilters"
                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              >
                <option
                  v-for="warehouse in warehouses"
                  :key="warehouse.id"
                  :value="warehouse.id"
                >
                  {{ warehouse.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >Action</label
              >
              <select
                v-model="filters.action"
                @change="applyFilters"
                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              >
                <option value="">All Actions</option>
                <option value="IN">Check-In</option>
                <option value="OUT">Check-Out</option>
                <option value="MOVE">Move</option>
                <option value="TRANSFER">Transfer</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >Days</label
              >
              <select
                v-model.number="filters.days"
                @change="applyFilters"
                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              >
                <option value="1">Last 24 Hours</option>
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
              </select>
            </div>

            <div class="flex items-end">
              <button
                @click="clearFilters"
                class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-md hover:bg-gray-400"
              >
                Clear Filters
              </button>
            </div>
          </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Date/Time
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Item
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Tag
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Reader
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Action
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Status
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="log in logs.data"
                  :key="log.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDateTime(log.created_at) }}
                  </td>
                  <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                    {{ log.item?.name || "-" }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ log.tag_code || "-" }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ log.reader?.name || "-" }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span
                      :class="{
                        'px-2 py-1 text-xs font-semibold rounded': true,
                        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200':
                          log.action === 'IN',
                        'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200':
                          log.action === 'OUT',
                        'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200':
                          log.action === 'MOVE',
                        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200':
                          log.action === 'TRANSFER',
                      }"
                    >
                      {{ log.action }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span
                      :class="{
                        'px-2 py-1 text-xs font-semibold rounded': true,
                        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200':
                          log.status === 'processed',
                        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200':
                          log.status === 'pending',
                        'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300':
                          log.status === 'duplicate',
                        'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200':
                          log.status === 'failed',
                      }"
                    >
                      {{ log.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div
            v-if="logs.links"
            class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"
          >
            <nav class="flex justify-between">
              <button
                v-for="link in logs.links"
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

          <div v-if="logs.data.length === 0" class="text-center py-8 text-gray-500">
            No scan logs found
          </div>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
  logs: Object,
  warehouses: Array,
  selectedWarehouse: Number,
  filters: Object,
});

const selectedWarehouse = ref(props.selectedWarehouse);
const logs = ref(props.logs);
const warehouses = ref(props.warehouses);

const filters = reactive({
  action: props.filters?.action || "",
  days: props.filters?.days || 1,
});

const formatDateTime = (date) => {
  return new Date(date).toLocaleString();
};

const applyFilters = () => {
  router.get(route("rfid.logs"), {
    warehouse_id: selectedWarehouse.value,
    action: filters.action || undefined,
    days: filters.days || undefined,
  });
};

const clearFilters = () => {
  selectedWarehouse.value = props.selectedWarehouse;
  filters.action = "";
  filters.days = 1;
  router.get(route("rfid.logs"), {
    warehouse_id: selectedWarehouse.value,
  });
};

const goToPage = (url) => {
  if (url) {
    router.visit(url);
  }
};
</script>
