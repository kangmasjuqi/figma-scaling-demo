<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Files</h2>
      <button 
        @click="createFile"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        + Create File
      </button>
    </div>

    <!-- Search and filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="searchQuery"
          @input="searchFiles"
          type="text"
          placeholder="Search files..."
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
        <select 
          v-model="sortBy"
          @change="fetchFiles"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="last_modified">Last Modified</option>
          <option value="created_at">Created Date</option>
          <option value="view_count">Most Viewed</option>
          <option value="name">Name</option>
        </select>
        <button 
          @click="fetchFiles"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Loading files...</p>
    </div>

    <!-- Files grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="file in files" 
        :key="file.id"
        class="bg-white rounded-lg shadow-sm hover:shadow-md transition p-6 border border-gray-200"
      >
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-lg font-semibold text-gray-900 truncate flex-1">
            {{ file.name }}
          </h3>
          <span class="ml-2 text-xs text-gray-500">v{{ file.version }}</span>
        </div>
        
        <div class="space-y-2 text-sm text-gray-600">
          <p>
            <span class="font-medium">Owner:</span> 
            {{ file.owner?.name || 'Unknown' }}
          </p>
          <p>
            <span class="font-medium">Organization:</span> 
            {{ file.organization?.name || 'N/A' }}
          </p>
          <p>
            <span class="font-medium">Views:</span> 
            {{ file.view_count.toLocaleString() }}
          </p>
          <p>
            <span class="font-medium">Modified:</span> 
            {{ formatDate(file.last_modified) }}
          </p>
        </div>

        <div class="mt-4 flex space-x-2">
          <button 
            @click="viewFile(file.id)"
            class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition text-sm"
          >
            View
          </button>
          <button 
            @click="deleteFile(file.id)"
            class="px-3 py-2 bg-red-50 text-red-600 rounded hover:bg-red-100 transition text-sm"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div
    v-if="!loading && totalPages > 1"
    class="mt-8 flex justify-center items-center gap-2 flex-wrap"
    >
        <!-- First -->
        <button
            :disabled="currentPage === 1"
            @click="goToPage(1)"
            class="px-3 py-2 border rounded-lg disabled:opacity-40"
        >
            First
        </button>

        <!-- Prev -->
        <button
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
            class="px-3 py-2 border rounded-lg disabled:opacity-40"
        >
            Prev
        </button>

        <!-- Page numbers -->
        <button
            v-for="page in paginationPages"
            :key="page"
            @click="goToPage(page)"
            :class="page === currentPage
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white text-gray-700 hover:bg-gray-50'"
            class="px-3 py-2 border rounded-lg transition"
        >
            {{ page }}
        </button>

        <!-- Next -->
        <button
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
            class="px-3 py-2 border rounded-lg disabled:opacity-40"
        >
            Next
        </button>

        <!-- Last -->
        <button
            :disabled="currentPage === totalPages"
            @click="goToPage(totalPages)"
            class="px-3 py-2 border rounded-lg disabled:opacity-40"
        >
            Last
        </button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

const files = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const sortBy = ref('last_modified');
const currentPage = ref(1);
const totalPages = ref(1);

const paginationPages = computed(() => {
  const p = currentPage.value;
  const pages = new Set();

  // Always include current
  pages.add(p);

  // ±1
  if (p - 1 >= 1) pages.add(p - 1);
  if (p + 1 <= totalPages.value) pages.add(p + 1);

  // ±5
  if (p - 5 >= 1) pages.add(p - 5);
  if (p + 5 <= totalPages.value) pages.add(p + 5);

  return Array.from(pages).sort((a, b) => a - b);
});

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return;
  currentPage.value = page;
  fetchFiles();
};

const fetchFiles = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`${API_URL}/api/v1/files`, {
      params: {
        page: currentPage.value,
        per_page: 12,
        sort_by: sortBy.value,
        search: searchQuery.value || undefined
      }
    });
    files.value = response.data.data;
    totalPages.value = response.data.last_page;
  } catch (error) {
    console.error('Failed to fetch files:', error);
  } finally {
    loading.value = false;
  }
};

const searchFiles = () => {
  currentPage.value = 1;
  fetchFiles();
};

const viewFile = async (fileId) => {
  try {
    await axios.post(`${API_URL}/api/v1/files/${fileId}/view`, {
      user_id: files.value[0]?.owner_id // Demo: use first file's owner
    });
    fetchFiles(); // Refresh to show updated view count
  } catch (error) {
    console.error('Failed to record view:', error);
  }
};

const deleteFile = async (fileId) => {
  if (!confirm('Delete this file?')) return;
  
  try {
    await axios.delete(`${API_URL}/api/v1/files/${fileId}`);
    fetchFiles();
  } catch (error) {
    console.error('Failed to delete file:', error);
  }
};

const createFile = () => {
  alert('Create file modal would open here (simplified for demo)');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  fetchFiles();
});
</script>