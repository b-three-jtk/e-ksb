<template>
  <div class="max-w-5xl mx-auto border rounded-xl p-10 dark:bg-gray-800 shadow-md overflow-hidden">
    <!-- Header -->
    <div class="mb-8" v-if="showHeader">
      <h1 
        v-if="title" 
        class="font-head text-2xl font-bold text-gray-900 mb-2 dark:text-gray-100"
      >
        {{ title }}
      </h1>
      <p 
        v-if="subtitle" 
        class="text-gray-600 dark:text-gray-300"
      >
        {{ subtitle }}
      </p>
    </div>

    <!-- Divider -->
    <div 
      v-if="showDivider" 
      class="border-b p-0 m-0 border-gray-300"
    ></div>

    <!-- Content Area -->
    <div :class="contentClass">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EmptyContainer',
  props: {
    title: {
      type: String,
      default: ''
    },
    subtitle: {
      type: String,
      default: ''
    },
    showHeader: {
      type: Boolean,
      default: true
    },
    showDivider: {
      type: Boolean,
      default: true
    },
    contentClass: {
      type: String,
      default: 'mt-8'
    }
  }
}
</script>