import { request, setToken } from './http'

export async function login(payload) {
  const result = await request('/auth/login', { method: 'POST', body: payload })
  if (result.token) setToken(result.token)
  return result
}

export function register(payload) {
  return request('/auth/register', { method: 'POST', body: payload })
}

export async function verifyOtp(payload) {
  const result = await request('/auth/verify-otp', {
    method: 'POST',
    body: payload
  })
  if (result.token) setToken(result.token)
  return result
}

export function resendOtp(email) {
  return request('/auth/resend-otp', { method: 'POST', body: { email } })
}
