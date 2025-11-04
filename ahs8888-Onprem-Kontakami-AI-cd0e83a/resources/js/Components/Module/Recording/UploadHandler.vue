<template>
    <ButtonPrimary icon="isax icon-folder-add" @click="showConfirmModal" :disabled="uploadState.uploading.value">
        New Recording
    </ButtonPrimary>

    <input type="file" webkitdirectory directory multiple hidden @change="handleFolderUpload" id="file-upload" />
    
    <!-- Confirmation Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                            <i class="isax icon-folder-add text-blue-600 text-2xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Upload Recording Folder
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Please confirm your upload settings before proceeding.
                                </p>
                                
                                <!-- Requires Ticket Checkbox -->
                                <div class="bg-gray-50 rounded-lg p-4 text-left">
                                    <label class="flex items-start cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            v-model="requiresTicket"
                                            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900">
                                                Requires Ticket Linking
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Check this if these recordings need to be linked to ticket information. 
                                                You can import ticket data later via CSV.
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button
                            type="button"
                            @click="confirmUpload"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm"
                        >
                            Continue
                        </button>
                        <button
                            type="button"
                            @click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { clickId, showAlert } from '@/Plugins/Function/global-function';
import ButtonPrimary from "@/Components/Button/ButtonPrimary.vue";
import { ref, onErrorCaptured } from 'vue';
import { useUploadState } from '@/Hooks/uploadState';

const emit = defineEmits(["fetchData"])

// Error handling for upload state
let uploadState: any;
try {
    uploadState = useUploadState()
} catch (error) {
    console.error('Upload state error:', error);
    // Provide default fallback
    uploadState = {
        uploading: ref(false),
        injectId: ref(''),
        folderName: ref(''),
        progress: ref(0),
    };
}

// Capture component errors
onErrorCaptured((err) => {
    console.error('Component error:', err);
    return false; // Prevent error from propagating
});

const showModal = ref(false)
const requiresTicket = ref(true) // Default to true as per requirements

const showConfirmModal = () => {
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
}

const confirmUpload = () => {
    showModal.value = false
    uploadState.injectId.value = ''
    clickId('file-upload')
}

const newRecord = () => {
    uploadState.injectId.value = ''
    clickId('file-upload')
}

const handleFolderUpload = async (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (!input.files) return;

    const files = Array.from(input.files);

    if (files.length === 0) {
        return showAlert("No file exists");
    }

    if (files.length > 1000) {
        return showAlert("Max file 1000 items");
    }

    const maxSizeMB = 40;
    const maxSizeBytes = maxSizeMB * 1024 * 1024;

    // const oversizedFiles = files.filter(file => file.size > maxSizeBytes);
    // if (oversizedFiles.length > 0) {
    //     const names = oversizedFiles.map(f => f.name).join(', ');
    //     showAlert(`File(s) exceed ${maxSizeMB} MB: ${names}`);
    //     input.value = '';
    //     return;
    // }

    const allowedExt = ['mp3', 'wav', 'gsm', 'mp4'];
    const invalidFiles = files.filter(file => {
        const ext = file.name.split('.').pop()?.toLowerCase();
        return !ext || !allowedExt.includes(ext);
    });

    if (invalidFiles.length > 0) {
        const invalidNames = invalidFiles.map(f => f.name).join(', ');
        showAlert(`Invalid file format detected: ${invalidNames}`   );
        input.value = '';
        return;
    }


    const firstPath = files[0].webkitRelativePath;
    const folderName = firstPath.split('/')[0];
    uploadState.folderName.value = folderName;

    uploadState.uploading.value = true;
    uploadState.progress.value = 0;

    window.addEventListener("beforeunload", handleBeforeUnload);

    const formData = new FormData();
    formData.append('folderName', folderName);
    formData.append('injectId', uploadState.injectId.value || '');
    formData.append('requiresTicket', requiresTicket.value ? '1' : '0');

    files.forEach((file, index) => {
        formData.append(`files[${index}]`, file);
        formData.append(`relativePaths[${index}]`, file.webkitRelativePath);
    });

    uploadState.alert.value = {
        title: '',
        type: '',
        description: '',
        folderName: ''
    }

    try {
        await uploadBatchWithProgress(formData);
        uploadState.alert.value = {
            title: 'File Uploaded Successfully ',
            type: 'success',
            description: 'Your files have been succesfully uploaded.',
            folderName: uploadState.folderName.value
        }
        emit("fetchData");
    } catch (xhr: any) {
        let message = 'Upload failed. Please try again.';
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.message) {
                message = response.message;
            }
        } catch (_) {
            // response not JSON, ignore
        }
        uploadState.alert.value = {
            title: 'File Upload Failed',
            type: 'error',
            description: 'There was an issue uploading your file. This could be due to the file size exceeding the limit, an incorrect file format, or a network error. Please check your file and try again.'
        }
    } finally {
        uploadState.uploading.value = false;
        input.value = '';

        window.removeEventListener("beforeunload", handleBeforeUnload);
    }
};


const uploadBatchWithProgress = (formData: FormData) => {
    return new Promise<void>((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                const rawProgress = (event.loaded / event.total) * 100;
                const adjustedProgress = Math.min(Math.round(rawProgress * 0.8), 80);
                uploadState.progress.value = adjustedProgress;

            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                simulateServerProcessingProgress().then(() => {
                    uploadState.progress.value = 100;
                    resolve();
                });
            } else {
                reject(xhr);
            }
        };

        xhr.onerror = () => reject(xhr);
        xhr.onabort = () => reject(xhr);

        xhr.open('POST', route('api.recordings.store-folder'));
        xhr.send(formData);
    });
};

const simulateServerProcessingProgress = () => {
    return new Promise<void>((done) => {
        const interval = setInterval(() => {
            if (uploadState.progress.value < 100) {
                uploadState.progress.value += 5;
            }
            if (uploadState.progress.value >= 100) {
                clearInterval(interval);
                done();
            }
        }, 200); // setiap 200ms
    });
};

const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    e.preventDefault();
    e.returnValue = '';
    // Chrome & modern browser akan tampilkan alert default:
    // "Changes you made may not be saved."
}


</script>
