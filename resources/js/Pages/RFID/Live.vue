<template>
  <admin-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Live RFID Monitor
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Real-time scan dashboard and statistics
          </p>
        </div>

        <!-- Warehouse Selector -->
        <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Select Warehouse</label
          >
          <select
            v-model="selectedWarehouse"
            @change="refreshData"
            class="mt-2 w-full md:w-64 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
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

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Total Scans Today
            </div>
            <div class="mt-2 flex items-baseline">
              <span class="text-3xl font-bold text-gray-900 dark:text-white">{{
                summary.total_today
              }}</span>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Success Rate
            </div>
            <div class="mt-2 flex items-baseline">
              <span class="text-3xl font-bold text-green-600"
                >{{ summary.success_rate }}%</span
              >
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Active Readers
            </div>
            <div class="mt-2 flex items-baseline">
              <span class="text-3xl font-bold text-blue-600">{{ readers.length }}</span>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Check-ins
            </div>
            <div class="mt-2 flex items-baseline">
              <span class="text-3xl font-bold text-purple-600">
                {{ summary.scans_by_action?.IN || 0 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Action Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Scans by Action
            </h3>
            <div class="space-y-2">
              <div
                v-for="(count, action) in summary.scans_by_action"
                :key="action"
                class="flex justify-between"
              >
                <span class="text-gray-600 dark:text-gray-400">{{ action }}</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{
                  count
                }}</span>
              </div>
            </div>
          </div>

          <!-- Readers Status -->
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Active Readers
            </h3>
            <div class="space-y-2">
              <div v-if="readers.length === 0" class="text-gray-500">
                No active readers
              </div>
              <div
                v-for="reader in readers"
                :key="reader.id"
                class="flex justify-between items-center"
              >
                <span class="text-gray-600 dark:text-gray-400">{{ reader.name }}</span>
                <span
                  class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-semibold rounded"
                >
                  Active
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Scans -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Recent Scans (Last 24 Hours)
            </h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Item
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
                    Time
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
                  v-for="scan in recentScans"
                  :key="scan.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                    {{ scan.item?.name || "Unknown" }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ scan.reader?.name || "Unknown" }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span
                      :class="{
                        'px-2 py-1 text-xs font-semibold rounded': true,
                        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200':
                          scan.action === 'IN',
                        'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200':
                          scan.action === 'OUT',
                        'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200':
                          scan.action === 'MOVE',
                        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200':
                          scan.action === 'TRANSFER',
                      }"
                    >
                      {{ scan.action }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(scan.created_at) }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span
                      :class="{
                        'px-2 py-1 text-xs font-semibold rounded': true,
                        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200':
                          scan.status === 'processed',
                        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200':
                          scan.status === 'pending',
                        'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300':
                          scan.status === 'duplicate',
                      }"
                    >
                      {{ scan.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="recentScans.length === 0" class="text-center py-8 text-gray-500">
              No scans in the last 24 hours
            </div>
          </div>
        </div>

        <!-- Auto Refresh -->
        <div
          class="mt-6 flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow"
        >
          <div class="flex items-center">
            <input
              type="checkbox"
              v-model="autoRefresh"
              id="autoRefresh"
              class="h-4 w-4 text-blue-600"
            />
            <label
              for="autoRefresh"
              class="ml-2 text-sm text-gray-700 dark:text-gray-300"
            >
              Auto-refresh ({{ refreshInterval }}s)
            </label>
          </div>
          <button
            @click="refreshData"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
          >
            Refresh Now
          </button>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const page = usePage();
const props = defineProps({
  warehouses: Array,
  selectedWarehouse: Number,
  recentScans: Array,
  summary: Object,
  readers: Array,
});

const selectedWarehouse = ref(props.selectedWarehouse);
const recentScans = ref(props.recentScans);
const summary = ref(props.summary);
const readers = ref(props.readers);
const warehouses = ref(props.warehouses);
const autoRefresh = ref(true);
const refreshInterval = ref(10);
let refreshTimer = null;

const formatDate = (date) => {
  return new Date(date).toLocaleString();
};

const refreshData = () => {
  route("rfid.live", { warehouse: selectedWarehouse.value });
};

const startAutoRefresh = () => {
  if (autoRefresh.value) {
    refreshTimer = setInterval(refreshData, refreshInterval.value * 1000);
  }
};

const stopAutoRefresh = () => {
  if (refreshTimer) {
    clearInterval(refreshTimer);
  }
};

onMounted(() => {
  startAutoRefresh();
});

onUnmounted(() => {
  stopAutoRefresh();
});
</script>
