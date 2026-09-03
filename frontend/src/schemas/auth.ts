import { z } from 'zod'

export const loginSchema = z.object({
  email: z
    .string()
    .min(1, 'Email wajib diisi.')
    .email('Format email tidak valid. Masukkan alamat email yang benar.'),
  password: z
    .string()
    .min(1, 'Password wajib diisi.'),
  remember: z.boolean().default(false),
})

export type LoginFormValues = z.infer<typeof loginSchema>
