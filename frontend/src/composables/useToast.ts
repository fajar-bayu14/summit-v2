import { ref } from 'vue'

export interface ToastItem {
  id: string
  title?: string
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
  duration?: number
}

const toasts = ref<ToastItem[]>([])

export function useToast() {
  function show(toast: Omit<ToastItem, 'id'>) {
    const id = Math.random().toString(36).substring(2, 9)
    const item: ToastItem = {
      id,
      duration: 4000,
      ...toast,
    }

    toasts.value.push(item)

    if (item.duration && item.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, item.duration)
    }

    return id
  }

  function success(message: string, title = 'Berhasil') {
    return show({ type: 'success', title, message })
  }

  function error(message: string, title = 'Terjadi Kesalahan') {
    return show({ type: 'error', title, message, duration: 6000 })
  }

  function warning(message: string, title = 'Peringatan') {
    return show({ type: 'warning', title, message })
  }

  function info(message: string, title = 'Informasi') {
    return show({ type: 'info', title, message })
  }

  function remove(id: string) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  return {
    toasts,
    show,
    success,
    error,
    warning,
    info,
    remove,
  }
}
