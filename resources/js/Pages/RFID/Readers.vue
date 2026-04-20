<template>
  <admin-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              RFID Readers Management
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              Configure and monitor RFID reader devices
            </p>
          </div>
          <button
            @click="showCreateModal = true"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
          >
            + Add Reader
          </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Total Readers
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
              {{ summary.total_readers }}
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Active</div>
            <div class="mt-2 text-3xl font-bold text-green-600">
              {{ summary.active_readers }}
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">
              Inactive
            </div>
            <div class="mt-2 text-3xl font-bold text-red-600">
              {{ summary.inactive_readers }}
            </div>
          </div>
        </div>

        <!-- Warehouse Selector -->
        <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Select Warehouse</label
          >
          <select
            v-model="selectedWarehouse"
            @change="filterByWarehouse"
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

        <!-- Readers Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Name
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    IP Address
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Location
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Range
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                  >
                    Status
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
                  v-for="reader in readers.data"
                  :key="reader.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                    {{ reader.name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ reader.ip_address }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ reader.location }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ reader.read_range }}m
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span
                      :class="{
                        'px-2 py-1 text-xs font-semibold rounded': true,
                        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200':
                          reader.status === 'active',
                        'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200':
                          reader.status === 'inactive',
                        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200':
                          reader.status === 'maintenance',
                      }"
                    >
                      {{ reader.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm space-x-2">
                    <button
                      @click="editReader(reader)"
                      class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-300"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteReader(reader)"
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
            v-if="readers.links"
            class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"
          >
            <nav class="flex justify-between">
              <button
                v-for="link in readers.links"
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

          <div v-if="readers.data.length === 0" class="text-center py-8 text-gray-500">
            No readers found
          </div>
        </div>

        <!-- Create/Edit Modal -->
        <div
          v-if="showCreateModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
              {{ editingReader ? "Edit Reader" : "Add New Reader" }}
            </h2>

            <form @submit.prevent="submitForm" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Name</label
                >
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                  placeholder="Main Gate Reader"
                />
                <span v-if="errors.name" class="text-red-600 text-sm">{{
                  errors.name[0]
                }}</span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >IP Address</label
                >
                <input
                  v-model="form.ip_address"
                  type="text"
                  required
                  class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                  placeholder="192.168.1.100"
                />
                <span v-if="errors.ip_address" class="text-red-600 text-sm">{{
                  errors.ip_address[0]
                }}</span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Location</label
                >
                <input
                  v-model="form.location"
                  type="text"
                  required
                  class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                  placeholder="Warehouse Entrance"
                />
                <span v-if="errors.location" class="text-red-600 text-sm">{{
                  errors.location[0]
                }}</span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Read Range (meters)</label
                >
                <input
                  v-model.number="form.read_range"
                  type="number"
                  min="1"
                  max="100"
                  class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
                  placeholder="5"
                />
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
                  <option value="maintenance">Maintenance</option>
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
  readers: Object,
  warehouses: Array,
  selectedWarehouse: Number,
  summary: Object,
});

const showCreateModal = ref(false);
const editingReader = ref(null);
const submitting = ref(false);
const errors = reactive({});

const selectedWarehouse = ref(props.selectedWarehouse);
const readers = ref(props.readers);
const warehouses = ref(props.warehouses);
const summary = ref(props.summary);

const form = reactive({
  name: "",
  ip_address: "",
  warehouse_id: props.selectedWarehouse,
  location: "",
  status: "active",
  read_range: 5,
  frequency: "",
  protocol: "TCP",
  port: 9096,
});

const editReader = (reader) => {
  editingReader.value = reader;
  form.name = reader.name;
  form.ip_address = reader.ip_address;
  form.warehouse_id = reader.warehouse_id;
  form.location = reader.location;
  form.status = reader.status;
  form.read_range = reader.read_range;
  form.frequency = reader.frequency || "";
  showCreateModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  editingReader.value = null;
  form.name = "";
  form.ip_address = "";
  form.warehouse_id = selectedWarehouse.value;
  form.location = "";
  form.status = "active";
  form.read_range = 5;
  form.frequency = "";
  Object.keys(errors).forEach((key) => delete errors[key]);
};

const submitForm = async () => {
  submitting.value = true;
  try {
    if (editingReader.value) {
      await axios.put(route("rfid.readers.update", editingReader.value.id), form);
    } else {
      await axios.post(route("rfid.readers.store"), form);
    }
    router.visit(route("rfid.readers", { warehouse_id: selectedWarehouse.value }));
    closeModal();
  } catch (error) {
    if (error.response?.data?.errors) {
      Object.assign(errors, error.response.data.errors);
    }
  } finally {
    submitting.value = false;
  }
};

const deleteReader = (reader) => {
  if (confirm("Are you sure you want to delete this reader?")) {
    router.delete(route("rfid.readers.destroy", reader.id));
  }
};

const filterByWarehouse = () => {
  router.visit(route("rfid.readers", { warehouse_id: selectedWarehouse.value }));
};

const goToPage = (url) => {
  if (url) {
    router.visit(url);
  }
};
</script>
