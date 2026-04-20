<template>
  <admin-layout :title="$t('RFID Live Scan Monitor')">
    <div class="px-4 md:px-0 space-y-6">
      <!-- Header Section -->
      <tec-section-title class="-mx-4 md:mx-0">
        <template #title>{{ $t('RFID Live Scan Monitor') }}</template>
        <template #description>{{ $t('Real-time monitoring of RFID scans with automated inventory synchronization') }}</template>
      </tec-section-title>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-gray-600 text-sm font-medium">{{ $t('Total Scans Today') }}</div>
          <div class="text-3xl font-bold text-blue-600 mt-2">{{ summary.total_today }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-gray-600 text-sm font-medium">{{ $t('Success Rate') }}</div>
          <div class="text-3xl font-bold text-green-600 mt-2">{{ summary.success_rate }}%</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-gray-600 text-sm font-medium">{{ $t('Unique Items') }}</div>
          <div class="text-3xl font-bold text-purple-600 mt-2">{{ summary.unique_items || 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-gray-600 text-sm font-medium">{{ $t('Active Readers') }}</div>
          <div class="text-3xl font-bold text-orange-600 mt-2">{{ readers.length }}</div>
        </div>
      </div>

      <!-- Warehouse Selector -->
      <div class="bg-white rounded-lg shadow p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('Select Warehouse') }}</label>
        <select v-model="selectedWarehouse" class="border border-gray-300 rounded-md px-4 py-2 w-full">
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
      </div>

      <!-- Recent Scans Section -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('Recent Scans') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Item') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Tag UID') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Reader') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Action') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Status') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Time') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="scan in recentScans" :key="scan.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-900">{{ scan.item?.name || 'N/A' }}</td>
                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ scan.tag_code }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ scan.reader?.name || 'N/A' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span :class="getActionBadgeClass(scan.action)" class="px-2 py-1 rounded text-xs font-medium">
                    {{ scan.action }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span :class="getStatusBadgeClass(scan.status)" class="px-2 py-1 rounded text-xs font-medium">
                    {{ scan.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ formatTime(scan.created_at) }}</td>
              </tr>
              <tr v-if="recentScans.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">{{ $t('No scans yet') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Action by Type Chart -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('Scans by Action Type') }}</h3>
          <div class="space-y-3">
            <div v-for="(count, action) in summary.scans_by_action" :key="action" class="flex items-center justify-between">
              <span class="text-sm text-gray-700">{{ action }}</span>
              <div class="flex items-center gap-2">
                <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div :style="{ width: getPercentage(count, summary.total_today) + '%' }" class="h-full bg-blue-500"></div>
                </div>
                <span class="text-sm font-medium text-gray-900">{{ count }}</span>
              </div>
            </div>
            <div v-if="!Object.keys(summary.scans_by_action).length" class="text-center py-4 text-gray-500">
              {{ $t('No scans yet') }}
            </div>
          </div>
        </div>

        <!-- Active Readers -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('Active Readers') }}</h3>
          <div class="space-y-3">
            <div v-for="reader in readers" :key="reader.id" class="flex items-center justify-between p-3 border border-gray-200 rounded">
              <div>
                <p class="font-medium text-gray-900">{{ reader.name }}</p>
                <p class="text-sm text-gray-600">{{ reader.location }}</p>
              </div>
              <span :class="{ 'bg-green-100 text-green-800': reader.status === 'active', 'bg-gray-100 text-gray-800': reader.status !== 'active' }" class="px-2 py-1 rounded text-xs font-medium">
                {{ reader.status }}
              </span>
            </div>
            <div v-if="readers.length === 0" class="text-center py-4 text-gray-500">
              {{ $t('No active readers') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TecSectionTitle from '@/Shared/TecSectionTitle.vue'

const props = defineProps({
  warehouses: Array,
  selectedWarehouse: [Number, String],
  recentScans: Array,
  summary: Object,
  readers: Array,
})

const selectedWarehouse = ref(props.selectedWarehouse)

const formatTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleTimeString()
}

const getActionBadgeClass = (action) => {
  const classes = {
    IN: 'bg-green-100 text-green-800',
    OUT: 'bg-red-100 text-red-800',
    MOVE: 'bg-blue-100 text-blue-800',
    TRANSFER: 'bg-purple-100 text-purple-800',
  }
  return classes[action] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    processed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    failed: 'bg-red-100 text-red-800',
    duplicate: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPercentage = (value, total) => {
  if (total === 0) return 0
  return Math.round((value / total) * 100)
}
</script>
