<template>
    <div class="min-h-screen xl:flex">
        <AppSidebar />
        <Backdrop />
        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']">
            <AppHeader />
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
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
