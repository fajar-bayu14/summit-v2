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
      if (err?.response?.status === 404) {
        error.value = 'Berkas foto identitas tidak ditemukan di server.'
      } else if (err?.response?.data instanceof Blob) {
        try {
          const text = await err.response.data.text()
          const json = JSON.parse(text)
          error.value = json.message || 'Gagal memuat dokumen identitas.'
        } catch {
          error.value = 'Gagal memuat dokumen identitas.'
        }
      } else {
        error.value = err?.response?.data?.message || 'Gagal memuat dokumen identitas.'
      }
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
