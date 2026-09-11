import { request } from './http'

export async function uploadSummitProof(invoice, formData) {
  return request(`/orders/${encodeURIComponent(invoice)}/logbook`, {
    method: 'POST',
    body: formData
  })
}

export async function getLogbookDetail(invoice) {
  return request(`/orders/${encodeURIComponent(invoice)}/logbook`)
}

export async function getBadges() {
  return request('/pendaki/badges')
}

export function getCertificateUrl(invoice) {
  const baseUrl =
    import.meta.env.VITE_API_BASE_URL || 'http://summit.test/api/v1'
  return `${baseUrl}/orders/${encodeURIComponent(invoice)}/certificate`
}
