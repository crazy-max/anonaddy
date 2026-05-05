<template>
  <div>
    <Head title="Failed Deliveries" />
    <h1 id="primary-heading" class="sr-only">Failed Deliveries</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">Failed Deliveries</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-200">
          A list of all the failed deliveries
          {{ search ? 'found for your search' : 'in your account' }}
          <button @click="moreInfoOpen = !moreInfoOpen">
            <InformationCircleIcon
              class="h-6 w-6 inline-block cursor-pointer text-grey-500 dark:text-grey-200"
              title="Click for more information"
            />
          </button>
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <select
          v-model="filterType"
          @change="updateFilter"
          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-grey-300 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm rounded-md dark:border-grey-600 dark:bg-grey-700 dark:text-grey-200"
        >
          <option value="all">All</option>
          <option value="outbound">Outbound Bounces</option>
          <option value="inbound">Inbound Rejections</option>
        </select>
      </div>
    </div>

    <vue-good-table
      v-if="rows.length"
      :columns="columns"
      :rows="rows"
      :sort-options="{
        enabled: false,
      }"
      styleClass="vgt-table"
    >
      <template #table-row="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none cursor-default text-sm text-grey-500 dark:text-grey-300"
          :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
          >{{ $filters.timeAgo(props.row.created_at) }}
        </span>
        <span
          v-else-if="props.column.field == 'email_type'"
          class="text-sm text-grey-500 dark:text-grey-300"
        >
          {{ props.row.email_type }}
        </span>
        <span v-else-if="props.column.field == 'recipient'">
          <span
            class="tooltip cursor-pointer outline-none text-sm font-medium text-grey-700 dark:text-grey-200"
            data-tippy-content="Click to copy"
            @click="clipboard(rows[props.row.originalIndex].destination)"
            >{{ rows[props.row.originalIndex].destination }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'alias'">
          <span
            class="tooltip cursor-pointer outline-none text-sm font-medium text-grey-700 dark:text-grey-200"
            data-tippy-content="Click to copy"
            @click="
              clipboard(
                rows[props.row.originalIndex].alias
                  ? rows[props.row.originalIndex].alias.email
                  : '',
              )
            "
            >{{ props.row.alias ? props.row.alias.email : '' }}</span
          >
        </span>
        <span
          v-else-if="props.column.field == 'sender'"
          class="text-sm font-medium text-grey-700 dark:text-grey-200"
        >
          <span
            class="tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            @click="clipboard(rows[props.row.originalIndex].sender)"
            >{{ props.row.sender }}</span
          >
          <span
            v-if="
              rows[props.row.originalIndex].sender && rows[props.row.originalIndex].sender !== '<>'
            "
            class="block text-grey-400 text-sm py-1 dark:text-grey-300"
          >
            <button @click="openBlockSenderModal(rows[props.row.originalIndex])">
              Add to blocklist
            </button>
          </span>
        </span>
        <span
          v-else-if="props.column.field == 'remote_mta'"
          class="text-sm text-grey-500 dark:text-grey-300"
        >
          {{ props.row.remote_mta }}
        </span>
        <span
          v-else-if="props.column.field == 'code'"
          class="text-sm text-grey-500 dark:text-grey-300"
        >
          {{ props.row.code }}
        </span>
        <span
          v-else-if="props.column.field == 'attempted_at'"
          class="tooltip outline-none text-sm text-grey-500 dark:text-grey-300"
          :data-tippy-content="$filters.formatDateTime(rows[props.row.originalIndex].attempted_at)"
          >{{ $filters.timeAgo(props.row.attempted_at) }}
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <a
            v-if="props.row.is_stored"
            :href="'api/v1/failed-deliveries/' + props.row.id + '/download'"
            class="mr-4 text-indigo-500 hover:text-indigo-800 font-medium dark:text-indigo-400 dark:hover:text-indigo-500"
          >
            Download
          </a>
          <button
            v-if="
              props.row.is_stored &&
              !props.row.quarantined &&
              !props.row.resent &&
              props.row.email_type === 'Forward'
            "
            @click="openResendModal(props.row)"
            as="button"
            type="button"
            class="mr-4 text-indigo-500 hover:text-indigo-800 font-medium dark:text-indigo-400 dark:hover:text-indigo-500"
          >
            Resend
          </button>
          <button
            @click="openDeleteModal(props.row.id)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 font-medium dark:text-indigo-400 dark:hover:text-indigo-500"
          >
            Delete
          </button>
        </span>
      </template>
    </vue-good-table>

    <!-- Pagination -->
    <PaginationControls
      v-if="$page.props.initialRows.data.length"
      :pagination="$page.props.initialRows"
      v-model:page-size="pageSize"
      :page-size-options="pageSizeOptions"
      :page-size-loading="updatePageSizeLoading"
      @page-size-change="updatePageSize"
    />

    <div v-else-if="search || filterType !== 'all'" class="text-center">
      <ExclamationTriangleIcon class="mx-auto h-16 w-16 text-grey-400 dark:text-grey-200" />
      <h3 class="mt-2 text-lg font-medium text-grey-900 dark:text-white">
        No Failed Deliveries found for that search or filter
      </h3>
      <p class="mt-1 text-md text-grey-500 dark:text-grey-200">
        Try entering a different search term or changing the filter.
      </p>
      <div class="mt-6">
        <Link
          :href="route('failed_deliveries.index')"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          View All Failed Deliveries
        </Link>
      </div>
    </div>

    <div v-else class="text-center">
      <ExclamationTriangleIcon class="mx-auto h-16 w-16 text-grey-400 dark:text-grey-200" />
      <h3 class="mt-2 text-lg font-medium text-grey-900 dark:text-white">No Failed Deliveries</h3>
      <p class="mt-1 text-md text-grey-500 dark:text-grey-200">
        You don't have any failed delivery attempts to display.
      </p>
    </div>

    <Modal :open="resendFailedDeliveryModalOpen" @close="closeResendModal">
      <template v-slot:title> Resend Failed Delivery </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          You can choose to resend to the original recipient or select a different one below. You
          can choose multiple recipients.
        </p>
        <p class="my-4 text-grey-700 dark:text-grey-200">
          Leave the select input empty if you would like to resend to the original recipient
          <b v-if="failedDeliveryToResend.recipient">{{ failedDeliveryToResend.recipient.email }}</b
          >.
        </p>
        <multiselect
          v-model="failedDeliveryRecipientsToResend"
          mode="tags"
          value-prop="id"
          :options="recipientOptions"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="true"
          :max="10"
          class="p-0"
          placeholder="Select recipient(s)"
          label="email"
          track-by="email"
        >
        </multiselect>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="resendFailedDelivery(failedDeliveryToResend)"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="resendFailedDeliveryLoading"
          >
            Resend failed delivery
            <loader v-if="resendFailedDeliveryLoading" />
          </button>
          <button
            @click="closeResendModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteFailedDeliveryModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete Failed Delivery </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Are you sure you want to delete this failed delivery?
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Failed deliveries are <b>automatically removed</b> when they are more than
          <b>7 days old</b>. Deleting a failed delivery also deletes the email if it has been
          stored.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteFailedDelivery(failedDeliveryIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteFailedDeliveryLoading"
          >
            Delete failed delivery
            <loader v-if="deleteFailedDeliveryLoading" />
          </button>
          <button
            @click="closeDeleteModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="moreInfoOpen" @close="moreInfoOpen = false">
      <template v-slot:title> More information </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Sometimes when addy.io attempts to send an email, the delivery is not successful. This is
          often referred to as a "bounced email".
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          This page allows you to see any failed deliveries relating to your account and the reason
          why they failed. It also displays any inbound emails that were rejected by the addy.io
          servers before reaching your alias.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Only failed delivery attempts from the addy.io servers to your recipients (or reply/send
          attempts from your aliases) and inbound rejections to your aliases will be shown here.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row">
          <button
            @click="moreInfoOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="blockSenderModalOpen" @close="closeBlockSenderModal">
      <template v-slot:title> Add sender to blocklist </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Choose whether to block just this sender's email address or their entire domain.
        </p>
        <p class="mt-4 text-sm text-grey-500 dark:text-grey-300 break-all">
          Sender email: <b class="text-grey-700 dark:text-grey-200">{{ senderToBlock.sender }}</b>
        </p>
        <p v-if="senderDomain" class="mt-1 text-sm text-grey-500 dark:text-grey-300 break-all">
          Sender domain: <b class="text-grey-700 dark:text-grey-200">{{ senderDomain }}</b>
        </p>
        <div class="mt-6 flex flex-col space-y-3">
          <button
            type="button"
            @click="blockSender('email')"
            class="w-full px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="blockSenderLoading"
          >
            Block email
            <loader v-if="blockSenderLoading && blockSenderType === 'email'" />
          </button>
          <button
            v-if="senderDomain"
            type="button"
            @click="blockSender('domain')"
            class="w-full px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="blockSenderLoading"
          >
            Block domain
            <loader v-if="blockSenderLoading && blockSenderType === 'domain'" />
          </button>
          <button
            @click="closeBlockSenderModal"
            class="w-full px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Modal from '../Components/Modal.vue'
import PaginationControls from '../Components/PaginationControls.vue'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import { notify } from '@kyvg/vue3-notification'
import { VueGoodTable } from 'vue-good-table-next'
import Multiselect from '@vueform/multiselect'
import { InformationCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  initialRows: {
    type: Object,
    required: true,
  },
  recipientOptions: {
    type: Array,
    required: true,
  },
  search: {
    type: String,
  },
  initialFilter: {
    type: String,
    default: 'all',
  },
  initialPageSize: {
    type: Number,
    default: 25,
  },
})

onMounted(() => {
  addTooltips()
})

const rows = ref(props.initialRows.data)

const filterType = ref(props.initialFilter)
const pageSize = ref(props.initialPageSize)
const updatePageSizeLoading = ref(false)
const pageSizeOptions = [25, 50, 100]

const resendFailedDeliveryLoading = ref(false)
const resendFailedDeliveryModalOpen = ref(false)
const deleteFailedDeliveryLoading = ref(false)
const deleteFailedDeliveryModalOpen = ref(false)
const blockSenderModalOpen = ref(false)
const blockSenderLoading = ref(false)
const blockSenderType = ref(null)
const senderToBlock = ref({})
const senderDomain = ref(null)
const moreInfoOpen = ref(false)
const failedDeliveryToResend = ref({})
const failedDeliveryRecipientsToResend = ref([])
const failedDeliveryIdToDelete = ref(null)
const tippyInstance = ref(null)
const errors = ref({})

watch(
  () => props.initialRows,
  newVal => {
    rows.value = newVal.data
  },
)

const columns = [
  {
    label: 'Created',
    field: 'created_at',
    globalSearchDisabled: true,
  },
  {
    label: 'Email Type',
    field: 'email_type',
  },
  {
    label: 'Destination',
    field: 'recipient',
    globalSearchDisabled: true,
  },
  {
    label: 'Alias',
    field: 'alias',
    globalSearchDisabled: true,
  },
  {
    label: 'Sender',
    field: 'sender',
  },
  {
    label: 'Mail Server',
    field: 'remote_mta',
  },
  {
    label: 'Code',
    field: 'code',
    sortable: false,
  },
  {
    label: 'First Attempted',
    field: 'attempted_at',
    globalSearchDisabled: true,
  },
  {
    label: '',
    field: 'actions',
    sortable: false,
    globalSearchDisabled: true,
  },
]

const visitWithParams = (extraParams = {}, omitKeys = []) => {
  let params = Object.assign({}, route().params, extraParams)

  if (filterType.value === 'all') {
    omitKeys.push('filter')
  }
  if (pageSize.value === 25) {
    omitKeys.push('page_size')
  }

  router.visit(route('failed_deliveries.index', _.omit(params, omitKeys)), {
    only: ['initialRows', 'search', 'initialFilter', 'initialPageSize'],
    preserveState: true,
  })
}

const updateFilter = () => {
  visitWithParams({ filter: filterType.value }, ['page'])
}

const updatePageSize = () => {
  updatePageSizeLoading.value = true
  visitWithParams({ page_size: pageSize.value }, ['page'])
  updatePageSizeLoading.value = false
}

const resendFailedDelivery = failedDelivery => {
  resendFailedDeliveryLoading.value = true

  axios
    .post(
      `/api/v1/failed-deliveries/${failedDelivery.id}/resend`,
      JSON.stringify({
        recipient_ids: failedDeliveryRecipientsToResend.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      successMessage('Failed Delivery Resent Successfully')
      failedDelivery.resent = true
      resendFailedDeliveryModalOpen.value = false
      resendFailedDeliveryLoading.value = false
    })
    .catch(error => {
      errorMessage()
      resendFailedDeliveryLoading.value = false
      resendFailedDeliveryModalOpen.value = false
    })
}

const deleteFailedDelivery = id => {
  deleteFailedDeliveryLoading.value = true

  axios
    .delete(`/api/v1/failed-deliveries/${id}`)
    .then(() => {
      router.reload({
        only: ['initialRows', 'search', 'initialFilter', 'initialPageSize'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: page => {
          const newRows = page.props.initialRows.data
          const currentPage = page.props.initialRows.current_page ?? 1

          deleteFailedDeliveryModalOpen.value = false
          deleteFailedDeliveryLoading.value = false

          if (!newRows.length && currentPage > 1) {
            visitWithParams({ page: currentPage - 1 }, [])
            return
          }

          rows.value = newRows
        },
      })
    })
    .catch(error => {
      errorMessage()
      deleteFailedDeliveryLoading.value = false
      deleteFailedDeliveryModalOpen.value = false
    })
}

const openResendModal = failedDelivery => {
  resendFailedDeliveryModalOpen.value = true
  failedDeliveryToResend.value = failedDelivery
}

const closeResendModal = () => {
  resendFailedDeliveryModalOpen.value = false
  failedDeliveryToResend.value = {}
  failedDeliveryRecipientsToResend.value = []
}

const openDeleteModal = id => {
  deleteFailedDeliveryModalOpen.value = true
  failedDeliveryIdToDelete.value = id
}

const closeDeleteModal = () => {
  deleteFailedDeliveryModalOpen.value = false
  failedDeliveryIdToDelete.value = null
}

const openBlockSenderModal = failedDelivery => {
  senderToBlock.value = failedDelivery
  const parts = failedDelivery.sender.split('@')
  senderDomain.value = parts.length === 2 ? parts[1] : null
  blockSenderModalOpen.value = true
}

const closeBlockSenderModal = () => {
  blockSenderModalOpen.value = false
  _.delay(() => {
    senderToBlock.value = {}
    senderDomain.value = null
    blockSenderType.value = null
  }, 300)
}

const blockSender = type => {
  blockSenderLoading.value = true
  blockSenderType.value = type

  const value = type === 'domain' ? senderDomain.value : senderToBlock.value.sender

  axios
    .post('/api/v1/blocklist', { type, value }, { withCredentials: true })
    .then(() => {
      successMessage(`Sender ${type === 'domain' ? 'domain' : 'email'} added to blocklist`)
      closeBlockSenderModal()
    })
    .catch(error => {
      if (error.response?.status === 422 && error.response?.data?.errors?.value) {
        errorMessage(error.response.data.errors.value[0])
      } else if (error.response?.data?.message) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
    .finally(() => {
      blockSenderLoading.value = false
    })
}

const addTooltips = () => {
  if (tippyInstance.value) {
    _.each(tippyInstance.value, instance => instance.destroy())
  }

  tippyInstance.value = tippy('.tooltip', {
    arrow: roundArrow,
    allowHTML: true,
  })
}

const debounceToolips = _.debounce(function () {
  addTooltips()
}, 50)

const clipboard = (str, success, error) => {
  // Needed as v-clipboard doesn't work inside modals!
  navigator.clipboard.writeText(str).then(
    () => {
      successMessage('Copied to clipboard')
    },
    () => {
      errorMessage('Could not copy to clipboard')
    },
  )
}

const successMessage = (text = '') => {
  notify({
    title: 'Success',
    text: text,
    type: 'success',
  })
}

const errorMessage = (text = 'An error has occurred, please try again later') => {
  notify({
    title: 'Error',
    text: text,
    type: 'error',
  })
}
</script>
