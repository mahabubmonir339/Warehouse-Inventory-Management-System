<template>
  <admin-layout :title="$t('RFID Scan Logs')">
    <div class="px-4 md:px-0">
      <tec-section-title class="-mx-4 md:mx-0 mb-6">
        <template #title>{{ $t('RFID Scan Logs') }}</template>
        <template #description>{{ $t('Review the latest RFID tag scans and assigned items.') }}</template>
      </tec-section-title>

      <div class="bg-white rounded-md shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">{{ $t('Tag Code') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">{{ $t('Item') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">{{ $t('Location') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">{{ $t('Scanned At') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="log in logs" :key="log.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ log.tag_code }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ log.item ? log.item.name : '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ log.location ?? '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ log.scanned_at ?? log.created_at }}</td>
            </tr>
            <tr v-if="logs.length === 0">
              <td class="px-6 py-4 text-center" colspan="4">{{ $t('There is no data to display.') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </admin-layout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TecSectionTitle from '@/Jetstream/SectionTitle.vue';

export default {
  name: 'RFIDLogs',
  components: {
    AdminLayout,
    TecSectionTitle,
  },
  props: {
    logs: Array,
  },
};
</script>