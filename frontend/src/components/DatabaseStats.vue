<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Database Statistics</h2>
      <button 
        @click="refreshStats"
        :disabled="loading"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
      >
        {{ loading ? 'Loading...' : 'Refresh' }}
      </button>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-sm font-medium text-gray-500 mb-2">Database Size</h3>
        <p class="text-3xl font-bold text-gray-900">{{ dbStats.database?.size || 'N/A' }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-sm font-medium text-gray-500 mb-2">Active Connections</h3>
        <p class="text-3xl font-bold text-blue-600">
          {{ dbStats.database?.active_connections || 0 }}
        </p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-sm font-medium text-gray-500 mb-2">Total Connections</h3>
        <p class="text-3xl font-bold text-gray-900">
          {{ dbStats.database?.total_connections || 0 }}
        </p>
      </div>
    </div>

    <!-- Table Sizes -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8 border border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Table Sizes</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Table
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Size
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Row Count
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="table in dbStats.table_sizes" :key="table.tablename">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">
                {{ table.tablename }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ table.size }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ getRowCount(table.tablename) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Slow Queries -->
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Slow Queries (Top 10)</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Query Preview
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Calls
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Avg Time (ms)
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Max Time (ms)
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(query, idx) in slowQueries" :key="idx">
              <td class="px-4 py-3 text-sm text-gray-900 font-mono max-w-md truncate">
                {{ query.query_preview }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ query.calls }}
              </td>
              <td class="px-4 py-3 text-sm" :class="query.avg_time_ms > 100 ? 'text-red-600 font-semibold' : 'text-gray-600'">
                {{ query.avg_time_ms }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ query.max_time_ms }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Connection Details -->
    <div class="mt-8 bg-white rounded-lg shadow-sm p-6 border border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Active Connections</h3>
      <div class="space-y-2">
        <div v-for="stat in connectionStats" :key="stat.state" class="flex justify-between">
          <span class="text-sm text-gray-600">{{ stat.state || 'unknown' }}</span>
          <span class="text-sm font-semibold text-gray-900">{{ stat.count }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

const loading = ref(false);
const dbStats = ref({});
const slowQueries = ref([]);
const connectionStats = ref([]);

const refreshStats = async () => {
  loading.value = true;
  try {
    // Fetch database stats
    const dbResponse = await axios.get(`${API_URL}/api/v1/stats/db`);
    dbStats.value = dbResponse.data;

    // Fetch slow queries
    const queriesResponse = await axios.get(`${API_URL}/api/v1/stats/queries`);
    slowQueries.value = queriesResponse.data.slow_queries.slice(0, 10);

    // Fetch connections
    const connResponse = await axios.get(`${API_URL}/api/v1/stats/connections`);
    connectionStats.value = connResponse.data.stats;
  } catch (error) {
    console.error('Failed to fetch stats:', error);
  } finally {
    loading.value = false;
  }
};

const getRowCount = (tablename) => {
  const row = dbStats.value.row_counts?.find(r => r.tablename === tablename);
  return row ? row.row_count.toLocaleString() : 'N/A';
};

onMounted(() => {
  refreshStats();
  // Auto-refresh every 10 seconds
  setInterval(refreshStats, 10000);
});
</script>