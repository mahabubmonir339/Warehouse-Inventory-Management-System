<template>
  <admin-layout :title="$t('Tag Assign')">
    <div class="px-4 md:px-0">
      <tec-section-title class="-mx-4 md:mx-0 mb-6">
        <template #title>{{ $t('Tag Assign') }}</template>
        <template #description>{{ $t('Assign RFID tags to inventory items.') }}</template>
      </tec-section-title>

      <div class="bg-white rounded-md shadow-sm p-6">
        <div v-if="flash.success" class="mb-4 rounded-md bg-green-50 border border-green-200 p-4 text-green-800">
          {{ flash.success }}
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label for="tag" class="block text-sm font-medium text-gray-700">{{ $t('Select Tag') }}</label>
            <select v-model="form.rfid_tag_id" id="tag" class="mt-1 block w-full border-gray-300 rounded-md" required>
              <option value="">{{ $t('Choose a tag') }}</option>
              <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.tag_code }}</option>
            </select>
          </div>

          <div>
            <label for="item" class="block text-sm font-medium text-gray-700">{{ $t('Select Item') }}</label>
            <select v-model="form.item_id" id="item" class="mt-1 block w-full border-gray-300 rounded-md" required>
              <option value="">{{ $t('Choose an item') }}</option>
              <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }}</option>
            </select>
          </div>

          <button type="submit" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
            {{ $t('Assign Tag') }}
          </button>
        </form>
      </div>
    </div>
  </admin-layout>
</template>

<script>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TecSectionTitle from '@/Jetstream/SectionTitle.vue';

export default {
  name: 'RFIDAssign',
  components: {
    AdminLayout,
    TecSectionTitle,
  },
  props: {
    tags: Array,
    items: Array,
  },
  setup() {
    const form = useForm({
      rfid_tag_id: '',
      item_id: '',
    });

    //const { props } = usePage();
    const page = usePage();
    const submitForm = () => {
      form.post(route('rfid.assign.store'), {
        onSuccess: () => {
          form.reset();
        },
      });
    };

    // return { form, submitForm, flash: props.value.flash };
    return {
      form,
      submitForm,
      flash: page.props.flash || {}, // ✅ safe access
    };
      },
};
</script>