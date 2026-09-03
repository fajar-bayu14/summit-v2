import { request } from './http'

export async function getMountains(page = 1) {
  const result = await request(`/mountains?page=${page}`)
  const data = result.data ?? {}
  return {
    items: data.data ?? [],
    meta: data.meta ?? {}
  }
}

export async function getMountainDetail(id) {
  return request(`/mountains/${id}`)
}

export async function getProducts(page = 1) {
  const result = await request(`/products?page=${page}`)
  return result.data?.data ?? result.data ?? []
}

export async function getProductDetail(id) {
  return request(`/products/${id}`)
}

export async function getRentalProducts(page = 1) {
  const result = await request(`/products?kategori=rental&page=${page}`)
  const data = result.data ?? {}
  return {
    items: data.data ?? [],
    meta: data.meta ?? {}
  }
}
