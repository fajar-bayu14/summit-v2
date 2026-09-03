import { request } from './http'

export async function getCart() {
  return request('/cart')
}

export async function saveCart(payload) {
  return request('/cart', {
    method: 'POST',
    body: payload
  })
}

export async function addCartItem(payload) {
  return request('/cart/items', {
    method: 'POST',
    body: payload
  })
}

export async function updateCartItem(itemId, payload) {
  return request(`/cart/items/${itemId}`, {
    method: 'PATCH',
    body: payload
  })
}

export async function removeCartItem(itemId) {
  return request(`/cart/items/${itemId}`, {
    method: 'DELETE'
  })
}

export async function clearCart() {
  return request('/cart', {
    method: 'DELETE'
  })
}
