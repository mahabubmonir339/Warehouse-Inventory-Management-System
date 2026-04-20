<template>
  <admin-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              RFID Tags Management
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              Create, manage and assign RFID tags
            </p>
          </div>
          <button
            @click="showCreateModal = true"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
          >
            + Create Tag
          </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Total Tags
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
              {{ summary.total_tags }}
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Assigned
            </div>
            <div class="mt-2 text-3xl font-bold text-green-600">
              {{ summary.assigned_tags }}
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Unassigned
            </div>
            <div class="mt-2 text-3xl font-bold text-orange-600">
              {{ summary.unassigned_tags }}
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Active</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">
              {{ summary.active_tags }}
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow mb-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >Status</label
              >
              <select
                v-model="filters.status"
                @change="applyFilters"
                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="lost">Lost</option>
                <option value="damaged">Damaged</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >Assignment</label
              >
              <select
                v-model="filters.assigned"
                @change="applyFilters"
                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              >
                <option value="">All Tags</option>
                <option value="yes">Assigned</option>
                <option value="no">Unassigned</option>
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

        <!-- Tags Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Tag Code
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Type
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Item
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Status
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Created
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="tag in tags.data"
                  :key="tag.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                    {{ tag.tag_code }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ tag.tag_type }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ tag.assignment?.item?.name || "-" }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span
                      :class="{
                        'px-2 py-1 text-xs font-semibold rounded': true,
                        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200':
                          tag.status === 'active',
                        'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300':
                          tag.status === 'inactive',
                        'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200':
                          tag.status === 'lost',
                        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200':
                          tag.status === 'damaged',
                      }"
                    >
                      {{ tag.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(tag.created_at) }}
                  </td>
                  <td class="px-6 py-4 text-sm space-x-2">
                    <button
                      @click="editTag(tag)"
                      class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-300"
                    >
                      Edit
                    </button>
                    <button
                      v-if="tag.assignment"
                      @click="unassignTag(tag)"
                      class="text-orange-600 hover:text-orange-900 dark:hover:text-orange-300"
                    >
                      Unassign
                    </button>
                    <button
                      @click="deleteTag(tag)"
                      class="text-red-600 hover:text-red-900 dark:hover:text-red-300"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div
            v-if="tags.links"
            class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"
          >
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

        <!-- Create/Edit Modal -->
        <div
          v-if="showCreateModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
              {{ editingTag ? "Edit Tag" : "Create New Tag" }}
            </h2>

            <form @submit.prevent="submitForm" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Tag Code</label
                >
                <input
                  v-model="form.tag_code"
                  type="text"
                  required
                  class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                  placeholder="TAG001"
                />
                <span v-if="errors.tag_code" class="text-red-600 text-sm">{{
                  errors.tag_code[0]
                }}</span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Type</label
                >
                <select
                  v-model="form.tag_type"
                  class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                >
                  <option value="UHF">UHF</option>
                  <option value="HF">HF</option>
                  <option value="LF">LF</option>
                  <option value="NFC">NFC</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Status</label
                >
                <select
                  v-model="form.status"
                  class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="lost">Lost</option>
                  <option value="damaged">Damaged</option>
                </select>
              </div>

              <div class="flex justify-end gap-4 mt-6">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="submitting"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                  {{ submitting ? "Saving..." : "Save" }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
  tags: Object,
  summary: Object,
  filters: Object,
});

const showCreateModal = ref(false);
const editingTag = ref(null);
const submitting = ref(false);
const errors = reactive({});

const filters = reactive({
  status: props.filters?.status || "",
  assigned: props.filters?.assigned || "",
});

const form = reactive({
  tag_code: "",
  tag_type: "UHF",
  status: "active",
});

const tags = ref(props.tags);
const summary = ref(props.summary);

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const editTag = (tag) => {
  editingTag.value = tag;
  form.tag_code = tag.tag_code;
  form.tag_type = tag.tag_type;
  form.status = tag.status;
  showCreateModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  editingTag.value = null;
  form.tag_code = "";
  form.tag_type = "UHF";
  form.status = "active";
  Object.keys(errors).forEach((key) => delete errors[key]);
};

// const submitForm = async () => {
//   submitting.value = true;
//   try {
//     if (editingTag.value) {
//       await axios.put(route("rfid.tags.update", editingTag.value.id), form);
//     } else {
//       await axios.post(route("rfid.tags.store"), form);
//     }
//     router.visit(route("rfid.tags"));
//     closeModal();
//   } catch (error) {
//     if (error.response?.data?.errors) {
//       Object.assign(errors, error.response.data.errors);
//     }
//   } finally {
//     submitting.value = false;
//   }
// };

const submitForm = () => {
  submitting.value = true;

  if (editingTag.value) {
    router.put(route("rfid.tags.update", editingTag.value.id), form, {
      onSuccess: () => closeModal(),
      onError: (err) => Object.assign(errors, err),
      onFinish: () => (submitting.value = false),
    });
  } else {
    router.post(route("rfid.tags.store"), form, {
      onSuccess: () => closeModal(),
      onError: (err) => Object.assign(errors, err),
      onFinish: () => (submitting.value = false),
    });
  }
};

const deleteTag = (tag) => {
  if (confirm("Are you sure you want to delete this tag?")) {
    router.delete(route("rfid.tags.destroy", tag.id));
  }
};

const unassignTag = (tag) => {
  if (confirm("Unassign this tag from the item?")) {
    router.post(route("rfid.tags.unassign", tag.id));
  }
};

const applyFilters = () => {
  router.get(route("rfid.tags"), {
    status: filters.status || undefined,
    assigned: filters.assigned || undefined,
  });
};

const clearFilters = () => {
  filters.status = "";
  filters.assigned = "";
  router.get(route("rfid.tags"));
};

const goToPage = (url) => {
  if (url) {
    router.visit(url);
  }
};
</script>
