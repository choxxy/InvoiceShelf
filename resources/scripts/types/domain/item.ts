import type { Company } from './company'
import type { Currency } from './currency'
import type { Tax } from './tax'

export interface Unit {
  id: number
  name: string
  company_id: number
  company?: Company
}

export interface Item {
  id: number
  name: string
  description: string | null
  price: number
  unit_id: number | null
  company_id: number
  creator_id: number
  currency_id: number | null
  created_at: string
  updated_at: string
  tax_per_item: string | null
  formatted_created_at: string
  sku?: string | null
  track_inventory?: boolean
  initial_quantity?: number
  quantity?: number
  reorder_level?: number | null
  quantity_sold?: number
  unit?: Unit
  company?: Company
  taxes?: Tax[]
  currency?: Currency
}
