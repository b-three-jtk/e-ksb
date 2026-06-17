import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'

export default function useUserAllocation(props) {
  const pjSearch = ref('')
  const selectedPjId = ref(props.pjUsers[0]?.id ?? '')
  const selectedMemberIds = ref([])
  const brokenMemberAvatarIds = ref(new Set())
  const brokenPjAvatarIds = ref(new Set())

  const form = useForm({
    pj_user_id: '',
    member_ids: [],
  })

  const memberRows = computed(() => props.members?.data ?? [])
  const selectedPj = computed(() => props.pjUsers.find((pj) => pj.id === selectedPjId.value) ?? null)
  const selectedCount = computed(() => selectedMemberIds.value.length)
  const totalAllocated = computed(() => selectedPj.value?.allocated_members_count ?? 0)
  const canAllocate = computed(() => Boolean(selectedPj.value && selectedMemberIds.value.length > 0))
  const visiblePjUsers = computed(() => {
    const keyword = pjSearch.value.trim().toLowerCase()

    if (!keyword) {
      return props.pjUsers
    }

    return props.pjUsers.filter((pj) => {
      return [pj.name, pj.user_code, pj.phone_number]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword))
    })
  })

  const visibleMemberIds = computed(() => memberRows.value.map((member) => member.member_id))
  const allVisibleSelected = computed(() => {
    if (!memberRows.value.length) {
      return false
    }

    return memberRows.value.every((member) => selectedMemberIds.value.includes(member.member_id))
  })

  watch(
    () => props.members?.data,
    () => {
      selectedMemberIds.value = []
    },
    { immediate: true }
  )

  const updateSelection = (memberId, checked) => {
    if (checked) {
      if (!selectedMemberIds.value.includes(memberId)) {
        selectedMemberIds.value = [...selectedMemberIds.value, memberId]
      }
      return
    }

    selectedMemberIds.value = selectedMemberIds.value.filter((id) => id !== memberId)
  }

  const toggleVisibleSelection = (checked) => {
    if (checked) {
      const nextIds = new Set(selectedMemberIds.value)
      visibleMemberIds.value.forEach((memberId) => nextIds.add(memberId))
      selectedMemberIds.value = Array.from(nextIds)
      return
    }

    selectedMemberIds.value = selectedMemberIds.value.filter((id) => !visibleMemberIds.value.includes(id))
  }

  const choosePj = (pjId) => {
    selectedPjId.value = pjId
  }

  const markBrokenMemberAvatar = (memberId) => {
    brokenMemberAvatarIds.value = new Set([...brokenMemberAvatarIds.value, memberId])
  }

  const markBrokenPjAvatar = (pjId) => {
    brokenPjAvatarIds.value = new Set([...brokenPjAvatarIds.value, pjId])
  }

  const submitAllocation = () => {
    if (!canAllocate.value) {
      toast('Pilih penanggung jawab dan minimal satu anggota.', {
        type: 'warning',
        position: 'bottom-right',
      })
      return
    }

    Swal.fire({
      title: 'Konfirmasi alokasi',
      text: `Alokasikan ${selectedCount.value} anggota ke ${selectedPj.value?.name ?? 'penanggung jawab ini'}?`,
      icon: 'question',
      iconColor: '#009141',
      showCancelButton: true,
      confirmButtonText: 'Ya, simpan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#009141',
    }).then((result) => {
      if (!result.isConfirmed) {
        return
      }

      form.pj_user_id = selectedPjId.value
      form.member_ids = selectedMemberIds.value

      form.post('/admin/allocation', {
        preserveScroll: true,
        onSuccess: () => {
          toast('Alokasi anggota berhasil disimpan.', {
            type: 'success',
            position: 'bottom-right',
          })
          selectedMemberIds.value = []
        },
        onError: () => {
          toast('Gagal menyimpan alokasi anggota.', {
            type: 'error',
            position: 'bottom-right',
          })
        },
      })
    })
  }

  return {
    form,
    pjSearch,
    selectedPjId,
    selectedMemberIds,
    brokenMemberAvatarIds,
    brokenPjAvatarIds,
    memberRows,
    selectedPj,
    selectedCount,
    totalAllocated,
    canAllocate,
    visiblePjUsers,
    visibleMemberIds,
    allVisibleSelected,
    updateSelection,
    toggleVisibleSelection,
    choosePj,
    markBrokenMemberAvatar,
    markBrokenPjAvatar,
    submitAllocation,
  }
}