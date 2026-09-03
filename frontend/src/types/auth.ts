export type UserRole = 'admin' | 'mitra' | 'pendaki'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  created_at?: string
  updated_at?: string
  pendaki?: any
  mitra?: any
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface AuthResponseData {
  user: User
  token: string
}
