<template>
    <div class="min-h-screen h-max xl:flex">
        <AppSidebar />
        <Backdrop />
        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']">
            <AppHeader />
            <div class="p-4 bg-admin dark:bg-gray-900 md:p-6 h-full w-full">
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import Backdrop from '../../Components/Backdrop.vue'
import { useSidebar } from '@/Composables/useSidebar'

const { isExpanded, isHovered } = useSidebar()
</script>
