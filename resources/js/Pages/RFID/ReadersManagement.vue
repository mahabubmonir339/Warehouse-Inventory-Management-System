<template>
  <admin-layout :title="$t('RFID Readers Management')">
    <div class="px-4 md:px-0 space-y-6">
      <!-- Header -->
      <tec-section-title class="-mx-4 md:mx-0">
        <template #title>{{ $t('RFID Readers Management') }}</template>
        <template #description>{{ $t('Configure and manage RFID reader devices') }}</template>
      </tec-section-title>

      <!-- Add Reader Form -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('Add New Reader') }}</h3>
        <form @submit.prevent="addReader" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('Reader Name') }}</label>
            <input v-model="newReader.name" type="text" required class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Gate 1">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('IP Address') }}</label>
            <input v-model="newReader.ip_address" type="text" required class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="192.168.1.100">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('Location') }}</label>
            <input v-model="newReader.location" type="text" required class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Main Gate">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('Warehouse') }}</label>
            <select v-model="newReader.warehouse_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
              <option value="">{{ $t('Select Warehouse') }}</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('Read Range (m)') }}</label>
            <input v-model="newReader.read_range" type="number" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="5">
          </div>

          <div class="flex items-end gap-2">
            <button type="submit" :disabled="loading" class="flex-1 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:bg-gray-400">
              {{ loading ? $t('Adding...') : $t('Add Reader') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Readers List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('Active Readers') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Name') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('IP Address') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Location') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Warehouse') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Status') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Range') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="reader in readers" :key="reader.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ reader.name }}</td>
                <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ reader.ip_address }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ reader.location }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ reader.warehouse?.name || 'N/A' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span :class="getStatusClass(reader.status)" class="px-2 py-1 rounded text-xs font-medium">
                    {{ reader.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ reader.read_range }}m</td>
                <td class="px-6 py-4 text-sm space-x-2">
                  <button @click="testReader(reader.id)" class="text-blue-600 hover:text-blue-900">{{ $t('Test') }}</button>
                  <button @click="editReader(reader)" class="text-green-600 hover:text-green-900">{{ $t('Edit') }}</button>
                  <button @click="deleteReader(reader.id)" class="text-red-600 hover:text-red-900">{{ $t('Delete') }}</button>
                </td>
              </tr>
              <tr v-if="readers.length === 0">
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">{{ $t('No readers configured') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TecSectionTitle from '@/Shared/TecSectionTitle.vue'

const props = defineProps({
  readers: Array,
  warehouses: Array,
  selectedWarehouse: [Number, String],
})

const loading = ref(false)

const newReader = ref({
  name: '',
  ip_address: '',
  location: '',
  warehouse_id: '',
  read_range: 5,
})

const addReader = async () => {
  loading.value = true
  // API call would go here
  loading.value = false
}

const testReader = async (readerId) => {
  // API call to test reader connectivity
}

const editReader = (reader) => {
  // Open edit modal
}

const deleteReader = async (readerId) => {
  if (confirm('Are you sure you want to delete this reader?')) {
    // API call to delete
  }
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>
