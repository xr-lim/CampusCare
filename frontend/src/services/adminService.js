import api from './api'

export const getAdminDashboard = () => api.get('/admin/dashboard')

export const getAdminLookups = () => api.get('/admin/lookups')

export const getAdminRequests = (params = {}) => api.get('/admin/requests', { params })

export const getAdminRequestById = (id) => api.get(`/admin/requests/${id}`)

export const assignTechnician = (id, technicianId) =>
  api.put(`/admin/requests/${id}/assign`, {
    technician_id: technicianId
  })

export const rejectAdminRequest = (id, reason) =>
  api.put(`/admin/requests/${id}/reject`, { reason })

export const getAdminRequestHistory = (id) => api.get(`/admin/requests/${id}/history`)
