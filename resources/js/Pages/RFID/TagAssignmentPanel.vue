<template>
  <admin-layout :title="$t('RFID Tag Assignment')">
    <div class="px-4 md:px-0 space-y-6">
      <!-- Header -->
      <tec-section-title class="-mx-4 md:mx-0">
        <template #title>{{ $t('RFID Tag Assignment') }}</template>
        <template #description>{{ $t('Assign RFID tags to inventory items') }}</template>
      </tec-section-title>

      <!-- Assignment Form -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('Assign New Tag') }}</h3>
            <form @submit.prevent="assignTag" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('Select Tag') }}</label>
                <select v-model="form.rfid_tag_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                  <option value="">{{ $t('Choose a tag...') }}</option>
                  <option v-for="tag in unassignedTags" :key="tag.id" :value="tag.id">
                    {{ tag.tag_code }} ({{ tag.tag_type }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('Select Item') }}</label>
                <select v-model="form.item_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                  <option value="">{{ $t('Choose an item...') }}</option>
                  <option v-for="item in items" :key="item.id" :value="item.id">
                    {{ item.name }} ({{ item.code }})
                  </option>
                </select>
              </div>

              <button type="submit" :disabled="loading" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:bg-gray-400">
                {{ loading ? $t('Assigning...') : $t('Assign Tag') }}
              </button>
            </form>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="space-y-4">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">{{ $t('Total Tags') }}</div>
            <div class="text-3xl font-bold text-blue-600 mt-2">{{ tags.length }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">{{ $t('Assigned') }}</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ assignedCount }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">{{ $t('Unassigned') }}</div>
            <div class="text-3xl font-bold text-orange-600 mt-2">{{ unassignedCount }}</div>
          </div>
        </div>
      </div>

      <!-- Tags List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('Assigned Tags') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Tag Code') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Type') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Item') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Status') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Assigned') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="tag in tags" :key="tag.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ tag.tag_code }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ tag.tag_type }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ tag.assignment?.item?.name || 'Unassigned' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span :class="getStatusClass(tag.status)" class="px-2 py-1 rounded text-xs font-medium">
                    {{ tag.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ tag.assignment?.assigned_at ? formatDate(tag.assignment.assigned_at) : 'N/A' }}</td>
                <td class="px-6 py-4 text-sm space-x-2">
                  <button v-if="tag.assignment" @click="unassignTag(tag.id)" class="text-red-600 hover:text-red-900">{{ $t('Unassign') }}</button>
                </td>
              </tr>
              <tr v-if="tags.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">{{ $t('No tags found') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TecSectionTitle from '@/Shared/TecSectionTitle.vue'

const props = defineProps({
  tags: Array,
  items: Array,
  warehouses: Array,
})

const form = ref({
  rfid_tag_id: '',
  item_id: '',
})

const loading = ref(false)

const unassignedTags = computed(() => props.tags.filter(t => !t.assignment))
const assignedCount = computed(() => props.tags.filter(t => t.assignment).length)
const unassignedCount = computed(() => props.tags.filter(t => !t.assignment).length)

const assignTag = async () => {
  loading.value = true
  // API call would go here
  loading.value = false
}

const unassignTag = async (tagId) => {
  // API call would go here
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    lost: 'bg-red-100 text-red-800',
    damaged: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>
