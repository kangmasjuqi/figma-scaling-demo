<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="flex items-center space-x-8">
            <h1 class="text-xl font-bold text-gray-900">Figma Scaling Demo</h1>
            <div class="flex space-x-4">
              <button 
                @click="activeView = 'files'"
                :class="activeView === 'files' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'"
                class="px-3 py-2 text-sm font-medium"
              >
                Files
              </button>
              <button 
                @click="activeView = 'stats'"
                :class="activeView === 'stats' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'"
                class="px-3 py-2 text-sm font-medium"
              >
                DB Stats
              </button>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">Phase 0: Monolith</span>
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <FilesList v-if="activeView === 'files'" />
      <DatabaseStats v-else-if="activeView === 'stats'" />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import FilesList from './components/FilesList.vue';
import DatabaseStats from './components/DatabaseStats.vue';

const activeView = ref('files');
</script>

<style>
#app {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}
</style>