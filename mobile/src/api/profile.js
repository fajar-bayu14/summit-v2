import { request } from './http'

export async function getProfile() {
  const result = await request('/profile')
  return result.data
}
