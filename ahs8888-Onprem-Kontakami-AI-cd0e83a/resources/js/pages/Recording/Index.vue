<template>
    <AppLayout title="Recording List">
        <!-- Ticket Linking Statistics -->
        <TicketStats 
            v-if="ticketStats" 
            :stats="ticketStats" 
            class="px-4 md:px-8 pt-4"
        />
        
        <!-- Unlinked Recordings Banner -->
        <div 
            v-if="unlinkedCount > 0" 
            class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-lg shadow-sm mx-4 md:mx-8"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="isax icon-info-circle text-yellow-600 text-2xl mr-3"></i>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">
                            {{ unlinkedCount }} recording(s) need ticket linking
                        </p>
                        <p class="text-xs text-yellow-700 mt-1">
                            Import ticket information to link these recordings
                        </p>
                    </div>
                </div>
                <a 
                    :href="route('ticket-import.index')"
                    class="flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors"
                >
                    Import Tickets
                    <i class="isax icon-arrow-right-3 ml-2"></i>
                </a>
            </div>
        </div>
        
        <RecordingList :paginate="paginate" />
    </AppLayout>
</template>
<script setup lang="ts">
import RecordingList from "@/Components/Module/Recording/RecordingList.vue"
import TicketStats from "@/Components/Module/Recording/TicketStats.vue"
import { usePaginate } from "@/Hooks/usePaginate";
import { provideUploadState } from "@/Hooks/uploadState";
import AppLayout from "@/Layouts/AppLayout.vue"

interface Props {
    unlinkedCount?: number;
    ticketStats?: {
        total: number;
        linked: number;
        unlinked: number;
    };
}

const props = withDefaults(defineProps<Props>(), {
    unlinkedCount: 0,
    ticketStats: () => ({ total: 0, linked: 0, unlinked: 0 })
});

// Provide upload state for child components
provideUploadState();

const paginate = usePaginate({
    route: route('api.recordings.datatable')
})
</script>
