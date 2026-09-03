import { request } from './http'

export async function createOrder(payload) {
  return request('/orders', {
    method: 'POST',
    body: payload
  })
}

export async function checkoutCart(payload) {
  return request('/orders/checkout', {
    method: 'POST',
    body: payload
  })
}

export async function getOrders(status = '', page = 1) {
  const query = new URLSearchParams()
  if (status) query.append('status', status)
  if (page) query.append('page', page)
  return request(`/orders?${query.toString()}`)
}

export async function getOrderDetail(invoice) {
  return request(`/orders/${encodeURIComponent(invoice)}`)
}

export async function cancelOrder(invoice) {
  return request(`/orders/${encodeURIComponent(invoice)}/cancel`, {
    method: 'POST'
  })
}

export async function requestRefund(invoice, payload) {
  return request(`/orders/${encodeURIComponent(invoice)}/refund-request`, {
    method: 'POST',
    body: payload
  })
}
