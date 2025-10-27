/**
 * Master-Detail Composable Tests
 * 
 * Unit tests for the useMasterDetail composable
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useMasterDetail } from '../composables/useMasterDetail'
import type { DetailRecord } from '../composables/useMasterDetail'
import axios from 'axios'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios, true)

// Mock alerts store
vi.mock('@userfrosting/sprinkle-core/stores', () => ({
  useAlertsStore: () => ({
    push: vi.fn()
  })
}))

describe('useMasterDetail', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('saveMasterWithDetails', () => {
    it('should create master record and detail records', async () => {
      // Arrange
      const { saveMasterWithDetails } = useMasterDetail('orders', 'order_details', 'order_id')

      const masterData = {
        order_number: 'ORD-001',
        customer_name: 'John Doe',
        total_amount: 99.99
      }

      const detailRecords: DetailRecord[] = [
        {
          line_number: 1,
          sku: 'PROD-001',
          quantity: 10,
          unit_price: 9.99,
          _action: 'create'
        }
      ]

      // Mock master POST response
      mockedAxios.post.mockResolvedValueOnce({
        status: 201,
        data: {
          success: true,
          data: { id: 123 }
        }
      })

      // Mock detail POST response
      mockedAxios.post.mockResolvedValueOnce({
        status: 201,
        data: { success: true }
      })

      // Act
      const result = await saveMasterWithDetails(null, masterData, detailRecords)

      // Assert
      expect(result.success).toBe(true)
      expect(result.master_id).toBe(123)
      expect(result.details_created).toBe(1)
      expect(mockedAxios.post).toHaveBeenCalledTimes(2)
      expect(mockedAxios.post).toHaveBeenCalledWith(
        '/api/crud6/orders',
        masterData
      )
    })

    it('should update master record and process detail changes', async () => {
      // Arrange
      const { saveMasterWithDetails } = useMasterDetail('orders', 'order_details', 'order_id')

      const masterData = {
        order_number: 'ORD-001',
        customer_name: 'Jane Doe',
        total_amount: 149.98
      }

      const detailRecords: DetailRecord[] = [
        {
          id: 1,
          line_number: 1,
          sku: 'PROD-001',
          quantity: 10,
          unit_price: 9.99,
          _action: 'update'
        },
        {
          line_number: 2,
          sku: 'PROD-002',
          quantity: 5,
          unit_price: 9.99,
          _action: 'create'
        },
        {
          id: 3,
          line_number: 3,
          sku: 'PROD-003',
          quantity: 1,
          unit_price: 50.00,
          _action: 'delete'
        }
      ]

      // Mock master PUT response
      mockedAxios.put.mockResolvedValueOnce({
        status: 200,
        data: { success: true }
      })

      // Mock detail PUT response
      mockedAxios.put.mockResolvedValueOnce({
        status: 200,
        data: { success: true }
      })

      // Mock detail POST response
      mockedAxios.post.mockResolvedValueOnce({
        status: 201,
        data: { success: true }
      })

      // Mock detail DELETE response
      mockedAxios.delete.mockResolvedValueOnce({
        status: 200,
        data: { success: true }
      })

      // Act
      const result = await saveMasterWithDetails(123, masterData, detailRecords)

      // Assert
      expect(result.success).toBe(true)
      expect(result.master_id).toBe(123)
      expect(result.details_created).toBe(1)
      expect(result.details_updated).toBe(1)
      expect(result.details_deleted).toBe(1)
      expect(mockedAxios.put).toHaveBeenCalledTimes(2)
      expect(mockedAxios.post).toHaveBeenCalledTimes(1)
      expect(mockedAxios.delete).toHaveBeenCalledTimes(1)
    })

    it('should handle errors gracefully', async () => {
      // Arrange
      const { saveMasterWithDetails } = useMasterDetail('orders', 'order_details', 'order_id')

      const masterData = {
        order_number: 'ORD-001',
        customer_name: 'John Doe'
      }

      // Mock master POST error
      mockedAxios.post.mockRejectedValueOnce({
        response: {
          data: {
            message: 'Validation error',
            severity: 'danger'
          }
        }
      })

      // Act & Assert
      await expect(
        saveMasterWithDetails(null, masterData, [])
      ).rejects.toMatchObject({
        message: 'Validation error'
      })
    })
  })

  describe('loadDetails', () => {
    it('should load detail records for a master', async () => {
      // Arrange
      const { loadDetails } = useMasterDetail('orders', 'order_details', 'order_id')

      const mockDetails = [
        { id: 1, order_id: 123, line_number: 1, sku: 'PROD-001' },
        { id: 2, order_id: 123, line_number: 2, sku: 'PROD-002' }
      ]

      // Mock GET response
      mockedAxios.get.mockResolvedValueOnce({
        status: 200,
        data: {
          rows: mockDetails
        }
      })

      // Act
      const result = await loadDetails(123)

      // Assert
      expect(result).toEqual(mockDetails)
      expect(mockedAxios.get).toHaveBeenCalledWith(
        '/api/crud6/orders/123/order_details'
      )
    })

    it('should handle empty detail records', async () => {
      // Arrange
      const { loadDetails } = useMasterDetail('orders', 'order_details', 'order_id')

      // Mock GET response with empty rows
      mockedAxios.get.mockResolvedValueOnce({
        status: 200,
        data: {
          rows: []
        }
      })

      // Act
      const result = await loadDetails(123)

      // Assert
      expect(result).toEqual([])
    })
  })

  describe('reactive state', () => {
    it('should update loading state during operations', async () => {
      // Arrange
      const { saveMasterWithDetails, apiLoading } = useMasterDetail('orders', 'order_details', 'order_id')

      // Mock delayed response
      mockedAxios.post.mockImplementationOnce(() =>
        new Promise(resolve => setTimeout(() => resolve({
          status: 201,
          data: { success: true, data: { id: 1 } }
        }), 100))
      )

      // Assert initial state
      expect(apiLoading.value).toBe(false)

      // Act
      const savePromise = saveMasterWithDetails(null, { order_number: 'ORD-001' }, [])
      
      // Assert loading state
      expect(apiLoading.value).toBe(true)

      await savePromise

      // Assert final state
      expect(apiLoading.value).toBe(false)
    })
  })
})
