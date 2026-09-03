import { ref, onBeforeUnmount } from 'vue'
import { kycApi } from '@/api/kyc'

export function useSecureDocument() {
  const documentUrl = ref<string | null>(null)
  const loading = ref<boolean>(false)
  const error = ref<string | null>(null)
  const mimeType = ref<string>('')

  function cleanup() {
    if (documentUrl.value) {
      URL.revokeObjectURL(documentUrl.value)
      documentUrl.value = null
    }
    error.value = null
  }

  async function loadDocument(kycId: number): Promise<string | null> {
    cleanup()
    loading.value = true
    error.value = null

    try {
      const blob = await kycApi.downloadDocumentBlob(kycId)
      mimeType.value = blob.type
      const url = URL.createObjectURL(blob)
      documentUrl.value = url
      return url
    } catch (err: any) {
      error.value = err?.response?.status === 404
        ? 'Berkas foto identitas tidak ditemukan di server.'
        : 'Gagal memuat dokumen identitas.'
      return null
    } finally {
      loading.value = false
    }
  }

  onBeforeUnmount(() => {
    cleanup()
  })

  return {
    documentUrl,
    loading,
    error,
    mimeType,
    loadDocument,
    cleanup,
  }
}
