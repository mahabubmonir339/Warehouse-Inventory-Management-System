<template>
  <admin-layout :title="$t('RFID Live Scan')">
    <div class="px-4 md:px-0">
      <tec-section-title class="-mx-4 md:mx-0 mb-6">
        <template #title>{{ $t('RFID Live Scan') }}</template>
        <template #description>{{ $t('Scan tags manually or connect an RFID reader to this endpoint.') }}</template>
      </tec-section-title>

      <div class="bg-white rounded-md shadow-sm p-6">
        <form @submit.prevent="submitScan" class="space-y-4">
          <div>
            <label for="tag_code" class="block text-sm font-medium text-gray-700">{{ $t('Tag Code') }}</label>
            <input
              id="tag_code"
              v-model="form.tag_code"
              type="text"
              class="mt-1 block w-full border-gray-300 rounded-md"
              placeholder="1234567890"
              required
            />
          </div>

          <div>
            <label for="location" class="block text-sm font-medium text-gray-700">{{ $t('Location') }}</label>
            <input
              id="location"
              v-model="form.location"
              type="text"
              class="mt-1 block w-full border-gray-300 rounded-md"
              placeholder="Warehouse A"
            />
          </div>

          <div>
            <label for="warehouse_id" class="block text-sm font-medium text-gray-700">{{ $t('Warehouse ID') }}</label>
            <input
              id="warehouse_id"
              v-model="form.warehouse_id"
              type="number"
              class="mt-1 block w-full border-gray-300 rounded-md"
              placeholder="1"
            />
          </div>

          <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
              {{ $t('Submit Scan') }}
            </button>
            <button type="button" @click="resetForm" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 hover:bg-gray-50">
              {{ $t('Reset') }}
            </button>
          </div>
        </form>

        <div class="mt-6 space-y-4">
          <div v-if="response" class="rounded-md border p-4 bg-green-50 text-green-800">
            <p class="font-semibold">{{ response.status }}</p>
            <pre class="whitespace-pre-wrap text-sm">{{ response }}</pre>
          </div>

          <div v-if="error" class="rounded-md border p-4 bg-red-50 text-red-800">
            <p class="font-semibold">{{ $t('Error') }}</p>
            <p>{{ error }}</p>
          </div>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TecSectionTitle from '@/Jetstream/SectionTitle.vue';

export default {
  name: 'RFIDLive',
  components: {
    AdminLayout,
    TecSectionTitle,
  },
  setup() {
    const form = useForm({
      tag_code: '',
      location: '',
      warehouse_id: 1,
    });

    const response = ref(null);
    const error = ref(null);

    const submitScan = async () => {
      error.value = null;
      response.value = null;

      try {
        const res = await axios.post('/api/rfid/scan', form);
        response.value = res.data;
        form.reset('tag_code', 'location');
      } catch (err) {
        error.value = err.response?.data?.error || err.response?.data?.message || err.message;
      }
    };

    const resetForm = () => {
      form.reset();
      response.value = null;
      error.value = null;
    };

    return {
      form,
      response,
      error,
      submitScan,
      resetForm,
    };
  },
};
</script>