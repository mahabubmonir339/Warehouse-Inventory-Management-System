<template>
  <admin-layout :title="$t('RFID Scan Logs')">
    <div class="px-4 md:px-0 space-y-6">
      <!-- Header -->
      <tec-section-title class="-mx-4 md:mx-0">
        <template #title>{{ $t("RFID Scan Logs") }}</template>
        <template #description>{{
          $t("Complete history of all RFID scan events and inventory transactions")
        }}</template>
      </tec-section-title>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{
              $t("Warehouse")
            }}</label>
            <select
              v-model="filters.warehouse_id"
              class="w-full border border-gray-300 rounded-md px-3 py-2"
            >
              <option value="">{{ $t("All Warehouses") }}</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                {{ wh.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{
              $t("Action")
            }}</label>
            <select
              v-model="filters.action"
              class="w-full border border-gray-300 rounded-md px-3 py-2"
            >
              <option value="">{{ $t("All Actions") }}</option>
              <option value="IN">{{ $t("IN") }}</option>
              <option value="OUT">{{ $t("OUT") }}</option>
              <option value="MOVE">{{ $t("MOVE") }}</option>
              <option value="TRANSFER">{{ $t("TRANSFER") }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{
              $t("Period")
            }}</label>
            <select
              v-model="filters.days"
              class="w-full border border-gray-300 rounded-md px-3 py-2"
            >
              <option value="1">{{ $t("Last 24 Hours") }}</option>
              <option value="7">{{ $t("Last 7 Days") }}</option>
              <option value="30">{{ $t("Last 30 Days") }}</option>
              <option value="90">{{ $t("Last 90 Days") }}</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <button
              @click="applyFilters"
              class="flex-1 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700"
            >
              {{ $t("Apply Filters") }}
            </button>
            <button
              @click="resetFilters"
              class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300"
            >
              {{ $t("Reset") }}
            </button>
          </div>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t("Scan Logs") }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Timestamp") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Item") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Tag") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Reader") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Warehouse") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Action") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Status") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                >
                  {{ $t("Related") }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-600">
                  {{ formatDateTime(log.created_at) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">
                  {{ log.item?.name || "N/A" }}
                </td>
                <td class="px-6 py-4 text-sm font-mono text-gray-600">
                  {{ log.tag_code }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                  {{ log.reader?.name || "N/A" }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                  {{ log.warehouse?.name || "N/A" }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="getActionBadgeClass(log.action)"
                    class="px-2 py-1 rounded text-xs font-medium"
                  >
                    {{ log.action }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="getStatusBadgeClass(log.status)"
                    class="px-2 py-1 rounded text-xs font-medium"
                  >
                    {{ log.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                  <span v-if="log.related_model" class="text-blue-600"
                    >{{ log.related_model }} #{{ log.related_id }}</span
                  >
                  <span v-else class="text-gray-400">—</span>
                </td>
              </tr>
              <tr v-if="logs.length === 0">
                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                  {{ $t("No logs found") }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TecSectionTitle from "@/Shared/TecSectionTitle.vue";

const props = defineProps({
  logs: Array,
  warehouses: Array,
  selectedWarehouse: [Number, String],
  filters: Object,
});

const filters = ref({
  warehouse_id: props.selectedWarehouse || "",
  action: "",
  days: "7",
  ...props.filters,
});

const applyFilters = () => {
  // Trigger filter application
};

const resetFilters = () => {
  filters.value = {
    warehouse_id: props.selectedWarehouse || "",
    action: "",
    days: "7",
  };
};

const formatDateTime = (date) => {
  if (!date) return "N/A";
  return new Date(date).toLocaleString();
};

const getActionBadgeClass = (action) => {
  const classes = {
    IN: "bg-green-100 text-green-800",
    OUT: "bg-red-100 text-red-800",
    MOVE: "bg-blue-100 text-blue-800",
    TRANSFER: "bg-purple-100 text-purple-800",
  };
  return classes[action] || "bg-gray-100 text-gray-800";
};

const getStatusBadgeClass = (status) => {
  const classes = {
    processed: "bg-green-100 text-green-800",
    pending: "bg-yellow-100 text-yellow-800",
    failed: "bg-red-100 text-red-800",
    duplicate: "bg-gray-100 text-gray-800",
  };
  return classes[status] || "bg-gray-100 text-gray-800";
};
</script>
