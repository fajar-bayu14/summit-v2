import { request } from './http'

export async function submitKyc(formData) {
  return request('/kyc/submit', {
    method: 'POST',
    body: formData
  })
}

export async function getKycStatus() {
  return request('/kyc/status')
}
