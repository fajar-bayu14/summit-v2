import { request } from './http'

export async function getActiveBanners(posisi = '') {
  const query = posisi ? `?posisi=${encodeURIComponent(posisi)}` : ''
  return request(`/ads/banners${query}`)
}

export async function trackBannerClick(bannerId) {
  return request(`/ads/banners/${bannerId}/click`, {
    method: 'POST'
  })
}
